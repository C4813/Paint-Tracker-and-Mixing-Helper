jQuery(function($) {

    // ---------- L10n helper ----------

    function pctL10n(key, fallback) {
        if (window.pctShadeHelperL10n && typeof window.pctShadeHelperL10n[key] === 'string') {
            return window.pctShadeHelperL10n[key];
        }
        return fallback;
    }

    // ---------- Global config from PHP ----------

    // 'strict' or 'relaxed', as set in Info & Settings → Shade helper hue behaviour
    var shadeHueMode = (window.pctShadeHelperL10n && window.pctShadeHelperL10n.hueMode) || 'strict';
    
    // ---------- Colour helpers (shared via pct-color-utils.js) ----------

    var hexToRgb        = window.pctColorUtils.hexToRgb;
    var rgbToHex        = window.pctColorUtils.rgbToHex;
    var mixColors       = window.pctColorUtils.mixColors;
    var textColorForHex = window.pctColorUtils.textColorForHex;

    // ---------- Shared dropdown helpers (shade helper) ----------

    function closeAllDropdowns() {
        $('.pct-mix-dropdown, .pct-mix-range-dropdown').each(function() {
            var $dd = $(this);
            $dd.removeClass('pct-mix-open');
            $dd.find('.pct-mix-list')
               .attr('hidden', 'hidden');
        });
    }

    $(document).on('click', function() {
        closeAllDropdowns();
    });

    function filterPaintOptions($column, rangeId) {
        var $dropdown = $column.find('.pct-mix-dropdown');
        var $list     = $dropdown.find('.pct-mix-list');
        var $options  = $list.find('.pct-mix-option');

        if (!rangeId) {
            // All ranges
            $options.show();
        } else {
            var selected = String(rangeId);
            $options.each(function() {
                var $opt = $(this);
                var shouldShow = window.pctColorUtils.optionMatchesRange($opt, selected);
                $opt.toggle(shouldShow);
            });
        }

        // Reset current paint selection when range changes
        $dropdown.find('.pct-mix-value').val('');
        $dropdown.attr('data-hex', '');
        $list.find('.pct-mix-option').removeClass('is-selected');
        $dropdown.find('.pct-mix-trigger-label').text(
            pctL10n('selectPaint', 'Select a paint')
        );
        $dropdown.find('.pct-mix-trigger-swatch').css('background-color', 'transparent');
    }

    // ---------- Shade helper dropdowns ----------

    function initPaintDropdown($dropdown) {
        var $trigger = $dropdown.find('.pct-mix-trigger');
        var $list    = $dropdown.find('.pct-mix-list');
        var $hidden  = $dropdown.find('.pct-mix-value');
        var $label   = $dropdown.find('.pct-mix-trigger-label');
        var $swatch  = $dropdown.find('.pct-mix-trigger-swatch');

        // Open/close list
        $trigger.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var isOpen = $dropdown.hasClass('pct-mix-open');
            closeAllDropdowns();
            if (!isOpen) {
                $dropdown.addClass('pct-mix-open');
                $list.removeAttr('hidden');
            }
        });

        // Select an option
        $list.on('click', '.pct-mix-option:visible', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $opt  = $(this);
            var hex   = $opt.data('hex') || '';
            var label = $opt.data('label') || '';

            $list.find('.pct-mix-option').removeClass('is-selected');
            $opt.addClass('is-selected');

            $hidden.val(hex);
            $dropdown.attr('data-hex', hex);

            if (label) {
                $label.text(label);
            }
            if ($swatch.length) {
                if (hex) {
                    $swatch.css('background-color', hex);
                } else {
                    $swatch.css('background-color', 'transparent');
                }
            }

            closeAllDropdowns();

            var $shadeContainer = $dropdown.closest('.pct-shade-container');
            if ($shadeContainer.length) {
                updateShadeScale($shadeContainer);
            }
        });
    }

    function initRangeDropdown($dropdown) {
        var $trigger = $dropdown.find('.pct-mix-trigger');
        var $list    = $dropdown.find('.pct-mix-list');
        var $hidden  = $dropdown.find('.pct-mix-range-value');
        var $label   = $dropdown.find('.pct-mix-trigger-label');

        // Open/close list
        $trigger.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var isOpen = $dropdown.hasClass('pct-mix-open');
            closeAllDropdowns();
            if (!isOpen) {
                $dropdown.addClass('pct-mix-open');
                $list.removeAttr('hidden');
            }
        });

        // Select a range
        $list.on('click', '.pct-mix-range-option', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $opt    = $(this);
            var rangeId = $opt.data('range'); // may be "" for All
            var label   = $opt.find('.pct-mix-option-label').text() || '';

            $list.find('.pct-mix-range-option').removeClass('is-selected');
            $opt.addClass('is-selected');

            $hidden.val(rangeId);
            if (label) {
                $label.text(label);
            }

            closeAllDropdowns();

            // Filter paints in this column based on range
            var $column = $dropdown.closest('.pct-mix-column');
            filterPaintOptions($column, rangeId);

            var $shadeContainer = $dropdown.closest('.pct-shade-container');
            if ($shadeContainer.length) {
                updateShadeScale($shadeContainer);
            }
        });
    }
    
    // ---------- Extra colour helpers for shade logic ----------

    function rgbToHsl(r, g, b) {
        r /= 255;
        g /= 255;
        b /= 255;

        var max = Math.max(r, g, b);
        var min = Math.min(r, g, b);
        var h, s;
        var l = (max + min) / 2;

        if (max === min) {
            // achromatic (grey)
            h = 0;
            s = 0;
        } else {
            var d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);

            switch (max) {
                case r:
                    h = (g - b) / d + (g < b ? 6 : 0);
                    break;
                case g:
                    h = (b - r) / d + 2;
                    break;
                case b:
                    h = (r - g) / d + 4;
                    break;
            }
            h = h * 60;
        }

        return { h: h, s: s, l: l };
    }

    function hexToHslSafe(hex) {
        var rgb = hexToRgb(hex);
        if (!rgb) {
            return null;
        }
        return rgbToHsl(rgb.r, rgb.g, rgb.b);
    }

    function hueDistanceDeg(h1, h2) {
        var d = Math.abs(h1 - h2);
        return Math.min(d, 360 - d);
    }

    function updateShadeScale($container) {
        var $shadeHelper = $container.find('.pct-shade-helper');
        if (!$shadeHelper.length) {
            return;
        }
    
        var $shadeColumn = $shadeHelper.find('.pct-mix-column-shade');
        var $scales      = $shadeHelper.find('.pct-shade-scale');
        if (!$shadeColumn.length || !$scales.length) {
            return;
        }
    
        var $paintDropdown = $shadeColumn.find('.pct-mix-dropdown-shade');
        var baseHex        = $paintDropdown.find('.pct-mix-value').val() || '';
    
        function renderEmpty($scale, key, fallback) {
            $scale.html(
                '<p class="pct-shade-empty">' +
                    pctL10n(key, fallback) +
                '</p>'
            );
        }
    
        // No base colour selected yet: show the empty message in both ladders
        if (!baseHex) {
            $scales.each(function () {
                renderEmpty($(this), 'selectPaint', 'Select a paint to see lighter and darker mixes.');
            });
            return;
        }
    
        var baseRgb = hexToRgb(baseHex);
        if (!baseRgb) {
            $scales.each(function () {
                renderEmpty($(this), 'invalidHex', 'This colour has an invalid hex value.');
            });
            return;
        }
    
        var baseLum = (0.299 * baseRgb.r + 0.587 * baseRgb.g + 0.114 * baseRgb.b) / 255;
        var baseHsl = rgbToHsl(baseRgb.r, baseRgb.g, baseRgb.b);
    
        // Figure out which paint option is the base, and sync activeRangeId
        var $selectedOption = $paintDropdown.find('.pct-mix-option.is-selected').first();
        if (!$selectedOption.length) {
            // Fallback: match by hex if the dropdown hasn't tracked the selection yet
            $paintDropdown.find('.pct-mix-option').each(function () {
                var $opt = $(this);
                var optHex = ($opt.data('hex') || '').toString();
                if (optHex && optHex.toLowerCase() === baseHex.toLowerCase()) {
                    $selectedOption = $opt;
                    return false;
                }
            });
        }
    
        var baseLabel = '';
        var baseType  = '';
        if ($selectedOption.length) {
            baseLabel = $selectedOption.data('label') || '';
            baseType  = ($selectedOption.data('base-type') || '').toString();
            var baseRangeId = $selectedOption.data('range');
            if (typeof baseRangeId === 'undefined' || baseRangeId === null) {
                activeRangeId = '';
            } else {
                activeRangeId = String(baseRangeId);
            }
        }
    
        // Thresholds for anchors
        var MAX_HUE_DIFF_DEG = 40;
        var SAT_NEUTRAL      = 0.05;
        var LUM_EPS          = 0.03;
    
        var baseIsNeutral =
            (baseHsl.s <= SAT_NEUTRAL) ||
            (baseLum < 0.25) ||
            (baseLum > 0.85);
    
        var darkerNeutral  = [];
        var darkerSameHue  = [];
        var lighterNeutral = [];
        var lighterSameHue = [];
    
        // Collect candidate darkeners/lighteners
        $paintDropdown.find('.pct-mix-option').each(function () {
            var $opt = $(this);
    
            var optHex = ($opt.data('hex') || '').toString();
            if (!optHex) {
                return;
            }
            if (optHex.toLowerCase() === baseHex.toLowerCase()) {
                return;
            }
    
            // Respect currently active range, if any
            if (activeRangeId) {
                var optRangeId = $opt.data('range');
                if (String(optRangeId || '') !== String(activeRangeId)) {
                    return;
                }
            }
            
            // Respect base type: don't mix acrylic/enamel/oil
            if (baseType) {
                var optBaseType = ($opt.data('base-type') || '').toString();
                if (!optBaseType || optBaseType !== baseType) {
                    return;
                }
            }
    
            var rgb = hexToRgb(optHex);
            if (!rgb) {
                return;
            }
    
            var lum   = (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255;
            var hsl   = rgbToHsl(rgb.r, rgb.g, rgb.b);
            var label = $opt.data('label') || '';
    
            var isNeutralLike =
                (hsl.s <= SAT_NEUTRAL) ||
                (lum < 0.25) ||
                (lum > 0.85);
    
            var hueDiff = 0;
            if (!isNeutralLike && !baseIsNeutral) {
                hueDiff = hueDistanceDeg(hsl.h, baseHsl.h);
            }
    
            var isDarkerCandidate  = (lum < baseLum - LUM_EPS);
            var isLighterCandidate = (lum > baseLum + LUM_EPS);
    
            // If base is neutral, only neutral-ish anchors make sense
            if (baseIsNeutral && !isNeutralLike) {
                return;
            }
    
            if (!isDarkerCandidate && !isLighterCandidate) {
                return;
            }
    
            var candidate = {
                hex: optHex,
                lum: lum,
                label: label,
                hueDiff: hueDiff,
                isNeutral: isNeutralLike
            };
    
            if (isDarkerCandidate) {
                if (isNeutralLike) {
                    darkerNeutral.push(candidate);
                } else if (hueDiff <= MAX_HUE_DIFF_DEG) {
                    darkerSameHue.push(candidate);
                }
            }
    
            if (isLighterCandidate) {
                if (isNeutralLike) {
                    lighterNeutral.push(candidate);
                } else if (hueDiff <= MAX_HUE_DIFF_DEG) {
                    lighterSameHue.push(candidate);
                }
            }
        });
    
        function pickDarkest(list) {
            var best = null;
            list.forEach(function (c) {
                if (!best || c.lum < best.lum) {
                    best = c;
                }
            });
            return best;
        }
    
        function pickLightest(list) {
            var best = null;
            list.forEach(function (c) {
                if (!best || c.lum > best.lum) {
                    best = c;
                }
            });
            return best;
        }
    
        // Compute anchors for each mode so we can see when they match
        function computeAnchors(mode) {
            var darkest  = null;
            var lightest = null;
    
            if (mode === 'relaxed') {
                if (baseIsNeutral) {
                    if (darkerNeutral.length) {
                        darkest = pickDarkest(darkerNeutral);
                    }
                    if (lighterNeutral.length) {
                        lightest = pickLightest(lighterNeutral);
                    }
                } else {
                    if (darkerSameHue.length) {
                        darkest = pickDarkest(darkerSameHue);
                    } else if (darkerNeutral.length) {
                        darkest = pickDarkest(darkerNeutral);
                    }
    
                    if (lighterSameHue.length) {
                        lightest = pickLightest(lighterSameHue);
                    } else if (lighterNeutral.length) {
                        lightest = pickLightest(lighterNeutral);
                    }
                }
            } else { // strict
                if (baseIsNeutral) {
                    if (darkerNeutral.length) {
                        darkest = pickDarkest(darkerNeutral);
                    }
                    if (lighterNeutral.length) {
                        lightest = pickLightest(lighterNeutral);
                    }
                } else {
                    var allDarker  = darkerSameHue.concat(darkerNeutral);
                    var allLighter = lighterSameHue.concat(lighterNeutral);
    
                    if (allDarker.length) {
                        darkest = pickDarkest(allDarker);
                    }
                    if (allLighter.length) {
                        lightest = pickLightest(allLighter);
                    }
                }
            }
    
            return { darkest: darkest, lightest: lightest };
        }
    
        var anchorsStrict  = computeAnchors('strict');
        var anchorsRelaxed = computeAnchors('relaxed');
        
        // Which ladders actually have darker / lighter anchors?
        var strictHasDarker   = !!anchorsStrict.darkest;
        var relaxedHasDarker  = !!anchorsRelaxed.darkest;
        var strictHasLighter  = !!anchorsStrict.lightest;
        var relaxedHasLighter = !!anchorsRelaxed.lightest;
        
        var mergeDarker = anchorsStrict.darkest && anchorsRelaxed.darkest &&
            anchorsStrict.darkest.hex.toLowerCase() === anchorsRelaxed.darkest.hex.toLowerCase();
        
        var mergeLighter = anchorsStrict.lightest && anchorsRelaxed.lightest &&
            anchorsStrict.lightest.hex.toLowerCase() === anchorsRelaxed.lightest.hex.toLowerCase();
    
        // Build HTML for one ladder (strict or relaxed)
        function buildLadderHtmlForMode(mode) {
            var anchors = (mode === 'relaxed') ? anchorsRelaxed : anchorsStrict;
            var darkest  = anchors.darkest;
            var lightest = anchors.lightest;
    
            var hasDarker  = !!darkest;
            var hasLighter = !!lightest;
    
            if (!hasDarker && !hasLighter) {
                return (
                    '<p class="pct-shade-empty">' +
                        pctL10n('notEnoughPaints', 'Not enough paints in this selection to build a shade ladder.') +
                    '</p>'
                );
            }
    
            var darkerRatios = [
                [1, 3],
                [1, 1],
                [3, 1]
            ];
            var lighterRatios = [
                [3, 1],
                [1, 1],
                [1, 3]
            ];
    
            var rows = [];
    
            // Dark mixes (above the primary colour)
            if (hasDarker) {
                darkerRatios.forEach(function (pair) {
                    var mixed = mixColors(baseHex, darkest.hex, pair[0], pair[1]);
                    if (!mixed) {
                        return;
                    }
                    rows.push({
                        type: 'darker',
                        ratio: pair,
                        hex: mixed.toUpperCase(),
                        otherLabel: darkest.label || ''
                    });
                });
    
                // Arrow pointing down towards the primary colour
                rows.push({
                    type: 'arrow-up',
                    ratio: null,
                    hex: '',
                    otherLabel: '',
                    arrowDir: 'up'
                });
            }
    
            // Primary colour in the centre
            rows.push({
                type: 'base',
                ratio: null,
                hex: baseHex.toUpperCase(),
                otherLabel: ''
            });
    
            // Arrow pointing down to lighter mixes (only if we actually have them)
            if (hasLighter) {
                rows.push({
                    type: 'arrow-down',
                    ratio: null,
                    hex: '',
                    otherLabel: '',
                    arrowDir: 'down'
                });
    
                lighterRatios.forEach(function (pair) {
                    var mixed = mixColors(baseHex, lightest.hex, pair[0], pair[1]);
                    if (!mixed) {
                        return;
                    }
                    rows.push({
                        type: 'lighter',
                        ratio: pair,
                        hex: mixed.toUpperCase(),
                        otherLabel: lightest.label || ''
                    });
                });
            }
    
            if (!rows.length) {
                return (
                    '<p class="pct-shade-empty">' +
                        pctL10n('unableToGenerate', 'Unable to generate mixes for this colour.') +
                    '</p>'
                );
            }
    
            var infoHtml = '';
            var showNoDarker, showNoLighter;
    
            if (mode === 'strict') {
                // Strict column: show message whenever *strict* has none
                showNoDarker  = !strictHasDarker;
                showNoLighter = !strictHasLighter;
            } else { // relaxed
                // Relaxed column: only show if relaxed is missing it but strict has it.
                // If both are missing, the strict column already shows the message.
                showNoDarker  = !relaxedHasDarker && strictHasDarker;
                showNoLighter = !relaxedHasLighter && strictHasLighter;
            }
    
            if (showNoDarker) {
                infoHtml += '<p class="pct-shade-empty">' +
                    pctL10n('noDarker', 'Not enough darker paints in this selection to generate darker mixes.') +
                    '</p>';
            }
    
            if (showNoLighter) {
                infoHtml += '<p class="pct-shade-empty">' +
                    pctL10n('noLighter', 'Not enough lighter paints in this selection to generate lighter mixes.') +
                    '</p>';
            }
    
            var html = '';
            rows.forEach(function (row) {
                var mainHtml  = '';
                var mergedClass = '';
                var extraClass  = '';
                var styleInline = '';
    
                if (row.type === 'arrow-up' || row.type === 'arrow-down') {
                    // Small black arrows above/below the primary colour
                    var symbol = (row.type === 'arrow-up') ? '▲' : '▼';
                    mainHtml =
                        '<div class="pct-shade-line">' +
                            '<span class="pct-shade-arrow-icon">' + symbol + '</span>' +
                        '</div>';
    
                    extraClass  = ' pct-shade-row-arrow';
                    styleInline = 'color: #000;';
                    row.hex     = ''; // no hex for arrow rows
                } else if (row.type === 'base') {
                    var baseLine = baseLabel || baseHex.toUpperCase();
                    mainHtml = '<div class="pct-shade-line">' + baseLine + '</div>';
    
                    var textColorBase = textColorForHex(row.hex);
                    styleInline = 'background-color: ' + row.hex + '; color: ' + textColorBase + ';';
                } else if (row.ratio) {
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
    
                    var textColorMix = textColorForHex(row.hex);
                    styleInline = 'background-color: ' + row.hex + '; color: ' + textColorMix + ';';
                } else {
                    var fallbackText = baseLabel || baseHex.toUpperCase();
                    mainHtml = '<div class="pct-shade-line">' + fallbackText + '</div>';
    
                    var textColorFallback = textColorForHex(row.hex);
                    styleInline = 'background-color: ' + row.hex + '; color: ' + textColorFallback + ';';
                }
    
                if (
                    (mergeLighter && (row.type === 'lighter' || row.type === 'arrow-down')) ||
                    (mergeDarker  && (row.type === 'darker' || row.type === 'arrow-up'))
                ) {
                    mergedClass = ' pct-shade-row-merged';
                }
    
                html +=
                    '<div class="pct-shade-row pct-shade-row-' + row.type + mergedClass + extraClass + '"' +
                        ' style="' + styleInline + '">' +
                        '<div class="pct-shade-row-main">' + mainHtml + '</div>' +
                        '<div class="pct-shade-row-hex">' + (row.hex || '') + '</div>' +
                    '</div>';
            });
    
            return html + infoHtml;
        }
    
        // Render each ladder separately, based on its data-hue-mode
        $scales.each(function () {
            var $scale = $(this);
            var mode   = $scale.data('hueMode');
    
            if (mode !== 'relaxed') {
                mode = 'strict';
            }
    
            $scale.html(buildLadderHtmlForMode(mode));
        });
    }

    // ---------- Init shade helper dropdowns + default hex ----------

    // Init dropdowns inside shade helpers
    $('.pct-shade-container .pct-mix-dropdown').each(function() {
        initPaintDropdown($(this));
    });

    $('.pct-shade-container .pct-mix-range-dropdown').each(function() {
        initRangeDropdown($(this));
    });

    // Initialise shade helper with default paint (by ID if available, otherwise by hex)
    $('.pct-shade-container').each(function() {
        var $container = $(this);
        var defaultHex = ($container.data('default-shade-hex') || '').toString().trim();
        var defaultId  = parseInt($container.data('default-shade-id'), 10);
        if (!isFinite(defaultId)) {
            defaultId = 0;
        }
    
        // Always initialise the empty state at least once
        if (!defaultId && !defaultHex) {
            updateShadeScale($container);
            return;
        }
    
        // Normalise the hex (# + lowercase) if provided
        if (defaultHex) {
            if (defaultHex.charAt(0) !== '#') {
                defaultHex = '#' + defaultHex;
            }
            defaultHex = defaultHex.toLowerCase();
        }
    
        var $shadeColumn   = $container.find('.pct-mix-column-shade');
        if (!$shadeColumn.length) {
            updateShadeScale($container);
            return;
        }
    
        var $paintDropdown = $shadeColumn.find('.pct-mix-dropdown-shade');
        var $options       = $paintDropdown.find('.pct-mix-option');
        if (!$options.length) {
            updateShadeScale($container);
            return;
        }
    
        // Prefer matching paint option by ID, then fall back to hex
        var $match = null;
    
        if (defaultId) {
            $options.each(function () {
                var optId = parseInt($(this).data('id'), 10);
                if (!isFinite(optId)) {
                    optId = 0;
                }
                if (optId === defaultId) {
                    $match = $(this);
                    return false; // break
                }
            });
        }
    
        if (!$match && defaultHex) {
            $options.each(function () {
                var hex = ($(this).data('hex') || '').toString().toLowerCase();
                if (hex === defaultHex) {
                    $match = $(this);
                    return false; // break
                }
            });
        }
    
        if (!$match) {
            updateShadeScale($container);
            return;
        }
    
        // Set the range dropdown based on the matched option
        var rangeId        = $match.data('range') || '';
        var $rangeDropdown = $shadeColumn.find('.pct-mix-range-dropdown-shade');
    
        if ($rangeDropdown.length) {
            var $rangeList   = $rangeDropdown.find('.pct-mix-list');
            var $rangeHidden = $rangeDropdown.find('.pct-mix-range-value');
            var $rangeLabel  = $rangeDropdown.find('.pct-mix-trigger-label');
    
            $rangeList.find('.pct-mix-range-option').removeClass('is-selected');
    
            var selector = '.pct-mix-range-option';
            if (rangeId !== '') {
                selector += '[data-range="' + String(rangeId) + '"]';
            } else {
                selector += '[data-range=""]';
            }
    
            var $rangeOpt = $rangeList.find(selector).first();
            if ($rangeOpt.length) {
                $rangeOpt.addClass('is-selected');
                $rangeHidden.val(rangeId);
                var rangeText = $rangeOpt.find('.pct-mix-option-label').text() || '';
                if (rangeText) {
                    $rangeLabel.text(rangeText);
                }
    
                // Filter paints by range (same as user picking a range)
                filterPaintOptions($shadeColumn, rangeId);
    
                // Re-find the matching paint after filtering (still prefer ID)
                $options = $paintDropdown.find('.pct-mix-option');
                var newMatch = null;
    
                if (defaultId) {
                    $options.each(function () {
                        var optId = parseInt($(this).data('id'), 10);
                        if (!isFinite(optId)) {
                            optId = 0;
                        }
                        if (optId === defaultId) {
                            newMatch = $(this);
                            return false;
                        }
                    });
                }
    
                if (!newMatch && defaultHex) {
                    $options.each(function () {
                        var hex = ($(this).data('hex') || '').toString().toLowerCase();
                        if (hex === defaultHex) {
                            newMatch = $(this);
                            return false;
                        }
                    });
                }
    
                if (newMatch) {
                    $match = newMatch;
                }
            }
        }
    
        if ($match) {
            var hexVal   = ($match.data('hex') || defaultHex || '').toString();
            var label    = $match.data('label') || '';
            var $hidden  = $paintDropdown.find('.pct-mix-value');
            var $labelEl = $paintDropdown.find('.pct-mix-trigger-label');
            var $swatch  = $paintDropdown.find('.pct-mix-trigger-swatch');
    
            $paintDropdown.attr('data-hex', hexVal);
            $hidden.val(hexVal);
            $paintDropdown.find('.pct-mix-option').removeClass('is-selected');
            $match.addClass('is-selected');
    
            if (label) {
                $labelEl.text(label);
            }
            if ($swatch.length && hexVal) {
                $swatch.css('background-color', hexVal);
            }
        }
    
        // Finally, build the shade ladder for this colour
        updateShadeScale($container);
    });
});
