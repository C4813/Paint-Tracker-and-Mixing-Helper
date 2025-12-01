<?php
/**
 * Frontend template for [mixing-helper].
 *
 * Expects:
 * - $pct_ranges : array of WP_Term (paint ranges)
 * - $pct_paints : array of paints:
 *                 [ 'id', 'name', 'number', 'hex', 'range_id' ]
 */

if ( ! isset( $pct_ranges, $pct_paints ) || empty( $pct_ranges ) || empty( $pct_paints ) ) {
    return;
}

/**
 * Build a parent → children map for ranges, preserving the order
 * coming from get_terms() (which is already ordered by term_order).
 */
$pct_ranges_by_parent = [];
foreach ( $pct_ranges as $range ) {
    $parent_id = (int) $range->parent;

    if ( ! isset( $pct_ranges_by_parent[ $parent_id ] ) ) {
        $pct_ranges_by_parent[ $parent_id ] = [];
    }

    $pct_ranges_by_parent[ $parent_id ][] = $range;
}

/**
 * Recursive renderer for hierarchical range options.
 *
 * We guard it with function_exists so it can be safely included
 * in multiple templates without fatal errors.
 */
if ( ! function_exists( 'pct_render_range_options_hierarchical' ) ) {
    function pct_render_range_options_hierarchical( $parent_id, $map, $depth = 0 ) {
        if ( empty( $map[ $parent_id ] ) ) {
            return;
        }

        foreach ( $map[ $parent_id ] as $term ) {
            $indent = str_repeat( '— ', max( 0, (int) $depth ) );
            ?>
            <div class="pct-mix-range-option"
                 data-range="<?php echo esc_attr( $term->term_id ); ?>">
                <span class="pct-mix-option-label">
                    <?php echo esc_html( $indent . $term->name ); ?>
                </span>
            </div>
            <?php
            pct_render_range_options_hierarchical( (int) $term->term_id, $map, $depth + 1 );
        }
    }
}
?>

<!-- ========== MAIN TWO-PAINT MIXER ========== -->
<div class="pct-mix-container">
    <div class="pct-mix-header">
        <?php esc_html_e( 'Mixing helper', 'paint-tracker-and-mixing-helper' ); ?>
    </div>

    <div class="pct-mix-row">
        <!-- Left column -->
        <div class="pct-mix-column pct-mix-column-left">
            <div class="pct-mix-field">
                <label>
                    <strong><?php esc_html_e( 'Paint 1', 'paint-tracker-and-mixing-helper' ); ?></strong>
                </label>
            </div>

            <!-- Range dropdown (custom) -->
            <div class="pct-mix-field">
                <label>
                    <?php esc_html_e( 'Range', 'paint-tracker-and-mixing-helper' ); ?><br>
                    <div class="pct-mix-range-dropdown pct-mix-range-dropdown-left">
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
                            <?php
                            pct_render_range_options_hierarchical( 0, $pct_ranges_by_parent );
                            ?>
                        </div>
                    </div>
                </label>
            </div>

            <!-- Paint dropdown (custom) -->
            <div class="pct-mix-field">
                <label>
                    <?php esc_html_e( 'Paint', 'paint-tracker-and-mixing-helper' ); ?><br>
                    <div class="pct-mix-dropdown pct-mix-dropdown-left">
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

            <div class="pct-mix-field">
                <label>
                    <?php esc_html_e( 'Parts', 'paint-tracker-and-mixing-helper' ); ?><br>
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
                <label>
                    <strong><?php esc_html_e( 'Paint 2', 'paint-tracker-and-mixing-helper' ); ?></strong>
                </label>
            </div>

            <!-- Range dropdown (custom) -->
            <div class="pct-mix-field">
                <label>
                    <?php esc_html_e( 'Range', 'paint-tracker-and-mixing-helper' ); ?><br>
                    <div class="pct-mix-range-dropdown pct-mix-range-dropdown-right">
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
                            <?php
                            pct_render_range_options_hierarchical( 0, $pct_ranges_by_parent );
                            ?>
                        </div>
                    </div>
                </label>
            </div>

            <!-- Paint dropdown (custom) -->
            <div class="pct-mix-field">
                <label>
                    <?php esc_html_e( 'Paint', 'paint-tracker-and-mixing-helper' ); ?><br>
                    <div class="pct-mix-dropdown pct-mix-dropdown-right">
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

            <div class="pct-mix-field">
                <label>
                    <?php esc_html_e( 'Parts', 'paint-tracker-and-mixing-helper' ); ?><br>
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
                <?php esc_html_e( 'Result', 'paint-tracker-and-mixing-helper' ); ?>
            </div>
            <div class="pct-shade-hex pct-mix-result-hex">
                #FFFFFF
            </div>
        </div>

        <div class="pct-mix-result-swatch"></div>
    </div>
</div><!-- /.pct-mix-container -->
