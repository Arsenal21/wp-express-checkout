( function( $ ) {
	"use strict";

	 // Add order note
	 $('#wpec_order_note_btn_submit').on('click', function(e) {
        e.preventDefault();

		var btn = $(this);

        var note = $('#wpec_order_note').val();
		var order_id = $('#wpec_order_id').val();

		if(!note || note=="")
		{
			alert("Please add some notes first");
			return;
		}

		btn.val("adding");
		btn.prop("disabled",true);

        if (note !== '') {
            var data = {
                action: 'wpec_add_order_note',
                wpec_note: note,
				wpec_order_id: order_id,
				nonce: wpecAdminSideVars.add_order_note_nonance
            };

            $.ajax({
                url: wpecAdminSideVars.ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
					btn.val("Add");
					btn.prop("disabled",false);
                    // Handle success response
                    if (response.success) {
						
						// Clear the note input
						$("#wpec_order_note").val("");
                        
                        // Append the new note to the table
                        var noteRow = '<div class="wpec-single-note" id="wpec_single_note_'+response.data.note.id+'">' +
                        '<p>' + response.data.note.content + '</p>' +
						'<div class="wpec-single-note-meta">'    +
							'<span title="Added by '+response.data.note.admin_name+'">added on '+response.data.note.note_date+'</span>'+
							'<a href="#" class="wpec-delete-order-note" data-orderid="'+response.data.note.order_id+'" data-note-id="' + response.data.note.id + '">Delete</a>'+
						'</div>'+						
                            '</div>';
                        $('#wpec-admin-order-notes').append(noteRow);
                    } else {
                        // Handle error response
                        console.error(response.error);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
					btn.val("Add");
					btn.prop("disabled",false);
                    // Handle Ajax error
                    console.error(textStatus, errorThrown);
                }
            });
        }
    });

    // Delete order note
    $(document).on('click', '.wpec-delete-order-note', function(e) {
        e.preventDefault();

		var btn = $(this);
        if (confirm('Are you sure you want to delete this order note?')) {
            var noteId = $(this).data('note-id');
			var order_id = $(this).data('orderid');

			btn.prop("disabled",true);
			btn.text("deleting");

            if (noteId !== '') {
                var data = {
                    action: 'wpec_delete_order_note',
                    wpec_note_id: noteId,
					nonce: wpecAdminSideVars.delete_order_note_nonance,
					wpec_order_id: order_id
                };

                $.ajax({
                    url: wpecAdminSideVars.ajaxurl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
						btn.prop("disabled",false);
						btn.text("Delete");
                        // Handle success response
                        if (response.success) {
                            // Remove the deleted note row from the table
                            $(e.target).closest('.wpec-single-note').remove();
                        } else {
                            // Handle error response
                            console.error(response.error);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
						btn.prop("disabled",false);
						btn.text("Delete");
                        // Handle Ajax error
                        console.error(textStatus, errorThrown);
                    }
                });
            }
        }
    });

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
