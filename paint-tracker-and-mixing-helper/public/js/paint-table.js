// Click a coloured row (with data-shade-url) to go to the shade helper page.
// Links and form controls still behave normally.
document.addEventListener( 'click', function( event ) {

    if ( event.target.closest( 'a, button, input, select, textarea' ) ) {
        return;
    }

    var row = event.target.closest( 'tr[data-shade-url]' );
    if ( ! row ) {
        return;
    }

    var url = row.getAttribute( 'data-shade-url' );
    if ( url ) {
        window.location.href = url;
    }
} );
