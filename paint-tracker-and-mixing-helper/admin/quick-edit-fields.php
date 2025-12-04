<?php
/**
 * Quick Edit fields template for Paint Colours.
 *
 * Variables available:
 * - $column_name
 * - $post_type
 */
?>
<fieldset class="inline-edit-col-left">
    <div class="inline-edit-col">
        <label>
            <span class="title">
                <?php esc_html_e( 'Identifier', 'paint-tracker-and-mixing-helper' ); ?>
            </span>
            <span class="input-text-wrap">
                <input type="text" name="pct_number" class="ptitle" value="">
            </span>
        </label>

        <label>
            <span class="title">
                <?php esc_html_e( 'Hex', 'paint-tracker-and-mixing-helper' ); ?>
            </span>
            <span class="input-text-wrap">
                <input type="text" name="pct_hex" class="ptitle" value="">
            </span>
        </label>

        <label>
            <span class="title">
                <?php esc_html_e( 'On Shelf?', 'paint-tracker-and-mixing-helper' ); ?>
            </span>
            <span class="input-text-wrap">
                <input type="checkbox" name="pct_on_shelf" value="1">
            </span>
        </label>

        <label>
            <span class="title">
                <?php esc_html_e( 'Exclude from shading helper', 'paint-tracker-and-mixing-helper' ); ?>
            </span>
            <span class="input-text-wrap">
                <input type="checkbox" name="pct_exclude_shade" value="1">
            </span>
        </label>

        <label>
            <span class="title">
                <?php esc_html_e( 'Base type', 'paint-tracker-and-mixing-helper' ); ?>
            </span>
            <span class="input-text-wrap">
                <select name="pct_base_type">
                    <option value="">
                        <?php esc_html_e( '— No change —', 'paint-tracker-and-mixing-helper' ); ?>
                    </option>
                    <option value="acrylic">
                        <?php esc_html_e( 'Acrylic', 'paint-tracker-and-mixing-helper' ); ?>
                    </option>
                    <option value="enamel">
                        <?php esc_html_e( 'Enamel', 'paint-tracker-and-mixing-helper' ); ?>
                    </option>
                    <option value="oil">
                        <?php esc_html_e( 'Oil', 'paint-tracker-and-mixing-helper' ); ?>
                    </option>
                    <option value="lacquer">
                        <?php esc_html_e( 'Lacquer', 'paint-tracker-and-mixing-helper' ); ?>
                    </option>
                </select>
            </span>
        </label>
    </div>
</fieldset>
<?php
wp_nonce_field( 'pct_quick_edit', 'pct_quick_edit_nonce' );
