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
					if( button_action == 'payment_refund'){
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

async function wpecOnAddNewOrderProductSelect(e){
	const input = e.target
	let productId = input.value.trim();
	const productDesc = document.getElementById('wpec_new_order_product_description');
	const quantityInputCont = document.getElementById('wpec_new_order_product_quantity');
	const variationsInputCont = document.getElementById('wpec_new_order_product_variations');

	productDesc.innerHTML = '';
	quantityInputCont.innerHTML = '';
	variationsInputCont.innerHTML = '';
	if(!productId){
		return;
	}

	productId = parseInt(productId);

	let response = await fetch(ajaxurl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
		},
		body: new URLSearchParams({
			action: 'wpec_get_order_product_by_id',
			productId,
			nonce: wpecAdminSideVars.add_new_order_nonce,
		}),
	});

	if (!response.ok) {
		console.log(response);
		return;
	}

	response = await response.json();

	if (!response.success) {
		console.log(response.data.message)
		return;
	}

	const product = response.data?.product;

	// console.log(response.data);

	const priceItems = [];
	const quantityInput = document.createElement('input');
	quantityInput.setAttribute('name', 'wpec_order_product_quantity');
	const quantityInputDiv = document.createElement('div');
	quantityInputDiv.classList.add('wpec-admin-product-quantity-div');
	quantityInput.setAttribute('required', true);
	const quantityInputLabel = document.createElement('label');
	quantityInputLabel.innerText = 'Quantity: ';
	quantityInput.value = product?.quantity || 1;
	quantityInput.setAttribute('type', 'number');
	quantityInput.setAttribute('min', 1);
	// if (!product?.isCustomQuantity) {
	// 	quantityInput.setAttribute('readonly', true);
	// }
	if (product?.isStockControl) {
		quantityInput.setAttribute('max', product?.stockItems);
	}
	if (product?.tax && parseFloat(product?.tax) > 0) {
		priceItems.push(`${product.tax}% tax`);
	}
	if (product?.shipping && parseFloat(product?.shipping) > 0) {
		priceItems.push(`${wpecAdminformatPrice(product.shipping)} shipping`);
	}
	if (product?.shippingPerQuantity && parseFloat(product?.shippingPerQuantity) > 0) {
		priceItems.push(`${wpecAdminformatPrice(product.shippingPerQuantity)} shipping per quantity`);
	}

	quantityInputDiv.appendChild(quantityInputLabel);
	quantityInputDiv.appendChild(quantityInput);
	quantityInputCont.appendChild(quantityInputDiv);

	if (priceItems.length) {	
		productDesc.innerText = priceItems.join(', ');
	}

	if (product.variations && product.variations.groups) {
		const variationGroups = product.variations.groups;
		variationGroups.forEach((groupName, i) => {
			const variationInputDiv = document.createElement('div');
			variationInputDiv.classList.add('wpec-admin-product-var-div');
			const variationInput = document.createElement('select');
			variationInput.setAttribute('name' , 'wpec_order_product_var[]')
			variationInput.setAttribute('required', true);

			const variationItems = product.variations[i];
			variationItems.names.forEach((varItemName, varItemIndex) => {
				const option = document.createElement('option');
				const priceMod = variationItems.prices[varItemIndex];
				option.innerText = varItemName + " (" + wpecAdminformatPriceMod(priceMod, true) + ")";
				option.value = varItemIndex;
				variationInput.appendChild(option);
			});

			const variationInputLabel = document.createElement('label');
			variationInputLabel.innerText = groupName + ': ';
			variationInputDiv.appendChild(variationInputLabel);
			variationInputDiv.appendChild(variationInput);
			variationsInputCont.appendChild(variationInputDiv);
		})
	}

	document.dispatchEvent(
		new CustomEvent( 'wpec_on_add_new_order_product_select', {
			detail: {
				response,
			}
		})
	)
}

document.addEventListener('DOMContentLoaded', () => {
	const productSelect = document.getElementById('wpec_add_new_order_product_id');
	
	if (productSelect) {
		productSelect.addEventListener('change', wpecOnAddNewOrderProductSelect);
	}
})

function wpecAdminformatPrice(price){
	price = "$" + Math.abs(price).toFixed(2);
	return price;
}

function wpecAdminformatPriceMod(price){
	price = parseFloat(price);

	let modSign = '';
	if (price < 0) {
		modSign = '-';
	} else if (price > 0){
		modSign = '+';
	} else {
		modSign = '';
	}

	price = modSign + wpecAdminformatPrice(price);

	return price;
}

class WPECSearchSelect {
    constructor(wrapper) {
        this.wrapper = wrapper;
        this.select = wrapper.querySelector('select');
		this.select.hidden = true;
		this.select.required = false; // Hidden required fields are not focusable, gives browser error.
        this.placeholder = wrapper.dataset.placeholder || 'Select';

        this.options = [...this.select.options].filter(o => o.value); // Remove all empty options

		this.optionTexts = this.options.map(o => o.textContent.trim());
		this.lastSelectedOptionText = '';
		this.lastSelectedOptionValue = '';

        this.build();
        this.bind();
    }

    build() {
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'wpec-search-select-dropdown wpec-search-select-hidden';

        this.search = document.createElement('input');
        this.search.className = 'wpec-search-select-search';
        this.search.type = 'text';
        this.search.required = true;
        this.search.placeholder = this.placeholder;

        this.list = document.createElement('div');

        this.dropdown.append(this.list);
        this.wrapper.append(this.search, this.dropdown);

        this.renderOptions(this.options);
    }

    renderOptions(options) {
        this.list.innerHTML = '';
        options.forEach((opt, index) => {
            const div = document.createElement('div');
            div.className = 'wpec-search-select-option';
            div.textContent = opt.text;
            div.dataset.value = opt.value;
            div.dataset.index = index;
            this.list.appendChild(div);
        });
    }

    bind() {
        this.search.addEventListener('click', () => this.toggle());
		
        this.search.addEventListener('input', () => {
            const term = this.search.value.toLowerCase();
            const filtered = this.options.filter(o =>
                o.text.toLowerCase().includes(term)
            );

			this.renderOptions(filtered);
        });

        this.list.addEventListener('click', e => {
            if (e.target.classList.contains('wpec-search-select-option')) {
                this.selectValue(e.target.dataset.value, e.target.textContent);
            }
        });

        document.addEventListener('click', e => {
            if (!this.wrapper.contains(e.target)) {
				const searchValue = this.search.value.trim();
				if (! searchValue.length) {
					this.selectValue('', '');
				} else if(! this.optionTexts.includes(searchValue)){
					this.search.value = this.lastSelectedOptionText;
					this.close();
				} else {
					this.close();
				}
            }
        });
    }

    toggle() {
        this.wrapper.classList.toggle('open');
        this.dropdown.classList.toggle('wpec-search-select-hidden');

        this.search.focus();
    }

    close() {
        this.wrapper.classList.remove('open');
        this.dropdown.classList.add('wpec-search-select-hidden');

        this.renderOptions(this.options);
    }

    selectValue(value, text) {
        this.select.value = value;
        this.search.value = text;

        this.lastSelectedOptionValue = value;
        this.lastSelectedOptionText = text;
        
		this.close();

        this.select.dispatchEvent(new Event('change'));
    }
}

document.querySelectorAll('.wpec-search-select').forEach(el => {
    new WPECSearchSelect(el);
});
