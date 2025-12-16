( function( window ) {
    'use strict';

    if ( ! window.pctColorUtils ) {
        window.pctColorUtils = {};
    }

    // ---------- Colour helpers ----------

    function hexToRgb( hex ) {
        if ( ! hex ) {
            return null;
        }

        hex = hex.toString().trim();

        if ( hex.charAt( 0 ) === '#' ) {
            hex = hex.slice( 1 );
        }

        if ( hex.length === 3 ) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }

        if ( hex.length !== 6 ) {
            return null;
        }

        var num = parseInt( hex, 16 );

        if ( isNaN( num ) ) {
            return null;
        }

        return {
            r: ( num >> 16 ) & 255,
            g: ( num >> 8 ) & 255,
            b: num & 255
        };
    }

    function componentToHex( c ) {
        var v = Math.max( 0, Math.min( 255, Math.round( c ) ) );
        var s = v.toString( 16 );

        return s.length === 1 ? '0' + s : s;
    }

    function rgbToHex( r, g, b ) {
        return '#' + componentToHex( r ) + componentToHex( g ) + componentToHex( b );
    }

    function mixColors( hex1, hex2, w1, w2 ) {
        var c1 = hexToRgb( hex1 );
        var c2 = hexToRgb( hex2 );

        if ( ! c1 || ! c2 ) {
            return null;
        }

        w1 = Number( w1 ) || 0;
        w2 = Number( w2 ) || 0;

        var total = w1 + w2;

        if ( total <= 0 ) {
            return null;
        }

        var r = ( c1.r * w1 + c2.r * w2 ) / total;
        var g = ( c1.g * w1 + c2.g * w2 ) / total;
        var b = ( c1.b * w1 + c2.b * w2 ) / total;

        return rgbToHex( r, g, b );
    }

    function textColorForHex( hex ) {
        var c = hexToRgb( hex );

        if ( ! c ) {
            return '#111827';
        }

        var lum = ( 0.299 * c.r + 0.587 * c.g + 0.114 * c.b ) / 255;

        return lum < 0.5 ? '#f9fafb' : '#111827';
    }

    /**
     * Build a gradient CSS payload for metallic (1) or shade (2).
     * Returns { background, backgroundImage, color }.
     */
    function gradientCss( hex, textColor, gradientType ) {
        if ( ! hex ) {
            return null;
        }

        gradientType = parseInt( gradientType, 10 ) || 0;

        // 0 = no gradient
        if ( gradientType === 0 ) {
            return {
                background: hex,
                backgroundImage: '',
                color: textColor
            };
        }

        var stops;

        if ( gradientType === 2 ) {
            stops =
                'circle at 90% 50%,' +
                'rgba(0,0,0,0.68) 0%,' +
                'rgba(0,0,0,0.42) 20%,' +
                'rgba(0,0,0,0.24) 36%,' +
                'rgba(0,0,0,0) 58%';
        } else {
            stops =
                'circle at 90% 50%,' +
                'rgba(255,255,255,0.68) 0%,' +
                'rgba(255,255,255,0.42) 20%,' +
                'rgba(255,255,255,0.24) 36%,' +
                'rgba(0,0,0,0) 58%';
        }

        return {
            background: hex,
            backgroundImage: 'radial-gradient(' + stops + ')',
            color: textColor
        };
    }

    // Helper: does this option belong to the selected range or one of its parents?
    function optionMatchesRange( $opt, selectedRangeId ) {
        var selected     = String( selectedRangeId );
        var rangeIdsAttr = $opt.attr( 'data-range-ids' );

        if ( rangeIdsAttr ) {
            var ids = String( rangeIdsAttr ).split( ',' );

            for ( var i = 0; i < ids.length; i++ ) {
                var id = String( ids[ i ] ).trim();

                if ( id === selected ) {
                    return true;
                }
            }
        } else {
            // Fallback to single data-range.
            var optRange = String( $opt.data( 'range' ) || '' );

            return optRange === selected;
        }

        return false;
    }

    // ---------- Shared UI helpers ----------

    // Make a simple localization helper for a given global object.
    // Example: makeL10nHelper( 'pctMixingHelperL10n' )
    function makeL10nHelper( objectName ) {
        return function( key, fallback ) {
            var source = window[ objectName ] || {};

            if ( typeof source[ key ] === 'string' ) {
                return source[ key ];
            }

            return fallback;
        };
    }

    // Expose helpers.
    window.pctColorUtils.hexToRgb           = hexToRgb;
    window.pctColorUtils.rgbToHex           = rgbToHex;
    window.pctColorUtils.mixColors          = mixColors;
    window.pctColorUtils.textColorForHex    = textColorForHex;
    window.pctColorUtils.optionMatchesRange = optionMatchesRange;
    window.pctColorUtils.makeL10nHelper     = makeL10nHelper;
    window.pctColorUtils.gradientCss        = gradientCss;

}( window ) );
