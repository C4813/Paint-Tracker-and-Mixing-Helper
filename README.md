# Paint-Table-Wordpress-Shortcodes
Adds the shortcode [paint_table] to display paint colour tables, configured in the admin panel.
Known issues:
When clicking a paint color to open the shade helper, it looks for the HEX, not the paint. This can cause trouble if multiple colors from different ranges have the same HEX. For example, if you add a test paint with HEX of FFFFFF into a test range, clicking a Vallejo White Glaze with a HEX of FFFFFF will result in a 'not enough paints in range' message. I need to change it so that when clicking the paint it filters down to that item, rather than HEX.
