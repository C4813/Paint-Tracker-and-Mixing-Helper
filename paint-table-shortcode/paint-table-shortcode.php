<?php
/**
 * Plugin Name: Paint Table Shortcodes
 * Description: Shortcode [paint_table] to display paint colour tables (starting with Vallejo Model Color), plus CSV import.
 * Version: 0.2.0
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
        const VERSION = '0.1.12';

        public function __construct() {
            add_action( 'init',                    [ $this, 'register_types' ] );
            add_action( 'init',                    [ $this, 'load_textdomain' ] );
            add_action( 'add_meta_boxes',          [ $this, 'add_meta_boxes' ] );
            add_action( 'save_post_' . self::CPT,  [ $this, 'save_paint_meta' ], 10, 2 );
            add_shortcode( 'paint_table',          [ $this, 'shortcode_paint_table' ] );
            add_action( 'wp_enqueue_scripts',      [ $this, 'enqueue_frontend_assets' ] );

            // Admin assets (CSS + JS)
            add_action( 'admin_enqueue_scripts',   [ $this, 'enqueue_admin_assets' ] );

            // Admin: CSV import page
            add_action( 'admin_menu',              [ $this, 'register_import_page' ] );

            // Admin: list table columns & sorting
            add_filter( 'manage_edit-' . self::CPT . '_columns',          [ $this, 'admin_columns' ] );
            add_action( 'manage_' . self::CPT . '_posts_custom_column',   [ $this, 'admin_columns_content' ], 10, 2 );
            add_filter( 'manage_edit-' . self::CPT . '_sortable_columns', [ $this, 'admin_sortable_columns' ] );
            add_action( 'pre_get_posts',                                   [ $this, 'admin_default_sort_by_number' ] );

            // Admin: Quick Edit support for "On shelf"
            add_action( 'quick_edit_custom_box',  [ $this, 'quick_edit_custom_box' ], 10, 2 );

            // Admin: Bulk Edit support for "On shelf"
            add_action( 'bulk_edit_custom_box',   [ $this, 'bulk_edit_custom_box' ], 10, 2 );
            add_action( 'load-edit.php',          [ $this, 'handle_bulk_edit' ] );
        }

        /**
         * Load plugin textdomain (for translations, if ever needed).
         */
        public function load_textdomain() {
            load_plugin_textdomain(
                'pct',
                false,
                dirname( plugin_basename( __FILE__ ) ) . '/languages'
            );
        }

        /**
         * Register custom post type and taxonomy.
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
                    'hierarchical' => false,
                ]
            );
        }

        /**
         * Enqueue admin-only CSS & JS for our CPT screens.
         */
        public function enqueue_admin_assets( $hook ) {
            global $typenow;

            if ( $typenow !== self::CPT ) {
                return;
            }

            wp_enqueue_style(
                'pct_paint_table_admin',
                plugin_dir_url( __FILE__ ) . 'admin-style.css',
                [],
                self::VERSION
            );

            wp_enqueue_script(
                'pct_paint_table_admin',
                plugin_dir_url( __FILE__ ) . 'admin.js',
                [ 'jquery' ],
                self::VERSION,
                true
            );

            // Localise strings for admin.js (for labels/placeholders)
            wp_localize_script(
                'pct_paint_table_admin',
                'pctAdmin',
                [
                    'linkTitleLabel' => __( 'Link title', 'pct' ),
                    'linkTitlePh'    => __( 'e.g. Tutorial, Review, Example Build', 'pct' ),
                    'linkUrlLabel'   => __( 'Link URL', 'pct' ),
                    'linkUrlPh'      => 'https://example.com/my-article',
                    'removeLink'     => __( 'Remove link', 'pct' ),
                ]
            );
        }

        /**
         * Add custom columns to Paint Colours admin list.
         */
        public function admin_columns( $columns ) {
            $new = [];

            foreach ( $columns as $key => $label ) {

                // Remove default Date column
                if ( 'date' === $key ) {
                    continue;
                }

                $new[ $key ] = $label;

                // Insert Number and On shelf right after Title
                if ( 'title' === $key ) {
                    $new['pct_number']   = __( 'Number', 'pct' );
                    $new['pct_on_shelf'] = __( 'On shelf', 'pct' );
                }
            }

            return $new;
        }

        /**
         * Render content for custom columns.
         */
        public function admin_columns_content( $column, $post_id ) {
            if ( 'pct_number' === $column ) {
                $number = get_post_meta( $post_id, self::META_NUMBER, true );
                echo esc_html( $number );
            }

            if ( 'pct_on_shelf' === $column ) {
                $on_shelf = get_post_meta( $post_id, self::META_ON_SHELF, true );
                $on_shelf = ( $on_shelf === '1' ) ? '1' : '0';

                echo ( $on_shelf === '1' )
                    ? esc_html__( 'Yes', 'pct' )
                    : '&mdash;';

                // Hidden marker so Quick Edit JS can read the value
                echo '<span class="hidden pct-on-shelf-value" data-on-shelf="' . esc_attr( $on_shelf ) . '"></span>';
            }
        }

        /**
         * Make columns sortable.
         */
        public function admin_sortable_columns( $columns ) {
            $columns['pct_number'] = 'pct_number';
            return $columns;
        }

        /**
         * Default sort by paint number in admin, and support clicking the Number column.
         */
        public function admin_default_sort_by_number( $query ) {
            if ( ! is_admin() || ! $query->is_main_query() ) {
                return;
            }

            $post_type = $query->get( 'post_type' );
            if ( self::CPT !== $post_type ) {
                return;
            }

            $orderby = $query->get( 'orderby' );

            if ( 'pct_number' === $orderby ) {
                $query->set( 'meta_key', self::META_NUMBER );
                $query->set( 'orderby', 'meta_value' );
                return;
            }

            if ( empty( $orderby ) ) {
                $query->set( 'meta_key', self::META_NUMBER );
                $query->set( 'orderby', 'meta_value' );
                $query->set( 'order', 'ASC' );
            }
        }

        /**
         * Quick Edit field for "On the shelf".
         */
        public function quick_edit_custom_box( $column_name, $post_type ) {
            if ( self::CPT !== $post_type || 'pct_on_shelf' !== $column_name ) {
                return;
            }
            ?>
            <fieldset class="inline-edit-col-right inline-edit-pct-on-shelf">
                <div class="inline-edit-col">
                    <label class="alignleft">
                        <span class="title"><?php esc_html_e( 'On the shelf', 'pct' ); ?></span>
                        <span class="input-text-wrap">
                            <input type="checkbox" name="pct_on_shelf" value="1">
                            <?php esc_html_e( 'Yes', 'pct' ); ?>
                        </span>
                    </label>
                </div>
            </fieldset>
            <?php
        }

        /**
         * Bulk Edit field for "On the shelf".
         */
        public function bulk_edit_custom_box( $column_name, $post_type ) {
            if ( self::CPT !== $post_type || 'pct_on_shelf' !== $column_name ) {
                return;
            }
            ?>
            <fieldset class="inline-edit-col-right inline-edit-pct-on-shelf-bulk">
                <div class="inline-edit-col">
                    <label class="alignleft">
                        <span class="title"><?php esc_html_e( 'On the shelf', 'pct' ); ?></span>
                        <span class="input-text-wrap">
                            <select name="pct_on_shelf_bulk">
                                <option value=""><?php esc_html_e( '— No change —', 'pct' ); ?></option>
                                <option value="1"><?php esc_html_e( 'Mark as on shelf', 'pct' ); ?></option>
                                <option value="0"><?php esc_html_e( 'Mark as not on shelf', 'pct' ); ?></option>
                            </select>
                        </span>
                    </label>
                </div>
            </fieldset>
            <?php
        }

        /**
         * Handle Bulk Edit submission for On the shelf.
         */
        public function handle_bulk_edit() {
            if ( ! isset( $_REQUEST['post_type'] ) || $_REQUEST['post_type'] !== self::CPT ) {
                return;
            }

            if ( ! current_user_can( 'edit_posts' ) ) {
                return;
            }

            // Bulk edit form nonce
            if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-posts' ) ) {
                return;
            }

            if ( ! isset( $_REQUEST['pct_on_shelf_bulk'] ) ) {
                return;
            }

            $value = sanitize_text_field( wp_unslash( $_REQUEST['pct_on_shelf_bulk'] ) );

            // Empty / no choice => no change
            if ( '' === $value ) {
                return;
            }

            // Normalise to '1' or '0'
            $on_shelf_value = ( '1' === $value ) ? '1' : '0';

            if ( ! isset( $_REQUEST['post'] ) || ! is_array( $_REQUEST['post'] ) ) {
                return;
            }

            $post_ids = array_map( 'intval', $_REQUEST['post'] );

            foreach ( $post_ids as $post_id ) {
                if ( $post_id <= 0 ) {
                    continue;
                }

                // Only update our CPT
                if ( get_post_type( $post_id ) !== self::CPT ) {
                    continue;
                }

                update_post_meta( $post_id, self::META_ON_SHELF, $on_shelf_value );
            }
        }

        /**
         * Add meta box for paint details.
         */
        public function add_meta_boxes() {
            add_meta_box(
                'pct_paint_details',
                __( 'Paint details', 'pct' ),
                [ $this, 'render_paint_meta_box' ],
                self::CPT,
                'normal',
                'default'
            );
        }

        /**
         * Render the meta box HTML (delegated to admin-template.php).
         */
        public function render_paint_meta_box( $post ) {

            $number   = get_post_meta( $post->ID, self::META_NUMBER, true );
            $hex      = get_post_meta( $post->ID, self::META_HEX, true );
            $on_shelf = get_post_meta( $post->ID, self::META_ON_SHELF, true );

            // Multiple links stored as array of ['title' => ..., 'url' => ...]
            $links = get_post_meta( $post->ID, self::META_LINKS, true );

            // Backwards compatibility for old single-link meta
            if ( empty( $links ) ) {
                $legacy_url = get_post_meta( $post->ID, self::META_LINK, true );
                if ( $legacy_url ) {
                    $links = [
                        [
                            'title' => '',
                            'url'   => $legacy_url,
                        ],
                    ];
                } else {
                    $links = [];
                }
            } elseif ( is_array( $links ) && ! empty( $links ) ) {
                $first = reset( $links );
                if ( is_string( $first ) ) {
                    $converted = [];
                    foreach ( $links as $url ) {
                        $converted[] = [
                            'title' => '',
                            'url'   => $url,
                        ];
                    }
                    $links = $converted;
                }
            }

            // Variables for the admin template
            $pct_admin_view  = 'meta_box';
            $pct_number      = $number;
            $pct_hex         = $hex;
            $pct_on_shelf    = $on_shelf;
            $pct_links       = $links;

            include plugin_dir_path( __FILE__ ) . 'admin-template.php';
        }

        /**
         * Save meta box fields (including on-shelf flag & multiple links).
         * Handles both full edit screen and Quick Edit saves.
         */
        public function save_paint_meta( $post_id, $post ) {

            // Allow either the main meta box nonce OR the Quick Edit nonce.
            $has_meta_nonce = (
                isset( $_POST['pct_paint_meta_nonce'] ) &&
                wp_verify_nonce( $_POST['pct_paint_meta_nonce'], 'pct_save_paint_meta' )
            );

            $has_inline_nonce = (
                isset( $_POST['_inline_edit'] ) &&
                wp_verify_nonce( $_POST['_inline_edit'], 'inlineeditnonce' )
            );

            if ( ! $has_meta_nonce && ! $has_inline_nonce ) {
                return;
            }

            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }

            /**
             * QUICK EDIT SAVE
             * Only update the on_shelf flag, do NOT touch number/hex/links.
             * (Bulk edit is handled separately in handle_bulk_edit())
             */
            if ( $has_inline_nonce && ! $has_meta_nonce && isset( $_POST['pct_on_shelf'] ) ) {
                $on_shelf = isset( $_POST['pct_on_shelf'] ) ? '1' : '0';
                update_post_meta( $post_id, self::META_ON_SHELF, $on_shelf );
                return;
            }

            /**
             * FULL EDIT SCREEN SAVE
             * Meta box was present, so we have all fields available.
             */
            if ( ! $has_meta_nonce ) {
                // Not a full edit save, nothing more to do here.
                return;
            }

            $number   = isset( $_POST['pct_number'] ) ? sanitize_text_field( wp_unslash( $_POST['pct_number'] ) ) : '';
            $hex      = isset( $_POST['pct_hex'] ) ? sanitize_text_field( wp_unslash( $_POST['pct_hex'] ) ) : '';
            $on_shelf = isset( $_POST['pct_on_shelf'] ) ? '1' : '0';

            update_post_meta( $post_id, self::META_NUMBER,   $number );
            update_post_meta( $post_id, self::META_HEX,      $hex );
            update_post_meta( $post_id, self::META_ON_SHELF, $on_shelf );

            // Multiple links with titles
            $titles = isset( $_POST['pct_links_title'] ) && is_array( $_POST['pct_links_title'] )
                ? array_map( 'wp_unslash', $_POST['pct_links_title'] )
                : [];

            $urls   = isset( $_POST['pct_links_url'] ) && is_array( $_POST['pct_links_url'] )
                ? array_map( 'wp_unslash', $_POST['pct_links_url'] )
                : [];

            $links = [];
            $count = max( count( $titles ), count( $urls ) );

            for ( $i = 0; $i < $count; $i++ ) {
                $title = isset( $titles[ $i ] ) ? trim( $titles[ $i ] ) : '';
                $url   = isset( $urls[ $i ] ) ? trim( $urls[ $i ] )   : '';

                if ( '' === $url ) {
                    continue; // skip empty rows
                }

                $links[] = [
                    'title' => sanitize_text_field( $title ),
                    'url'   => esc_url_raw( $url ),
                ];
            }

            if ( ! empty( $links ) ) {
                update_post_meta( $post_id, self::META_LINKS, $links );
                update_post_meta( $post_id, self::META_LINK,  $links[0]['url'] );
            } else {
                delete_post_meta( $post_id, self::META_LINKS );
                delete_post_meta( $post_id, self::META_LINK );
            }
        }

        /**
         * Enqueue front-end stylesheet (separate file).
         */
        public function enqueue_frontend_assets() {
            wp_enqueue_style(
                'pct_paint_table',
                plugin_dir_url( __FILE__ ) . 'style.css',
                [],
                self::VERSION
            );
        }

        /**
         * Shortcode handler:
         *
         * [paint_table range="vallejo-model-color" limit="-1" orderby="meta_number|title" shelf="yes|any"]
         *
         * - shelf="yes" => only paints marked "On the shelf"
         * - shelf omitted or anything else => all paints in the range
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
                    'value'   => '1',
                    'compare' => '=',
                ];
            }

            if ( ! empty( $meta_query ) ) {
                $args['meta_query'] = $meta_query;
            }

            $q = new WP_Query( $args );

            if ( ! $q->have_posts() ) {
                return '<p>No paints found.</p>';
            }

            // Build data array for the template
            $paints_data = [];

            while ( $q->have_posts() ) {
                $q->the_post();
                $id     = get_the_ID();
                $name   = get_the_title();
                $number = get_post_meta( $id, self::META_NUMBER, true );
                $hex    = get_post_meta( $id, self::META_HEX, true );

                $links = $this->get_paint_links( $id );

                $paints_data[] = [
                    'name'   => $name,
                    'number' => $number,
                    'hex'    => $hex,
                    'links'  => $links,
                ];
            }

            wp_reset_postdata();

            // Make data available to the template
            $pct_paints      = $paints_data;
            $pct_range_title = $range_title;

            ob_start();
            include plugin_dir_path( __FILE__ ) . 'template.php';
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
                            'url'   => $single,
                        ],
                    ];
                }
                return [];
            }

            // If it’s an array of simple URLs, normalise to [title,url] arrays
            if ( is_array( $links ) ) {
                $first = reset( $links );
                if ( is_string( $first ) ) {
                    $normalised = [];
                    foreach ( $links as $url ) {
                        $normalised[] = [
                            'title' => '',
                            'url'   => $url,
                        ];
                    }
                    return $normalised;
                }
            }

            return is_array( $links ) ? $links : [];
        }

        /**
         * Helper: get range title (term name) from slug.
         */
        private function get_range_title_by_slug( $slug ) {
            if ( ! $slug ) {
                return '';
            }

            $term = get_term_by( 'slug', $slug, self::TAX );
            if ( $term && ! is_wp_error( $term ) ) {
                return $term->name;
            }

            return '';
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
         * Render the CSV import page + handle form submission.
         * Delegates HTML to admin-template.php.
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

                        $created = 0;
                        $handle  = fopen( $file_path, 'r' );

                        if ( $handle ) {
                            $row = 0;
                            while ( ( $data = fgetcsv( $handle, 0, ',' ) ) !== false ) {
                                $row++;

                                // Skip header row
                                if ( 1 === $row ) {
                                    continue;
                                }

                                // Expecting at least 3 columns: name, number, hex
                                if ( count( $data ) < 3 ) {
                                    continue;
                                }
                                
                                $name   = trim( $data[0] );
                                $number = trim( $data[1] );
                                $hex    = trim( $data[2] );
                                
                                // Optional 4th column: On shelf (yes/no)
                                $on_shelf_raw = isset( $data[3] ) ? trim( $data[3] ) : '';
                                $on_shelf_val = '0';
                                
                                if ( $on_shelf_raw !== '' ) {
                                    $lower = strtolower( $on_shelf_raw );
                                    if ( in_array( $lower, [ 'yes', 'y', '1', 'true' ], true ) ) {
                                        $on_shelf_val = '1';
                                    }
                                }
                                
                                if ( '' === $name ) {
                                    continue;
                                }
                                
                                $post_id = wp_insert_post(
                                    [
                                        'post_type'   => self::CPT,
                                        'post_status' => 'publish',
                                        'post_title'  => $name,
                                    ]
                                );
                                
                                if ( ! is_wp_error( $post_id ) && $post_id ) {
                                    update_post_meta( $post_id, self::META_NUMBER,   $number );
                                    update_post_meta( $post_id, self::META_HEX,      $hex );
                                    update_post_meta( $post_id, self::META_ON_SHELF, $on_shelf_val );
                                    wp_set_object_terms( $post_id, $range_id, self::TAX );
                                    $created++;
                                }
                            }
                            fclose( $handle );

                            $message = sprintf(
                                _n( 'Imported %d paint.', 'Imported %d paints.', $created, 'pct' ),
                                $created
                            );
                        } else {
                            $errors[] = __( 'Unable to open the uploaded CSV file.', 'pct' );
                        }
                    }
                }
            }

            $pct_admin_view    = 'import_page';
            $pct_import_msg    = $message;
            $pct_import_errors = $errors;

            include plugin_dir_path( __FILE__ ) . 'admin-template.php';
        }
    }
}

if ( class_exists( 'PCT_Paint_Table_Plugin' ) ) {
    new PCT_Paint_Table_Plugin();
}
