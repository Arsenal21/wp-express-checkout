( function( $ ) {
	"use strict";

	$( '.wpec-order-action' ).on( 'click', function( e ) {
		e.preventDefault();
		var button = $( this );
		var label = $(this).find(".wpec-order-action-label");		
		var original_text = label.text().trim();
		var button_action = button.data( 'action' );
		console.log('Order action: ' + button_action);
		
		if (confirm("Do you really want to "+original_text+"?") == true) {									
			button.prop("disabled",true);
			label.text("processing...");

		$.post( ajaxurl, {
				action: "wpec_order_action_" + button.data( 'action' ),
				nonce: button.data( 'nonce' ),
				order: button.data( 'order' )
			} )
			.done( function( data ) {
				var msg = data.data ? data.data : "Something went wrong!";
					button.prop( "disabled",false );
					label.text( original_text );
					alert( msg );
					if( button_action == 'paypal_refund'){
						console.log('Refund successful. Reloading the page.');
						window.location.reload();
					}
				} )
				.fail( function() {
					button.prop( "disabled",false );
					label.text( original_text );
					alert( "Something went wrong!" );
				} );
		}
	} );

}( jQuery ) );
