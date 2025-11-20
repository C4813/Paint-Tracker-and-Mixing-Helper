<?php
/**
 * Admin template for Paint Tracker and Mixing Helper plugin.
 *
 * Uses $pct_admin_view to decide what to render:
 * - 'meta_box'    : paint details meta box
 * - 'import_page' : CSV import page
 * - 'export_page' : CSV export page
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
elseif ( 'export_page' === $pct_admin_view ) : ?>

    <div class="wrap">
        <h1><?php esc_html_e( 'Export Paints to CSV', 'pct' ); ?></h1>

        <?php
        $errors = isset( $pct_export_errors ) && is_array( $pct_export_errors ) ? $pct_export_errors : [];

        if ( ! empty( $errors ) ) : ?>
            <div class="notice notice-error">
                <p><?php echo implode( '<br>', array_map( 'esc_html', $errors ) ); ?></p>
            </div>
        <?php endif; ?>

        <p>
            <?php esc_html_e( 'Generate a CSV file with one paint per row.', 'pct' ); ?>
        </p>
        <p>
            <?php esc_html_e( 'Columns: name, number, hex colour, on shelf (yes/no).', 'pct' ); ?>
        </p>

        <form method="post">
            <?php wp_nonce_field( 'pct_export_paints', 'pct_export_nonce' ); ?>

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
                        <label for="pct_shelf"><?php esc_html_e( 'On shelf filter', 'pct' ); ?></label>
                    </th>
                    <td>
                        <select name="pct_shelf" id="pct_shelf">
                            <option value="any"><?php esc_html_e( 'All paints in range', 'pct' ); ?></option>
                            <option value="yes"><?php esc_html_e( 'Only paints marked as on shelf', 'pct' ); ?></option>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Download CSV', 'pct' ), 'primary', 'pct_export_submit' ); ?>
        </form>
    </div>

<?php
endif;
