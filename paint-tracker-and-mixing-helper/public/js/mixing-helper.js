jQuery(function($) {
    
    var pctMixL10n       = window.pctColorUtils.makeL10nHelper('pctMixingHelperL10n');
    var closeAllDropdowns = window.pctColorUtils.closeAllDropdowns;
    
    // Turn "acrylic" -> "Acrylic" etc.
    function pctHumanBaseType(type) {
        type = (type || '').toString().toLowerCase();
        if (!type) {
            return '';
        }
        return type.charAt(0).toUpperCase() + type.slice(1);
    }

    // ---------- Colour helpers (shared via pct-color-utils.js) ----------
    
    var hexToRgb        = window.pctColorUtils.hexToRgb;
    var rgbToHex        = window.pctColorUtils.rgbToHex;
    var mixColors       = window.pctColorUtils.mixColors;
    var textColorForHex = window.pctColorUtils.textColorForHex;

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
        $dropdown.removeAttr('data-base-type');
        $list.find('.pct-mix-option').removeClass('is-selected');
        $dropdown.find('.pct-mix-trigger-label').text(
            pctMixL10n('selectPaint', 'Select a paint')
        );
        $dropdown.find('.pct-mix-trigger-swatch').css('background-color', 'transparent');
    }

    // ---------- Paint dropdowns ----------

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
            var baseType  = $opt.data('base-type') || '';

            $list.find('.pct-mix-option').removeClass('is-selected');
            $opt.addClass('is-selected');

            $hidden.val(hex);
            $dropdown.attr('data-hex', hex);
            
            if (baseType) {
                $dropdown.attr('data-base-type', baseType);
            } else {
                $dropdown.removeAttr('data-base-type');
            }
    
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

            var $mixContainer = $dropdown.closest('.pct-mix-container');
            if ($mixContainer.length) {
                updateMix($mixContainer);
            }
        });
    }

    // ---------- Range dropdowns (mixer) ----------

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

            var $mixContainer = $dropdown.closest('.pct-mix-container');
            if ($mixContainer.length) {
                updateMix($mixContainer);
            }
        });
    }

    // ---------- Mixing logic (two-paint mixer) ----------

    function updateMix($container) {
        var $leftDropdown  = $container.find('.pct-mix-dropdown-left');
        var $rightDropdown = $container.find('.pct-mix-dropdown-right');
        var $leftParts     = $container.find('.pct-mix-parts-left');
        var $rightParts    = $container.find('.pct-mix-parts-right');

        var hexLeft  = $leftDropdown.find('.pct-mix-value').val() || '';
        var hexRight = $rightDropdown.find('.pct-mix-value').val() || '';

        var partsLeft  = parseInt($leftParts.val(), 10);
        var partsRight = parseInt($rightParts.val(), 10);

        var baseTypeLeft  = $leftDropdown.attr('data-base-type') || '';
        var baseTypeRight = $rightDropdown.attr('data-base-type') || '';

        var $resultBlock  = $container.find('.pct-mix-result-block');
        var $resultHex    = $container.find('.pct-mix-result-hex');
        var $resultSwatch = $container.find('.pct-mix-result-swatch');

        if (!$resultBlock.length) {
            return;
        }

        // Hide the old circle swatch
        if ($resultSwatch.length) {
            $resultSwatch.hide();
        }

        // If anything is missing/invalid, just hide the whole result row
        if (!hexLeft || !hexRight || !partsLeft || !partsRight ||
            partsLeft <= 0 || partsRight <= 0) {

            $resultBlock.hide();
            return;
        }

        // If both paints have a base type and they differ, show a warning instead of a mix
        if (baseTypeLeft && baseTypeRight && baseTypeLeft !== baseTypeRight) {
            var niceLeft  = pctHumanBaseType(baseTypeLeft);
            var niceRight = pctHumanBaseType(baseTypeRight);

            var msgTemplate = pctMixL10n(
                'cannotMixBaseTypes',
                'Cannot mix {left} with {right}.'
            );
            var msg = msgTemplate
                .replace('{left}', niceLeft || baseTypeLeft)
                .replace('{right}', niceRight || baseTypeRight);

            if ($resultHex.length) {
                $resultHex.text(msg);
            }

            // Clear background / color so we don’t look like a real paint
            $resultBlock.css({
                'background-color': '',
                'color': ''
            });

            // Show the block (flex)
            $resultBlock.css('display', 'flex');
            return;
        }

        var mixedHex = mixColors(hexLeft, hexRight, partsLeft, partsRight);
        if (!mixedHex) {
            $resultBlock.hide();
            return;
        }

        mixedHex = mixedHex.toUpperCase();

        if ($resultHex.length) {
            $resultHex.text(mixedHex);
        }

        $resultBlock.css({
            'background-color': mixedHex,
            'color': textColorForHex(mixedHex)
        });

        // Show once we have a valid result (as flex)
        $resultBlock.css('display', 'flex');
    }

    // Parts inputs: enforce whole numbers > 0
    $(document).on('change input', '.pct-mix-parts', function() {
        var $input = $(this);
        var raw = $input.val();

        var num = parseFloat(raw);
        if (!isFinite(num) || num <= 0) {
            num = 1;
        }
        num = Math.floor(num);
        if (num < 1) {
            num = 1;
        }

        $input.val(num);

        var $mixContainer = $input.closest('.pct-mix-container');
        if ($mixContainer.length) {
            updateMix($mixContainer);
        }
    });

    // ---------- Init all mixer dropdowns ----------

    $('.pct-mix-container .pct-mix-dropdown').each(function() {
        initPaintDropdown($(this));
    });

    $('.pct-mix-container .pct-mix-range-dropdown').each(function() {
        initRangeDropdown($(this));
    });
});
