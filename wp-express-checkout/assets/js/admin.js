( function( $ ) {
	"use strict";

	$( '.wpec-order-action' ).on( 'click', function( e ) {
		e.preventDefault();

		var button = $( this );

		$.post( ajaxurl, {
				action: "wpec_order_action_" + button.data( 'action' ),
				nonce: button.data( 'nonce' ),
				order: button.data( 'order' )
			} )
			.done( function( data ) {
				var msg = data.data ? data.data : "Something went wrong!";
				alert( msg );
			} )
			.fail( function() {
				alert( "Something went wrong!" );
			} );
	} );

}( jQuery ) );
