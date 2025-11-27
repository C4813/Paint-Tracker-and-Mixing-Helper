<?php
/**
 * Frontend template for [shade-helper].
 *
 * Expects:
 * - $pct_ranges            : array of WP_Term (paint ranges)
 * - $pct_paints            : array of paints: [ 'id', 'name', 'number', 'hex', 'range_id' ]
 * - $pct_default_shade_hex : string (optional hex from URL)
 * - $pct_default_shade_id  : int    (optional paint ID from URL)
 */

if ( ! isset( $pct_ranges, $pct_paints ) || empty( $pct_ranges ) || empty( $pct_paints ) ) {
    return;
}

$default_shade_hex = isset( $pct_default_shade_hex ) ? $pct_default_shade_hex : '';
$default_shade_id  = isset( $pct_default_shade_id ) ? (int) $pct_default_shade_id : 0;
?>

<!-- ========== SHADE RANGE HELPER (SEPARATE TOOL) ========== -->
<div class="pct-shade-container"
     data-default-shade-hex="<?php echo esc_attr( $default_shade_hex ); ?>"
     data-default-shade-id="<?php echo esc_attr( $default_shade_id ); ?>">
    <div class="pct-shade-helper">
        <div class="pct-shade-header">
            <?php esc_html_e( 'Shade helper', 'paint-tracker-and-mixing-helper' ); ?>
        </div>

        <div class="pct-shade-controls">
            <div class="pct-mix-column pct-mix-column-shade">
                <!-- Range dropdown -->
                <div class="pct-mix-field">
                    <label>
                        <?php esc_html_e( 'Range', 'paint-tracker-and-mixing-helper' ); ?><br>
                        <div class="pct-mix-range-dropdown pct-mix-range-dropdown-shade">
                            <button type="button" class="pct-mix-trigger">
                                <span class="pct-mix-trigger-label">
                                    <?php esc_html_e( 'All', 'paint-tracker-and-mixing-helper' ); ?>
                                </span>
                                <span class="pct-mix-trigger-caret">&#9662;</span>
                            </button>
                            <input type="hidden" class="pct-mix-range-value" value="">
                            <div class="pct-mix-list" hidden>
                                <div class="pct-mix-range-option" data-range="">
                                    <span class="pct-mix-option-label">
                                        <?php esc_html_e( 'All', 'paint-tracker-and-mixing-helper' ); ?>
                                    </span>
                                </div>
                                <?php foreach ( $pct_ranges as $range ) : ?>
                                    <div class="pct-mix-range-option"
                                        data-range="<?php echo esc_attr( $range->term_id ); ?>">
                                        <span class="pct-mix-option-label">
                                            <?php echo esc_html( $range->name ); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Paint dropdown -->
                <div class="pct-mix-field">
                    <label>
                        <?php esc_html_e( 'Paint', 'paint-tracker-and-mixing-helper' ); ?><br>
                        <div class="pct-mix-dropdown pct-mix-dropdown-shade">
                            <button type="button" class="pct-mix-trigger">
                                <span class="pct-mix-trigger-swatch"></span>
                                <span class="pct-mix-trigger-label">
                                    <?php esc_html_e( 'Select a paint', 'paint-tracker-and-mixing-helper' ); ?>
                                </span>
                                <span class="pct-mix-trigger-caret">&#9662;</span>
                            </button>
                            <input type="hidden" class="pct-mix-value" value="">
                            <div class="pct-mix-list" hidden>
                                <?php PCT_Paint_Table_Plugin::render_mix_paint_options( $pct_paints ); ?>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="pct-shade-ladders">
            
                <div class="pct-shade-ladder pct-shade-ladder--strict">
                    <div class="pct-shade-scale pct-shade-scale--strict"
                        aria-live="polite"
                        data-hue-mode="strict">
                        <p class="pct-shade-empty">
                            <?php esc_html_e( 'Select a paint to see lighter and darker mixes.', 'paint-tracker-and-mixing-helper' ); ?>
                        </p>
                    </div>
                </div>
            
                <div class="pct-shade-ladder pct-shade-ladder--relaxed">
                    <div class="pct-shade-scale pct-shade-scale--relaxed"
                        aria-live="polite"
                        data-hue-mode="relaxed">
                        <p class="pct-shade-empty">
                            <?php esc_html_e( 'Select a paint to see lighter and darker mixes.', 'paint-tracker-and-mixing-helper' ); ?>
                        </p>
                    </div>
                </div>
            
            </div><!-- /.pct-shade-ladders -->

        </div>
    </div>
</div><!-- /.pct-shade-container -->
