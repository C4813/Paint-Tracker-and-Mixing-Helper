<?php
/**
 * Frontend template for [mixing_helper].
 *
 * Expects:
 * - $pct_ranges : array of WP_Term (paint ranges)
 * - $pct_paints : array of paints:
 *                 [ 'id', 'name', 'number', 'hex', 'range_id' ]
 */

if ( ! isset( $pct_ranges, $pct_paints ) || empty( $pct_ranges ) || empty( $pct_paints ) ) {
    return;
}
?>

<!-- ========== MAIN TWO-PAINT MIXER ========== -->
<div class="pct-mix-container">

    <div class="pct-mix-header">
        <?php esc_html_e( 'Mixing helper', 'pct' ); ?>
    </div>

    <div class="pct-mix-row">
        <!-- Left column -->
        <div class="pct-mix-column pct-mix-column-left">
            <div class="pct-mix-field">
                <label><strong><?php esc_html_e( 'Paint 1', 'pct' ); ?></strong></label>
            </div>

            <!-- Range dropdown (custom) -->
            <div class="pct-mix-field">
                <label>
                    <?php esc_html_e( 'Range', 'pct' ); ?><br>
                    <div class="pct-mix-range-dropdown pct-mix-range-dropdown-left">
                        <button type="button" class="pct-mix-trigger">
                            <span class="pct-mix-trigger-label">
                                <?php esc_html_e( 'All', 'pct' ); ?>
                            </span>
                            <span class="pct-mix-trigger-caret">&#9662;</span>
                        </button>
                        <input type="hidden" class="pct-mix-range-value" value="">
                        <div class="pct-mix-list" hidden>
                            <div class="pct-mix-range-option" data-range="">
                                <span class="pct-mix-option-label">
                                    <?php esc_html_e( 'All', 'pct' ); ?>
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

            <!-- Paint dropdown (custom) -->
            <div class="pct-mix-field">
                <label>
                    <?php esc_html_e( 'Paint', 'pct' ); ?><br>
                    <div class="pct-mix-dropdown pct-mix-dropdown-left">
                        <button type="button" class="pct-mix-trigger">
                            <span class="pct-mix-trigger-swatch"></span>
                            <span class="pct-mix-trigger-label">
                                <?php esc_html_e( 'Select a paint', 'pct' ); ?>
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

            <div class="pct-mix-field">
                <label>
                    <?php esc_html_e( 'Parts', 'pct' ); ?><br>
                    <input type="number"
                           class="pct-mix-parts pct-mix-parts-left"
                           min="1"
                           step="1"
                           value="1">
                </label>
            </div>
        </div>

        <!-- Right column -->
        <div class="pct-mix-column pct-mix-column-right">
            <div class="pct-mix-field">
                <label><strong><?php esc_html_e( 'Paint 2', 'pct' ); ?></strong></label>
            </div>

            <!-- Range dropdown (custom) -->
            <div class="pct-mix-field">
                <label>
                    <?php esc_html_e( 'Range', 'pct' ); ?><br>
                    <div class="pct-mix-range-dropdown pct-mix-range-dropdown-right">
                        <button type="button" class="pct-mix-trigger">
                            <span class="pct-mix-trigger-label">
                                <?php esc_html_e( 'All', 'pct' ); ?>
                            </span>
                            <span class="pct-mix-trigger-caret">&#9662;</span>
                        </button>
                        <input type="hidden" class="pct-mix-range-value" value="">
                        <div class="pct-mix-list" hidden>
                            <div class="pct-mix-range-option" data-range="">
                                <span class="pct-mix-option-label">
                                    <?php esc_html_e( 'All', 'pct' ); ?>
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

            <!-- Paint dropdown (custom) -->
            <div class="pct-mix-field">
                <label>
                    <?php esc_html_e( 'Paint', 'pct' ); ?><br>
                    <div class="pct-mix-dropdown pct-mix-dropdown-right">
                        <button type="button" class="pct-mix-trigger">
                            <span class="pct-mix-trigger-swatch"></span>
                            <span class="pct-mix-trigger-label">
                                <?php esc_html_e( 'Select a paint', 'pct' ); ?>
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

            <div class="pct-mix-field">
                <label>
                    <?php esc_html_e( 'Parts', 'pct' ); ?><br>
                    <input type="number"
                           class="pct-mix-parts pct-mix-parts-right"
                           min="1"
                           step="1"
                           value="1">
                </label>
            </div>
        </div>
    </div>

    <div class="pct-mix-result-block pct-shade-row pct-shade-row-base">
        <div class="pct-shade-meta">
            <div class="pct-shade-ratio pct-mix-result-label">
                <?php esc_html_e( 'Result', 'pct' ); ?>
            </div>
            <div class="pct-shade-hex pct-mix-result-hex">
                #FFFFFF
            </div>
        </div>

        <div class="pct-mix-result-swatch"></div>
    </div>

</div><!-- /.pct-mix-container -->

<!-- ========== SHADE RANGE HELPER (SEPARATE TOOL) ========== -->
<div class="pct-shade-container">
    <div class="pct-shade-helper">
        <div class="pct-shade-header">
            <?php esc_html_e( 'Shade range helper', 'pct' ); ?>
        </div>

        <div class="pct-shade-controls">
            <div class="pct-mix-column pct-mix-column-shade">
                <!-- Range dropdown -->
                <div class="pct-mix-field">
                    <label>
                        <?php esc_html_e( 'Range', 'pct' ); ?><br>
                        <div class="pct-mix-range-dropdown pct-mix-range-dropdown-shade">
                            <button type="button" class="pct-mix-trigger">
                                <span class="pct-mix-trigger-label">
                                    <?php esc_html_e( 'All', 'pct' ); ?>
                                </span>
                                <span class="pct-mix-trigger-caret">&#9662;</span>
                            </button>
                            <input type="hidden" class="pct-mix-range-value" value="">
                            <div class="pct-mix-list" hidden>
                                <div class="pct-mix-range-option" data-range="">
                                    <span class="pct-mix-option-label">
                                        <?php esc_html_e( 'All', 'pct' ); ?>
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
                        <?php esc_html_e( 'Paint', 'pct' ); ?><br>
                        <div class="pct-mix-dropdown pct-mix-dropdown-shade">
                            <button type="button" class="pct-mix-trigger">
                                <span class="pct-mix-trigger-swatch"></span>
                                <span class="pct-mix-trigger-label">
                                    <?php esc_html_e( 'Select a paint', 'pct' ); ?>
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

            <div class="pct-shade-scale" aria-live="polite">
                <p class="pct-shade-empty">
                    <?php esc_html_e( 'Select a paint to see lighter and darker mixes.', 'pct' ); ?>
                </p>
            </div>
        </div>
    </div>
</div><!-- /.pct-shade-container -->
