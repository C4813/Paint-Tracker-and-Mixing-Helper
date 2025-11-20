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

    // ---------- Dropdown behaviour ----------

    function closeAllDropdowns() {
        $('.pct-mix-dropdown').removeClass('pct-mix-open');
        $('.pct-mix-dropdown .pct-mix-list').attr('hidden', 'hidden');
    }

    function initDropdown($dropdown) {
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
        $list.on('click', '.pct-mix-option', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $opt  = $(this);
            var hex   = $opt.data('hex') || '';
            var label = $opt.data('label') || '';

            // Visual selected state in the list
            $list.find('.pct-mix-option').removeClass('is-selected');
            $opt.addClass('is-selected');

            // Update trigger + hidden value
            $hidden.val(hex);
            $dropdown.attr('data-hex', hex);
            if (label) {
                $label.text(label);
            }
            if (hex) {
                $swatch.css('background-color', hex);
            } else {
                $swatch.css('background-color', 'transparent');
            }

            closeAllDropdowns();

            // Trigger mix recalculation for this widget
            var $container = $dropdown.closest('.pct-mix-container');
            if ($container.length) {
                updateMix($container);
            }
        });
    }

    // Close dropdowns when clicking outside
    $(document).on('click', function() {
        closeAllDropdowns();
    });

    // ---------- Mixing logic ----------

    function updateMix($container) {
        var $leftDropdown  = $container.find('.pct-mix-dropdown-left');
        var $rightDropdown = $container.find('.pct-mix-dropdown-right');
        var $leftParts     = $container.find('.pct-mix-parts-left');
        var $rightParts    = $container.find('.pct-mix-parts-right');

        var hexLeft  = $leftDropdown.find('.pct-mix-value').val() || '';
        var hexRight = $rightDropdown.find('.pct-mix-value').val() || '';

        var partsLeft  = parseInt($leftParts.val(), 10);
        var partsRight = parseInt($rightParts.val(), 10);

        if (!hexLeft || !hexRight || !partsLeft || !partsRight || partsLeft <= 0 || partsRight <= 0) {
            // Reset result
            $container.find('.pct-mix-result-hex').text('—');
            $container.find('.pct-mix-result-swatch').css('background-color', 'transparent');
            return;
        }

        var mixedHex = mixColors(hexLeft, hexRight, partsLeft, partsRight);
        if (!mixedHex) {
            $container.find('.pct-mix-result-hex').text('—');
            $container.find('.pct-mix-result-swatch').css('background-color', 'transparent');
            return;
        }

        $container.find('.pct-mix-result-hex').text(mixedHex.toUpperCase());
        $container.find('.pct-mix-result-swatch').css('background-color', mixedHex);
    }

    // Parts inputs still drive recalculation
    $(document).on('change input', '.pct-mix-parts', function() {
        var $container = $(this).closest('.pct-mix-container');
        if ($container.length) {
            updateMix($container);
        }
    });

    // Initialise all dropdown instances on load
    $('.pct-mix-dropdown').each(function() {
        initDropdown($(this));
    });
});
