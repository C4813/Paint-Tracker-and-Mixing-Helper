jQuery(function($) {

    /**
     * QUICK EDIT: copy number, hex, "On shelf" and "Exclude from shading" from row into inline editor.
     */
    if (typeof inlineEditPost !== 'undefined') {
        var $wp_inline_edit = inlineEditPost.edit;

        inlineEditPost.edit = function(id) {
            // Call original handler
            $wp_inline_edit.apply(this, arguments);

            var postId = 0;
            if (typeof id === 'object') {
                postId = parseInt(this.getId(id), 10);
            } else {
                postId = parseInt(id, 10);
            }

            if (postId > 0) {
                var $row     = $('#post-' + postId);
                var $editRow = $('#edit-' + postId);

                // Read values from the table columns (unchanged from your working version)
                var number = $('.column-pct_number', $row).text().trim();
                var hex    = $('.column-pct_hex', $row).text().trim();

                // Read on-shelf + exclude-from-shade flags from the span
                var metaEl       = $row.find('.pct-on-shelf-value');
                var onShelf      = false;
                var excludeShade = false;

                if (metaEl.length) {
                    var shelfVal   = metaEl.data('on-shelf');
                    var excludeVal = metaEl.data('exclude-shade');

                    onShelf      = (shelfVal === 1 || shelfVal === '1');
                    excludeShade = (excludeVal === 1 || excludeVal === '1');
                }

                // Push into the Quick Edit form fields
                $('input[name="pct_number"]',        $editRow).val(number);
                $('input[name="pct_hex"]',           $editRow).val(hex);
                $('input[name="pct_on_shelf"]',      $editRow).prop('checked', onShelf);
                $('input[name="pct_exclude_shade"]', $editRow).prop('checked', excludeShade);
            }
        };
    }

    /**
     * META BOX: dynamic add/remove link rows.
     */
    var $wrapper = $('#pct-links-wrapper');
    if ($wrapper.length) {

        var linkTitleLabel = (window.pctAdmin && pctAdmin.linkTitleLabel) || 'Link title';
        var linkTitlePh    = (window.pctAdmin && pctAdmin.linkTitlePh)    || 'e.g. Tutorial, Review, Example Build';
        var linkUrlLabel   = (window.pctAdmin && pctAdmin.linkUrlLabel)   || 'Link URL';
        var linkUrlPh      = (window.pctAdmin && pctAdmin.linkUrlPh)      || 'https://example.com/my-article';
        var removeLinkText = (window.pctAdmin && pctAdmin.removeLink)     || 'Remove link';

        $('#pct-add-link').on('click', function(e) {
            e.preventDefault();

            var $row = $(
                '<div class="pct-link-row">' +
                    '<p class="pct-link-row-field">' +
                        '<label>' +
                            linkTitleLabel + '<br>' +
                            '<input type="text" name="pct_links_title[]" value="" class="regular-text" placeholder="' + linkTitlePh + '">' +
                        '</label>' +
                    '</p>' +
                    '<p class="pct-link-row-field">' +
                        '<label>' +
                            linkUrlLabel + '<br>' +
                            '<input type="url" name="pct_links_url[]" value="" class="regular-text" placeholder="' + linkUrlPh + '">' +
                        '</label>' +
                    '</p>' +
                    '<p class="pct-link-row-field">' +
                        '<button type="button" class="button pct-remove-link">' + removeLinkText + '</button>' +
                    '</p>' +
                '</div>'
            );

            $wrapper.append($row);
        });

        $wrapper.on('click', '.pct-remove-link', function(e) {
            e.preventDefault();
            var $rows = $wrapper.find('.pct-link-row');
            if ($rows.length > 1) {
                $(this).closest('.pct-link-row').remove();
            } else {
                $(this).closest('.pct-link-row').find('input').val('');
            }
        });
    }
    
    /**
     * Info & Settings: auto-save options
     * - Paint table display (radio)
     * - Shading page URL (on change)
     * - Shade helper hue behaviour (radio)
     */

    // Helper: submit closest form via native submit()
    function pctSubmitClosestForm(el) {
        var $form = el.closest('form');
        if ($form.length && $form[0] && typeof $form[0].submit === 'function') {
            $form[0].submit();
        }
    }

    // Auto-submit when the paint table display mode changes
    $(document).on('change', 'input[name="pct_table_display_mode"]', function() {
        pctSubmitClosestForm($(this));
    });

    // Auto-submit when the shading page URL is changed (on change/blur)
    $(document).on('change', '#pct_mixing_page_url', function() {
        pctSubmitClosestForm($(this));
    });

    // Auto-submit when the shade helper hue behaviour changes
    $(document).on('change', 'input[name="pct_shade_hue_mode"]', function() {
        pctSubmitClosestForm($(this));
    });
    
    /**
     * Quick Edit: hide password / private controls for Paint Colours.
     */
    if ($('body').hasClass('post-type-paint_color')) {
        $('.inline-edit-row input[name="post_password"]').closest('label').hide();
        $('.inline-edit-row input[name="keep_private"]').closest('label').hide();
    }

});
