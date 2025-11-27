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
            <strong><?php esc_html_e( 'Paint code / type', 'paint-tracker-and-mixing-helper' ); ?></strong>
            (e.g. 70.800, A.MIG-023, Base)
        </label><br>
        <input type="text" id="pct_number" name="pct_number"
            value="<?php echo isset( $pct_number ) ? esc_attr( $pct_number ) : ''; ?>"
            class="regular-text">
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
            <option value="acrylic" <?php selected( isset( $pct_base_type ) ? $pct_base_type : '', 'acrylic' ); ?>>
                <?php esc_html_e( 'Acrylic', 'paint-tracker-and-mixing-helper' ); ?>
            </option>
            <option value="enamel" <?php selected( isset( $pct_base_type ) ? $pct_base_type : '', 'enamel' ); ?>>
                <?php esc_html_e( 'Enamel', 'paint-tracker-and-mixing-helper' ); ?>
            </option>
            <option value="oil" <?php selected( isset( $pct_base_type ) ? $pct_base_type : '', 'oil' ); ?>>
                <?php esc_html_e( 'Oil', 'paint-tracker-and-mixing-helper' ); ?>
            </option>
        </select>
    </p>

    <p>
        <label for="pct_hex">
            <strong><?php esc_html_e( 'Hex colour', 'paint-tracker-and-mixing-helper' ); ?></strong>
            (e.g. #2f353a)
        </label><br>
        <input type="text" id="pct_hex" name="pct_hex"
            value="<?php echo isset( $pct_hex ) ? esc_attr( $pct_hex ) : ''; ?>"
            class="regular-text">
    </p>

    <p>
        <label for="pct_on_shelf">
            <strong><?php esc_html_e( 'On the shelf', 'paint-tracker-and-mixing-helper' ); ?></strong>
        </label><br>
        <label>
            <input type="checkbox" id="pct_on_shelf" name="pct_on_shelf" value="1"
                <?php checked( isset( $pct_on_shelf ) ? $pct_on_shelf : '', '1' ); ?>>
            <?php esc_html_e( 'Yes, I currently have this paint on my shelf', 'paint-tracker-and-mixing-helper' ); ?>
        </label>
    </p>

    <p><strong><?php esc_html_e( 'Linked posts / URLs', 'paint-tracker-and-mixing-helper' ); ?></strong></p>

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
                        <input type="text"
                            name="pct_links_title[]"
                            value="<?php echo esc_attr( $ltitle ); ?>"
                            class="regular-text"
                            placeholder="<?php esc_attr_e( 'e.g. Tutorial, Review, Example Build', 'paint-tracker-and-mixing-helper' ); ?>">
                    </label>
                </p>
                <p class="pct-link-row-field">
                    <label>
                        <?php esc_html_e( 'Link URL', 'paint-tracker-and-mixing-helper' ); ?><br>
                        <input type="url"
                            name="pct_links_url[]"
                            value="<?php echo esc_attr( $lurl ); ?>"
                            class="regular-text"
                            placeholder="https://example.com/my-article">
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

        if ( ! empty( $errors ) ) : ?>
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
            <?php esc_html_e( 'Upload a CSV file to automatically create paints in a specific range.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>
        <p>
            <?php esc_html_e(
                'Expected format (per row): title, number, hex colour, base type (acrylic/enamel/oil), on shelf (0/1, optional; 1 = yes, 0 = no).',
                'paint-tracker-and-mixing-helper'
            ); ?>
        </p>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'pct_import_paints', 'pct_import_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="pct_range"><?php esc_html_e( 'Paint range', 'paint-tracker-and-mixing-helper' ); ?></label>
                    </th>
                    <td>
                        <?php
                        wp_dropdown_categories(
                            [
                                'taxonomy'         => PCT_Paint_Table_Plugin::TAX,
                                'name'             => 'pct_range',
                                'id'               => 'pct_range',
                                'hide_empty'       => false,
                                'show_option_none' => __( 'Select a range', 'paint-tracker-and-mixing-helper' ),
                            ]
                        );
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="pct_csv"><?php esc_html_e( 'CSV file', 'paint-tracker-and-mixing-helper' ); ?></label>
                    </th>
                    <td>
                        <input type="file" name="pct_csv" id="pct_csv" accept=".csv">
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Import paints', 'paint-tracker-and-mixing-helper' ), 'primary', 'pct_import_submit' ); ?>
        </form>
    </div>

<?php
elseif ( 'export_page' === $pct_admin_view ) : ?>

    <div class="wrap">
        <h1><?php esc_html_e( 'Export Paints to CSV', 'paint-tracker-and-mixing-helper' ); ?></h1>

        <p>
            <?php esc_html_e(
                'Download your paint collection as a CSV file you can open in Excel, Numbers, or Google Sheets.',
                'paint-tracker-and-mixing-helper'
            ); ?>
        </p>

        <?php
        // Optional filters: by range and "on shelf" status.
        $ranges = get_terms(
            [
                'taxonomy'   => PCT_Paint_Table_Plugin::TAX,
                'hide_empty' => false,
                'orderby'    => 'name',
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
                                <?php esc_html_e(
                                    'Optional: only export paints from a single paint range.',
                                    'paint-tracker-and-mixing-helper'
                                ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="pct_export_only_shelf">
                                <?php esc_html_e( 'Only paints on shelf', 'paint-tracker-and-mixing-helper' ); ?>
                            </label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="pct_export_only_shelf"
                                       id="pct_export_only_shelf"
                                       value="1">
                                <?php esc_html_e(
                                    'Only export paints marked as “On the shelf”.',
                                    'paint-tracker-and-mixing-helper'
                                ); ?>
                            </label>
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
        $message   = isset( $pct_info_message ) ? $pct_info_message : '';
        $info_url  = isset( $pct_info_url ) ? $pct_info_url : '';
        $mode      = isset( $pct_table_display_mode ) ? $pct_table_display_mode : 'dots';
        $hue_mode  = isset( $pct_shade_hue_mode ) ? $pct_shade_hue_mode : 'strict';

        if ( $message ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html( $message ); ?></p>
            </div>
        <?php endif; ?>

        <h2><?php esc_html_e( 'What this plugin does', 'paint-tracker-and-mixing-helper' ); ?></h2>
        <p>
            <?php esc_html_e( 'Paint Tracker and Mixing Helper helps you keep a structured list of your miniature paints, track which ones you currently own, link each paint to useful resources, and provide interactive colour tools on the front end.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>

        <hr>

        <h2><?php esc_html_e( 'How the data is stored', 'paint-tracker-and-mixing-helper' ); ?></h2>
        <p>
            <?php esc_html_e( 'The plugin adds a custom post type called “Paint Colours” and a taxonomy called “Paint Ranges”.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>
        <ul>
            <li>
                <strong><?php esc_html_e( 'Paint Colours', 'paint-tracker-and-mixing-helper' ); ?></strong>
                – <?php esc_html_e( 'each paint has a name (title), a paint number, a hex colour, an “On the shelf” flag, and optional links (tutorials, reviews, example builds, etc.).', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Paint Ranges', 'paint-tracker-and-mixing-helper' ); ?></strong>
                – <?php esc_html_e( 'group paints into ranges such as Vallejo Model Color, Vallejo Game Color, and so on.', 'paint-tracker-and-mixing-helper' ); ?>
            </li>
        </ul>

        <hr>

        <h2><?php esc_html_e( 'Shortcodes', 'paint-tracker-and-mixing-helper' ); ?></h2>

        <h3><?php esc_html_e( '[paint_table]', 'paint-tracker-and-mixing-helper' ); ?></h3>
        <p>
            <?php esc_html_e( 'Displays a table of paints, optionally filtered to a single paint range.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>
        <p><strong><?php esc_html_e( 'Attributes:', 'paint-tracker-and-mixing-helper' ); ?></strong></p>
        <ul>
            <li><code>range</code> – <?php esc_html_e( 'taxonomy slug of the paint range (optional).', 'paint-tracker-and-mixing-helper' ); ?></li>
            <li><code>limit</code> – <?php esc_html_e( 'number of paints to show (-1 shows all).', 'paint-tracker-and-mixing-helper' ); ?></li>
            <li><code>orderby</code> – <?php esc_html_e( 'either "meta_number" (paint number) or "title".', 'paint-tracker-and-mixing-helper' ); ?></li>
            <li><code>shelf</code> – <?php esc_html_e( '"yes" to show only paints marked as on shelf, or "any" to show all paints.', 'paint-tracker-and-mixing-helper' ); ?></li>
        </ul>
        <p>
            <strong><?php esc_html_e( 'Example:', 'paint-tracker-and-mixing-helper' ); ?></strong>
            <br>
            <code>[paint_table range="vallejo-model-color" limit="-1" orderby="meta_number" shelf="any"]</code>
        </p>

        <h4><?php esc_html_e( 'Paint table display', 'paint-tracker-and-mixing-helper' ); ?></h4>
        <p>
            <?php esc_html_e( 'Use the option below to choose whether the paint table shows small colour dots (swatches) or highlights the entire row with the paint colour.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>

        <!-- Form 1: Paint table display (auto-saves, no button) -->
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
                                <input type="radio"
                                    name="pct_table_display_mode"
                                    value="dots"
                                    <?php checked( $mode, 'dots' ); ?> />
                                <?php esc_html_e( 'Show colour dots (swatch column)', 'paint-tracker-and-mixing-helper' ); ?>
                            </label>
                            <br>
                            <label>
                                <input type="radio"
                                    name="pct_table_display_mode"
                                    value="rows"
                                    <?php checked( $mode, 'rows' ); ?> />
                                <?php esc_html_e( 'Highlight the entire row with the paint colour', 'paint-tracker-and-mixing-helper' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'Row highlighting applies the paint colour to the whole row and adjusts text to light/dark for readability.', 'paint-tracker-and-mixing-helper' ); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </form>

        <hr>

        <h3><?php esc_html_e( '[mixing-helper]', 'paint-tracker-and-mixing-helper' ); ?></h3>
        <p>
            <?php esc_html_e( 'Shows the two-paint mixing tool. You can pick two paints, set how many parts of each to mix, and see the resulting colour and hex value.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>
        <p>
            <?php esc_html_e( 'Each paint dropdown can be filtered by range so you can quickly find the colours you want to mix.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>

        <hr>

        <h3><?php esc_html_e( '[shade-helper]', 'paint-tracker-and-mixing-helper' ); ?></h3>
        <p>
            <?php esc_html_e( 'Shows the shade helper as a standalone tool. Choose a paint and the plugin will look for the darkest and lightest paints in the same range, then build a small ladder of lighter and darker mixes using those anchor colours.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>
        <p>
            <?php esc_html_e( 'If a visitor arrives from the paint table by clicking a swatch or row, the shade helper can start with that colour already selected.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>

        <h4><?php esc_html_e( 'Shading page URL', 'paint-tracker-and-mixing-helper' ); ?></h4>
        <p>
            <?php esc_html_e( 'This setting tells the plugin where your Shade helper page lives. It should be the URL of the page where you are using the [shade-helper] shortcode.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>
        <p>
            <?php esc_html_e( 'When this URL is set, the colour swatches (or highlighted rows, if you use row mode) in your [paint_table] output become links. Clicking them will take the visitor to your Shade helper page and automatically pass the clicked colour so that the ladder is built around that paint.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>
        <p>
            <?php esc_html_e( 'If you leave this field empty, the swatches/rows remain as simple colour indicators and are not clickable.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>

        <!-- Form 2: Shading page URL (auto-saves, no button) -->
        <form method="post">
            <?php wp_nonce_field( 'pct_info_settings', 'pct_info_settings_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="pct_mixing_page_url"><?php esc_html_e( 'Shading page URL', 'paint-tracker-and-mixing-helper' ); ?></label>
                    </th>
                    <td>
                        <input type="url"
                            name="pct_mixing_page_url"
                            id="pct_mixing_page_url"
                            class="regular-text"
                            value="<?php echo esc_attr( $info_url ); ?>"
                            placeholder="https://example.com/shade-helper">
                        <p class="description">
                            <?php esc_html_e( 'Enter the URL of the page where you are using the [shade-helper] shortcode.', 'paint-tracker-and-mixing-helper' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </form>
        
        <hr>

        <h2><?php esc_html_e( 'Importing paints from CSV', 'paint-tracker-and-mixing-helper' ); ?></h2>
        <p>
            <?php esc_html_e( 'Under “Paint Colours → Import from CSV” you can bulk-create paints from a CSV file.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>
        <ul>
            <li><?php esc_html_e( 'Choose the paint range that the new paints should be assigned to.', 'paint-tracker-and-mixing-helper' ); ?></li>
            <li><?php esc_html_e( 'Upload a CSV file with one paint per row.', 'paint-tracker-and-mixing-helper' ); ?></li>
        </ul>
        <p><strong><?php esc_html_e( 'Expected columns (per row):', 'paint-tracker-and-mixing-helper' ); ?></strong></p>
        <ul>
            <li><?php esc_html_e( 'title – paint name', 'paint-tracker-and-mixing-helper' ); ?></li>
            <li><?php esc_html_e( 'number – paint number (optional)', 'paint-tracker-and-mixing-helper' ); ?></li>
            <li><?php esc_html_e( 'hex – hex colour, e.g. #2f353a or 2f353a', 'paint-tracker-and-mixing-helper' ); ?></li>
            <li><?php esc_html_e( 'on_shelf – 0 or 1 (optional)', 'paint-tracker-and-mixing-helper' ); ?></li>
        </ul>
        <p>
            <?php esc_html_e( 'An optional header row with column names (title, number, hex, on_shelf) is supported and will be detected automatically.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>

        <hr>

        <h2><?php esc_html_e( 'Exporting paints to CSV', 'paint-tracker-and-mixing-helper' ); ?></h2>
        <p>
            <?php esc_html_e( 'Under “Paint Colours → Export to CSV” you can download your paint collection as a CSV file.', 'paint-tracker-and-mixing-helper' ); ?>
        </p>
        <ul>
            <li><?php esc_html_e( 'Filter by paint range.', 'paint-tracker-and-mixing-helper' ); ?></li>
            <li><?php esc_html_e( 'Optionally limit the export to paints marked as on shelf.', 'paint-tracker-and-mixing-helper' ); ?></li>
        </ul>
        <p><strong><?php esc_html_e( 'Exported columns:', 'paint-tracker-and-mixing-helper' ); ?></strong></p>
        <ul>
            <li><?php esc_html_e( 'title – paint name', 'paint-tracker-and-mixing-helper' ); ?></li>
            <li><?php esc_html_e( 'number – paint number', 'paint-tracker-and-mixing-helper' ); ?></li>
            <li><?php esc_html_e( 'hex – hex colour', 'paint-tracker-and-mixing-helper' ); ?></li>
            <li><?php esc_html_e( 'on_shelf – 0 or 1', 'paint-tracker-and-mixing-helper' ); ?></li>
            <li><?php esc_html_e( 'ranges – list of range names (pipe-separated if more than one).', 'paint-tracker-and-mixing-helper' ); ?></li>
        </ul>
    </div>

<?php
endif;
