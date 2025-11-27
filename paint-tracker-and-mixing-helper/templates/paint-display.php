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

// Determine mode (fallback to dots)
$display_mode = isset( $pct_table_display_mode ) ? $pct_table_display_mode : 'dots';
if ( 'rows' !== $display_mode ) {
    $display_mode = 'dots';
}

// Build container classes
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
                    <th><?php esc_html_e( 'Code / Type', 'paint-tracker-and-mixing-helper' ); ?></th>
                    <th class="pct-models-header"><?php esc_html_e( 'Models', 'paint-tracker-and-mixing-helper' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $pct_paints as $paint ) :
                $id     = isset( $paint['id'] )     ? (int) $paint['id'] : 0;
                $name   = isset( $paint['name'] )   ? $paint['name']    : '';
                $number = isset( $paint['number'] ) ? $paint['number']  : '';
                $hex    = isset( $paint['hex'] )    ? $paint['hex']     : '';
                $links  = isset( $paint['links'] )  && is_array( $paint['links'] )
                    ? $paint['links']
                    : [];

                // Build shade helper URL if one is configured.
                $shade_url = '';
                if ( ! empty( $pct_mixing_page_url ) && ( $id || $hex ) ) {
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

                if ( 'rows' === $display_mode && $hex ) {
                    $bg_color   = $hex;
                    $text_color = pct_text_color_for_hex_for_table( $hex );
                    $row_style  = sprintf(
                        'background-color:%1$s; color:%2$s;',
                        $bg_color,
                        $text_color
                    );
                }

                // Make entire row clickable in row-highlight mode when we have a shade URL.
                $row_data_attr = '';
                if ( 'rows' === $display_mode && $shade_url ) {
                    $row_classes[] = 'pct-row-clickable';
                    $row_data_attr = ' data-shade-url="' . esc_url( $shade_url ) . '"';
                }

                $class_attr = '';
                if ( ! empty( $row_classes ) ) {
                    $class_attr = ' class="' . esc_attr( implode( ' ', $row_classes ) ) . '"';
                }

                $style_attr = '';
                if ( $row_style ) {
                    $style_attr = ' style="' . esc_attr( $row_style ) . '"';
                }
                ?>
                <tr<?php echo $class_attr . $style_attr . $row_data_attr; ?>>
                    <?php if ( 'dots' === $display_mode ) : ?>
                        <td class="pct-swatch-cell">
                            <?php if ( $hex ) : ?>

                                <?php if ( $shade_url ) : ?>
                                    <a href="<?php echo esc_url( $shade_url ); ?>" class="pct-swatch-link">
                                        <span class="pct-swatch" style="background-color: <?php echo esc_attr( $hex ); ?>"></span>
                                    </a>
                                <?php else : ?>
                                    <span class="pct-swatch" style="background-color: <?php echo esc_attr( $hex ); ?>"></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>

                    <td class="pct-name-cell">
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
                                $url    = isset( $link['url'] )   ? $link['url']   : '';
                                $ltitle = isset( $link['title'] ) ? $link['title'] : '';

                                if ( ! $url ) {
                                    continue;
                                }

                                if ( '' === $ltitle ) {
                                    $ltitle = ( $total_links > 1 )
                                        ? sprintf( __( 'View %d', 'paint-tracker-and-mixing-helper' ), $i + 1 )
                                        : __( 'View', 'paint-tracker-and-mixing-helper' );
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
