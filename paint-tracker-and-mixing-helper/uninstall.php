<?php
/**
 * Uninstall handler for Paint Tracker and Mixing Helper.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options.
delete_option( 'pct_mixing_page_url' );
delete_option( 'pct_table_display_mode' );
delete_option( 'pct_shade_hue_mode' );
