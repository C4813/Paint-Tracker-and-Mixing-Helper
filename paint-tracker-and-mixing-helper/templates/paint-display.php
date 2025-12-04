<?php
/**
 * Frontend table template for Paint Tracker and Mixing Helper.
 *
 * Expects:
 * - $pct_paints              : array of [ 'id', 'name', 'number', 'hex', 'links' ]
 * - $pct_range_title         : string (range name, e.g. "Vallejo Model Color")
 * - $pct_mixing_page_url     : string (URL of page with [shade-helper])
 * - $pct_table_display_mode  : string 'dots' or 'rows'
 */

if ( ! isset( $pct_paints ) || ! is_array( $pct_paints ) || empty( $pct_paints ) ) {
    return;
}

// ---- Small helpers for row mode ----
if ( ! function_exists( 'pct_hex_to_rgb_for_table' ) ) {
    function pct_hex_to_rgb_for_table( $hex ) {
        if ( ! $hex ) {
            return null;
        }

        $hex = trim( (string) $hex );
        $hex = ltrim( $hex, '#' );

        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if ( strlen( $hex ) !== 6 ) {
            return null;
        }

        $int = hexdec( $hex );

        return [
            'r' => ( $int >> 16 ) & 255,
            'g' => ( $int >> 8 ) & 255,
            'b' => $int & 255,
        ];
    }
}

if ( ! function_exists( 'pct_text_color_for_hex_for_table' ) ) {
    function pct_text_color_for_hex_for_table( $hex ) {
        $rgb = pct_hex_to_rgb_for_table( $hex );
        if ( ! $rgb ) {
            return '#111827';
        }

        $lum = ( 0.299 * $rgb['r'] + 0.587 * $rgb['g'] + 0.114 * $rgb['b'] ) / 255;

        return ( $lum < 0.5 ) ? '#f9fafb' : '#111827';
    }
}

if ( ! function_exists( 'pct_gradient_background_for_hex_for_table' ) ) {
    /**
     * Return a CSS background style string that uses a simple radial gradient
     * based on the supplied hex colour.
     *
     * We keep the existing shape/size of swatches and rows – only the fill
     * changes to a gradient.
     */
    function pct_gradient_background_for_hex_for_table( $hex ) {
        if ( ! $hex ) {
            return '';
        }

        $hex = trim( (string) $hex );
        if ( '#' !== substr( $hex, 0, 1 ) ) {
            $hex = '#' . $hex;
        }

        // Flat colour + simple highlight-to-shadow radial gradient.
        // (We deliberately keep this generic so it works for any paint.)
        return sprintf(
            'background-color:%1$s; background-image: radial-gradient(circle at 30%% 30%%, #ffffff, %1$s 40%%, #000000 100%%);',
            $hex
        );
    }
}

if ( ! function_exists( 'pct_gradient_row_background_for_hex_for_table' ) ) {
    /**
     * Subtle, full-width gradient for row highlight mode.
     */
    function pct_gradient_row_background_for_hex_for_table( $hex ) {
        if ( ! $hex ) {
            return '';
        }

        $hex = trim( (string) $hex );
        if ( '#' !== substr( $hex, 0, 1 ) ) {
            $hex = '#' . $hex;
        }

        // Base colour + soft white highlight on the left fading into a
        // gentle shadow across the full width of the row.
        return sprintf(
            'background:%1$s; background-image: radial-gradient(
                circle at 40%% 60%%,
                rgba(255,255,255,0.68) 0%%,
                rgba(255,255,255,0.42) 20%%,
                rgba(255,255,255,0.24) 36%%,
                rgba(0,0,0,0) 58%%,
                rgba(0,0,0,0.25) 100%%
            );',
            $hex
        );
    }
}
// Determine mode (fallback to dots).
$display_mode = isset( $pct_table_display_mode ) ? $pct_table_display_mode : 'dots';
if ( 'rows' !== $display_mode ) {
    $display_mode = 'dots';
}

// Build container classes.
$container_classes = [
    'pct-table-container',
    'pct-table-mode-' . $display_mode,
];

if ( 'rows' === $display_mode ) {
    $container_classes[] = 'pct-row-highlight-mode';
}

$container_class_attr = implode( ' ', array_map( 'sanitize_html_class', $container_classes ) );
?>

<div class="<?php echo esc_attr( $container_class_attr ); ?>">
    <?php if ( ! empty( $pct_range_title ) ) : ?>
        <div class="pct-range-title">
            <?php echo esc_html( $pct_range_title ); ?>
        </div>
    <?php endif; ?>

    <div class="pct-table-wrapper">
        <table class="pct-table">
            <thead>
                <tr>
                    <?php if ( 'dots' === $display_mode ) : ?>
                        <th class="pct-swatch-header" aria-hidden="true"></th>
                    <?php endif; ?>
                    <th><?php esc_html_e( 'Colour', 'paint-tracker-and-mixing-helper' ); ?></th>
                    <th><?php esc_html_e( 'Identifier', 'paint-tracker-and-mixing-helper' ); ?></th>
                    <th class="pct-models-header"><?php esc_html_e( 'Models', 'paint-tracker-and-mixing-helper' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $pct_paints as $paint ) :
                    $id     = isset( $paint['id'] ) ? (int) $paint['id'] : 0;
                    $name   = isset( $paint['name'] ) ? $paint['name'] : '';
                    $number = isset( $paint['number'] ) ? $paint['number'] : '';
                    $hex      = isset( $paint['hex'] ) ? $paint['hex'] : '';
                    $links    = ( isset( $paint['links'] ) && is_array( $paint['links'] ) ) ? $paint['links'] : [];
                    $gradient = ! empty( $paint['gradient'] );
                    $no_hex_label = __( 'No colour hex', 'paint-tracker-and-mixing-helper' );
                    
                    $swatch_style = '';
                    if ( $hex ) {
                        if ( $gradient ) {
                            $swatch_style = pct_gradient_background_for_hex_for_table( $hex );
                        } else {
                            $swatch_style = 'background-color: ' . $hex . ';';
                        }
                    }
                    
                    // Build shade helper URL if one is configured.
                    // Rows with no hex should NOT be clickable in row-highlight mode,
                    // so we only generate a URL when a hex value exists.
                    $shade_url = '';
                    if ( ! empty( $pct_mixing_page_url ) && $hex && ( $id || $hex ) ) {
                        $args = [];

                        if ( $id ) {
                            $args['pct_shade_id'] = $id;
                        }

                        if ( $hex ) {
                            // Drop any leading '#' so it doesn't become a fragment.
                            $args['pct_shade_hex'] = ltrim( $hex, '#' );
                        }

                        if ( ! empty( $args ) ) {
                            $shade_url = add_query_arg(
                                $args,
                                $pct_mixing_page_url
                            );
                        }
                    }

                    $row_style   = '';
                    $row_classes = [];

                    // Row colouring in "rows" mode if we have a hex.
                    if ( 'rows' === $display_mode && $hex ) {
                        $text_color = pct_text_color_for_hex_for_table( $hex );
                    
                        if ( $gradient ) {
                            // More subtle, full-width row gradient.
                            $row_style = pct_gradient_row_background_for_hex_for_table( $hex );
                            $row_style .= ' color:' . $text_color . ';';
                        } else {
                            $bg_color  = $hex;
                            $row_style = sprintf(
                                'background-color:%1$s; color:%2$s;',
                                $bg_color,
                                $text_color
                            );
                        }
                    }

                    // Make entire row clickable in row-highlight mode when we have a shade URL.
                    if ( 'rows' === $display_mode && $shade_url ) {
                        $row_classes[] = 'pct-row-clickable';
                    }

                    // Mark rows with no hex so we can style them differently.
                    if ( 'rows' === $display_mode && ! $hex ) {
                        $row_classes[] = 'pct-row-no-hex';
                    }
                    ?>
                    <tr
                        <?php if ( ! empty( $row_classes ) ) : ?>
                            class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>"
                        <?php endif; ?>
                        <?php if ( $row_style ) : ?>
                            style="<?php echo esc_attr( $row_style ); ?>"
                        <?php endif; ?>
                        <?php if ( 'rows' === $display_mode && $shade_url ) : ?>
                            data-shade-url="<?php echo esc_url( $shade_url ); ?>"
                        <?php endif; ?>
                    >
                        <?php if ( 'dots' === $display_mode ) : ?>
                            <td class="pct-swatch-cell">
                                <?php if ( $hex ) : ?>
                                    <?php if ( $shade_url ) : ?>
                                        <a href="<?php echo esc_url( $shade_url ); ?>" class="pct-swatch-link">
                                            <span
                                                class="pct-swatch"
                                                style="<?php echo esc_attr( $swatch_style ); ?>"
                                            ></span>
                                        </a>
                                    <?php else : ?>
                                        <span
                                            class="pct-swatch"
                                            style="<?php echo esc_attr( $swatch_style ); ?>"
                                        ></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>

                        <td
                            class="pct-name-cell<?php echo ( 'rows' === $display_mode && ! $hex ) ? ' pct-no-hex-cell' : ''; ?>"
                            <?php if ( 'rows' === $display_mode && ! $hex ) : ?>
                                data-no-hex-label="<?php echo esc_attr( $no_hex_label ); ?>"
                            <?php endif; ?>
                        >
                            <span class="pct-name"><?php echo esc_html( $name ); ?></span>
                        </td>
                        <td class="pct-number">
                            <?php echo esc_html( $number ); ?>
                        </td>
                        <td class="pct-models">
                            <?php if ( ! empty( $links ) ) : ?>
                                <?php
                                $total_links = count( $links );
                                $shown       = 0;

                                foreach ( $links as $i => $link ) {
                                    $url    = isset( $link['url'] ) ? $link['url'] : '';
                                    $ltitle = isset( $link['title'] ) ? $link['title'] : '';

                                    if ( ! $url ) {
                                        continue;
                                    }

                                    if ( '' === $ltitle ) {
                                        if ( $total_links > 1 ) {
                                            $ltitle = sprintf(
                                                /* translators: %d: index number of this paint link. */
                                                __( 'View %d', 'paint-tracker-and-mixing-helper' ),
                                                $i + 1
                                            );
                                        } else {
                                            $ltitle = __( 'View', 'paint-tracker-and-mixing-helper' );
                                        }
                                    }

                                    $shown++;
                                    ?>
                                    <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">
                                        <?php echo esc_html( $ltitle ); ?>
                                    </a>
                                    <?php if ( $shown < $total_links ) : ?>
                                        <br>
                                    <?php endif; ?>
                                <?php } ?>
                            <?php else : ?>
                                &mdash;
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
