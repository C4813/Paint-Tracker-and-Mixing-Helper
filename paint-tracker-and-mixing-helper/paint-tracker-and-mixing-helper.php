<?php
/**
 * Plugin Name: Paint Tracker and Mixing Helper
 * Description: Shortcodes to display your miniature paint collection, as well as a mixing and shading helper for specific colours.
 * Version: 0.10.6
 * Author: C4813
 * Text Domain: paint-tracker-and-mixing-helper
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'PCT_Paint_Table_Plugin' ) ) {

    class PCT_Paint_Table_Plugin {

        const CPT = 'paint_color';
        const TAX = 'paint_range';

        // Meta keys
        const META_NUMBER        = '_pct_number';
        const META_HEX           = '_pct_hex';
        const META_ON_SHELF      = '_pct_on_shelf';
        const META_LINKS         = '_pct_links';
        const META_LINK          = '_pct_link'; // legacy single link
        const META_BASE_TYPE     = '_pct_base_type';
        const META_EXCLUDE_SHADE = '_pct_exclude_shade';
        const META_GRADIENT      = '_pct_gradient';

        // Plugin version (used for asset cache-busting)
        const VERSION = '0.10.6';

        public function __construct() {
            add_action( 'init', [ $this, 'register_types' ] );

            // Metaboxes & saving
            add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
            add_action( 'save_post_' . self::CPT, [ $this, 'save_paint_meta' ], 10, 2 );

            // Quick Edit + Bulk Edit
            add_action( 'quick_edit_custom_box', [ $this, 'quick_edit_fields' ], 10, 2 );
            add_action( 'bulk_edit_custom_box', [ $this, 'bulk_edit_fields' ], 10, 2 );
            add_action( 'save_post_' . self::CPT, [ $this, 'save_quick_edit' ], 10, 2 );
            add_action( 'admin_init', [ $this, 'handle_bulk_on_shelf_update' ] );

            // Frontend assets
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );

            // Shortcodes
            add_shortcode( 'paint_table', [ $this, 'shortcode_paint_table' ] );
            add_shortcode( 'mixing-helper', [ $this, 'shortcode_mixing_helper' ] );
            add_shortcode( 'shade-helper', [ $this, 'shortcode_shade_helper' ] );

            // Admin assets (CSS + JS)
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

            // Admin: CSV import & export pages
            add_action( 'admin_menu', [ $this, 'register_import_page' ] );
            add_action( 'admin_menu', [ $this, 'register_export_page' ] );
            add_action( 'admin_menu', [ $this, 'register_info_settings_page' ] );

            // Admin-post handler for CSV export
            add_action( 'admin_post_pct_export_paints', [ $this, 'handle_export_paints' ] );

            // Admin: list table columns & sorting
            add_filter( 'manage_edit-' . self::CPT . '_columns', [ $this, 'manage_edit_columns' ] );
            add_action( 'manage_' . self::CPT . '_posts_custom_column', [ $this, 'manage_custom_column' ], 10, 2 );
            add_filter( 'manage_edit-' . self::CPT . '_sortable_columns', [ $this, 'sortable_columns' ] );
            add_action( 'pre_get_posts', [ $this, 'handle_admin_sorting' ] );
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
                        'name'          => __( 'Paint Colours', 'paint-tracker-and-mixing-helper' ),
                        'singular_name' => __( 'Paint Colour', 'paint-tracker-and-mixing-helper' ),
                        'add_new_item'  => __( 'Add New Paint Colour', 'paint-tracker-and-mixing-helper' ),
                        'edit_item'     => __( 'Edit Paint Colour', 'paint-tracker-and-mixing-helper' ),
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
                        'name'          => __( 'Paint Ranges', 'paint-tracker-and-mixing-helper' ),
                        'singular_name' => __( 'Paint Range', 'paint-tracker-and-mixing-helper' ),
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
                __( 'Paint Details', 'paint-tracker-and-mixing-helper' ),
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
            $number        = get_post_meta( $post->ID, self::META_NUMBER, true );
            $hex           = get_post_meta( $post->ID, self::META_HEX, true );
            $on_shelf      = get_post_meta( $post->ID, self::META_ON_SHELF, true );
            $links         = get_post_meta( $post->ID, self::META_LINKS, true );
            $base_type     = get_post_meta( $post->ID, self::META_BASE_TYPE, true );
            $exclude_shade = get_post_meta( $post->ID, self::META_EXCLUDE_SHADE, true );
            $gradient      = get_post_meta( $post->ID, self::META_GRADIENT, true );

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

            $pct_admin_view    = 'meta_box';
            $pct_number        = $number;
            $pct_hex           = $hex;
            $pct_on_shelf      = $on_shelf;
            $pct_links         = $links;
            $pct_base_type     = $base_type;
            $pct_exclude_shade = (int) $exclude_shade;
            $pct_gradient      = (int) $gradient;

            include plugin_dir_path( __FILE__ ) . 'admin/admin-page.php';
        }

        /**
         * Save meta box fields (including on-shelf flag & multiple links).
         * Handles:
         * - Full edit screen
         * - Quick Edit (delegated to save_quick_edit)
         */
        public function save_paint_meta( $post_id, $post ) {
            // Only handle our CPT
            if ( self::CPT !== $post->post_type ) {
                return;
            }

            // Autosave?
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }

            // Permission check
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }

            // Quick Edit is handled in save_quick_edit(); just verify nonce and bail.
            if ( isset( $_POST['pct_quick_edit_nonce'] ) ) {
                $quick_edit_nonce = sanitize_text_field( wp_unslash( $_POST['pct_quick_edit_nonce'] ) );
                if ( ! wp_verify_nonce( $quick_edit_nonce, 'pct_quick_edit' ) ) {
                    return;
                }
                return;
            }

            // Normal edit screen must have meta box nonce
            if ( ! isset( $_POST['pct_paint_meta_nonce'] ) ) {
                return;
            }

            $paint_meta_nonce = sanitize_text_field( wp_unslash( $_POST['pct_paint_meta_nonce'] ) );
            if ( ! wp_verify_nonce( $paint_meta_nonce, 'pct_save_paint_meta' ) ) {
                return;
            }

            // ----- Normal edit screen save below -----

            // Save number (code / type)
            $number = isset( $_POST['pct_number'] )
                ? sanitize_text_field( wp_unslash( $_POST['pct_number'] ) )
                : '';
            update_post_meta( $post_id, self::META_NUMBER, $number );

            // Save base type (required in UI, but guard + normalise here)
            $base_type = isset( $_POST['pct_base_type'] )
                ? sanitize_text_field( wp_unslash( $_POST['pct_base_type'] ) )
                : '';

            $allowed_base_types = [ 'acrylic', 'enamel', 'oil', 'lacquer' ];
            if ( ! in_array( $base_type, $allowed_base_types, true ) ) {
                // Fallback default; you can change this if you prefer.
                $base_type = 'acrylic';
            }
            update_post_meta( $post_id, self::META_BASE_TYPE, $base_type );

            // Save hex
            $hex = isset( $_POST['pct_hex'] )
                ? sanitize_text_field( wp_unslash( $_POST['pct_hex'] ) )
                : '';
            update_post_meta( $post_id, self::META_HEX, $hex );
            
            // Save gradient toggle
            $gradient = isset( $_POST['pct_gradient'] ) ? 1 : 0;
            update_post_meta( $post_id, self::META_GRADIENT, $gradient );

            // Save on-shelf flag
            $on_shelf = isset( $_POST['pct_on_shelf'] ) ? 1 : 0;
            update_post_meta( $post_id, self::META_ON_SHELF, $on_shelf );

            // Save "exclude from shading helper" flag
            $exclude_shade = isset( $_POST['pct_exclude_shade'] ) ? 1 : 0;
            update_post_meta( $post_id, self::META_EXCLUDE_SHADE, $exclude_shade );

            // -------- Save multiple links (legacy format: pct_links_title[] + pct_links_url[]) --------
            $links = [];

            $raw_titles = [];
            $raw_urls   = [];

            if ( isset( $_POST['pct_links_title'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $raw_titles = wp_unslash( $_POST['pct_links_title'] );
            }

            if ( isset( $_POST['pct_links_url'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $raw_urls = wp_unslash( $_POST['pct_links_url'] );
            }

            if ( ! is_array( $raw_titles ) ) {
                $raw_titles = [];
            }

            if ( ! is_array( $raw_urls ) ) {
                $raw_urls = [];
            }

            if ( ! empty( $raw_titles ) || ! empty( $raw_urls ) ) {
                // Sanitise arrays
                $titles = array_map( 'sanitize_text_field', (array) $raw_titles );
                $urls   = array_map( 'esc_url_raw', (array) $raw_urls );

                // Normalise indexes
                $titles = array_values( $titles );
                $urls   = array_values( $urls );

                $max = max( count( $titles ), count( $urls ) );

                for ( $i = 0; $i < $max; $i++ ) {
                    $title = isset( $titles[ $i ] ) ? $titles[ $i ] : '';
                    $url   = isset( $urls[ $i ] ) ? $urls[ $i ] : '';

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

            $template = plugin_dir_path( __FILE__ ) . 'admin/quick-edit-fields.php';

            if ( file_exists( $template ) ) {
                include $template;
            }
        }

        /**
         * Output Bulk Edit field for On Shelf.
         */
        public function bulk_edit_fields( $column_name, $post_type ) {
            if ( self::CPT !== $post_type || 'pct_number' !== $column_name ) {
                return;
            }

            $template = plugin_dir_path( __FILE__ ) . 'admin/bulk-edit-fields.php';

            if ( file_exists( $template ) ) {
                include $template;
            }
        }

        /**
         * Handle bulk "On Shelf?" updates from the list table bulk edit UI.
         */
        public function handle_bulk_on_shelf_update() {
            if ( ! is_admin() ) {
                return;
            }

            // Only run on our CPT list screen
            $post_type = isset( $_REQUEST['post_type'] ) ? sanitize_key( $_REQUEST['post_type'] ) : '';
            if ( self::CPT !== $post_type ) {
                return;
            }

            // Only when the bulk edit form was used
            if ( ! isset( $_REQUEST['bulk_edit'] ) ) {
                return;
            }

            // At least one of our bulk fields must be present
            $has_on_shelf  = isset( $_REQUEST['pct_bulk_on_shelf'] );
            $has_base_type = isset( $_REQUEST['pct_bulk_base_type'] );
            $has_exclude   = isset( $_REQUEST['pct_bulk_exclude_shade'] );

            if ( ! $has_on_shelf && ! $has_base_type && ! $has_exclude ) {
                return;
            }

            // Check nonce from bulk_edit_fields()
            if ( ! isset( $_REQUEST['pct_bulk_edit_nonce'] ) ) {
                return;
            }

            $bulk_nonce = sanitize_text_field( wp_unslash( $_REQUEST['pct_bulk_edit_nonce'] ) );
            if ( ! wp_verify_nonce( $bulk_nonce, 'pct_bulk_edit' ) ) {
                return;
            }

            // Posts selected
            if ( empty( $_REQUEST['post'] ) || ! is_array( $_REQUEST['post'] ) ) {
                return;
            }

            if ( ! current_user_can( 'edit_posts' ) ) {
                return;
            }

            $bulk_on_shelf_val = $has_on_shelf ? sanitize_text_field( wp_unslash( $_REQUEST['pct_bulk_on_shelf'] ) ) : '';
            $bulk_base_type    = $has_base_type ? sanitize_text_field( wp_unslash( $_REQUEST['pct_bulk_base_type'] ) ) : '';
            $bulk_exclude_val  = $has_exclude ? sanitize_text_field( wp_unslash( $_REQUEST['pct_bulk_exclude_shade'] ) ) : '';

            $do_on_shelf = ( '' !== $bulk_on_shelf_val );
            $do_base_type = ( '' !== $bulk_base_type );
            $do_exclude = ( '' !== $bulk_exclude_val );

            if ( ! $do_on_shelf && ! $do_base_type && ! $do_exclude ) {
                return;
            }

            if ( $do_base_type ) {
                $allowed_base_types = [ 'acrylic', 'enamel', 'oil', 'lacquer' ];
                if ( ! in_array( $bulk_base_type, $allowed_base_types, true ) ) {
                    $do_base_type = false;
                }
            }

            $on_shelf = null;
            if ( $do_on_shelf ) {
                $on_shelf = ( '1' === $bulk_on_shelf_val ) ? 1 : 0;
            }

            $exclude_shade = null;
            if ( $do_exclude ) {
                $exclude_shade = ( '1' === $bulk_exclude_val ) ? 1 : 0;
            }

            $post_ids = array_map( 'absint', (array) $_REQUEST['post'] );

            foreach ( $post_ids as $post_id ) {
                if ( ! $post_id ) {
                    continue;
                }

                if ( get_post_type( $post_id ) !== self::CPT ) {
                    continue;
                }

                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    continue;
                }

                if ( $do_on_shelf ) {
                    update_post_meta( $post_id, self::META_ON_SHELF, $on_shelf );
                }

                if ( $do_base_type ) {
                    update_post_meta( $post_id, self::META_BASE_TYPE, $bulk_base_type );
                }

                if ( $do_exclude ) {
                    update_post_meta( $post_id, self::META_EXCLUDE_SHADE, $exclude_shade );
                }
            }
        }

        /**
         * Save Quick Edit values.
         */
        public function save_quick_edit( $post_id, $post ) {
            if ( ! isset( $_POST['pct_quick_edit_nonce'] ) ) {
                return;
            }

            $quick_edit_nonce = sanitize_text_field( wp_unslash( $_POST['pct_quick_edit_nonce'] ) );
            if ( ! wp_verify_nonce( $quick_edit_nonce, 'pct_quick_edit' ) ) {
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

            // Save base type (only if user picked something)
            if ( isset( $_POST['pct_base_type'] ) && '' !== $_POST['pct_base_type'] ) {
                $base_type          = sanitize_text_field( wp_unslash( $_POST['pct_base_type'] ) );
                $allowed_base_types = [ 'acrylic', 'enamel', 'oil', 'lacquer' ];
                if ( in_array( $base_type, $allowed_base_types, true ) ) {
                    update_post_meta( $post_id, self::META_BASE_TYPE, $base_type );
                }
            }

            // Save on shelf
            $on_shelf = isset( $_POST['pct_on_shelf'] ) ? 1 : 0;
            update_post_meta( $post_id, self::META_ON_SHELF, $on_shelf );

            // Save exclude-from-shade flag
            $exclude_shade = isset( $_POST['pct_exclude_shade'] ) ? 1 : 0;
            update_post_meta( $post_id, self::META_EXCLUDE_SHADE, $exclude_shade );
        }

        /**
         * Enqueue admin scripts.
         */
        public function enqueue_admin_assets( $hook ) {
            global $typenow;

            if ( $typenow !== self::CPT ) {
                return;
            }

            // Admin-only stylesheet
            wp_enqueue_style(
                'pct_paint_table_admin',
                plugin_dir_url( __FILE__ ) . 'admin/admin.css',
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
                    // For the meta box "linked posts / URLs" UI
                    'addLinkLabel'   => __( 'Add another link', 'paint-tracker-and-mixing-helper' ),
                    'linkTitleLabel' => __( 'Link title', 'paint-tracker-and-mixing-helper' ),
                    'linkTitlePh'    => __( 'e.g. Tutorial, Review, Example Build', 'paint-tracker-and-mixing-helper' ),
                    'linkUrlLabel'   => __( 'Link URL', 'paint-tracker-and-mixing-helper' ),
                    'linkUrlPh'      => 'https://example.com/my-article',
                    'removeLink'     => __( 'Remove link', 'paint-tracker-and-mixing-helper' ),
                ]
            );
        }

        /**
         * Enqueue front-end stylesheet + helper JS.
         */
        public function enqueue_frontend_assets() {
            // Only load assets on singular posts/pages that actually use our shortcodes.
            if ( ! is_singular() ) {
                return;
            }

            global $post;

            if ( ! ( $post instanceof WP_Post ) ) {
                return;
            }

            $content    = $post->post_content;
            $has_paint  = has_shortcode( $content, 'paint_table' );
            $has_mixing = has_shortcode( $content, 'mixing-helper' );
            $has_shade  = has_shortcode( $content, 'shade-helper' );

            // If none of our shortcodes are present, bail early.
            if ( ! $has_paint && ! $has_mixing && ! $has_shade ) {
                return;
            }

            // Shared frontend styles (used by all three UIs).
            wp_enqueue_style(
                'pct_paint_table',
                plugin_dir_url( __FILE__ ) . 'public/css/style.css',
                [],
                self::VERSION
            );

            // Paint table only.
            if ( $has_paint ) {
                wp_enqueue_script(
                    'pct_paint_table_js',
                    plugin_dir_url( __FILE__ ) . 'public/js/paint-table.js',
                    [],
                    self::VERSION,
                    true
                );
            }

            // Colour utility helpers (used by mixing + shading).
            if ( $has_mixing || $has_shade ) {
                wp_enqueue_script(
                    'pct_color_utils',
                    plugin_dir_url( __FILE__ ) . 'public/js/pct-color-utils.js',
                    [ 'jquery' ],
                    self::VERSION,
                    true
                );
            }

            // Mixing helper.
            if ( $has_mixing ) {
                wp_enqueue_script(
                    'pct_mixing_helper',
                    plugin_dir_url( __FILE__ ) . 'public/js/mixing-helper.js',
                    [ 'jquery', 'pct_color_utils' ],
                    self::VERSION,
                    true
                );

                // Localise strings for mixing-helper.js
                wp_localize_script(
                    'pct_mixing_helper',
                    'pctMixingHelperL10n',
                    [
                        'selectPaint' => __( 'Select a paint', 'paint-tracker-and-mixing-helper' ),
                    ]
                );
            }

            // Shade helper.
            if ( $has_shade ) {
                wp_enqueue_script(
                    'pct_shade_helper',
                    plugin_dir_url( __FILE__ ) . 'public/js/shade-helper.js',
                    [ 'jquery', 'pct_color_utils' ],
                    self::VERSION,
                    true
                );

                $shade_mode = get_option( 'pct_shade_hue_mode', 'strict' );

                // Localise strings for shade-helper.js
                wp_localize_script(
                    'pct_shade_helper',
                    'pctShadeHelperL10n',
                    [
                        'selectPaint'      => __( 'Select a paint to generate lighter and darker mixes.', 'paint-tracker-and-mixing-helper' ),
                        'invalidHex'       => __( 'This colour has an invalid hex value.', 'paint-tracker-and-mixing-helper' ),
                        'noSelectedPaint'  => __( 'Could not determine the selected paint in this range.', 'paint-tracker-and-mixing-helper' ),
                        'noRange'          => __( 'This paint is not assigned to a range.', 'paint-tracker-and-mixing-helper' ),
                        'notEnoughPaints'  => __( 'Not enough paints in this range to build a shade ladder.', 'paint-tracker-and-mixing-helper' ),
                        'unableToGenerate' => __( 'Unable to generate mixes for this colour.', 'paint-tracker-and-mixing-helper' ),
                        'noDarker'         => __( 'Not enough darker paints to generate darker mixes.', 'paint-tracker-and-mixing-helper' ),
                        'noLighter'        => __( 'Not enough lighter paints to generate lighter mixes.', 'paint-tracker-and-mixing-helper' ),
                        'hueMode'          => $shade_mode, // 'strict' or 'relaxed'
                    ]
                );
            }
        }

        /**
         * Shortcode handler:
         *
         * [paint_table range="vallejo-model-color" limit="-1" orderby="meta_number|title" shelf="yes|any"]
         */
        public function shortcode_paint_table( $atts ) {
            $atts = shortcode_atts(
                [
                    'range'   => '',            // empty = all ranges by default
                    'limit'   => -1,
                    'orderby' => 'meta_number', // or "title"
                    'shelf'   => 'any',         // 'yes' or 'any'
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

            if ( 'meta_value' === $orderby ) {
                $args['meta_key'] = $meta_key;
            }

            $range_slug  = '';
            $range_title = '';

            if ( ! empty( $atts['range'] ) ) {
                $range_slug        = sanitize_title( $atts['range'] );
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

            if ( 'yes' === strtolower( $atts['shelf'] ) ) {
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
                return '<p>' . esc_html__( 'No paints found.', 'paint-tracker-and-mixing-helper' ) . '</p>';
            }

            // Build data array for the template.
            $paints_data = [];

            while ( $q->have_posts() ) {
                $q->the_post();
                $id       = get_the_ID();
                $name     = get_the_title();
                $number   = get_post_meta( $id, self::META_NUMBER, true );
                $hex      = get_post_meta( $id, self::META_HEX, true );
                $gradient = get_post_meta( $id, self::META_GRADIENT, true );
            
                $links = $this->get_paint_links( $id );
            
                $paints_data[] = [
                    'id'       => $id,
                    'name'     => $name,
                    'number'   => $number,
                    'hex'      => $hex,
                    'links'    => $links,
                    'gradient' => (int) $gradient,
                ];
            }

            wp_reset_postdata();

            // Make data available to the template
            $pct_paints             = $paints_data;
            $pct_range_title        = $range_title;
            $pct_mixing_page_url    = get_option( 'pct_mixing_page_url', '' );
            $pct_table_display_mode = get_option( 'pct_table_display_mode', 'dots' );

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
                return '<p>' . esc_html__( 'No paint ranges found.', 'paint-tracker-and-mixing-helper' ) . '</p>';
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
                return '<p>' . esc_html__( 'No paints found for mixing.', 'paint-tracker-and-mixing-helper' ) . '</p>';
            }

            $paints = [];

            while ( $q->have_posts() ) {
                $q->the_post();
                $post_id = get_the_ID();

                $name                 = get_the_title();
                $number               = get_post_meta( $post_id, self::META_NUMBER, true );
                $hex                  = get_post_meta( $post_id, self::META_HEX, true );
                $base_type            = get_post_meta( $post_id, self::META_BASE_TYPE, true );
                $exclude_from_shading = get_post_meta( $post_id, self::META_EXCLUDE_SHADE, true ) ? 1 : 0;

                // Take the first range term for this paint (if multiple, first is fine)
                $term_ids = wp_get_object_terms(
                    $post_id,
                    self::TAX,
                    [
                        'fields' => 'ids',
                    ]
                );

                $range_id = ! empty( $term_ids ) ? (int) $term_ids[0] : 0;

                // Build list of all related range IDs (this paint's range + any parents)
                $all_range_ids = [];
                if ( $range_id ) {
                    $ancestors     = get_ancestors( $range_id, self::TAX );
                    $all_range_ids = array_unique(
                        array_map(
                            'intval',
                            array_merge( [ $range_id ], $ancestors )
                        )
                    );
                }

                $paints[] = [
                    'id'              => $post_id,
                    'name'            => $name,
                    'number'          => $number,
                    'hex'             => $hex,
                    'base_type'       => $base_type,
                    'range_id'        => $range_id,
                    'all_range_ids'   => $all_range_ids,
                    'exclude_shading' => $exclude_from_shading,
                ];
            }

            wp_reset_postdata();

            if ( empty( $paints ) ) {
                return '<p>' . esc_html__( 'No paints found for mixing.', 'paint-tracker-and-mixing-helper' ) . '</p>';
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

            // These GET parameters only affect default UI state and do not modify data.
            if ( isset( $_GET['pct_shade_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $default_shade_id = absint( $_GET['pct_shade_id'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            }

            // These GET parameters only affect default UI state and do not modify data.
            if ( isset( $_GET['pct_shade_hex'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $raw_hex = wp_unslash( $_GET['pct_shade_hex'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $raw_hex = sanitize_text_field( $raw_hex );
                $raw_hex = trim( $raw_hex );

                if ( '' !== $raw_hex ) {
                    if ( '#' !== $raw_hex[0] ) {
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
                return '<p>' . esc_html__( 'No paint ranges found for shade helper.', 'paint-tracker-and-mixing-helper' ) . '</p>';
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
                return '<p>' . esc_html__( 'No paints found for shade helper.', 'paint-tracker-and-mixing-helper' ) . '</p>';
            }

            $paints = [];

            while ( $q->have_posts() ) {
                $q->the_post();
                $post_id = get_the_ID();

                $name                 = get_the_title();
                $number               = get_post_meta( $post_id, self::META_NUMBER, true );
                $hex                  = get_post_meta( $post_id, self::META_HEX, true );
                $base_type            = get_post_meta( $post_id, self::META_BASE_TYPE, true );
                $exclude_from_shading = get_post_meta( $post_id, self::META_EXCLUDE_SHADE, true ) ? 1 : 0;

                $term_ids = wp_get_object_terms(
                    $post_id,
                    self::TAX,
                    [
                        'fields' => 'ids',
                    ]
                );

                $range_id = ! empty( $term_ids ) ? (int) $term_ids[0] : 0;

                // Build list of all related range IDs (this paint's range + any parents)
                $all_range_ids = [];
                if ( $range_id ) {
                    $ancestors     = get_ancestors( $range_id, self::TAX );
                    $all_range_ids = array_unique(
                        array_map(
                            'intval',
                            array_merge( [ $range_id ], $ancestors )
                        )
                    );
                }

                $paints[] = [
                    'id'              => $post_id,
                    'name'            => $name,
                    'number'          => $number,
                    'hex'             => $hex,
                    'base_type'       => $base_type,
                    'range_id'        => $range_id,
                    'all_range_ids'   => $all_range_ids,
                    'exclude_shading' => $exclude_from_shading,
                ];
            }

            wp_reset_postdata();

            if ( empty( $paints ) ) {
                return '<p>' . esc_html__( 'No paints found for shade helper.', 'paint-tracker-and-mixing-helper' ) . '</p>';
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
                $id              = isset( $paint['id'] ) ? (int) $paint['id'] : 0;
                $name            = isset( $paint['name'] ) ? $paint['name'] : '';
                $number          = isset( $paint['number'] ) ? $paint['number'] : '';
                $hex             = isset( $paint['hex'] ) ? $paint['hex'] : '';
                $range_id        = isset( $paint['range_id'] ) ? (int) $paint['range_id'] : 0;
                $base_type       = isset( $paint['base_type'] ) ? $paint['base_type'] : '';
                $exclude_shading = ! empty( $paint['exclude_shading'] ) ? 1 : 0;

                if ( '' === $name || '' === $hex ) {
                    continue;
                }

                // All range IDs this paint belongs to (its own term + any parents)
                $all_range_ids = [];
                if ( ! empty( $paint['all_range_ids'] ) && is_array( $paint['all_range_ids'] ) ) {
                    $all_range_ids = array_map( 'intval', $paint['all_range_ids'] );
                } elseif ( $range_id ) {
                    // Fallback for safety
                    $all_range_ids = [ $range_id ];
                }

                if ( empty( $all_range_ids ) ) {
                    continue;
                }

                $range_ids_attr = implode( ',', $all_range_ids );

                $label = $name;
                if ( '' !== $number ) {
                    $label .= ' (' . $number . ')';
                }

                // Choose text colour for contrast (simple luminance check)
                $text_color = '#000000';
                $hex_clean  = ltrim( $hex, '#' );
                if ( 6 === strlen( $hex_clean ) ) {
                    $r         = hexdec( substr( $hex_clean, 0, 2 ) );
                    $g         = hexdec( substr( $hex_clean, 2, 2 ) );
                    $b         = hexdec( substr( $hex_clean, 4, 2 ) );
                    $luminance = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;
                    $text_color = ( $luminance < 0.5 ) ? '#f9fafb' : '#111827';
                }

                $style = sprintf(
                    'background-color:%1$s;color:%2$s;',
                    $hex,
                    $text_color
                );
                ?>
                <div class="pct-mix-option"
                     data-hex="<?php echo esc_attr( $hex ); ?>"
                     data-label="<?php echo esc_attr( $label ); ?>"
                     data-range="<?php echo esc_attr( $range_id ); ?>"
                     data-range-ids="<?php echo esc_attr( $range_ids_attr ); ?>"
                     data-id="<?php echo esc_attr( $id ); ?>"
                     data-base-type="<?php echo esc_attr( $base_type ); ?>"
                     data-exclude-shading="<?php echo esc_attr( $exclude_shading ); ?>"
                     style="<?php echo esc_attr( $style ); ?>">
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
                __( 'Import Paints from CSV', 'paint-tracker-and-mixing-helper' ),
                __( 'Import from CSV', 'paint-tracker-and-mixing-helper' ),
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
                __( 'Export Paints to CSV', 'paint-tracker-and-mixing-helper' ),
                __( 'Export to CSV', 'paint-tracker-and-mixing-helper' ),
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
                __( 'Info & Settings', 'paint-tracker-and-mixing-helper' ),
                __( 'Info & Settings', 'paint-tracker-and-mixing-helper' ),
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
                wp_die( esc_html__( 'You do not have permission to access this page.', 'paint-tracker-and-mixing-helper' ) );
            }

            $message = '';

            // Load existing values first
            $info_url   = get_option( 'pct_mixing_page_url', '' );
            $mode       = get_option( 'pct_table_display_mode', 'dots' );
            $shade_mode = get_option( 'pct_shade_hue_mode', 'strict' ); // 'strict' or 'relaxed'

            // Save if the Info & Settings nonce is present and valid
            if ( isset( $_POST['pct_info_settings_nonce'] ) ) {
                $info_nonce = sanitize_text_field( wp_unslash( $_POST['pct_info_settings_nonce'] ) );
                if ( wp_verify_nonce( $info_nonce, 'pct_info_settings' ) ) {
                    // Shading page URL
                    if ( isset( $_POST['pct_mixing_page_url'] ) ) {
                        $info_url = esc_url_raw( wp_unslash( $_POST['pct_mixing_page_url'] ) );
                        update_option( 'pct_mixing_page_url', $info_url );
                    }

                    // Paint table display mode
                    if ( isset( $_POST['pct_table_display_mode'] ) ) {
                        $mode_raw = sanitize_text_field( wp_unslash( $_POST['pct_table_display_mode'] ) );
                        $mode     = in_array( $mode_raw, [ 'dots', 'rows' ], true ) ? $mode_raw : 'dots';
                        update_option( 'pct_table_display_mode', $mode );
                    }

                    // Shade helper hue behaviour (strict vs relaxed)
                    if ( isset( $_POST['pct_shade_hue_mode'] ) ) {
                        $shade_raw  = sanitize_text_field( wp_unslash( $_POST['pct_shade_hue_mode'] ) );
                        $shade_mode = in_array( $shade_raw, [ 'strict', 'relaxed' ], true ) ? $shade_raw : 'strict';
                        update_option( 'pct_shade_hue_mode', $shade_mode );
                    }

                    $message = __( 'Settings saved.', 'paint-tracker-and-mixing-helper' );
                }
            }

            $pct_admin_view         = 'info_settings';
            $pct_info_message       = $message;
            $pct_info_url           = $info_url;
            $pct_table_display_mode = $mode;
            $pct_shade_hue_mode     = $shade_mode;

            include plugin_dir_path( __FILE__ ) . 'admin/admin-page.php';
        }

        /**
         * Render the CSV import page + handle form submission.
         * Delegates HTML to admin/admin-page.php.
         */
        public function render_import_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'You do not have permission to access this page.', 'paint-tracker-and-mixing-helper' ) );
            }

            $message = '';
            $errors  = [];

            if ( isset( $_POST['pct_import_submit'] ) ) {
                check_admin_referer( 'pct_import_paints', 'pct_import_nonce' );

                $range_id = isset( $_POST['pct_range'] ) ? intval( $_POST['pct_range'] ) : 0;
                if ( ! $range_id ) {
                    $errors[] = __( 'Please choose a paint range.', 'paint-tracker-and-mixing-helper' );
                }

                if ( empty( $_FILES['pct_csv']['tmp_name'] ) ) {
                    $errors[] = __( 'Please upload a CSV file.', 'paint-tracker-and-mixing-helper' );
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
                                __( 'Imported %d paints.', 'paint-tracker-and-mixing-helper' ),
                                intval( $result )
                            );
                        }

                        // Always delete the uploaded CSV file after processing.
                        if ( ! empty( $file_path ) && file_exists( $file_path ) ) {
                            wp_delete_file( $file_path );
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
         * Render the CSV export page (just the UI).
         * Actual CSV download is handled via admin-post.php.
         */
        public function render_export_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'You do not have permission to access this page.', 'paint-tracker-and-mixing-helper' ) );
            }

            $pct_admin_view = 'export_page';

            include plugin_dir_path( __FILE__ ) . 'admin/admin-page.php';
        }

        /**
         * Handle CSV export via admin-post.php.
         */
        public function handle_export_paints() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'You do not have permission to export paints.', 'paint-tracker-and-mixing-helper' ) );
            }

            if ( ! isset( $_POST['pct_export_nonce'] ) ) {
                wp_die( esc_html__( 'Security check failed.', 'paint-tracker-and-mixing-helper' ) );
            }

            $export_nonce = sanitize_text_field( wp_unslash( $_POST['pct_export_nonce'] ) );
            if ( ! wp_verify_nonce( $export_nonce, 'pct_export_paints' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'paint-tracker-and-mixing-helper' ) );
            }

            $this->export_csv();
            exit;
        }

        /**
         * Import paints from a CSV file into a specific range.
         *
         * Expected columns: title, identifier, hex, base_type, on_shelf (0/1)
         */
        private function import_csv_file( $file_path, $range_id ) {
            if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
                return new WP_Error( 'pct_csv_missing', __( 'CSV file is missing or not readable.', 'paint-tracker-and-mixing-helper' ) );
            }

            $handle = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
            if ( ! $handle ) {
                return new WP_Error( 'pct_csv_open', __( 'Unable to open CSV file.', 'paint-tracker-and-mixing-helper' ) );
            }

            $row      = 0;
            $imported = 0;

            // Optional: if first row is header, detect & skip
            $first_row = fgetcsv( $handle );
            if ( ! $first_row ) {
                fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
                return new WP_Error( 'pct_csv_empty', __( 'CSV file is empty.', 'paint-tracker-and-mixing-helper' ) );
            }

            // If header row contains "title" etc., skip; otherwise treat as data.
            $header = array_map( 'strtolower', $first_row );
            if (
                in_array( 'title', $header, true )
                || in_array( 'identifier', $header, true )
            ) {
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
                $base_type = isset( $data[3] ) ? sanitize_text_field( $data[3] ) : '';
                $on_shelf  = isset( $data[4] ) ? intval( $data[4] ) : 0;

                // Normalise hex: allow "2f353a" or "#2f353a" in CSV, but always store "#2f353a"
                if ( '' !== $hex ) {
                    $hex = ltrim( $hex, " \t\n\r\0\x0B" ); // trim whitespace
                    if ( '#' !== $hex[0] ) {
                        $hex = '#' . $hex;
                    }
                }

                // Normalise base type
                $base_type          = strtolower( trim( $base_type ) );
                $allowed_base_types = [ 'acrylic', 'enamel', 'oil', 'lacquer' ];
                if ( ! in_array( $base_type, $allowed_base_types, true ) ) {
                    $base_type = 'acrylic'; // default if invalid/missing
                }

                if ( '' === $title ) {
                    continue;
                }

                // Check for an existing paint with the same title, code/type, base type and hex.
                // If found, skip this row to avoid duplicates.
                $existing_posts = get_posts(
                    [
                        'post_type'      => self::CPT,
                        'post_status'    => [ 'publish', 'draft', 'pending' ],
                        'posts_per_page' => 10,
                        'fields'         => 'ids',
                        'meta_query'     => [
                            'relation' => 'AND',
                            [
                                'key'   => self::META_NUMBER,
                                'value' => $number,
                            ],
                            [
                                'key'   => self::META_HEX,
                                'value' => $hex,
                            ],
                            [
                                'key'   => self::META_BASE_TYPE,
                                'value' => $base_type,
                            ],
                        ],
                    ]
                );

                $duplicate_found = false;

                if ( ! empty( $existing_posts ) ) {
                    foreach ( $existing_posts as $existing_id ) {
                        $existing_title = get_the_title( $existing_id );
                        if ( $existing_title === $title ) {
                            $duplicate_found = true;
                            break;
                        }
                    }
                }

                if ( $duplicate_found ) {
                    // Already have this exact paint; skip importing this row.
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
                update_post_meta( $post_id, self::META_BASE_TYPE, $base_type );
                update_post_meta( $post_id, self::META_ON_SHELF, $on_shelf );

                // Assign to range
                wp_set_post_terms( $post_id, [ $range_id ], self::TAX );

                $imported++;
            }

            fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

            return $imported;
        }

        /**
         * Export paints to CSV and stream to browser.
         */
        private function export_csv() {
            // Make sure no stray output corrupts the CSV
            if ( ob_get_length() ) {
                ob_end_clean();
            }

            $filename = 'paint-export-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';

            // Send headers
            nocache_headers();
            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

            $output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

            // Header row
            fputcsv( $output, [ 'title', 'identifier', 'hex', 'base_type', 'on_shelf', 'ranges' ] );

            // Base query
            $args = [
                'post_type'      => self::CPT,
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            ];

            // OPTIONAL FILTERS (only used if you add them to the form):
            // Limit by range
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $export_range = isset( $_POST['pct_export_range'] ) ? absint( wp_unslash( $_POST['pct_export_range'] ) ) : 0;
            if ( $export_range ) {
                $args['tax_query'] = [
                    [
                        'taxonomy' => self::TAX,
                        'field'    => 'term_id',
                        'terms'    => $export_range,
                    ],
                ];
            }

            // Only paints marked as "on shelf"
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $export_only_shelf = ! empty( $_POST['pct_export_only_shelf'] );
            if ( $export_only_shelf ) {
                $args['meta_query'] = [
                    [
                        'key'   => self::META_ON_SHELF,
                        'value' => '1',
                    ],
                ];
            }

            $query = new WP_Query( $args );

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $post_id = get_the_ID();

                    $title     = get_the_title();
                    $number    = get_post_meta( $post_id, self::META_NUMBER, true );
                    $hex       = get_post_meta( $post_id, self::META_HEX, true );
                    $base_type = get_post_meta( $post_id, self::META_BASE_TYPE, true );
                    $on_shelf  = get_post_meta( $post_id, self::META_ON_SHELF, true );

                    $ranges      = wp_get_post_terms( $post_id, self::TAX );
                    $range_names = wp_list_pluck( $ranges, 'name' );
                    $range_str   = implode( '|', $range_names );

                    fputcsv(
                        $output,
                        [
                            $title,
                            $number,
                            $hex,
                            $base_type,
                            $on_shelf,
                            $range_str,
                        ]
                    );
                }

                wp_reset_postdata();
            }

            fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
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
            $new_columns['title'] = __( 'Title', 'paint-tracker-and-mixing-helper' );

            // Then our custom columns
            $new_columns['pct_number']    = __( 'Identifier', 'paint-tracker-and-mixing-helper' );
            $new_columns['pct_ranges']    = __( 'Range(s)', 'paint-tracker-and-mixing-helper' );
            $new_columns['pct_hex']       = __( 'Hex', 'paint-tracker-and-mixing-helper' );
            $new_columns['pct_base_type'] = __( 'Base type', 'paint-tracker-and-mixing-helper' );
            $new_columns['pct_on_shelf']  = __( 'On Shelf?', 'paint-tracker-and-mixing-helper' );

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
            } elseif ( 'pct_ranges' === $column ) {
                $terms = get_the_terms( $post_id, self::TAX );
                if ( is_wp_error( $terms ) || empty( $terms ) ) {
                    echo '—';
                } else {
                    $names = wp_list_pluck( $terms, 'name' );
                    echo esc_html( implode( ', ', $names ) );
                }
            } elseif ( 'pct_hex' === $column ) {
                $hex = get_post_meta( $post_id, self::META_HEX, true );
                echo esc_html( $hex );
            } elseif ( 'pct_base_type' === $column ) {
                $base_type = get_post_meta( $post_id, self::META_BASE_TYPE, true );
                if ( $base_type ) {
                    // Stored as "acrylic", "enamel", "oil" – make it look nice
                    $label = ucfirst( strtolower( $base_type ) );
                    echo esc_html( $label );
                } else {
                    echo '—';
                }
            } elseif ( 'pct_on_shelf' === $column ) {
                $on_shelf      = get_post_meta( $post_id, self::META_ON_SHELF, true );
                $exclude_shade = get_post_meta( $post_id, self::META_EXCLUDE_SHADE, true );
                ?>
                <span class="pct-on-shelf-value"
                      data-on-shelf="<?php echo esc_attr( $on_shelf ); ?>"
                      data-exclude-shade="<?php echo esc_attr( $exclude_shade ); ?>">
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
