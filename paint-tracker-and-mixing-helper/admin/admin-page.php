<?php
/**
 * Admin template for Paint Tracker and Mixing Helper plugin.
 *
 * Uses $pct_admin_view to decide what to render:
 * - 'meta_box'      : paint details meta box
 * - 'import_page'   : CSV import page
 * - 'export_page'   : CSV export page
 * - 'info_settings' : info & settings page
 */

if ( ! isset( $pct_admin_view ) ) {
    return;
}

if ( 'meta_box' === $pct_admin_view ) : ?>

    <?php wp_nonce_field( 'pct_save_paint_meta', 'pct_paint_meta_nonce' ); ?>

    <p>
        <label for="pct_number">
            <strong><?php esc_html_e( 'Identifier', 'paint-tracker-and-mixing-helper' ); ?></strong>
            (e.g. 70.800, A.MIG-023, Base)
        </label><br>
        <input
            type="text"
            id="pct_number"
            name="pct_number"
            value="<?php echo isset( $pct_number ) ? esc_attr( $pct_number ) : ''; ?>"
            class="regular-text"
        >
    </p>
    
    <p>
        <label for="pct_type">
            <strong><?php esc_html_e( 'Type', 'paint-tracker-and-mixing-helper' ); ?></strong>
        </label><br>
        <input
            type="text"
            id="pct_type"
            name="pct_type"
            value="<?php echo isset( $pct_type ) ? esc_attr( $pct_type ) : ''; ?>"
            class="regular-text"
        >
    </p>

    <p>
        <label for="pct_base_type">
            <strong><?php esc_html_e( 'Base type', 'paint-tracker-and-mixing-helper' ); ?></strong>
            (<?php esc_html_e( 'required', 'paint-tracker-and-mixing-helper' ); ?>)
        </label><br>
        <select id="pct_base_type" name="pct_base_type" required>
            <option value="">
                <?php esc_html_e( 'Select base type…', 'paint-tracker-and-mixing-helper' ); ?>
            </option>
            <option
                value="acrylic"
                <?php selected( isset( $pct_base_type ) ? $pct_base_type : '', 'acrylic' ); ?>
            >
                <?php esc_html_e( 'Acrylic', 'paint-tracker-and-mixing-helper' ); ?>
            </option>
            <option
                value="enamel"
                <?php selected( isset( $pct_base_type ) ? $pct_base_type : '', 'enamel' ); ?>
            >
                <?php esc_html_e( 'Enamel', 'paint-tracker-and-mixing-helper' ); ?>
            </option>
            <option
                value="oil"
                <?php selected( isset( $pct_base_type ) ? $pct_base_type : '', 'oil' ); ?>
            >
                <?php esc_html_e( 'Oil', 'paint-tracker-and-mixing-helper' ); ?>
            </option>
            <option
                value="lacquer"
                <?php selected( isset( $pct_base_type ) ? $pct_base_type : '', 'lacquer' ); ?>
            >
                <?php esc_html_e( 'Lacquer', 'paint-tracker-and-mixing-helper' ); ?>
            </option>
        </select>
    </p>

    <p>
        <label for="pct_hex">
            <strong><?php esc_html_e( 'Hex colour', 'paint-tracker-and-mixing-helper' ); ?></strong>
            (e.g. #2f353a)
        </label><br>
        <input
            type="text"
            id="pct_hex"
            name="pct_hex"
            value="<?php echo isset( $pct_hex ) ? esc_attr( $pct_hex ) : ''; ?>"
            class="regular-text"
        >
    </p>
    
    <p>
        <label>
            <input
                type="checkbox"
                name="pct_gradient_metallic"
                value="1"
                <?php checked( isset( $pct_gradient ) ? (int) $pct_gradient : 0, 1 ); ?>
            />
            <?php esc_html_e( 'Metallic colour (use a metallic-style swatch)', 'paint-tracker-and-mixing-helper' ); ?>
        </label>
        <br>
        <label>
            <input
                type="checkbox"
                name="pct_gradient_shade"
                value="1"
                <?php checked( isset( $pct_gradient ) ? (int) $pct_gradient : 0, 2 ); ?>
            />
            <?php esc_html_e( 'Shade colour (use a darker shade-style swatch)', 'paint-tracker-and-mixing-helper' ); ?>
        </label>
        <br>
        <span class="description">
            <?php esc_html_e( 'Use at most one of these options – leave both unchecked for a flat colour swatch.', 'paint-tracker-and-mixing-helper' ); ?>
        </span>
    </p>

    <!-- On shelf + exclude from shading helper -->
    <p>
        <label>
            <input
                type="checkbox"
                name="pct_on_shelf"
                value="1"
                <?php checked( (int) $pct_on_shelf, 1 ); ?>
            />
            <?php esc_html_e( 'On the shelf', 'paint-tracker-and-mixing-helper' ); ?>
        </label>
    </p>

    <p>
        <label>
            <input
                type="checkbox"
                name="pct_exclude_shade"
                value="1"
                <?php checked( (int) $pct_exclude_shade, 1 ); ?>
            />
            <?php esc_html_e( 'Exclude from shading helper', 'paint-tracker-and-mixing-helper' ); ?>
        </label>
    </p>

    <p>
        <strong><?php esc_html_e( 'Linked posts / URLs', 'paint-tracker-and-mixing-helper' ); ?></strong>
    </p>

    <div id="pct-links-wrapper">
        <?php
        $links = ( isset( $pct_links ) && is_array( $pct_links ) ) ? $pct_links : [];
        if ( empty( $links ) ) {
            $links = [
                [
                    'title' => '',
                    'url'   => '',
                ],
            ];
        }

        foreach ( $links as $link ) :
            $ltitle = isset( $link['title'] ) ? $link['title'] : '';
            $lurl   = isset( $link['url'] ) ? $link['url'] : '';
            ?>
            <div class="pct-link-row">
                <p class="pct-link-row-field">
                    <label>
                        <?php esc_html_e( 'Link title', 'paint-tracker-and-mixing-helper' ); ?><br>
                        <input
                            type="text"
                            name="pct_links_title[]"
                            value="<?php echo esc_attr( $ltitle ); ?>"
                            class="regular-text"
                            placeholder="<?php esc_attr_e( 'e.g. Tutorial, Review, Example Build', 'paint-tracker-and-mixing-helper' ); ?>"
                        >
                    </label>
                </p>
                <p class="pct-link-row-field">
                    <label>
                        <?php esc_html_e( 'Link URL', 'paint-tracker-and-mixing-helper' ); ?><br>
                        <input
                            type="url"
                            name="pct_links_url[]"
                            value="<?php echo esc_attr( $lurl ); ?>"
                            class="regular-text"
                            placeholder="https://example.com/my-article"
                        >
                    </label>
                </p>
                <p class="pct-link-row-field">
                    <button type="button" class="button pct-remove-link">
                        <?php esc_html_e( 'Remove link', 'paint-tracker-and-mixing-helper' ); ?>
                    </button>
                </p>
            </div>
        <?php endforeach; ?>
    </div>

    <p>
        <button type="button" class="button button-secondary" id="pct-add-link">
            <?php esc_html_e( 'Add another link', 'paint-tracker-and-mixing-helper' ); ?>
        </button>
    </p>

<?php
elseif ( 'import_page' === $pct_admin_view ) : ?>

    <div class="wrap">
        <h1><?php esc_html_e( 'Import Paints from CSV', 'paint-tracker-and-mixing-helper' ); ?></h1>

        <?php
        $errors  = isset( $pct_import_errors ) && is_array( $pct_import_errors ) ? $pct_import_errors : [];
        $message = isset( $pct_import_message ) ? $pct_import_message : '';

        if ( ! empty( $errors ) ) :
            ?>
            <div class="notice notice-error">
                <p><?php echo implode( '<br>', array_map( 'esc_html', $errors ) ); ?></p>
            </div>
        <?php endif; ?>

        <?php if ( $message ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html( $message ); ?></p>
            </div>
        <?php endif; ?>

        <p>
            <?php esc_html_e( 'Upload a CSV file to automatically create paints in a specific range. Each row in the file represents one paint.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>
        <p>
            <?php esc_html_e( 'Expected CSV columns (per row):', 'paint-tracker-and-mixing-helper' ); ?>
        </p>
        <ul>
            <li>
                <?php esc_html_e(
                    'name – paint name/title.',
                    'paint-tracker-and-mixing-helper'
                ); ?>
            </li>
            <li>
                <?php esc_html_e(
                    'identifier/number – e.g. 70.861.',
                    'paint-tracker-and-mixing-helper'
                ); ?>
            </li>
            <li>
                <?php esc_html_e(
                    'type – e.g. Base, Layer, Wash.',
                    'paint-tracker-and-mixing-helper'
                ); ?>
            </li>
            <li>
                <?php esc_html_e(
                    'hex colour – e.g. #2f353a.',
                    'paint-tracker-and-mixing-helper'
                ); ?>
            </li>
            <li>
                <?php esc_html_e(
                    'base type – acrylic, enamel, oil, -OR- lacquer.',
                    'paint-tracker-and-mixing-helper'
                ); ?>
            </li>
            <li>
                <?php esc_html_e(
                    'on shelf – 0 or 1 (optional; 1 = on shelf, 0 = not on shelf).',
                    'paint-tracker-and-mixing-helper'
                ); ?>
            </li>
            <li>
                <?php esc_html_e(
                    'gradient – 0, 1 or 2 (optional; 0 = no special swatch, 1 = metallic colour, 2 = shade colour).',
                    'paint-tracker-and-mixing-helper'
                ); ?>
            </li>
            <li>
                <?php esc_html_e(
                    'ranges – optional; used when “Pull range from CSV” is enabled. Multiple ranges are separated by a pipe (|), e.g. Vallejo|Vallejo Model Color.',
                    'paint-tracker-and-mixing-helper'
                ); ?>
            </li>
        </ul>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'pct_import_paints', 'pct_import_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="pct_range">
                                <?php esc_html_e( 'Paint range', 'paint-tracker-and-mixing-helper' ); ?>
                            </label>
                        </th>
                        <td>
                            <?php
                            // Selected range from POST (if any).
                            $selected_range = isset( $_POST['pct_range'] )
                                ? (int) $_POST['pct_range']
                                : 0;

                            // Build parent → children map from $pct_import_ranges,
                            // preserving the order coming from get_terms()
                            // (which is already ordered by term_order ASC).
                            $pct_ranges_by_parent = [];

                            if ( ! empty( $pct_import_ranges ) && ! is_wp_error( $pct_import_ranges ) ) {
                                foreach ( $pct_import_ranges as $range ) {
                                    $parent_id = (int) $range->parent;

                                    if ( ! isset( $pct_ranges_by_parent[ $parent_id ] ) ) {
                                        $pct_ranges_by_parent[ $parent_id ] = [];
                                    }

                                    $pct_ranges_by_parent[ $parent_id ][] = $range;
                                }
                            }

                            if ( ! function_exists( 'pct_render_import_range_options_hierarchical' ) ) {
                                /**
                                 * Render <option> tags hierarchically, matching the
                                 * order used by the front-end mixer/shader dropdowns.
                                 */
                                function pct_render_import_range_options_hierarchical( $parent_id, $map, $depth, $selected ) {
                                    if ( empty( $map[ $parent_id ] ) ) {
                                        return;
                                    }

                                    foreach ( $map[ $parent_id ] as $term ) {
                                        $indent = str_repeat( '— ', max( 0, (int) $depth ) );

                                        printf(
                                            '<option value="%1$d"%2$s>%3$s%4$s</option>',
                                            (int) $term->term_id,
                                            selected( $selected, $term->term_id, false ),
                                            esc_html( $indent ),
                                            esc_html( $term->name )
                                        );

                                        pct_render_import_range_options_hierarchical(
                                            $term->term_id,
                                            $map,
                                            $depth + 1,
                                            $selected
                                        );
                                    }
                                }
                            }
                            ?>
                            <select name="pct_range" id="pct_range">
                                <option value="">
                                    <?php esc_html_e( 'Select a range', 'paint-tracker-and-mixing-helper' ); ?>
                                </option>
                                <?php
                                // Render all ranges starting from top-level parents (0)
                                if ( ! empty( $pct_ranges_by_parent ) ) {
                                    pct_render_import_range_options_hierarchical(
                                        0,
                                        $pct_ranges_by_parent,
                                        0,
                                        $selected_range
                                    );
                                }
                                ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e(
                                    'Used when “Pull range from CSV” is not enabled.',
                                    'paint-tracker-and-mixing-helper'
                                ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"></th>
                        <td>
                            <label for="pct_pull_range_from_csv">
                                <input
                                    type="checkbox"
                                    name="pct_pull_range_from_csv"
                                    id="pct_pull_range_from_csv"
                                    value="1"
                                    <?php
                                    echo ! empty( $pct_pull_range_from_csv ) ? 'checked="checked"' : '';
                                    ?>
                                />
                                <?php esc_html_e( 'Pull range from CSV', 'paint-tracker-and-mixing-helper' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e(
                                    'When enabled, the “Paint range” dropdown is ignored and ranges are read from the “ranges” column in the CSV.',
                                    'paint-tracker-and-mixing-helper'
                                ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="pct_csv">
                                <?php esc_html_e( 'CSV file', 'paint-tracker-and-mixing-helper' ); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="file"
                                name="pct_csv"
                                id="pct_csv"
                                accept=".csv,text/csv"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button( __( 'Import paints', 'paint-tracker-and-mixing-helper' ), 'primary', 'pct_import_submit' ); ?>
        </form>
    </div>

<?php
elseif ( 'export_page' === $pct_admin_view ) : ?>

    <div class="wrap">
        <h1><?php esc_html_e( 'Export Paints to CSV', 'paint-tracker-and-mixing-helper' ); ?></h1>

        <p>
            <?php
            esc_html_e(
                'Download your paint collection as a CSV file you can open in Excel, Numbers, or Google Sheets.',
                'paint-tracker-and-mixing-helper'
            );
            ?>
        </p>

        <?php
        $ranges = get_terms(
            [
                'taxonomy'   => PCT_Paint_Table_Plugin::TAX,
                'hide_empty' => false,
                'orderby'    => 'term_order',
                'order'      => 'ASC',
            ]
        );
        ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'pct_export_paints', 'pct_export_nonce' ); ?>
            <input type="hidden" name="action" value="pct_export_paints">

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="pct_export_range">
                                <?php esc_html_e( 'Limit to range', 'paint-tracker-and-mixing-helper' ); ?>
                            </label>
                        </th>
                        <td>
                            <select name="pct_export_range" id="pct_export_range">
                                <option value="">
                                    <?php esc_html_e( 'All ranges', 'paint-tracker-and-mixing-helper' ); ?>
                                </option>
                                <?php if ( ! is_wp_error( $ranges ) && ! empty( $ranges ) ) : ?>
                                    <?php foreach ( $ranges as $range ) : ?>
                                        <option value="<?php echo esc_attr( $range->term_id ); ?>">
                                            <?php echo esc_html( $range->name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <p class="description">
                                <?php
                                esc_html_e(
                                    'Optional: only export paints from a single paint range.',
                                    'paint-tracker-and-mixing-helper'
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e( 'Download CSV', 'paint-tracker-and-mixing-helper' ); ?>
                </button>
            </p>
        </form>
    </div>

<?php
elseif ( 'info_settings' === $pct_admin_view ) : ?>

    <div class="wrap">
        <h1><?php esc_html_e( 'Info & Settings', 'paint-tracker-and-mixing-helper' ); ?></h1>

        <?php
        $message  = isset( $pct_info_message ) ? $pct_info_message : '';
        $info_url = isset( $pct_info_url ) ? $pct_info_url : '';
        $mode     = isset( $pct_table_display_mode ) ? $pct_table_display_mode : 'dots';
        $hue_mode = isset( $pct_shade_hue_mode ) ? $pct_shade_hue_mode : 'strict';
        ?>

        <?php if ( $message ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html( $message ); ?></p>
            </div>
        <?php endif; ?>

        <h2><?php esc_html_e( 'Overview', 'paint-tracker-and-mixing-helper' ); ?></h2>
        <p>
            <?php
            esc_html_e(
                'Paint Tracker and Mixing Helper keeps a structured list of your miniature paints and gives you three main tools on the front end: a searchable paint table, a two-paint mixing helper, and a shading helper for picking highlight and shadow colours.',
                'paint-tracker-and-mixing-helper'
            );
            ?>
        </p>

        <h3><?php esc_html_e( 'Quick start', 'paint-tracker-and-mixing-helper' ); ?></h3>
        <p>
            <?php
                printf(
                    wp_kses_post(
                        __( 'You can download free .csv files to import various paints and ranges from <a href="%s" target="_blank" rel="noopener noreferrer">https://www.scalemodelsbycable.com/paint-ranges</a>.', 'paint-tracker-and-mixing-helper' )
                    ),
                        esc_url( 'https://www.scalemodelsbycable.com/paint-ranges/' )
                    );
                    ?>
        </p>
        <p>
            <?php
                esc_html_e(
                    'Or, if you want a more personal approach, you can create your own .csv to import, or:',
                    'paint-tracker-and-mixing-helper'
                );
                ?>
        </p>
        <ol>
            <li>
                <?php
                esc_html_e(
                    'Create one or more paint ranges under “Paint Colours → Paint Ranges” (for example: Citadel, Two Thin Coats, Vallejo Model Color).',
                    'paint-tracker-and-mixing-helper'
                );
                ?>
            </li>

            <li>
                <?php
                esc_html_e(
                    'Add paints under “Paint Colours → Add New” and assign each paint to the appropriate range.',
                    'paint-tracker-and-mixing-helper'
                );
                ?>
            </li>
            <li>
                <?php
                esc_html_e(
                    'For each paint, fill in the Identifier, optional Type (e.g. Base, Layer, Wash), hex colour, base type, whether it is on your shelf, and (optionally) whether it is a metallic or shade colour.',
                    'paint-tracker-and-mixing-helper'
                );
                ?>
            </li>
            <li>
                <?php
                esc_html_e(
                    'Create pages that use the shortcodes below, for example a main paint list page and separate pages for the mixing and shading helpers.',
                    'paint-tracker-and-mixing-helper'
                );
                ?>
            </li>
        </ol>

        <hr>

        <h2><?php esc_html_e( 'How the data is stored', 'paint-tracker-and-mixing-helper' ); ?></h2>
        <p>
            <?php
            esc_html_e(
                'The plugin adds a custom post type called “Paint Colours” and a taxonomy called “Paint Ranges”.',
                'paint-tracker-and-mixing-helper'
            );
            ?>
        </p>
        <ul>
            <li>
                <strong><?php esc_html_e( 'Paint Colours', 'paint-tracker-and-mixing-helper' ); ?></strong>
                – <?php
            esc_html_e(
                'each paint has a title (name), an optional Identifier, an optional Type (e.g. Base, Layer, Wash), a main hex colour, a base type (acrylic, enamel, oil, lacquer, etc.), an “on shelf” flag, an “Exclude from shading helper” flag, optional metallic/shade flags, and optional external links (manufacturer site, reviews, example builds, etc.).',
                'paint-tracker-and-mixing-helper'
            );
                ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Paint Ranges', 'paint-tracker-and-mixing-helper' ); ?></strong>
                – <?php
                esc_html_e(
                    'group paints into ranges such as Vallejo Model Color, Vallejo Game Color, Citadel Base, and so on. Ranges are used for filtering on the front end and for CSV import/export.',
                    'paint-tracker-and-mixing-helper'
                );
                ?>
            </li>
        </ul>

        <hr>

        <h2><?php esc_html_e( 'Shortcodes', 'paint-tracker-and-mixing-helper' ); ?></h2>

        <h3><?php esc_html_e( '[paint_table]', 'paint-tracker-and-mixing-helper' ); ?></h3>
        <p>
            <?php
            esc_html_e(
                'Displays a table of paints, optionally limited to a single paint range.',
                'paint-tracker-and-mixing-helper'
            );
            ?>
        </p>
        <p>
            <strong><?php esc_html_e( 'Attributes:', 'paint-tracker-and-mixing-helper' ); ?></strong>
        </p>
        <ul>
            <li>
                <code>range</code> – <?php esc_html_e( 'taxonomy slug of the paint range (optional).', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <code>limit</code> – <?php esc_html_e( 'number of paints to show (-1 shows all).', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <code>orderby</code> – <?php esc_html_e( 'either "meta_number" (Identifier), "title", or "type".', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <code>shelf</code> – <?php esc_html_e( '"yes" to show only paints marked as on shelf, or "any" to show all paints.', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
        </ul>
        <p>
            <strong><?php esc_html_e( 'Example:', 'paint-tracker-and-mixing-helper' ); ?></strong><br>
            <code>[paint_table range="vallejo-model-color" limit="-1" orderby="meta_number" shelf="any"]</code>
        </p>

        <h4><?php esc_html_e( 'Paint table display', 'paint-tracker-and-mixing-helper' ); ?></h4>
        <p>
            <?php
            esc_html_e(
                'Use this option to control how the paint colour is shown in the table. The setting below applies to all [paint_table] shortcodes.',
                'paint-tracker-and-mixing-helper'
            );
            ?>
        </p>

        <form method="post">
            <?php wp_nonce_field( 'pct_info_settings', 'pct_info_settings_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Paint table display', 'paint-tracker-and-mixing-helper' ); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input
                                    type="radio"
                                    name="pct_table_display_mode"
                                    value="dots"
                                    <?php checked( $mode, 'dots' ); ?>
                                >
                                <?php esc_html_e( 'Show colour dots (swatch column).', 'paint-tracker-and-mixing-helper' ); ?>
                            </label>
                            <br>
                            <label>
                                <input
                                    type="radio"
                                    name="pct_table_display_mode"
                                    value="rows"
                                    <?php checked( $mode, 'rows' ); ?>
                                >
                                <?php esc_html_e( 'Highlight the entire row with the paint colour.', 'paint-tracker-and-mixing-helper' ); ?>
                            </label>
                            <p class="description">
                                <?php
                                esc_html_e(
                                    'Row highlighting still keeps text legible by choosing light or dark text automatically.',
                                    'paint-tracker-and-mixing-helper'
                                );
                                ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </form>

        <hr>

        <h3><?php esc_html_e( '[mixing-helper]', 'paint-tracker-and-mixing-helper' ); ?></h3>
        <p>
            <?php
            esc_html_e(
                'Shows the two-paint mixing tool. Choose two paints, set percentages, and the helper will calculate the mixed colour and hex value.',
                'paint-tracker-and-mixing-helper'
            );
            ?>
        </p>
        <p>
            <?php
            esc_html_e(
                'Each paint dropdown can be filtered by range so you can quickly find the paints you want to mix. Base type is respected: paints with incompatible base types (for example acrylic vs enamel) cannot be mixed and will show a warning instead.',
                'paint-tracker-and-mixing-helper'
            );
            ?>
        </p>

        <hr>

        <h3><?php esc_html_e( '[shade-helper]', 'paint-tracker-and-mixing-helper' ); ?></h3>
        <p>
            <?php
            esc_html_e(
                'Shows the shade helper as a standalone tool. Choose a paint and the plugin will look for suitable darker and lighter colours to use as shadow and highlight.',
                'paint-tracker-and-mixing-helper'
            );
            ?>
        </p>

        <p>
            <?php
            esc_html_e(
                'If a visitor arrives from the paint table by clicking a swatch or row, the shade helper can start with that paint already selected.',
                'paint-tracker-and-mixing-helper'
            );
            ?>
        </p>

        <h4><?php esc_html_e( 'Shading page URL', 'paint-tracker-and-mixing-helper' ); ?></h4>
        <p>
            <?php
            esc_html_e(
                'This setting tells the plugin where your dedicated shading helper page lives. It should be the URL of the page where you placed the [shade-helper] shortcode.',
                'paint-tracker-and-mixing-helper'
            );
            ?>
        </p>
        <p>
            <?php
            esc_html_e(
                'When this URL is set, the colour dots/rows in the paint table become links. Clicking them will take the visitor to your Shade helper page and pre-select that paint.',
                'paint-tracker-and-mixing-helper'
            );
            ?>
        </p>
        <p>
            <?php
            esc_html_e(
                'If you leave this field empty, the dots/rows are shown but are not clickable.',
                'paint-tracker-and-mixing-helper'
            );
            ?>
        </p>

        <form method="post">
            <?php wp_nonce_field( 'pct_info_settings', 'pct_info_settings_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="pct_mixing_page_url">
                            <?php esc_html_e( 'Shading page URL', 'paint-tracker-and-mixing-helper' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="url"
                            name="pct_mixing_page_url"
                            id="pct_mixing_page_url"
                            class="regular-text"
                            value="<?php echo esc_attr( $info_url ); ?>"
                            placeholder="https://example.com/shade-helper"
                        >
                        <p class="description">
                            <?php
                            esc_html_e(
                                'Enter the URL of the page that contains your [shade-helper] shortcode.',
                                'paint-tracker-and-mixing-helper'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
            </table>
        </form>
        <details class="pct-nitty-gritty">
        <summary class="pct-nitty-gritty-summary">
        <?php esc_html_e( 'Nitty-gritty details (exact Shade Helper behaviour)', 'paint-tracker-and-mixing-helper' ); ?>
        </summary>
        
        <div class="pct-nitty-gritty-content">
        
        <h4><?php esc_html_e( 'What the Shade Helper is actually doing', 'paint-tracker-and-mixing-helper' ); ?></h4>
        <p>
        <?php esc_html_e(
        'When you select a paint, the Shade Helper does not generate colours or gradients. Instead, it searches your real paint collection, filters out unsuitable paints, and builds two ordered ladders around the selected paint. These ladders are rebuilt from scratch every time you change the selection.',
        'paint-tracker-and-mixing-helper'
        ); ?>
        </p>
        
        <h4><?php esc_html_e( 'Why there are two ladders', 'paint-tracker-and-mixing-helper' ); ?></h4>
        <ul>
        <li><?php esc_html_e( 'Dark ladder: paints darker than the base paint, intended for shadows and recesses.', 'paint-tracker-and-mixing-helper' ); ?></li>
        <li><?php esc_html_e( 'Light ladder: paints lighter than the base paint, intended for highlights.', 'paint-tracker-and-mixing-helper' ); ?></li>
        </ul>
        <p>
        <?php esc_html_e(
        'A paint can only ever appear in one ladder. The selected base paint is always fixed between them and never moves.',
        'paint-tracker-and-mixing-helper'
        ); ?>
        </p>
        
        <h4><?php esc_html_e( 'Hard rules (always enforced)', 'paint-tracker-and-mixing-helper' ); ?></h4>
        <ul>
        <li><?php esc_html_e( 'The selected paint itself is never included.', 'paint-tracker-and-mixing-helper' ); ?></li>
        <li><?php esc_html_e( 'Paints marked “exclude from shading” are never used.', 'paint-tracker-and-mixing-helper' ); ?></li>
        <li><?php esc_html_e( 'Ladders never mix flat, metallic, and shade paints — the ladder always matches the finish type of the selected paint.', 'paint-tracker-and-mixing-helper' ); ?></li>
        <li><?php esc_html_e( 'If a base type is set (e.g. acrylic), only paints with the same base type are used.', 'paint-tracker-and-mixing-helper' ); ?></li>
        </ul>
        
        <h4><?php esc_html_e( 'Strict vs Relaxed ladders', 'paint-tracker-and-mixing-helper' ); ?></h4>
        <p>
        <?php esc_html_e(
        'Strict and Relaxed modes control how broadly the helper searches for candidate paints. They do not change how ladders are ordered.',
        'paint-tracker-and-mixing-helper'
        ); ?>
        </p>
        
        <strong><?php esc_html_e( 'Strict mode', 'paint-tracker-and-mixing-helper' ); ?></strong>
        <ul>
        <li><?php esc_html_e( 'Paints must share at least one range with the selected paint.', 'paint-tracker-and-mixing-helper' ); ?></li>
        <li><?php esc_html_e( 'Cross-range matches are excluded.', 'paint-tracker-and-mixing-helper' ); ?></li>
        </ul>
        
        <strong><?php esc_html_e( 'Relaxed mode', 'paint-tracker-and-mixing-helper' ); ?></strong>
        <ul>
        <li><?php esc_html_e( 'Paint range matching is not required.', 'paint-tracker-and-mixing-helper' ); ?></li>
        <li><?php esc_html_e( 'Paints from different ranges may appear.', 'paint-tracker-and-mixing-helper' ); ?></li>
        <li><?php esc_html_e( 'Colour similarity still matters, but nothing is excluded purely due to range.', 'paint-tracker-and-mixing-helper' ); ?></li>
        </ul>
        
        <h4><?php esc_html_e( 'How paints are split into ladders', 'paint-tracker-and-mixing-helper' ); ?></h4>
        <p>
        <?php esc_html_e(
        'After filtering, paints are compared to the selected paint by lightness. Darker paints go into the dark ladder, lighter paints go into the light ladder. Paints with equal lightness are ignored.',
        'paint-tracker-and-mixing-helper'
        ); ?>
        </p>
        
        <h4><?php esc_html_e( 'How paints are ordered inside each ladder', 'paint-tracker-and-mixing-helper' ); ?></h4>
        <ul>
        <li><?php esc_html_e( 'Primary factor: difference in lightness from the selected paint.', 'paint-tracker-and-mixing-helper' ); ?></li>
        <li><?php esc_html_e( 'Secondary factor: hue distance, used only to break ties.', 'paint-tracker-and-mixing-helper' ); ?></li>
        </ul>
        <p>
        <?php esc_html_e(
        'Dark ladders start with the darkest paints and move toward the base colour. Light ladders start with the closest highlight and move toward the lightest paint.',
        'paint-tracker-and-mixing-helper'
        ); ?>
        </p>
        
        <h4><?php esc_html_e( 'Limits and non-features', 'paint-tracker-and-mixing-helper' ); ?></h4>
        <ul>
        <li><?php esc_html_e( 'Each ladder has a maximum size; excess paints are discarded.', 'paint-tracker-and-mixing-helper' ); ?></li>
        <li><?php esc_html_e( 'No colours are generated, blended, or interpolated.', 'paint-tracker-and-mixing-helper' ); ?></li>
        <li><?php esc_html_e( 'Paint numbers, names, and brands are never used for ordering.', 'paint-tracker-and-mixing-helper' ); ?></li>
        <li><?php esc_html_e( 'Every ladder is rebuilt fresh when you change the selection.', 'paint-tracker-and-mixing-helper' ); ?></li>
        </ul>
        
        </div>
        </details>
        <hr>

        <h2><?php esc_html_e( 'Importing paints from CSV', 'paint-tracker-and-mixing-helper' ); ?></h2>
        <p>
            <?php
            esc_html_e(
                'Under “Paint Colours → Import from CSV” you can bulk-create paints from a CSV file instead of adding them one by one.',
                'paint-tracker-and-mixing-helper'
            );
            ?>
        </p>
        <ul>
            <li>
                <?php esc_html_e( 'Choose the paint range that the new paints should be assigned to.', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'Upload a CSV file with one paint per row.', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
        </ul>
        <p>
            <strong><?php esc_html_e( 'Expected columns (per row):', 'paint-tracker-and-mixing-helper' ); ?></strong>
        </p>
        <ul>
            <li>
                <?php esc_html_e( 'title – paint name', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'number – paint identifier (optional)', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'type – optional free-text type, e.g. Base, Layer, Wash', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'hex – hex colour, e.g. #2f353a or 2f353a', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'base_type – base type for the paint, e.g. acrylic, enamel, oil, lacquer (used to warn about incompatible mixes).', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'on_shelf – 0 or 1 to indicate whether the paint is on your shelf.', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'gradient – optional; 0 = none, 1 = metallic colour, 2 = shade colour. Legacy CSVs with 0/1 are still accepted.', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'ranges – only used if “Pull range from CSV” is enabled; list of range names separated by "|", if more than one.', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
        </ul>
        <p>
            <?php
            esc_html_e(
                'An optional header row with column names is allowed and will be detected automatically. The plugin expects the columns in the order shown above.',
                'paint-tracker-and-mixing-helper'
            );
            ?>
        </p>

        <hr>

        <h2><?php esc_html_e( 'Exporting paints to CSV', 'paint-tracker-and-mixing-helper' ); ?></h2>
        <p>
            <?php
            esc_html_e(
                'Under “Paint Colours → Export to CSV” you can download your paints for backup, analysis, or editing in a spreadsheet.',
                'paint-tracker-and-mixing-helper'
            );
            ?>
        </p>
        <ul>
            <li>
                <?php esc_html_e( 'Filter by paint range (or export all ranges).', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
        </ul>
        <p>
            <strong><?php esc_html_e( 'Exported columns:', 'paint-tracker-and-mixing-helper' ); ?></strong>
        </p>
        <ul>
            <li>
                <?php esc_html_e( 'title – paint name', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'identifier – paint identifier (number/code)', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'type – free-text type, e.g. Base, Layer, Wash (optional)', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'hex – hex colour', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'base_type – base type for the paint, e.g. acrylic, enamel, oil, lacquer.', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'on_shelf – 0 or 1', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'ranges – list of range names separated by "|", if more than one.', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'gradient – 0 = none, 1 = metallic colour, 2 = shade colour.', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
        </ul>
    </div>

<?php
endif;
