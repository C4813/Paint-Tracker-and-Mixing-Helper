( function( window ) {
    'use strict';

    var $ = window.jQuery;
    if ( ! $ ) {
        return;
    }

    window.pctUiUtils = window.pctUiUtils || {};

    function closeAllDropdowns() {
        $( '.pct-mix-dropdown, .pct-mix-range-dropdown' ).each( function() {
            var $dd = $( this );
            $dd.removeClass( 'pct-mix-open' );
            $dd.find( '.pct-mix-list' ).attr( 'hidden', 'hidden' );
        } );
    }

    /**
     * Bind shared open/close behavior for dropdowns that follow the
     * .pct-mix-... markup pattern.
     */
    function bindOpenClose( $dropdown ) {
        var $trigger = $dropdown.find( '.pct-mix-trigger' );
        var $list    = $dropdown.find( '.pct-mix-list' );

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
    }

    window.pctUiUtils.closeAllDropdowns = closeAllDropdowns;
    window.pctUiUtils.bindOpenClose     = bindOpenClose;

}( window ) );
