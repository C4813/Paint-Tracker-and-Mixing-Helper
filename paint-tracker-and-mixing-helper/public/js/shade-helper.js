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
            $options.each(function() {
                var $opt      = $(this);
                var optRange  = String($opt.data('range') || '');
                var shouldShow = (optRange === String(rangeId));
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

    // ---------- Shade range helper logic (strict vs relaxed) ----------

    function updateShadeScale($container) {
        var $shadeHelper = $container.find('.pct-shade-helper');
        if (!$shadeHelper.length) {
            return;
        }

        var $shadeColumn = $shadeHelper.find('.pct-mix-column-shade');
        var $scale       = $shadeHelper.find('.pct-shade-scale');
        if (!$shadeColumn.length || !$scale.length) {
            return;
        }

        var $paintDropdown = $shadeColumn.find('.pct-mix-dropdown-shade');
        var baseHex        = $paintDropdown.find('.pct-mix-value').val() || '';

        if (!baseHex) {
            $scale.html(
                '<p class="pct-shade-empty">' +
                    pctL10n('selectPaint', 'Select a paint to see lighter and darker mixes.') +
                '</p>'
            );
            return;
        }

        var baseRgb = hexToRgb(baseHex);
        if (!baseRgb) {
            $scale.html(
                '<p class="pct-shade-empty">' +
                    pctL10n('invalidHex', 'This colour has an invalid hex value.') +
                '</p>'
            );
            return;
        }

        var baseLum = (0.299 * baseRgb.r + 0.587 * baseRgb.g + 0.114 * baseRgb.b) / 255;
        var baseHsl = rgbToHsl(baseRgb.r, baseRgb.g, baseRgb.b);

        // find the selected option for this base colour
        var $selectedOption = $paintDropdown.find('.pct-mix-option.is-selected').first();
        if (!$selectedOption.length) {
            // Fallback: match by hex
            $paintDropdown.find('.pct-mix-option').each(function () {
                var $opt   = $(this);
                var optHex = ($opt.data('hex') || '').toString().toLowerCase();
                if (optHex === baseHex.toString().toLowerCase()) {
                    $selectedOption = $opt;
                    return false; // break
                }
            });
        }

        if (!$selectedOption.length) {
            $scale.html(
                '<p class="pct-shade-empty">' +
                    pctL10n('noSelectedPaint', 'Could not determine the selected paint in this range.') +
                '</p>'
            );
            return;
        }

        var baseLabel = $selectedOption.data('label') || '';

        // Determine which range we should respect:
        // - If there's a range dropdown, use its current value
        // - Otherwise, fall back to the base paint's own range
        var $rangeDropdown = $shadeHelper.find('.pct-mix-range-dropdown-shade');
        var activeRangeId  = '';

        if ($rangeDropdown.length) {
            var val = $rangeDropdown.find('.pct-mix-range-value').val();
            if (typeof val === 'undefined' || val === null) {
                activeRangeId = '';
            } else {
                activeRangeId = String(val);
            }
        } else {
            var baseRangeId = $selectedOption.data('range');
            if (typeof baseRangeId === 'undefined' || baseRangeId === null) {
                activeRangeId = '';
            } else {
                activeRangeId = String(baseRangeId);
            }
        }

        // Thresholds for "sane" anchors
        var MAX_HUE_DIFF_DEG = 40;   // max hue difference for same-hue-ish colours
        var SAT_NEUTRAL      = 0.05; // very low-sat colours = neutral-ish
        var LUM_EPS          = 0.03; // minimum luminance difference to count as darker/lighter

        // Base is considered neutral if it's very low-sat or extremely bright/dark
        var baseIsNeutral =
            (baseHsl.s <= SAT_NEUTRAL) ||
            (baseLum < 0.25) ||
            (baseLum > 0.85);

        // Buckets for candidates
        var darkerSameHue   = [];
        var lighterSameHue  = [];
        var darkerNeutral   = [];
        var lighterNeutral  = [];

        // Collect paints into buckets
        $paintDropdown.find('.pct-mix-option').each(function () {
            var $opt     = $(this);
            var optHex   = $opt.data('hex') || '';
            var optRange = $opt.data('range');

            if (!optHex) {
                return;
            }

            // If a specific range is selected, respect that.
            // If activeRangeId === '' we are in "All" mode and accept all ranges.
            if (activeRangeId !== '' && String(optRange) !== activeRangeId) {
                return;
            }

            // Skip the base colour itself when choosing anchors
            if (optHex.toString().toLowerCase() === baseHex.toString().toLowerCase()) {
                return;
            }

            var rgb = hexToRgb(optHex);
            if (!rgb) {
                return;
            }

            var lum = (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255;
            var hsl = rgbToHsl(rgb.r, rgb.g, rgb.b);
            var label = $opt.data('label') || '';

            // Neutral-ish candidates:
            //   - very low saturation (grey/white/black)
            //   - OR very dark
            //   - OR very light
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

            // If base is neutral (e.g. white/grey/black), only accept neutral-ish anchors
            if (baseIsNeutral && !isNeutralLike) {
                return;
            }

            var candidate = {
                hex: optHex,
                lum: lum,
                label: label,
                hueDiff: hueDiff,
                isNeutral: isNeutralLike
            };

            // RELAXED and STRICT both fill the same buckets;
            // we choose between them later when picking anchors.
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

        var darkest  = null;
        var lightest = null;

        if (shadeHueMode === 'relaxed') {
            // RELAXED MODE:
            //  - Prefer same-hue anchors when available
            //  - Fall back to neutral-ish paints (black/white/grey/tinted)
            if (baseIsNeutral) {
                // Neutral base: only neutral anchors make sense
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
        } else {
            // STRICT MODE:
            //  - Strong protection against big hue shifts
            //  - Neutral-ish paints are always OK, same-hue allowed too
            if (baseIsNeutral) {
                if (darkerNeutral.length) {
                    darkest = pickDarkest(darkerNeutral);
                }
                if (lighterNeutral.length) {
                    lightest = pickLightest(lighterNeutral);
                }
            } else {
                var allDarker = darkerSameHue.concat(darkerNeutral);
                var allLighter = lighterSameHue.concat(lighterNeutral);

                if (allDarker.length) {
                    darkest = pickDarkest(allDarker);
                }
                if (allLighter.length) {
                    lightest = pickLightest(allLighter);
                }
            }
        }

        var hasDarker  = !!darkest;
        var hasLighter = !!lightest;

        // If we truly have nothing useful, show an error message
        if (!hasDarker && !hasLighter) {
            $scale.html(
                '<p class="pct-shade-empty">' +
                    pctL10n('notEnoughPaints', 'Not enough paints in this selection to build a shade ladder.') +
                '</p>'
            );
            return;
        }

        // Ratios: [partsBase, partsOther]
        var darkerRatios = [
            [1, 3], // darkest: 1 base, 3 darkener
            [1, 1],
            [3, 1]  // closest to base: 3 base, 1 darkener
        ];
        var lighterRatios = [
            [3, 1], // closest to base: 3 base, 1 lightener
            [1, 1],
            [1, 3]  // lightest: 1 base, 3 lightener
        ];

        var rows = [];

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
        }

        // Base in the centre
        rows.push({
            type: 'base',
            ratio: null,
            hex: baseHex.toUpperCase(),
            baseLabel: baseLabel
        });

        if (hasLighter) {
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
            $scale.html(
                '<p class="pct-shade-empty">' +
                    pctL10n('unableToGenerate', 'Unable to generate mixes for this colour.') +
                '</p>'
            );
            return;
        }

        // Build info messages for missing sides (consistent styling)
        var infoHtml = '';
        if (!hasDarker) {
            infoHtml += '<p class="pct-shade-empty">' +
                pctL10n('noDarker', 'Not enough darker paints in this selection to generate darker mixes.') +
                '</p>';
        }
        if (!hasLighter) {
            infoHtml += '<p class="pct-shade-empty">' +
                pctL10n('noLighter', 'Not enough lighter paints in this selection to generate lighter mixes.') +
                '</p>';
        }

        var html = '';
        rows.forEach(function (row) {
            var mainText = '';
            var textColor = textColorForHex(row.hex);

            if (row.type === 'base') {
                mainText = row.baseLabel || '';
            } else {
                var otherLabel = row.otherLabel || '';
                var partsBase  = row.ratio ? row.ratio[0] : 0;
                var partsOther = row.ratio ? row.ratio[1] : 0;

                if (otherLabel && baseLabel && partsBase && partsOther) {
                    mainText = otherLabel + ' ' + partsOther + ' : ' + partsBase + ' ' + baseLabel;
                } else {
                    mainText = row.hex;
                }
            }

            html += '<div class="pct-shade-row pct-shade-row-' + row.type +
                    '" style="background-color:' + row.hex + ';color:' + textColor + ';">';
            html += '  <div class="pct-shade-meta">';
            html += '    <div class="pct-shade-ratio">' + mainText + '</div>';
            html += '    <div class="pct-shade-hex">' + row.hex + '</div>';
            html += '  </div>';
            html += '</div>';
        });

        $scale.html(infoHtml + html);
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
