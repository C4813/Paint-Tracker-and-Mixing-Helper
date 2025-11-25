<?php
/**
 * Plugin Name: Paint Tracker and Mixing Helper
 * Description: Shortcodes to display your miniature paint collection, as well as a mixing and shading helper for specific colours.
 * Version: 0.6.3
 * Author: C4813
 * Text Domain: pct
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'PCT_Paint_Table_Plugin' ) ) {

    class PCT_Paint_Table_Plugin {

        const CPT = 'paint_color';
        const TAX = 'paint_range';

        // Meta keys
        const META_NUMBER   = '_pct_number';
        const META_HEX      = '_pct_hex';
        const META_ON_SHELF = '_pct_on_shelf';
        const META_LINKS    = '_pct_links';
        const META_LINK     = '_pct_link'; // legacy single link

        // Plugin version (used for asset cache-busting)
        const VERSION = '0.6.3';

        public function __construct() {
            add_action( 'init',                    [ $this, 'register_types' ] );
            add_action( 'init',                    [ $this, 'load_textdomain' ] );

            // Metaboxes & saving
            add_action( 'add_meta_boxes',          [ $this, 'add_meta_boxes' ] );
            add_action( 'save_post_' . self::CPT,  [ $this, 'save_paint_meta' ], 10, 2 );

            // Quick Edit
            add_action( 'quick_edit_custom_box',   [ $this, 'quick_edit_fields' ], 10, 2 );
            add_action( 'save_post_' . self::CPT,  [ $this, 'save_quick_edit' ], 10, 2 );
            add_action( 'admin_footer-edit.php',   [ $this, 'print_quick_edit_js' ] );

            // Frontend assets
            add_action( 'wp_enqueue_scripts',      [ $this, 'enqueue_frontend_assets' ] );

            // Shortcodes
            add_shortcode( 'paint_table',          [ $this, 'shortcode_paint_table' ] );
            add_shortcode( 'mixing-helper',        [ $this, 'shortcode_mixing_helper' ] );
            add_shortcode( 'shade-helper',         [ $this, 'shortcode_shade_helper' ] );

            // Admin assets (CSS + JS)
            add_action( 'admin_enqueue_scripts',   [ $this, 'enqueue_admin_assets' ] );

            // Admin: CSV import & export pages
            add_action( 'admin_menu',              [ $this, 'register_import_page' ] );
            add_action( 'admin_menu',              [ $this, 'register_export_page' ] );
            add_action( 'admin_menu',              [ $this, 'register_info_settings_page' ] );

            // Admin: list table columns & sorting
            add_filter( 'manage_edit-' . self::CPT . '_columns',           [ $this, 'manage_edit_columns' ] );
            add_action( 'manage_' . self::CPT . '_posts_custom_column',    [ $this, 'manage_custom_column' ], 10, 2 );
            add_filter( 'manage_edit-' . self::CPT . '_sortable_columns',  [ $this, 'sortable_columns' ] );
            add_action( 'pre_get_posts',                                   [ $this, 'handle_admin_sorting' ] );
        }

        /**
         * Load plugin textdomain.
         */
        public function load_textdomain() {
            load_plugin_textdomain(
                'pct',
                false,
                dirname( plugin_basename( __FILE__ ) ) . '/languages'
            );
        }

        /**
         * Register custom post type & taxonomy.
         */
        public function register_types() {

            // Custom post type for individual paints
            register_post_type(
                self::CPT,
                [
                    'labels' => [
                        'name'          => __( 'Paint Colours', 'pct' ),
                        'singular_name' => __( 'Paint Colour', 'pct' ),
                        'add_new_item'  => __( 'Add New Paint Colour', 'pct' ),
                        'edit_item'     => __( 'Edit Paint Colour', 'pct' ),
                    ],
                    'public'       => false,
                    'show_ui'      => true,
                    'show_in_menu' => true,
                    'menu_icon'    => 'dashicons-art',
                    'supports'     => [ 'title' ],
                ]
            );

            // Taxonomy for ranges (Vallejo Model Color, etc.)
            register_taxonomy(
                self::TAX,
                self::CPT,
                [
                    'labels' => [
                        'name'          => __( 'Paint Ranges', 'pct' ),
                        'singular_name' => __( 'Paint Range', 'pct' ),
                    ],
                    'public'       => false,
                    'show_ui'      => true,
                    'show_in_menu' => true,
                    'hierarchical' => true,
                ]
            );
        }

        /**
         * Add meta boxes to the paint color edit screen.
         */
        public function add_meta_boxes() {
            add_meta_box(
                'pct_paint_details',
                __( 'Paint Details', 'pct' ),
                [ $this, 'render_paint_meta_box' ],
                self::CPT,
                'normal',
                'default'
            );
        }

        /**
         * Render the meta box HTML (delegated to admin/admin-page.php).
         */
        public function render_paint_meta_box( $post ) {
            $number   = get_post_meta( $post->ID, self::META_NUMBER, true );
            $hex      = get_post_meta( $post->ID, self::META_HEX, true );
            $on_shelf = get_post_meta( $post->ID, self::META_ON_SHELF, true );
            $links    = get_post_meta( $post->ID, self::META_LINKS, true );

            if ( ! is_array( $links ) ) {
                $links = [];
            }

            // Legacy single link: add as first item if structured links are empty
            if ( empty( $links ) ) {
                $legacy_link = get_post_meta( $post->ID, self::META_LINK, true );
                if ( $legacy_link ) {
                    $links[] = [
                        'title' => '',
                        'url'   => $legacy_link,
                    ];
                }
            }

            wp_nonce_field( 'pct_save_paint_meta', 'pct_paint_meta_nonce' );

            $pct_admin_view = 'meta_box';
            $pct_number     = $number;
            $pct_hex        = $hex;
            $pct_on_shelf   = $on_shelf;
            $pct_links      = $links;

            include plugin_dir_path( __FILE__ ) . 'admin/admin-page.php';
        }

        /**
         * Save meta box fields (including on-shelf flag & multiple links).
         * Handles both full edit screen and Quick Edit saves.
         */
        public function save_paint_meta( $post_id, $post ) {

            // Allow either the main meta box nonce OR quick edit nonce
            $has_nonce = isset( $_POST['pct_paint_meta_nonce'] ) || isset( $_POST['pct_quick_edit_nonce'] );
            if ( ! $has_nonce ) {
                return;
            }

            // For simplicity, verify whichever nonce is present
            if ( isset( $_POST['pct_paint_meta_nonce'] ) && ! wp_verify_nonce( $_POST['pct_paint_meta_nonce'], 'pct_save_paint_meta' ) ) {
                return;
            }

            if ( isset( $_POST['pct_quick_edit_nonce'] ) && ! wp_verify_nonce( $_POST['pct_quick_edit_nonce'], 'pct_quick_edit' ) ) {
                return;
            }

            // Check autosave
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }

            // Check permissions
            if ( self::CPT !== $post->post_type ) {
                return;
            }

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }

            // Save number
            $number = isset( $_POST['pct_number'] ) ? sanitize_text_field( wp_unslash( $_POST['pct_number'] ) ) : '';
            update_post_meta( $post_id, self::META_NUMBER, $number );

            // Save hex
            $hex = isset( $_POST['pct_hex'] ) ? sanitize_text_field( wp_unslash( $_POST['pct_hex'] ) ) : '';
            update_post_meta( $post_id, self::META_HEX, $hex );

            // Save on-shelf flag
            $on_shelf = isset( $_POST['pct_on_shelf'] ) ? 1 : 0;
            update_post_meta( $post_id, self::META_ON_SHELF, $on_shelf );

            // -------- Save multiple links --------
            $links = [];

            // Preferred structured format: pct_links[0][title], pct_links[0][url], ...
            if ( isset( $_POST['pct_links'] ) && is_array( $_POST['pct_links'] ) ) {
                foreach ( $_POST['pct_links'] as $link ) {
                    if ( ! is_array( $link ) ) {
                        continue;
                    }

                    $title = isset( $link['title'] ) ? sanitize_text_field( wp_unslash( $link['title'] ) ) : '';
                    $url   = isset( $link['url'] ) ? esc_url_raw( wp_unslash( $link['url'] ) ) : '';

                    if ( $url ) {
                        $links[] = [
                            'title' => $title,
                            'url'   => $url,
                        ];
                    }
                }
            }
            // Backwards/alternate format: pct_links_title[] + pct_links_url[]
            elseif (
                ( isset( $_POST['pct_links_title'] ) && is_array( $_POST['pct_links_title'] ) ) ||
                ( isset( $_POST['pct_links_url'] ) && is_array( $_POST['pct_links_url'] ) )
            ) {
                $titles = isset( $_POST['pct_links_title'] ) ? (array) $_POST['pct_links_title'] : [];
                $urls   = isset( $_POST['pct_links_url'] )   ? (array) $_POST['pct_links_url']   : [];

                // Normalise indexes
                $titles = array_values( $titles );
                $urls   = array_values( $urls );

                $max = max( count( $titles ), count( $urls ) );

                for ( $i = 0; $i < $max; $i++ ) {
                    $title_raw = isset( $titles[ $i ] ) ? $titles[ $i ] : '';
                    $url_raw   = isset( $urls[ $i ] )   ? $urls[ $i ]   : '';

                    $title = sanitize_text_field( wp_unslash( $title_raw ) );
                    $url   = esc_url_raw( wp_unslash( $url_raw ) );

                    if ( $url ) {
                        $links[] = [
                            'title' => $title,
                            'url'   => $url,
                        ];
                    }
                }
            }

            update_post_meta( $post_id, self::META_LINKS, $links );

            // If we now have structured links, remove the legacy single link
            if ( ! empty( $links ) ) {
                delete_post_meta( $post_id, self::META_LINK );
            }
        }

        /**
         * Output Quick Edit fields.
         */
        public function quick_edit_fields( $column_name, $post_type ) {
            if ( self::CPT !== $post_type || 'pct_number' !== $column_name ) {
                return;
            }
            ?>
            <fieldset class="inline-edit-col-left">
                <div class="inline-edit-col">
                    <label>
                        <span class="title"><?php esc_html_e( 'Number', 'pct' ); ?></span>
                        <span class="input-text-wrap">
                            <input type="text" name="pct_number" class="ptitle" value="">
                        </span>
                    </label>
                    <label>
                        <span class="title"><?php esc_html_e( 'Hex', 'pct' ); ?></span>
                        <span class="input-text-wrap">
                            <input type="text" name="pct_hex" class="ptitle" value="">
                        </span>
                    </label>
                    <label>
                        <span class="title"><?php esc_html_e( 'On Shelf?', 'pct' ); ?></span>
                        <span class="input-text-wrap">
                            <input type="checkbox" name="pct_on_shelf" value="1">
                        </span>
                    </label>
                </div>
            </fieldset>
            <?php
            wp_nonce_field( 'pct_quick_edit', 'pct_quick_edit_nonce' );
        }

        /**
         * Save Quick Edit values.
         */
        public function save_quick_edit( $post_id, $post ) {
            if ( ! isset( $_POST['pct_quick_edit_nonce'] ) || ! wp_verify_nonce( $_POST['pct_quick_edit_nonce'], 'pct_quick_edit' ) ) {
                return;
            }

            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }

            if ( self::CPT !== $post->post_type ) {
                return;
            }

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }

            // Save number
            if ( isset( $_POST['pct_number'] ) ) {
                $number = sanitize_text_field( wp_unslash( $_POST['pct_number'] ) );
                update_post_meta( $post_id, self::META_NUMBER, $number );
            }

            // Save hex
            if ( isset( $_POST['pct_hex'] ) ) {
                $hex = sanitize_text_field( wp_unslash( $_POST['pct_hex'] ) );
                update_post_meta( $post_id, self::META_HEX, $hex );
            }

            // Save on shelf
            $on_shelf = isset( $_POST['pct_on_shelf'] ) ? 1 : 0;
            update_post_meta( $post_id, self::META_ON_SHELF, $on_shelf );
        }

        /**
         * Print Quick Edit JS to populate fields from the row.
         */
        public function print_quick_edit_js() {
            $screen = get_current_screen();
            if ( empty( $screen ) || self::CPT !== $screen->post_type ) {
                return;
            }
            ?>
            <script type="text/javascript">
            jQuery(function($) {
                var $wp_inline_edit = inlineEditPost.edit;

                inlineEditPost.edit = function( id ) {
                    $wp_inline_edit.apply( this, arguments );

                    var postId = 0;
                    if ( typeof(id) === 'object' ) {
                        postId = parseInt( this.getId(id), 10 );
                    }

                    if ( postId > 0 ) {
                        var $editRow   = $('#edit-' + postId);
                        var $postRow   = $('#post-' + postId);
                        var number     = $('.column-pct_number', $postRow).text().trim();
                        var hex        = $('.column-pct_hex', $postRow).text().trim();
                        var onShelfVal = $('.pct-on-shelf-value', $postRow).data('on-shelf');

                        $('input[name="pct_number"]', $editRow).val(number);
                        $('input[name="pct_hex"]', $editRow).val(hex);

                        if ( onShelfVal === 1 || onShelfVal === '1' ) {
                            $('input[name="pct_on_shelf"]', $editRow).prop('checked', true);
                        } else {
                            $('input[name="pct_on_shelf"]', $editRow).prop('checked', false);
                        }
                    }
                };
            });
            </script>
            <?php
        }

        /**
         * Enqueue admin scripts.
         */
        public function enqueue_admin_assets( $hook ) {
            global $typenow;

            if ( $typenow !== self::CPT ) {
                return;
            }

            // Use main stylesheet (contains both frontend + admin rules)
            wp_enqueue_style(
                'pct_paint_table_admin',
                plugin_dir_url( __FILE__ ) . 'public/css/style.css',
                [],
                self::VERSION
            );

            wp_enqueue_script(
                'pct_paint_table_admin',
                plugin_dir_url( __FILE__ ) . 'admin/admin.js',
                [ 'jquery' ],
                self::VERSION,
                true
            );

            // Localise strings for admin/admin.js (for labels/placeholders)
            wp_localize_script(
                'pct_paint_table_admin',
                'pctAdmin',
                [
                    'add_link_label'   => __( 'Add another link', 'pct' ),
                    'link_title_label' => __( 'Link Title', 'pct' ),
                    'link_url_label'   => __( 'Link URL', 'pct' ),
                ]
            );
        }

        /**
         * Enqueue front-end stylesheet + helper JS.
         */
        public function enqueue_frontend_assets() {
            wp_enqueue_style(
                'pct_paint_table',
                plugin_dir_url( __FILE__ ) . 'public/css/style.css',
                [],
                self::VERSION
            );

            wp_enqueue_script(
                'pct_mixing_helper',
                plugin_dir_url( __FILE__ ) . 'public/js/mixing-helper.js',
                [ 'jquery' ],
                self::VERSION,
                true
            );

            wp_enqueue_script(
                'pct_shade_helper',
                plugin_dir_url( __FILE__ ) . 'public/js/shade-helper.js',
                [ 'jquery' ],
                self::VERSION,
                true
            );
        }

        /**
         * Shortcode handler:
         *
         * [paint_table range="vallejo-model-color" limit="-1" orderby="meta_number|title" shelf="yes|any"]
         */
        public function shortcode_paint_table( $atts ) {
            $atts = shortcode_atts(
                [
                    'range'   => 'vallejo-model-color', // taxonomy slug
                    'limit'   => -1,
                    'orderby' => 'meta_number',         // or "title"
                    'shelf'   => 'any',                 // 'yes' or 'any'
                ],
                $atts,
                'paint_table'
            );

            $meta_key = self::META_NUMBER;
            $orderby  = ( $atts['orderby'] === 'title' ) ? 'title' : 'meta_value';

            $args = [
                'post_type'      => self::CPT,
                'posts_per_page' => intval( $atts['limit'] ),
                'post_status'    => 'publish',
                'orderby'        => $orderby,
                'order'          => 'ASC',
            ];

            if ( $orderby === 'meta_value' ) {
                $args['meta_key'] = $meta_key;
            }

            $range_slug  = '';
            $range_title = '';

            if ( ! empty( $atts['range'] ) ) {
                $range_slug = sanitize_title( $atts['range'] );
                $args['tax_query'] = [
                    [
                        'taxonomy' => self::TAX,
                        'field'    => 'slug',
                        'terms'    => $range_slug,
                    ],
                ];

                $range_title = $this->get_range_title_by_slug( $range_slug );
            }

            // Shelf filter: only show paints that are marked "on the shelf"
            $meta_query = [];

            if ( strtolower( $atts['shelf'] ) === 'yes' ) {
                $meta_query[] = [
                    'key'     => self::META_ON_SHELF,
                    'value'   => 1,
                    'compare' => '=',
                ];
            }

            if ( ! empty( $meta_query ) ) {
                $args['meta_query'] = $meta_query;
            }

            $q = new WP_Query( $args );

            if ( ! $q->have_posts() ) {
                return '<p>' . esc_html__( 'No paints found.', 'pct' ) . '</p>';
            }

            // Build data array for the template.
            $paints_data = [];

            while ( $q->have_posts() ) {
                $q->the_post();
                $id     = get_the_ID();
                $name   = get_the_title();
                $number = get_post_meta( $id, self::META_NUMBER, true );
                $hex    = get_post_meta( $id, self::META_HEX, true );

                $links = $this->get_paint_links( $id );

                $paints_data[] = [
                    'id'     => $id,
                    'name'   => $name,
                    'number' => $number,
                    'hex'    => $hex,
                    'links'  => $links,
                ];
            }

            wp_reset_postdata();

            // Make data available to the template
            $pct_paints              = $paints_data;
            $pct_range_title         = $range_title;
            $pct_mixing_page_url     = get_option( 'pct_mixing_page_url', '' );
            $pct_table_display_mode  = get_option( 'pct_table_display_mode', 'dots' );

            ob_start();
            include plugin_dir_path( __FILE__ ) . 'templates/paint-display.php';
            return ob_get_clean();
        }

        /**
         * Helper: Get range title from slug.
         */
        private function get_range_title_by_slug( $slug ) {
            $term = get_term_by( 'slug', $slug, self::TAX );
            if ( $term && ! is_wp_error( $term ) ) {
                return $term->name;
            }
            return '';
        }

        /**
         * Shortcode: [mixing-helper]
         *
         * Shows a two-paint mixing UI with ranges + paints.
         */
        public function shortcode_mixing_helper( $atts ) {
            // Get all paint ranges
            $ranges = get_terms(
                [
                    'taxonomy'   => self::TAX,
                    'hide_empty' => false,
                    'orderby'    => 'term_order',
                    'order'      => 'ASC',
                ]
            );

            if ( is_wp_error( $ranges ) || empty( $ranges ) ) {
                return '<p>' . esc_html__( 'No paint ranges found.', 'pct' ) . '</p>';
            }

            // Query all paints, ordered by number
            $q = new WP_Query(
                [
                    'post_type'      => self::CPT,
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'orderby'        => 'meta_value',
                    'order'          => 'ASC',
                    'meta_key'       => self::META_NUMBER,
                ]
            );

            if ( ! $q->have_posts() ) {
                return '<p>' . esc_html__( 'No paints found for mixing.', 'pct' ) . '</p>';
            }

            $paints = [];

            while ( $q->have_posts() ) {
                $q->the_post();
                $post_id = get_the_ID();

                $name   = get_the_title();
                $number = get_post_meta( $post_id, self::META_NUMBER, true );
                $hex    = get_post_meta( $post_id, self::META_HEX, true );

                // Take the first range term for this paint (if multiple, first is fine)
                $term_ids = wp_get_object_terms(
                    $post_id,
                    self::TAX,
                    [
                        'fields' => 'ids',
                    ]
                );

                $range_id = ! empty( $term_ids ) ? (int) $term_ids[0] : 0;

                $paints[] = [
                    'id'       => $post_id,
                    'name'     => $name,
                    'number'   => $number,
                    'hex'      => $hex,
                    'range_id' => $range_id,
                ];
            }

            wp_reset_postdata();

            if ( empty( $paints ) ) {
                return '<p>' . esc_html__( 'No paints found for mixing.', 'pct' ) . '</p>';
            }

            // Expose to template
            $pct_ranges = $ranges;
            $pct_paints = $paints;

            ob_start();
            include plugin_dir_path( __FILE__ ) . 'templates/mixing-helper.php';
            return ob_get_clean();
        }

        /**
         * Shortcode: [shade-helper]
         *
         * Shows the shade range helper as a standalone tool.
         */
        public function shortcode_shade_helper( $atts ) {
            // Optional: default shade hex and paint ID passed via URL when coming from [paint_table]
            $default_shade_hex = '';
            $default_shade_id  = 0;
        
            if ( isset( $_GET['pct_shade_id'] ) ) {
                $default_shade_id = absint( $_GET['pct_shade_id'] );
            }
        
            if ( isset( $_GET['pct_shade_hex'] ) ) {
                // Decode any %23 etc, then sanitise/trim
                $raw_hex = wp_unslash( $_GET['pct_shade_hex'] );
                $raw_hex = rawurldecode( $raw_hex );
                $raw_hex = sanitize_text_field( $raw_hex );
                $raw_hex = trim( $raw_hex );
        
                if ( '' !== $raw_hex ) {
                    // Ensure it starts with '#'
                    if ( $raw_hex[0] !== '#' ) {
                        $raw_hex = '#' . $raw_hex;
                    }
                    $default_shade_hex = $raw_hex;
                }
            }
        
            // Get all paint ranges
            $ranges = get_terms(
                [
                    'taxonomy'   => self::TAX,
                    'hide_empty' => false,
                    'orderby'    => 'term_order',
                    'order'      => 'ASC',
                ]
            );

            if ( is_wp_error( $ranges ) || empty( $ranges ) ) {
                return '<p>' . esc_html__( 'No paint ranges found for shade helper.', 'pct' ) . '</p>';
            }

            // Get their IDs for the query
            $range_ids = wp_list_pluck( $ranges, 'term_id' );

            // Query all paints in these ranges, ordered by number
            $q = new WP_Query(
                [
                    'post_type'      => self::CPT,
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'orderby'        => 'meta_value',
                    'order'          => 'ASC',
                    'meta_key'       => self::META_NUMBER,
                    'tax_query'      => [
                        [
                            'taxonomy' => self::TAX,
                            'field'    => 'term_id',
                            'terms'    => $range_ids,
                        ],
                    ],
                ]
            );

            if ( ! $q->have_posts() ) {
                return '<p>' . esc_html__( 'No paints found for shade helper.', 'pct' ) . '</p>';
            }

            $paints = [];

            while ( $q->have_posts() ) {
                $q->the_post();
                $post_id = get_the_ID();

                $name   = get_the_title();
                $number = get_post_meta( $post_id, self::META_NUMBER, true );
                $hex    = get_post_meta( $post_id, self::META_HEX, true );

                // Take the first range term for this paint (if multiple, first is fine)
                $term_ids = wp_get_object_terms(
                    $post_id,
                    self::TAX,
                    [
                        'fields' => 'ids',
                    ]
                );

                $range_id = ! empty( $term_ids ) ? (int) $term_ids[0] : 0;

                $paints[] = [
                    'id'       => $post_id,
                    'name'     => $name,
                    'number'   => $number,
                    'hex'      => $hex,
                    'range_id' => $range_id,
                ];
            }

            wp_reset_postdata();

            if ( empty( $paints ) ) {
                return '<p>' . esc_html__( 'No paints found for shade helper.', 'pct' ) . '</p>';
            }

            // Expose to template
            $pct_ranges            = $ranges;
            $pct_paints            = $paints;
            $pct_default_shade_hex = $default_shade_hex;
            $pct_default_shade_id  = $default_shade_id;
        
            ob_start();
            include plugin_dir_path( __FILE__ ) . 'templates/shade-helper.php';
            return ob_get_clean();
        }

        /**
         * Helper: Get normalised links for a paint post.
         * Returns an array of [ 'title' => string, 'url' => string ].
         */
        private function get_paint_links( $post_id ) {
            $links = get_post_meta( $post_id, self::META_LINKS, true );

            // No structured links: fall back to legacy single URL
            if ( empty( $links ) ) {
                $single = get_post_meta( $post_id, self::META_LINK, true );
                if ( $single ) {
                    return [
                        [
                            'title' => '',
                            'url'   => esc_url( $single ),
                        ],
                    ];
                }
                return [];
            }

            // Normalise & sanitise
            $normalised = [];
            foreach ( $links as $link ) {
                if ( ! is_array( $link ) ) {
                    continue;
                }

                $title = isset( $link['title'] ) ? sanitize_text_field( $link['title'] ) : '';
                $url   = isset( $link['url'] ) ? esc_url( $link['url'] ) : '';

                if ( $url ) {
                    $normalised[] = [
                        'title' => $title,
                        'url'   => $url,
                    ];
                }
            }

            return $normalised;
        }
        
        /**
         * Render paint options for mixer / shade helper dropdowns.
         *
         * @param array $paints Array of paints with keys: id, name, number, hex, range_id.
         */
        public static function render_mix_paint_options( $paints ) {
            foreach ( $paints as $paint ) {
                $name     = isset( $paint['name'] ) ? $paint['name'] : '';
                $number   = isset( $paint['number'] ) ? $paint['number'] : '';
                $hex      = isset( $paint['hex'] ) ? $paint['hex'] : '';
                $range_id = isset( $paint['range_id'] ) ? (int) $paint['range_id'] : 0;
        
                if ( '' === $name || '' === $hex || ! $range_id ) {
                    continue;
                }
        
                $label = $name;
                if ( '' !== $number ) {
                    $label .= ' (' . $number . ')';
                }
        
                // Choose text colour for contrast (simple luminance check)
                $text_color = '#000000';
                $hex_clean  = ltrim( $hex, '#' );
                if ( strlen( $hex_clean ) === 6 ) {
                    $r = hexdec( substr( $hex_clean, 0, 2 ) );
                    $g = hexdec( substr( $hex_clean, 2, 2 ) );
                    $b = hexdec( substr( $hex_clean, 4, 2 ) );
                    $luminance = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;
                    $text_color = ( $luminance < 0.5 ) ? '#f9fafb' : '#111827';
                }
        
                $style = sprintf(
                    'background-color:%1$s;color:%2$s;',
                    esc_attr( $hex ),
                    esc_attr( $text_color )
                );
                ?>
                <div class="pct-mix-option"
                    data-hex="<?php echo esc_attr( $hex ); ?>"
                    data-label="<?php echo esc_attr( $label ); ?>"
                    data-range="<?php echo esc_attr( $range_id ); ?>"
                    data-id="<?php echo isset( $paint['id'] ) ? esc_attr( $paint['id'] ) : ''; ?>"
                    style="<?php echo $style; ?>">
                    <span class="pct-mix-option-swatch"></span>
                    <span class="pct-mix-option-label"><?php echo esc_html( $label ); ?></span>
                </div>
                <?php
            }
        }

        /**
         * Register "Import from CSV" submenu.
         */
        public function register_import_page() {
            add_submenu_page(
                'edit.php?post_type=' . self::CPT,
                __( 'Import Paints from CSV', 'pct' ),
                __( 'Import from CSV', 'pct' ),
                'manage_options',
                'pct-import-paints',
                [ $this, 'render_import_page' ]
            );
        }

        /**
         * Register "Export to CSV" submenu.
         */
        public function register_export_page() {
            add_submenu_page(
                'edit.php?post_type=' . self::CPT,
                __( 'Export Paints to CSV', 'pct' ),
                __( 'Export to CSV', 'pct' ),
                'manage_options',
                'pct-export-paints',
                [ $this, 'render_export_page' ]
            );
        }

        /**
         * Register "Info & Settings" submenu.
         */
        public function register_info_settings_page() {
            add_submenu_page(
                'edit.php?post_type=' . self::CPT,
                __( 'Info & Settings', 'pct' ),
                __( 'Info & Settings', 'pct' ),
                'manage_options',
                'pct-info-settings',
                [ $this, 'render_info_settings_page' ]
            );
        }

        /**
         * Render the Info & Settings page and handle saving options.
         * - Shading page URL (for [shade-helper] deep-linking from [paint_table])
         * - Paint table display mode (colour dots vs full row highlight)
         */
        public function render_info_settings_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'You do not have permission to access this page.', 'pct' ) );
            }

            $message = '';

            // Load existing values first
            $info_url = get_option( 'pct_mixing_page_url', '' );
            $mode     = get_option( 'pct_table_display_mode', 'dots' );

            // Save if the Info & Settings nonce is present and valid
            if (
                isset( $_POST['pct_info_settings_nonce'] )
                && wp_verify_nonce( $_POST['pct_info_settings_nonce'], 'pct_info_settings' )
            ) {
                // Shading page URL (may or may not be present depending on which form posted)
                if ( isset( $_POST['pct_mixing_page_url'] ) ) {
                    $info_url = esc_url_raw( wp_unslash( $_POST['pct_mixing_page_url'] ) );
                    update_option( 'pct_mixing_page_url', $info_url );
                }

                // Paint table display mode (may or may not be present depending on which form posted)
                if ( isset( $_POST['pct_table_display_mode'] ) ) {
                    $mode_raw = sanitize_text_field( wp_unslash( $_POST['pct_table_display_mode'] ) );
                    $mode     = in_array( $mode_raw, [ 'dots', 'rows' ], true ) ? $mode_raw : 'dots';
                    update_option( 'pct_table_display_mode', $mode );
                }

                $message = __( 'Settings saved.', 'pct' );
            }

            // Pass values to the template
            $pct_admin_view           = 'info_settings';
            $pct_info_message         = $message;
            $pct_info_url             = $info_url;
            $pct_table_display_mode   = $mode;

            include plugin_dir_path( __FILE__ ) . 'admin/admin-page.php';
        }

        /**
         * Render the CSV import page + handle form submission.
         * Delegates HTML to admin/admin-page.php.
         */
        public function render_import_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'You do not have permission to access this page.', 'pct' ) );
            }

            $message = '';
            $errors  = [];

            if ( isset( $_POST['pct_import_submit'] ) ) {
                check_admin_referer( 'pct_import_paints', 'pct_import_nonce' );

                $range_id = isset( $_POST['pct_range'] ) ? intval( $_POST['pct_range'] ) : 0;
                if ( ! $range_id ) {
                    $errors[] = __( 'Please choose a paint range.', 'pct' );
                }

                if ( empty( $_FILES['pct_csv']['tmp_name'] ) ) {
                    $errors[] = __( 'Please upload a CSV file.', 'pct' );
                }

                if ( empty( $errors ) ) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';

                    $uploaded_file = wp_handle_upload(
                        $_FILES['pct_csv'],
                        [ 'test_form' => false ]
                    );

                    if ( isset( $uploaded_file['error'] ) ) {
                        $errors[] = $uploaded_file['error'];
                    } else {
                        $file_path = $uploaded_file['file'];

                        $result = $this->import_csv_file( $file_path, $range_id );

                        if ( is_wp_error( $result ) ) {
                            $errors[] = $result->get_error_message();
                        } else {
                            $message = sprintf(
                                /* translators: %d: number of paints imported */
                                __( 'Imported %d paints.', 'pct' ),
                                intval( $result )
                            );
                        }
                    }
                }
            }

            $pct_admin_view     = 'import_page';
            $pct_import_message = $message;
            $pct_import_errors  = $errors;

            $pct_import_ranges = get_terms(
                [
                    'taxonomy'   => self::TAX,
                    'hide_empty' => false,
                    'orderby'    => 'term_order',
                    'order'      => 'ASC',
                ]
            );

            include plugin_dir_path( __FILE__ ) . 'admin/admin-page.php';
        }

        /**
         * Render the CSV export page and handle output.
         * Delegates HTML to admin/admin-page.php when not exporting.
         */
        public function render_export_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'You do not have permission to access this page.', 'pct' ) );
            }

            // If user clicked "Download", output CSV and exit.
            if ( isset( $_POST['pct_export_download'] ) ) {
                check_admin_referer( 'pct_export_paints', 'pct_export_nonce' );
                $this->export_csv();
                exit;
            }

            $pct_admin_view = 'export_page';

            include plugin_dir_path( __FILE__ ) . 'admin/admin-page.php';
        }

        /**
         * Import paints from a CSV file into a specific range.
         *
         * Expected columns: title, number, hex, on_shelf (0/1)
         */
        private function import_csv_file( $file_path, $range_id ) {
            if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
                return new WP_Error( 'pct_csv_missing', __( 'CSV file is missing or not readable.', 'pct' ) );
            }

            $handle = fopen( $file_path, 'r' );
            if ( ! $handle ) {
                return new WP_Error( 'pct_csv_open', __( 'Unable to open CSV file.', 'pct' ) );
            }

            $row       = 0;
            $imported  = 0;

            // Optional: if first row is header, detect & skip
            $first_row = fgetcsv( $handle );
            if ( ! $first_row ) {
                fclose( $handle );
                return new WP_Error( 'pct_csv_empty', __( 'CSV file is empty.', 'pct' ) );
            }

            // If header row contains "title" etc., skip; otherwise treat as data.
            $header = array_map( 'strtolower', $first_row );
            if ( in_array( 'title', $header, true ) || in_array( 'number', $header, true ) ) {
                // Already consumed header.
            } else {
                // Rewind to treat first row as data.
                rewind( $handle );
            }

            while ( ( $data = fgetcsv( $handle ) ) !== false ) {
                $row++;

                $title     = isset( $data[0] ) ? sanitize_text_field( $data[0] ) : '';
                $number    = isset( $data[1] ) ? sanitize_text_field( $data[1] ) : '';
                $hex       = isset( $data[2] ) ? sanitize_text_field( $data[2] ) : '';
                $on_shelf  = isset( $data[3] ) ? intval( $data[3] ) : 0;

                if ( '' === $title ) {
                    continue;
                }

                $post_id = wp_insert_post(
                    [
                        'post_type'   => self::CPT,
                        'post_title'  => $title,
                        'post_status' => 'publish',
                    ]
                );

                if ( is_wp_error( $post_id ) ) {
                    continue;
                }

                update_post_meta( $post_id, self::META_NUMBER, $number );
                update_post_meta( $post_id, self::META_HEX, $hex );
                update_post_meta( $post_id, self::META_ON_SHELF, $on_shelf );

                // Assign to range
                wp_set_post_terms( $post_id, [ $range_id ], self::TAX );

                $imported++;
            }

            fclose( $handle );

            return $imported;
        }

        /**
         * Export paints to CSV and stream to browser.
         */
        private function export_csv() {
            $filename = 'paint-export-' . date( 'Y-m-d-H-i-s' ) . '.csv';

            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=' . $filename );

            $output = fopen( 'php://output', 'w' );

            // Header row
            fputcsv( $output, [ 'title', 'number', 'hex', 'on_shelf', 'ranges' ] );

            $query = new WP_Query(
                [
                    'post_type'      => self::CPT,
                    'posts_per_page' => -1,
                    'post_status'    => 'publish',
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                ]
            );

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $post_id = get_the_ID();

                    $title    = get_the_title();
                    $number   = get_post_meta( $post_id, self::META_NUMBER, true );
                    $hex      = get_post_meta( $post_id, self::META_HEX, true );
                    $on_shelf = get_post_meta( $post_id, self::META_ON_SHELF, true );

                    $ranges = wp_get_post_terms( $post_id, self::TAX );
                    $range_names = wp_list_pluck( $ranges, 'name' );
                    $range_str   = implode( '|', $range_names );

                    fputcsv(
                        $output,
                        [
                            $title,
                            $number,
                            $hex,
                            $on_shelf,
                            $range_str,
                        ]
                    );
                }
                wp_reset_postdata();
            }

            fclose( $output );
        }

        /**
         * Columns in the admin list table.
         */
        public function manage_edit_columns( $columns ) {
            $new_columns = [];

            // Always keep the checkbox first
            if ( isset( $columns['cb'] ) ) {
                $new_columns['cb'] = $columns['cb'];
            }

            // Title first
            $new_columns['title'] = __( 'Title', 'pct' );

            // Then our custom columns
            $new_columns['pct_number']    = __( 'Number', 'pct' );
            $new_columns['pct_hex']       = __( 'Hex', 'pct' );
            $new_columns['pct_on_shelf']  = __( 'On Shelf?', 'pct' );

            // We intentionally DO NOT re-add 'date', so it disappears

            return $new_columns;
        }

        /**
         * Render custom column content.
         */
        public function manage_custom_column( $column, $post_id ) {
            if ( 'pct_number' === $column ) {
                $number = get_post_meta( $post_id, self::META_NUMBER, true );
                echo esc_html( $number );
            } elseif ( 'pct_hex' === $column ) {
                $hex = get_post_meta( $post_id, self::META_HEX, true );
                echo esc_html( $hex );
            } elseif ( 'pct_on_shelf' === $column ) {
                $on_shelf = get_post_meta( $post_id, self::META_ON_SHELF, true );
                ?>
                <span class="pct-on-shelf-value" data-on-shelf="<?php echo esc_attr( $on_shelf ); ?>">
                    <?php echo $on_shelf ? '✔' : '—'; ?>
                </span>
                <?php
            }
        }

        /**
         * Make columns sortable.
         */
        public function sortable_columns( $columns ) {
            $columns['pct_number'] = 'pct_number';
            return $columns;
        }

        /**
         * Handle admin sorting for Paint Colours.
         *
         * - Default: order by Number (meta_value of META_NUMBER).
         * - When clicking the Number column: also order by Number.
         * - When clicking other columns (e.g. Title): respect core behaviour.
         */
        public function handle_admin_sorting( $query ) {
            if ( ! is_admin() || ! $query->is_main_query() ) {
                return;
            }

            $screen = get_current_screen();
            if ( empty( $screen ) || self::CPT !== $screen->post_type ) {
                return;
            }

            $orderby = $query->get( 'orderby' );

            // If user explicitly clicked the Number column
            if ( 'pct_number' === $orderby ) {
                $query->set( 'meta_key', self::META_NUMBER );
                $query->set( 'orderby', 'meta_value' );
                return;
            }

            // No explicit order set (initial load) → default to Number ASC
            if ( empty( $orderby ) ) {
                $query->set( 'meta_key', self::META_NUMBER );
                $query->set( 'orderby', 'meta_value' );
                $query->set( 'order', 'ASC' );
            }
        }
    }

    // Bootstrap plugin
    function pct_paint_table_plugin_init() {
        static $instance = null;

        if ( null === $instance ) {
            $instance = new PCT_Paint_Table_Plugin();
        }

        return $instance;
    }

    pct_paint_table_plugin_init();
}
