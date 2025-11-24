jQuery(function($) {

    // ---------- Colour helpers ----------

    function hexToRgb(hex) {
        if (!hex) return null;
        hex = hex.toString().trim();
        if (hex.charAt(0) === '#') {
            hex = hex.slice(1);
        }
        if (hex.length === 3) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }
        if (hex.length !== 6) {
            return null;
        }
        var num = parseInt(hex, 16);
        if (isNaN(num)) {
            return null;
        }
        return {
            r: (num >> 16) & 255,
            g: (num >> 8) & 255,
            b: num & 255
        };
    }

    function componentToHex(c) {
        var v = Math.max(0, Math.min(255, Math.round(c)));
        var s = v.toString(16);
        return s.length === 1 ? '0' + s : s;
    }

    function rgbToHex(r, g, b) {
        return '#' + componentToHex(r) + componentToHex(b);
    }

    function rgbToHex(r, g, b) {
        return '#' + componentToHex(r) + componentToHex(g) + componentToHex(b);
    }

    function mixColors(hex1, hex2, w1, w2) {
        var c1 = hexToRgb(hex1);
        var c2 = hexToRgb(hex2);
        if (!c1 || !c2) {
            return null;
        }

        w1 = Number(w1) || 0;
        w2 = Number(w2) || 0;

        var total = w1 + w2;
        if (total <= 0) {
            return null;
        }

        var r = (c1.r * w1 + c2.r * w2) / total;
        var g = (c1.g * w1 + c2.g * w2) / total;
        var b = (c1.b * w1 + c2.b * w2) / total;

        return rgbToHex(r, g, b);
    }

    function textColorForHex(hex) {
        var c = hexToRgb(hex);
        if (!c) return '#111827';
        var lum = (0.299 * c.r + 0.587 * c.g + 0.114 * c.b) / 255;
        return lum < 0.5 ? '#f9fafb' : '#111827';
    }

    // ---------- Shared dropdown helpers (for mixer) ----------

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
        $dropdown.find('.pct-mix-trigger-label').text('Select a paint');
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
