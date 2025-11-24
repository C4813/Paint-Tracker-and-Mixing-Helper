<?php
/**
 * Admin template for Paint Tracker and Mixing Helper plugin.
 *
 * Uses $pct_admin_view to decide what to render:
 * - 'meta_box'      : paint details meta box
 * - 'import_page'   : CSV import page
 * - 'info_settings' : info & settings page
 * - 'export_page'   : CSV export page
 */

if ( ! isset( $pct_admin_view ) ) {
    return;
}

if ( 'meta_box' === $pct_admin_view ) : ?>

    <?php wp_nonce_field( 'pct_save_paint_meta', 'pct_paint_meta_nonce' ); ?>

    <p>
        <label for="pct_number"><strong><?php esc_html_e( 'Paint number', 'pct' ); ?></strong> (e.g. 70.800)</label><br>
        <input type="text" id="pct_number" name="pct_number"
            value="<?php echo isset( $pct_number ) ? esc_attr( $pct_number ) : ''; ?>"
            class="regular-text">
    </p>

    <p>
        <label for="pct_hex"><strong><?php esc_html_e( 'Hex colour', 'pct' ); ?></strong> (e.g. #2f353a)</label><br>
        <input type="text" id="pct_hex" name="pct_hex"
            value="<?php echo isset( $pct_hex ) ? esc_attr( $pct_hex ) : ''; ?>"
            class="regular-text">
    </p>

    <p>
        <label for="pct_on_shelf">
            <strong><?php esc_html_e( 'On the shelf', 'pct' ); ?></strong>
        </label><br>
        <label>
            <input type="checkbox" id="pct_on_shelf" name="pct_on_shelf" value="1"
                <?php checked( isset( $pct_on_shelf ) ? $pct_on_shelf : '', '1' ); ?>>
            <?php esc_html_e( 'Yes, I currently have this paint on my shelf', 'pct' ); ?>
        </label>
    </p>

    <p><strong><?php esc_html_e( 'Linked posts / URLs', 'pct' ); ?></strong></p>

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
                        <?php esc_html_e( 'Link title', 'pct' ); ?><br>
                        <input type="text"
                            name="pct_links_title[]"
                            value="<?php echo esc_attr( $ltitle ); ?>"
                            class="regular-text"
                            placeholder="<?php esc_attr_e( 'e.g. Tutorial, Review, Example Build', 'pct' ); ?>">
                    </label>
                </p>
                <p class="pct-link-row-field">
                    <label>
                        <?php esc_html_e( 'Link URL', 'pct' ); ?><br>
                        <input type="url"
                            name="pct_links_url[]"
                            value="<?php echo esc_attr( $lurl ); ?>"
                            class="regular-text"
                            placeholder="https://example.com/my-article">
                    </label>
                </p>
                <p class="pct-link-row-field">
                    <button type="button" class="button pct-remove-link">
                        <?php esc_html_e( 'Remove link', 'pct' ); ?>
                    </button>
                </p>
            </div>
        <?php endforeach; ?>
    </div>

    <p>
        <button type="button" class="button button-secondary" id="pct-add-link">
            <?php esc_html_e( 'Add another link', 'pct' ); ?>
        </button>
    </p>

<?php
elseif ( 'import_page' === $pct_admin_view ) : ?>

    <div class="wrap">
        <h1><?php esc_html_e( 'Import Paints from CSV', 'pct' ); ?></h1>

        <?php
        $errors  = isset( $pct_import_errors ) && is_array( $pct_import_errors ) ? $pct_import_errors : [];
        $message = isset( $pct_import_msg ) ? $pct_import_msg : '';

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
            <?php esc_html_e( 'Upload a CSV file to automatically create paints in a specific range.', 'pct' ); ?>
        </p>
        <p>
            <?php esc_html_e( 'Expected format (per row): name, number, hex colour, on shelf (yes/no, optional).', 'pct' ); ?>
        </p>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'pct_import_paints', 'pct_import_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="pct_range"><?php esc_html_e( 'Paint range', 'pct' ); ?></label>
                    </th>
                    <td>
                        <?php
                        wp_dropdown_categories(
                            [
                                'taxonomy'         => PCT_Paint_Table_Plugin::TAX,
                                'name'             => 'pct_range',
                                'id'               => 'pct_range',
                                'hide_empty'       => false,
                                'show_option_none' => __( 'Select a range', 'pct' ),
                            ]
                        );
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="pct_csv"><?php esc_html_e( 'CSV file', 'pct' ); ?></label>
                    </th>
                    <td>
                        <input type="file" name="pct_csv" id="pct_csv" accept=".csv">
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Import paints', 'pct' ), 'primary', 'pct_import_submit' ); ?>
        </form>
    </div>

<?php
elseif ( 'info_settings' === $pct_admin_view ) : ?>

    <div class="wrap">
        <h1><?php esc_html_e( 'Info & Settings', 'pct' ); ?></h1>

        <?php
        $message  = isset( $pct_info_message ) ? $pct_info_message : '';
        $info_url = isset( $pct_info_url ) ? $pct_info_url : '';
        $mode     = isset( $pct_table_display_mode ) ? $pct_table_display_mode : 'dots';

        if ( $message ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html( $message ); ?></p>
            </div>
        <?php endif; ?>

        <h2><?php esc_html_e( 'What this plugin does', 'pct' ); ?></h2>
        <p>
            <?php esc_html_e( 'Paint Tracker and Mixing Helper helps you keep a structured list of your miniature paints, track which ones you currently own, link each paint to useful resources, and provide interactive colour tools on the front end.', 'pct' ); ?>
        </p>

        <hr>

        <h2><?php esc_html_e( 'How the data is stored', 'pct' ); ?></h2>
        <p>
            <?php esc_html_e( 'The plugin adds a custom post type called “Paint Colours” and a taxonomy called “Paint Ranges”.', 'pct' ); ?>
        </p>
        <ul>
            <li>
                <strong><?php esc_html_e( 'Paint Colours', 'pct' ); ?></strong>
                – <?php esc_html_e( 'each paint has a name (title), a paint number, a hex colour, an “On the shelf” flag, and optional links (tutorials, reviews, example builds, etc.).', 'pct' ); ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Paint Ranges', 'pct' ); ?></strong>
                – <?php esc_html_e( 'group paints into ranges such as Vallejo Model Color, Vallejo Game Color, and so on.', 'pct' ); ?>
            </li>
        </ul>

        <hr>

        <h2><?php esc_html_e( 'Shortcodes', 'pct' ); ?></h2>

        <h3><?php esc_html_e( '[paint_table]', 'pct' ); ?></h3>
        <p>
            <?php esc_html_e( 'Displays a table of paints, optionally filtered to a single paint range.', 'pct' ); ?>
        </p>
        <p><strong><?php esc_html_e( 'Attributes:', 'pct' ); ?></strong></p>
        <ul>
            <li><code>range</code> – <?php esc_html_e( 'taxonomy slug of the paint range (optional).', 'pct' ); ?></li>
            <li><code>limit</code> – <?php esc_html_e( 'number of paints to show (-1 shows all).', 'pct' ); ?></li>
            <li><code>orderby</code> – <?php esc_html_e( 'either "meta_number" (paint number) or "title".', 'pct' ); ?></li>
            <li><code>shelf</code> – <?php esc_html_e( '"yes" to show only paints marked as on shelf, or "any" to show all paints.', 'pct' ); ?></li>
        </ul>
        <p>
            <strong><?php esc_html_e( 'Example:', 'pct' ); ?></strong>
            <br>
            <code>[paint_table range="vallejo-model-color" limit="-1" orderby="meta_number" shelf="any"]</code>
        </p>

        <h4><?php esc_html_e( 'Paint table display', 'pct' ); ?></h4>
        <p>
            <?php esc_html_e( 'Use the option below to choose whether the paint table shows small colour dots (swatches) or highlights the entire row with the paint colour.', 'pct' ); ?>
        </p>

        <!-- Form 1: Paint table display (auto-saves, no button) -->
        <form method="post">
            <?php wp_nonce_field( 'pct_info_settings', 'pct_info_settings_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Paint table display', 'pct' ); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio"
                                    name="pct_table_display_mode"
                                    value="dots"
                                    <?php checked( $mode, 'dots' ); ?> />
                                <?php esc_html_e( 'Show colour dots (swatch column)', 'pct' ); ?>
                            </label>
                            <br>
                            <label>
                                <input type="radio"
                                    name="pct_table_display_mode"
                                    value="rows"
                                    <?php checked( $mode, 'rows' ); ?> />
                                <?php esc_html_e( 'Highlight the entire row with the paint colour', 'pct' ); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e( 'Row highlighting applies the paint colour to the whole row and adjusts text to light/dark for readability.', 'pct' ); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </form>

        <hr>

        <h3><?php esc_html_e( '[mixing-helper]', 'pct' ); ?></h3>
        <p>
            <?php esc_html_e( 'Shows the two-paint mixing tool. You can pick two paints, set how many parts of each to mix, and see the resulting colour and hex value.', 'pct' ); ?>
        </p>
        <p>
            <?php esc_html_e( 'Each paint dropdown can be filtered by range so you can quickly find the colours you want to mix.', 'pct' ); ?>
        </p>

        <hr>

        <h3><?php esc_html_e( '[shade-helper]', 'pct' ); ?></h3>
        <p>
            <?php esc_html_e( 'Shows the shade helper as a standalone tool. Choose a paint and the plugin will look for the darkest and lightest paints in the same range, then build a small ladder of lighter and darker mixes using those anchor colours.', 'pct' ); ?>
        </p>
        <p>
            <?php esc_html_e( 'If a visitor arrives from the paint table by clicking a swatch or row, the shade helper can start with that colour already selected.', 'pct' ); ?>
        </p>

        <h4><?php esc_html_e( 'Shading page URL', 'pct' ); ?></h4>
        <p>
            <?php esc_html_e( 'This setting tells the plugin where your Shade helper page lives. It should be the URL of the page where you are using the [shade-helper] shortcode.', 'pct' ); ?>
        </p>
        <p>
            <?php esc_html_e( 'When this URL is set, the colour swatches (or highlighted rows, if you use row mode) in your [paint_table] output become links. Clicking them will take the visitor to your Shade helper page and automatically pass the clicked colour so that the ladder is built around that paint.', 'pct' ); ?>
        </p>
        <p>
            <?php esc_html_e( 'If you leave this field empty, the swatches/rows remain as simple colour indicators and are not clickable.', 'pct' ); ?>
        </p>

        <!-- Form 2: Shading page URL (auto-saves, no button) -->
        <form method="post">
            <?php wp_nonce_field( 'pct_info_settings', 'pct_info_settings_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="pct_mixing_page_url"><?php esc_html_e( 'Shading page URL', 'pct' ); ?></label>
                    </th>
                    <td>
                        <input type="url"
                            name="pct_mixing_page_url"
                            id="pct_mixing_page_url"
                            class="regular-text"
                            value="<?php echo esc_attr( $info_url ); ?>"
                            placeholder="https://example.com/shade-helper">
                        <p class="description">
                            <?php esc_html_e( 'Enter the URL of the page where you are using the [shade-helper] shortcode.', 'pct' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </form>

        <hr>

        <h2><?php esc_html_e( 'Importing paints from CSV', 'pct' ); ?></h2>
        <p>
            <?php esc_html_e( 'Under “Paint Colours → Import from CSV” you can bulk-create paints from a CSV file.', 'pct' ); ?>
        </p>
        <ul>
            <li><?php esc_html_e( 'Choose the paint range that the new paints should be assigned to.', 'pct' ); ?></li>
            <li><?php esc_html_e( 'Upload a CSV file with one paint per row.', 'pct' ); ?></li>
        </ul>
        <p><strong><?php esc_html_e( 'Expected columns (per row):', 'pct' ); ?></strong></p>
        <ul>
            <li><?php esc_html_e( 'title – paint name', 'pct' ); ?></li>
            <li><?php esc_html_e( 'number – paint number (optional)', 'pct' ); ?></li>
            <li><?php esc_html_e( 'hex – hex colour, e.g. #2f353a or 2f353a', 'pct' ); ?></li>
            <li><?php esc_html_e( 'on_shelf – 0 or 1 (optional)', 'pct' ); ?></li>
        </ul>
        <p>
            <?php esc_html_e( 'An optional header row with column names (title, number, hex, on_shelf) is supported and will be detected automatically.', 'pct' ); ?>
        </p>

        <hr>

        <h2><?php esc_html_e( 'Exporting paints to CSV', 'pct' ); ?></h2>
        <p>
            <?php esc_html_e( 'Under “Paint Colours → Export to CSV” you can download your paint collection as a CSV file.', 'pct' ); ?>
        </p>
        <ul>
            <li><?php esc_html_e( 'Filter by paint range.', 'pct' ); ?></li>
            <li><?php esc_html_e( 'Optionally limit the export to paints marked as on shelf.', 'pct' ); ?></li>
        </ul>
        <p><strong><?php esc_html_e( 'Exported columns:', 'pct' ); ?></strong></p>
        <ul>
            <li><?php esc_html_e( 'title – paint name', 'pct' ); ?></li>
            <li><?php esc_html_e( 'number – paint number', 'pct' ); ?></li>
            <li><?php esc_html_e( 'hex – hex colour', 'pct' ); ?></li>
            <li><?php esc_html_e( 'on_shelf – 0 or 1', 'pct' ); ?></li>
            <li><?php esc_html_e( 'ranges – list of range names (pipe-separated if more than one).', 'pct' ); ?></li>
        </ul>
    </div>

<?php

endif;
