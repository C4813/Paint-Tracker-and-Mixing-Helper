jQuery( function( $ ) {

    var pctL10n           = window.pctColorUtils.makeL10nHelper( 'pctShadeHelperL10n' );
    var closeAllDropdowns = window.pctColorUtils.closeAllDropdowns;

    // ---------- Colour helpers (shared via pct-color-utils.js) ----------

    var hexToRgb        = window.pctColorUtils.hexToRgb;
    var mixColors       = window.pctColorUtils.mixColors;
    var textColorForHex = window.pctColorUtils.textColorForHex;

    $( document ).on( 'click', function() {
        closeAllDropdowns();
    } );

    function filterPaintOptions( $column, rangeId ) {
        var $dropdown = $column.find( '.pct-mix-dropdown' );
        var $list     = $dropdown.find( '.pct-mix-list' );
        var $options  = $list.find( '.pct-mix-option' );

        if ( ! rangeId ) {
            $options.show();
        } else {
            var selected = String( rangeId );

            $options.each( function() {
                var $opt       = $( this );
                var shouldShow = window.pctColorUtils.optionMatchesRange( $opt, selected );

                $opt.toggle( shouldShow );
            } );
        }

        $dropdown.find( '.pct-mix-value' ).val( '' );
        $dropdown.attr( 'data-hex', '' );
        $list.find( '.pct-mix-option' ).removeClass( 'is-selected' );
        $dropdown.find( '.pct-mix-trigger-label' ).text(
            pctL10n( 'selectPaint', 'Select a paint' )
        );
        $dropdown.find( '.pct-mix-trigger-swatch' ).css( 'background-color', 'transparent' );
    }

    // ---------- Shade helper dropdowns ----------

    function initPaintDropdown( $dropdown ) {
        var $trigger = $dropdown.find( '.pct-mix-trigger' );
        var $list    = $dropdown.find( '.pct-mix-list' );
        var $hidden  = $dropdown.find( '.pct-mix-value' );
        var $label   = $dropdown.find( '.pct-mix-trigger-label' );
        var $swatch  = $dropdown.find( '.pct-mix-trigger-swatch' );

        $trigger.on( 'click', function( e ) {
            e.preventDefault();
            e.stopPropagation();

            var isOpen = $dropdown.hasClass( 'pct-mix-open' );

            closeAllDropdowns();

            if ( ! isOpen ) {
                $dropdown.addClass( 'pct-mix-open' );
                $list.removeAttr( 'hidden' );
            }
        } );

        $list.on( 'click', '.pct-mix-option:visible', function( e ) {
            e.preventDefault();
            e.stopPropagation();

            var $opt  = $( this );
            var hex   = $opt.data( 'hex' ) || '';
            var label = $opt.data( 'label' ) || '';

            $list.find( '.pct-mix-option' ).removeClass( 'is-selected' );
            $opt.addClass( 'is-selected' );

            $hidden.val( hex );
            $dropdown.attr( 'data-hex', hex );

            if ( label ) {
                $label.text( label );
            }

            if ( $swatch.length ) {
                if ( hex ) {
                    $swatch.css( 'background-color', hex );
                } else {
                    $swatch.css( 'background-color', 'transparent' );
                }
            }

            closeAllDropdowns();

            var $shadeContainer = $dropdown.closest( '.pct-shade-container' );

            if ( $shadeContainer.length ) {
                updateShadeScale( $shadeContainer );
            }
        } );
    }

    function initRangeDropdown( $dropdown ) {
        var $trigger = $dropdown.find( '.pct-mix-trigger' );
        var $list    = $dropdown.find( '.pct-mix-list' );
        var $hidden  = $dropdown.find( '.pct-mix-range-value' );
        var $label   = $dropdown.find( '.pct-mix-trigger-label' );

        $trigger.on( 'click', function( e ) {
            e.preventDefault();
            e.stopPropagation();

            var isOpen = $dropdown.hasClass( 'pct-mix-open' );

            closeAllDropdowns();

            if ( ! isOpen ) {
                $dropdown.addClass( 'pct-mix-open' );
                $list.removeAttr( 'hidden' );
            }
        } );

        $list.on( 'click', '.pct-mix-range-option', function( e ) {
            e.preventDefault();
            e.stopPropagation();

            var $opt    = $( this );
            var rangeId = $opt.data( 'range' );
            var label   = $opt.find( '.pct-mix-option-label' ).text() || '';

            $list.find( '.pct-mix-range-option' ).removeClass( 'is-selected' );
            $opt.addClass( 'is-selected' );

            $hidden.val( rangeId );

            if ( label ) {
                $label.text( label );
            }

            closeAllDropdowns();

            var $column = $dropdown.closest( '.pct-mix-column' );
            filterPaintOptions( $column, rangeId );

            var $shadeContainer = $dropdown.closest( '.pct-shade-container' );

            if ( $shadeContainer.length ) {
                updateShadeScale( $shadeContainer );
            }
        } );
    }

    // ---------- Extra colour helpers for shade logic ----------

    function rgbToHsl( r, g, b ) {
        r /= 255;
        g /= 255;
        b /= 255;

        var max = Math.max( r, g, b );
        var min = Math.min( r, g, b );
        var h, s;
        var l = ( max + min ) / 2;

        if ( max === min ) {
            h = 0;
            s = 0;
        } else {
            var d = max - min;

            s = l > 0.5 ? d / ( 2 - max - min ) : d / ( max + min );

            switch ( max ) {
                case r:
                    h = ( g - b ) / d + ( g < b ? 6 : 0 );
                    break;
                case g:
                    h = ( b - r ) / d + 2;
                    break;
                case b:
                    h = ( r - g ) / d + 4;
                    break;
            }

            h = h * 60;
        }

        return { h: h, s: s, l: l };
    }

    function hueDistanceDeg( h1, h2 ) {
        var d = Math.abs( h1 - h2 );

        return Math.min( d, 360 - d );
    }

    function updateShadeScale( $container ) {
        var $shadeHelper = $container.find( '.pct-shade-helper' );

        if ( ! $shadeHelper.length ) {
            return;
        }

        var $shadeColumn = $shadeHelper.find( '.pct-mix-column-shade' );
        var $scales      = $shadeHelper.find( '.pct-shade-scale' );

        if ( ! $shadeColumn.length || ! $scales.length ) {
            return;
        }

        var $paintDropdown = $shadeColumn.find( '.pct-mix-dropdown-shade' );
        var baseHex        = $paintDropdown.find( '.pct-mix-value' ).val() || '';

        function renderEmpty( $scale, key, fallback ) {
            $scale.html(
                '<p class="pct-shade-empty">' +
                    pctL10n( key, fallback ) +
                '</p>'
            );
        }

        if ( ! baseHex ) {
            $scales.each( function() {
                renderEmpty(
                    $( this ),
                    'selectPaint',
                    'Select a paint to see lighter and darker mixes.'
                );
            } );
            return;
        }

        var baseRgb = hexToRgb( baseHex );

        if ( ! baseRgb ) {
            $scales.each( function() {
                renderEmpty(
                    $( this ),
                    'invalidHex',
                    'This colour has an invalid hex value.'
                );
            } );
            return;
        }

        var baseLum = ( 0.299 * baseRgb.r + 0.587 * baseRgb.g + 0.114 * baseRgb.b ) / 255;
        var baseHsl = rgbToHsl( baseRgb.r, baseRgb.g, baseRgb.b );

        var $selectedOption = $paintDropdown.find( '.pct-mix-option.is-selected' ).first();

        if ( ! $selectedOption.length ) {
            $paintDropdown.find( '.pct-mix-option' ).each( function() {
                var $opt  = $( this );
                var optHex = ( $opt.data( 'hex' ) || '' ).toString();

                if ( optHex && optHex.toLowerCase() === baseHex.toLowerCase() ) {
                    $selectedOption = $opt;
                    return false;
                }
            } );
        }

        var baseLabel = '';
        var baseType  = '';

        if ( $selectedOption.length ) {
            baseLabel = $selectedOption.data( 'label' ) || '';
            baseType  = ( $selectedOption.data( 'base-type' ) || '' ).toString();
        }

        var $rangeDropdown = $shadeHelper.find( '.pct-mix-range-dropdown-shade' );
        var activeRangeId  = '';

        if ( $rangeDropdown.length ) {
            activeRangeId = String(
                $rangeDropdown.find( '.pct-mix-range-value' ).val() || ''
            );
        }

        var MAX_HUE_DIFF_DEG = 40;
        var SAT_NEUTRAL      = 0.1;
        var LUM_EPS_DARK     = 0.01;
        var LUM_EPS_LIGHT    = 0.03;
        var GREY_DARK_SLACK  = 0.03;

        var baseIsNeutral =
            ( baseHsl.s <= SAT_NEUTRAL ) ||
            ( baseLum < 0.25 ) ||
            ( baseLum > 0.85 );

        var baseIsPureBlack = baseIsNeutral && baseLum < 0.10;
        var baseIsPureWhite = baseIsNeutral && baseLum > 0.90;

        var darkerNeutral  = [];
        var darkerSameHue  = [];
        var lighterNeutral = [];
        var lighterSameHue = [];

        function collectCandidates( ignoreBaseType ) {
            darkerNeutral  = [];
            darkerSameHue  = [];
            lighterNeutral = [];
            lighterSameHue = [];

            $paintDropdown.find( '.pct-mix-option' ).each( function() {
                var $opt = $( this );

                var optHex = ( $opt.data( 'hex' ) || '' ).toString();
                if ( ! optHex ) {
                    return;
                }

                if ( optHex.toLowerCase() === baseHex.toLowerCase() ) {
                    return;
                }

                if ( activeRangeId ) {
                    var inRange = window.pctColorUtils.optionMatchesRange( $opt, activeRangeId );
                    if ( ! inRange ) {
                        return;
                    }
                }

                if ( baseType && ! ignoreBaseType ) {
                    var optBaseType = ( $opt.data( 'base-type' ) || '' ).toString();

                    if ( optBaseType && optBaseType !== baseType ) {
                        return;
                    }
                }

                var excludeShading = $opt.data( 'excludeShading' );
                if ( excludeShading === 1 || excludeShading === '1' ) {
                    return;
                }

                var rgb = hexToRgb( optHex );

                if ( ! rgb ) {
                    return;
                }

                var lum   = ( 0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b ) / 255;
                var hsl   = rgbToHsl( rgb.r, rgb.g, rgb.b );
                var label = $opt.data( 'label' ) || '';

                var isGrey = ( hsl.s <= SAT_NEUTRAL );

                var isNeutralLike =
                    isGrey ||
                    ( lum < 0.25 ) ||
                    ( lum > 0.85 );

                var hueDiff = 0;

                if ( ! isNeutralLike && ! baseIsNeutral ) {
                    hueDiff = hueDistanceDeg( hsl.h, baseHsl.h );
                }

                var isDarkerCandidate  = ( lum < baseLum - LUM_EPS_DARK );
                var isLighterCandidate = ( lum > baseLum + LUM_EPS_LIGHT );

                if ( baseIsNeutral && ! isNeutralLike ) {
                    return;
                }

                if ( baseIsPureBlack ) {
                    isDarkerCandidate = false;
                } else if ( baseIsPureWhite ) {
                    isLighterCandidate = false;
                } else if ( ! isDarkerCandidate && ! isLighterCandidate && isGrey ) {
                    if ( lum <= baseLum + GREY_DARK_SLACK ) {
                        isDarkerCandidate = true;
                    }
                }

                if ( ! isDarkerCandidate && ! isLighterCandidate ) {
                    return;
                }

                var candidate = {
                    hex: optHex,
                    lum: lum,
                    label: label,
                    hueDiff: hueDiff,
                    isNeutral: isNeutralLike,
                    isGrey: isGrey
                };

                if ( isDarkerCandidate ) {
                    if ( isNeutralLike ) {
                        darkerNeutral.push( candidate );
                    } else if ( hueDiff <= MAX_HUE_DIFF_DEG ) {
                        darkerSameHue.push( candidate );
                    }
                }

                if ( isLighterCandidate ) {
                    if ( isNeutralLike ) {
                        lighterNeutral.push( candidate );
                    } else if ( hueDiff <= MAX_HUE_DIFF_DEG ) {
                        lighterSameHue.push( candidate );
                    }
                }
            } );
        }

        collectCandidates( false );

        if (
            baseType &&
            ! darkerNeutral.length &&
            ! darkerSameHue.length &&
            ! lighterNeutral.length &&
            ! lighterSameHue.length
        ) {
            collectCandidates( true );
        }

        function pickDarkest( list ) {
            var best = null;

            list.forEach( function( c ) {
                if ( ! best || c.lum < best.lum ) {
                    best = c;
                }
            } );

            return best;
        }

        function pickLightest( list ) {
            var best = null;

            list.forEach( function( c ) {
                if ( ! best || c.lum > best.lum ) {
                    best = c;
                }
            } );

            return best;
        }

        function pickDarkestGreyFirst( list ) {
            var greys = [];

            list.forEach( function( c ) {
                if ( c.isGrey ) {
                    greys.push( c );
                }
            } );

            if ( greys.length ) {
                return pickDarkest( greys );
            }

            return pickDarkest( list );
        }

        function pickLightestGreyFirst( list ) {
            var greys = [];

            list.forEach( function( c ) {
                if ( c.isGrey ) {
                    greys.push( c );
                }
            } );

            if ( greys.length ) {
                return pickLightest( greys );
            }

            return pickLightest( list );
        }

        function computeAnchors( mode ) {
            var darkest  = null;
            var lightest = null;

            if ( mode === 'relaxed' ) {
                if ( baseIsNeutral ) {
                    if ( darkerNeutral.length ) {
                        darkest = pickDarkest( darkerNeutral );
                    }

                    if ( lighterNeutral.length ) {
                        lightest = pickLightest( lighterNeutral );
                    }
                } else {
                    if ( darkerSameHue.length ) {
                        darkest = pickDarkest( darkerSameHue );
                    } else if ( darkerNeutral.length ) {
                        darkest = pickDarkest( darkerNeutral );
                    }

                    if ( lighterSameHue.length ) {
                        lightest = pickLightest( lighterSameHue );
                    } else if ( lighterNeutral.length ) {
                        lightest = pickLightest( lighterNeutral );
                    }
                }
            } else {
                if ( baseIsNeutral ) {
                    if ( darkerNeutral.length ) {
                        darkest = pickDarkestGreyFirst( darkerNeutral );
                    }

                    if ( lighterNeutral.length ) {
                        lightest = pickLightestGreyFirst( lighterNeutral );
                    }
                } else {
                    if ( darkerNeutral.length ) {
                        darkest = pickDarkestGreyFirst( darkerNeutral );
                    } else if ( darkerSameHue.length ) {
                        darkest = pickDarkest( darkerSameHue );
                    }

                    if ( lighterNeutral.length ) {
                        lightest = pickLightestGreyFirst( lighterNeutral );
                    } else if ( lighterSameHue.length ) {
                        lightest = pickLightest( lighterSameHue );
                    }
                }
            }

            return { darkest: darkest, lightest: lightest };
        }

        var anchorsStrict  = computeAnchors( 'strict' );
        var anchorsRelaxed = computeAnchors( 'relaxed' );

        var strictHasDarker   = !! anchorsStrict.darkest;
        var relaxedHasDarker  = !! anchorsRelaxed.darkest;
        var strictHasLighter  = !! anchorsStrict.lightest;
        var relaxedHasLighter = !! anchorsRelaxed.lightest;

        var mergeDarker =
            anchorsStrict.darkest &&
            anchorsRelaxed.darkest &&
            anchorsStrict.darkest.hex.toLowerCase() === anchorsRelaxed.darkest.hex.toLowerCase();

        var mergeLighter =
            anchorsStrict.lightest &&
            anchorsRelaxed.lightest &&
            anchorsStrict.lightest.hex.toLowerCase() === anchorsRelaxed.lightest.hex.toLowerCase();

        function buildLadderHtmlForMode( mode ) {
            var anchors = ( mode === 'relaxed' ) ? anchorsRelaxed : anchorsStrict;
            var darkest  = anchors.darkest;
            var lightest = anchors.lightest;

            var hasDarker  = !! darkest;
            var hasLighter = !! lightest;

            if ( ! hasDarker && ! hasLighter ) {
                return (
                    '<p class="pct-shade-empty">' +
                        pctL10n(
                            'notEnoughPaints',
                            'Not enough paints in this selection to build a shade ladder.'
                        ) +
                    '</p>'
                );
            }

            var darkerRatios = [
                [ 1, 3 ],
                [ 1, 1 ],
                [ 3, 1 ]
            ];
            var lighterRatios = [
                [ 3, 1 ],
                [ 1, 1 ],
                [ 1, 3 ]
            ];

            var rows = [];

            if ( hasDarker ) {
                darkerRatios.forEach( function( pair ) {
                    var mixed = mixColors( baseHex, darkest.hex, pair[0], pair[1] );

                    if ( ! mixed ) {
                        return;
                    }

                    rows.push( {
                        type: 'darker',
                        ratio: pair,
                        hex: mixed.toUpperCase(),
                        otherLabel: darkest.label || ''
                    } );
                } );

                rows.push( {
                    type: 'arrow-up',
                    ratio: null,
                    hex: '',
                    otherLabel: '',
                    arrowDir: 'up'
                } );
            }

            rows.push( {
                type: 'base',
                ratio: null,
                hex: baseHex.toUpperCase(),
                otherLabel: ''
            } );

            if ( hasLighter ) {
                rows.push( {
                    type: 'arrow-down',
                    ratio: null,
                    hex: '',
                    otherLabel: '',
                    arrowDir: 'down'
                } );

                lighterRatios.forEach( function( pair ) {
                    var mixed = mixColors( baseHex, lightest.hex, pair[0], pair[1] );

                    if ( ! mixed ) {
                        return;
                    }

                    rows.push( {
                        type: 'lighter',
                        ratio: pair,
                        hex: mixed.toUpperCase(),
                        otherLabel: lightest.label || ''
                    } );
                } );
            }

            if ( ! rows.length ) {
                return (
                    '<p class="pct-shade-empty">' +
                        pctL10n(
                            'unableToGenerate',
                            'Unable to generate mixes for this colour.'
                        ) +
                    '</p>'
                );
            }

            var infoHtml      = '';
            var showNoDarker  = false;
            var showNoLighter = false;

            if ( mode === 'strict' ) {
                showNoDarker  = ! strictHasDarker;
                showNoLighter = ! strictHasLighter;
            } else {
                showNoDarker  = ! relaxedHasDarker && strictHasDarker;
                showNoLighter = ! relaxedHasLighter && strictHasLighter;
            }

            if ( showNoDarker ) {
                infoHtml += '<p class="pct-shade-empty">' +
                    pctL10n(
                        'noDarker',
                        'Not enough darker paints in this selection to generate darker mixes.'
                    ) +
                    '</p>';
            }

            if ( showNoLighter ) {
                infoHtml += '<p class="pct-shade-empty">' +
                    pctL10n(
                        'noLighter',
                        'Not enough lighter paints in this selection to generate lighter mixes.'
                    ) +
                    '</p>';
            }

            var html = '';

            rows.forEach( function( row ) {
                var mainHtml    = '';
                var mergedClass = '';
                var extraClass  = '';
                var styleInline = '';

                if ( row.type === 'arrow-up' || row.type === 'arrow-down' ) {
                    var symbol = ( row.type === 'arrow-up' ) ? '▲' : '▼';

                    mainHtml =
                        '<div class="pct-shade-line">' +
                            '<span class="pct-shade-arrow-icon">' + symbol + '</span>' +
                        '</div>';

                    extraClass  = ' pct-shade-row-arrow';
                    styleInline = 'color: #000;';
                    row.hex     = '';
                } else if ( row.type === 'base' ) {
                    var baseLine = baseLabel || baseHex.toUpperCase();

                    mainHtml = '<div class="pct-shade-line">' + baseLine + '</div>';

                    var textColorBase = textColorForHex( row.hex );
                    styleInline = 'background-color: ' + row.hex + '; color: ' + textColorBase + ';';
                } else if ( row.ratio ) {
                    var other      = row.otherLabel || '';
                    var baseLine2  = baseLabel || baseHex.toUpperCase();
                    var baseParts  = row.ratio[0];
                    var otherParts = row.ratio[1];

                    mainHtml =
                        '<div class="pct-shade-line">' +
                            other + ' ×' + otherParts +
                        '</div>' +
                        '<div class="pct-shade-line">' +
                            baseLine2 + ' ×' + baseParts +
                        '</div>';

                    var textColorMix = textColorForHex( row.hex );
                    styleInline = 'background-color: ' + row.hex + '; color: ' + textColorMix + ';';
                } else {
                    var fallbackText = baseLabel || baseHex.toUpperCase();

                    mainHtml = '<div class="pct-shade-line">' + fallbackText + '</div>';

                    var textColorFallback = textColorForHex( row.hex );
                    styleInline = 'background-color: ' + row.hex + '; color: ' + textColorFallback + ';';
                }

                if (
                    ( mergeLighter && ( row.type === 'lighter' || row.type === 'arrow-down' ) ) ||
                    ( mergeDarker  && ( row.type === 'darker' || row.type === 'arrow-up' ) )
                ) {
                    mergedClass = ' pct-shade-row-merged';
                }

                html +=
                    '<div class="pct-shade-row pct-shade-row-' + row.type + mergedClass + extraClass + '"' +
                        ' style="' + styleInline + '">' +
                        '<div class="pct-shade-row-main">' + mainHtml + '</div>' +
                        '<div class="pct-shade-row-hex">' + ( row.hex || '' ) + '</div>' +
                    '</div>';
            } );

            return html + infoHtml;
        }

        $scales.each( function() {
            var $scale = $( this );
            var mode   = $scale.data( 'hueMode' );

            if ( mode !== 'relaxed' ) {
                mode = 'strict';
            }

            $scale.html( buildLadderHtmlForMode( mode ) );
        } );
    }

    // ---------- Init shade helper dropdowns + default hex ----------

    $( '.pct-shade-container .pct-mix-dropdown' ).each( function() {
        initPaintDropdown( $( this ) );
    } );

    $( '.pct-shade-container .pct-mix-range-dropdown' ).each( function() {
        initRangeDropdown( $( this ) );
    } );

    $( '.pct-shade-container' ).each( function() {
        var $container = $( this );
        var defaultHex = ( $container.data( 'default-shade-hex' ) || '' ).toString().trim();
        var defaultId  = parseInt( $container.data( 'default-shade-id' ), 10 );

        if ( ! isFinite( defaultId ) ) {
            defaultId = 0;
        }

        if ( ! defaultId && ! defaultHex ) {
            updateShadeScale( $container );
            return;
        }

        if ( defaultHex ) {
            if ( defaultHex.charAt( 0 ) !== '#' ) {
                defaultHex = '#' + defaultHex;
            }
            defaultHex = defaultHex.toLowerCase();
        }

        var $shadeColumn = $container.find( '.pct-mix-column-shade' );

        if ( ! $shadeColumn.length ) {
            updateShadeScale( $container );
            return;
        }

        var $paintDropdown = $shadeColumn.find( '.pct-mix-dropdown-shade' );
        var $options       = $paintDropdown.find( '.pct-mix-option' );

        if ( ! $options.length ) {
            updateShadeScale( $container );
            return;
        }

        var $match = null;

        if ( defaultId ) {
            $options.each( function() {
                var optId = parseInt( $( this ).data( 'id' ), 10 );

                if ( ! isFinite( optId ) ) {
                    optId = 0;
                }

                if ( optId === defaultId ) {
                    $match = $( this );
                    return false;
                }
            } );
        }

        if ( ! $match && defaultHex ) {
            $options.each( function() {
                var hex = ( $( this ).data( 'hex' ) || '' ).toString().toLowerCase();

                if ( hex === defaultHex ) {
                    $match = $( this );
                    return false;
                }
            } );
        }

        if ( ! $match ) {
            updateShadeScale( $container );
            return;
        }

        var rangeId        = $match.data( 'range' ) || '';
        var $rangeDropdown = $shadeColumn.find( '.pct-mix-range-dropdown-shade' );

        if ( $rangeDropdown.length ) {
            var $rangeList   = $rangeDropdown.find( '.pct-mix-list' );
            var $rangeHidden = $rangeDropdown.find( '.pct-mix-range-value' );
            var $rangeLabel  = $rangeDropdown.find( '.pct-mix-trigger-label' );

            $rangeList.find( '.pct-mix-range-option' ).removeClass( 'is-selected' );

            var selector = '.pct-mix-range-option';

            if ( rangeId !== '' ) {
                selector += '[data-range="' + String( rangeId ) + '"]';
            } else {
                selector += '[data-range=""]';
            }

            var $rangeOpt = $rangeList.find( selector ).first();

            if ( $rangeOpt.length ) {
                $rangeOpt.addClass( 'is-selected' );
                $rangeHidden.val( rangeId );

                var rangeText = $rangeOpt.find( '.pct-mix-option-label' ).text() || '';

                if ( rangeText ) {
                    $rangeLabel.text( rangeText );
                }

                filterPaintOptions( $shadeColumn, rangeId );

                $options = $paintDropdown.find( '.pct-mix-option' );
                var newMatch = null;

                if ( defaultId ) {
                    $options.each( function() {
                        var optId = parseInt( $( this ).data( 'id' ), 10 );

                        if ( ! isFinite( optId ) ) {
                            optId = 0;
                        }

                        if ( optId === defaultId ) {
                            newMatch = $( this );
                            return false;
                        }
                    } );
                }

                if ( ! newMatch && defaultHex ) {
                    $options.each( function() {
                        var hex = ( $( this ).data( 'hex' ) || '' ).toString().toLowerCase();

                        if ( hex === defaultHex ) {
                            newMatch = $( this );
                            return false;
                        }
                    } );
                }

                if ( newMatch ) {
                    $match = newMatch;
                }
            }
        }

        if ( $match ) {
            var hexVal   = ( $match.data( 'hex' ) || defaultHex || '' ).toString();
            var label    = $match.data( 'label' ) || '';
            var $hidden  = $paintDropdown.find( '.pct-mix-value' );
            var $labelEl = $paintDropdown.find( '.pct-mix-trigger-label' );
            var $swatch  = $paintDropdown.find( '.pct-mix-trigger-swatch' );

            $paintDropdown.attr( 'data-hex', hexVal );
            $hidden.val( hexVal );
            $paintDropdown.find( '.pct-mix-option' ).removeClass( 'is-selected' );
            $match.addClass( 'is-selected' );

            if ( label ) {
                $labelEl.text( label );
            }

            if ( $swatch.length && hexVal ) {
                $swatch.css( 'background-color', hexVal );
            }
        }

        updateShadeScale( $container );
    } );

} );
