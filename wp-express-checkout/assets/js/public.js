var ppecHandler = function( data ) {	
	this.data = data;
	this.actions = {};

	var parent = this;

	this.processPayment = function( payment, action ) {
		jQuery.post( ppecFrontVars.ajaxUrl, {
				'action': action,
				wp_ppdg_payment: payment,
				data: parent.data,
				nonce: parent.data.nonce
			} )
			.done( function( data ) {
				parent.completePayment( data );
			} );
	};

	this.completePayment = function( data ) {
		var ret = true;
		var dlgTitle = ppecFrontVars.str.paymentCompleted;
		var dlgMsg = ppecFrontVars.str.redirectMsg;
		if ( data.redirect_url ) {
			var redirect_url = data.redirect_url;
		} else {
			dlgTitle = ppecFrontVars.str.errorOccurred;
			dlgMsg = data;
			ret = false;
		}
		jQuery( '.wp-ppec-overlay[data-ppec-button-id="' + parent.data.id + '"]' ).hide();
		var dialog = jQuery( '<div id="wp-ppdg-dialog-message" title="' + dlgTitle + '"><p id="wp-ppdg-dialog-msg">' + dlgMsg + '</p></div>' );
		jQuery( '#' + parent.data.id ).before( dialog ).fadeIn();
		if ( redirect_url ) {
			location.href = redirect_url;
		}
		return ret;
	};

	this.ValidatorTos = function( input ) {
		return input.prop( 'checked' ) !== true ? ppecFrontVars.str.acceptTos : null;
	};

	this.ValidatorBilling = function( input ) {
		var val = input.attr( 'type' ) === 'checkbox' ? input.prop( 'checked' ) === true : input.val();
		return input.is( ':visible' ) && !val ? ppecFrontVars.str.required : null;
	};

	this.isValidTotal = function() {
		parent.calcTotal();
		return !!parent.data.total;
	};

	this.ValidatorQuantity = function( input ) {
		var val_orig = input.val();
		var val = parseInt( val_orig );
		var error = false;
		if ( isNaN( val ) || ( val_orig % 1 !== 0 ) || val <= 0 ) {
			error = ppecFrontVars.str.enterQuantity;
		} else if ( parent.data.stock_enabled && val > parent.data.stock_items ) {
			error = ppecFrontVars.str.stockErr.replace( '%d', parent.data.stock_items );
		} else {
			parent.data.quantity = val;
		}
		return error;
	};

	this.ValidatorAmount = function( input ) {
		var val_orig = input.val();
		var val = parseFloat( val_orig );
		var min_val = input.attr( 'min' );
		var error = false;
		var errMsg = ppecFrontVars.str.enterAmount;
		if ( !isNaN( val ) && min_val <= val ) {
			parent.data.orig_price = val;
			parent.data.price = parent.applyVariations( val );
		} else {
			error = errMsg;
		}
		return error;
	};

	if ( this.data.btnStyle.layout === 'horizontal' ) {
		this.data.btnStyle.tagline = false;
	}

	this.clientVars = {};

	this.clientVars[ this.data.env ] = this.data.client_id;

	this.inputs = [];

	this.validateInput = function( input, validator ) {
		if ( 1 > input.length ) {
			return false;
		}

		this.inputs.push( [ input, validator ] );

		input.change( function() {
			parent.displayInputError( jQuery( this ), validator );
			parent.validateOrder();
		} );
	};

	this.validateOrder = function() {
		var enable_actions = true;

		jQuery( '#wpec_billing_' + parent.data.id + ', #place-order-' + parent.data.id ).toggle( !parent.isValidTotal() );
		jQuery( '#' + parent.data.id ).toggle( !jQuery( '#place-order-' + parent.data.id ).is( ':visible' ) );

		parent.inputs.forEach( function( inputArr ) {
			var input = inputArr[ 0 ];
			var validator = inputArr[ 1 ];
			if ( validator( input ) ) {
				enable_actions = false;
			}
		} );

		if ( !parent.isValidTotal() ) {
			enable_actions = false;
		}

		if ( enable_actions ) {
			parent.actions.enable();
		} else {
			parent.actions.disable();
		}

		parent.updateAllAmounts();
		jQuery( document ).trigger( 'wpec_validate_order', [ parent ] );
	};

	this.displayInputError = function( input, validator ) {
		var error = validator( input );
		var errMsgCont = input.is( ':checkbox' ) || input.attr( 'type' ) === 'number' ? input.parent().siblings( '.wp-ppec-form-error-msg' ) : input.siblings( '.wp-ppec-form-error-msg' );
		input.toggleClass( 'hasError', !!error );
		errMsgCont.html( error );
		if ( error && errMsgCont.length ) {
			errMsgCont.fadeIn( 'slow' );
		} else {
			errMsgCont.fadeOut( 'fast' );
		}
	};

	this.displayErrors = function() {
		parent.inputs.forEach( function( input ) {
			parent.displayInputError( input[ 0 ], input[ 1 ] );
		} );
	};

	this.scCont = jQuery( '.wp-ppec-shortcode-container[data-ppec-button-id="' + parent.data.id + '"]' );

	this.buttonArgs = {
		env: parent.data.env,
		client: parent.clientVars,
		style: parent.data.btnStyle,
		commit: true,
		onInit: function( data, actions ) {
			parent.actions = actions;
			parent.validateInput( jQuery( '#wpec-tos-' + parent.data.id ), parent.ValidatorTos );
			parent.validateInput( jQuery( '#wp-ppec-custom-quantity[data-ppec-button-id="' + parent.data.id + '"]' ), parent.ValidatorQuantity );
			parent.validateInput( jQuery( '#wp-ppec-custom-amount[data-ppec-button-id="' + parent.data.id + '"]' ), parent.ValidatorAmount );
			jQuery( '#wpec_billing_' + parent.data.id + ' .wpec_required' ).each( function() {
				parent.validateInput( jQuery( this ), parent.ValidatorBilling );
			} );
			parent.data.orig_price = parseFloat( parent.data.price );
			parent.scCont.find( 'select.wpec-product-variations-select, input.wpec-product-variations-select-radio' ).change( function() {
				var grpId = jQuery( this ).data( 'wpec-variations-group-id' );
				var varId = jQuery( this ).val();
				if ( Object.getOwnPropertyNames( parent.data.variations ).length !== 0 ) {
					if ( !parent.data.variations.applied ) {
						parent.data.variations.applied = [];
					}
					parent.data.variations.applied[ grpId ] = varId;
					parent.data.price = parent.applyVariations( parent.data.orig_price );
					parent.validateOrder();
				}
			} );
			parent.scCont.find( 'select.wpec-product-variations-select, input.wpec-product-variations-select-radio:checked' ).change();

			parent.scCont.find( '.wpec_product_shipping_enable' ).change( function() {
				parent.scCont.find( '.wpec_shipping_address_container' ).toggle();
				parent.scCont.find( '.wpec_address_wrap' ).toggleClass( 'shipping_enabled' );
			} );

			jQuery( '#place-order-' + parent.data.id ).click( function() {				
				parent.buttonArgs.onClick();
			} );

			jQuery( '#wpec-redeem-coupon-btn-' + parent.data.id ).click( function( e ) {
				e.preventDefault();
				var couponCode = jQuery( this ).siblings( '#wpec-coupon-field-' + parent.data.id ).val();
				if ( couponCode === '' ) {
					return false;
				}
				var wpecCouponBtn = jQuery( this );
				var wpecCouponSpinner = wpecCouponBtn.find( 'svg' );
				wpecCouponBtn.prop( 'disabled', true );
				wpecCouponSpinner.show();
				var ajaxData = {
					'action': 'wpec_check_coupon',
					'product_id': parent.data.product_id,
					'coupon_code': couponCode,
					'curr': parent.data.currency,
					/*'amount': parent.data.item_price,
					'tax': parent.data.tax,
					'shipping': parent.data.shipping*/
				};
				jQuery.post( ppecFrontVars.ajaxUrl, ajaxData, function( response ) {
					if ( response.success ) {
						parent.data.discount = response.discount;
						parent.data.discountType = response.discountType;
						parent.data.couponCode = response.code;

						jQuery( '#wpec-coupon-info-' + parent.data.id ).html( '<span class="wpec_coupon_code">' + response.discountStr + '</span> <button class="wpec_coupon_apply_btn" id="wpec-remove-coupon-' + parent.data.id + '" title="' + ppecFrontVars.str.strRemoveCoupon + '">' + ppecFrontVars.str.strRemove + '</button>' );
						jQuery( '#wpec-redeem-coupon-btn-' + parent.data.id ).hide();
						jQuery( '#wpec-coupon-field-' + parent.data.id ).hide();
						var totalCont = parent.getPriceContainer();
						var totCurr;
						var totNew;
						var priceCurr;
						var priceNew;
						if ( totalCont.find( '.wpec_price_full_total' ).length !== 0 ) {
							totCurr = totalCont.children().find( 'span.wpec_tot_current_price' ).addClass( 'wpec_line_through' );
							totNew = totalCont.children().find( 'span.wpec_tot_new_price' );
						} else {
							totCurr = totalCont.find( 'span.wpec_price_amount' ).addClass( 'wpec_line_through' );
							totNew = totalCont.find( 'span.wpec_new_price_amount' );
						}
						priceCurr = totalCont.find( 'span.wpec-price-amount' ).addClass( 'wpec_line_through' );
						priceNew = totalCont.find( 'span.wpec-new-price-amount' );
						parent.validateOrder();
						jQuery( '#wpec-remove-coupon-' + parent.data.id ).on( 'click', function( e ) {
							e.preventDefault();
							jQuery( '#wpec-coupon-info-' + parent.data.id ).html( '' );
							jQuery( '#wpec-coupon-field-' + parent.data.id ).val( '' );
							jQuery( '#wpec-coupon-field-' + parent.data.id ).show();
							jQuery( '#wpec-redeem-coupon-btn-' + parent.data.id ).show();
							totCurr.removeClass( 'wpec_line_through' );
							priceCurr.removeClass( 'wpec_line_through' );
							totNew.html( '' );
							priceNew.html( '' );
							delete parent.data.discount;
							delete parent.data.discountType;
							delete parent.data.couponCode;
							delete parent.data.discountAmount;
							delete parent.data.newPrice;
							parent.validateOrder();
						} );
					} else {
						jQuery( '#wpec-coupon-info-' + parent.data.id ).html( response.msg );
					}
					wpecCouponSpinner.hide();
					wpecCouponBtn.prop( 'disabled', false );
				} );
			} );

			jQuery( '#wpec-coupon-field-' + parent.data.id ).keydown( function( e ) {
				if ( e.keyCode === 13 ) {
					e.preventDefault();
					jQuery( '#wpec-redeem-coupon-btn-' + parent.data.id ).click();
					return false;
				}
			} );

			parent.validateOrder();

			if ( parent.data.errors ) {
				var errorEl = jQuery( '<div class="wp-ppec-form-error-msg">' + parent.data.errors + '</div>' );
				parent.scCont.append( errorEl );
				errorEl.show();
			}

			jQuery( '.wpec-button-placeholder' ).remove();
		},
		onClick: function() {
			jQuery("#place-order-"+parent.data.id).find("button>svg").show();
			jQuery("#place-order-"+parent.data.id).find("button").attr("disabled",true);
			
			parent.displayErrors();
			var errInput = parent.scCont.find( '.hasError' ).first();
			if ( errInput.length > 0 ) {

				
				errInput.focus();
				errInput.trigger( 'change' );

				jQuery("#place-order-"+parent.data.id).find("button>svg").hide();
				jQuery("#place-order-"+parent.data.id).find("button").attr("disabled",false);
			} else if ( !parent.data.total ) {
			
				parent.processPayment( {
					payer: {
						name: {
							given_name: jQuery( '#wpec_billing_first_name-' + parent.data.id ).val(),
							surname: jQuery( '#wpec_billing_last_name-' + parent.data.id ).val()
						},
						email_address: jQuery( '#wpec_billing_email-' + parent.data.id ).val(),
						address: {
							address_line_1: jQuery( '#wpec_billing_address-' + parent.data.id ).val(),
							admin_area_1: jQuery( '#wpec_billing_state-' + parent.data.id ).val(),
							admin_area_2: jQuery( '#wpec_billing_city-' + parent.data.id ).val(),
							postal_code: jQuery( '#wpec_billing_postal_code-' + parent.data.id ).val(),
							country_code: jQuery( '#wpec_billing_country-' + parent.data.id ).val()
						},
						shipping_address: {
							address_line_1: jQuery( '#wpec_shipping_address-' + parent.data.id ).val(),
							admin_area_1: jQuery( '#wpec_shipping_state-' + parent.data.id ).val(),
							admin_area_2: jQuery( '#wpec_shipping_city-' + parent.data.id ).val(),
							postal_code: jQuery( '#wpec_shipping_postal_code-' + parent.data.id ).val(),
							country_code: jQuery( '#wpec_shipping_country-' + parent.data.id ).val()
						}
					}
				}, 'wpec_process_empty_payment' );
			}
		},
		createOrder: async function( data, actions ) {
			parent.calcTotal();

			//We need to round to 2 decimal places to make sure that the API call will not fail.
			let itemTotalValueRounded = (parent.data.price * parent.data.quantity).toFixed(2);
			let itemTotalValueRoundedAsNumber = parseFloat(itemTotalValueRounded);
			//console.log('Item total value rounded: ' + itemTotalValueRoundedAsNumber);
			//console.log(parent.data);

			// Checking if shipping will be required
			let shipping_pref = 'NO_SHIPPING'; // Default value
			if (parent.data.shipping !== "" || parent.data.shipping_enable === true) {
				console.log("The physical product checkbox is enabled or there is shipping value has been configured. Setting the shipping preference to collect shipping.");
				shipping_pref = 'GET_FROM_FILE';
			}

			// Create order_data object to be sent to the server.
			var order_data = {
				intent: 'CAPTURE',
				payment_source: {
					paypal: {
						experience_context: {
							payment_method_preference: 'IMMEDIATE_PAYMENT_REQUIRED',
							shipping_preference: shipping_pref,
							user_action: 'PAY_NOW',
						}
					}
				},
				purchase_units: [ {
					amount: {
						value: parent.data.total,
						currency_code: parent.data.currency,
						breakdown: {
							item_total: {
								currency_code: parent.data.currency,
								value: itemTotalValueRoundedAsNumber
							}
						}
					},
					items: [ {
						name: parent.data.name,
						quantity: parent.data.quantity,
						unit_amount: {
							value: parent.data.price,
							currency_code: parent.data.currency
						}
					} ]
				} ]
			};

			if ( parent.data.tax ) {
				order_data.purchase_units[ 0 ].amount.breakdown.tax_total = {
					currency_code: parent.data.currency,
					value: parent.PHP_round( parent.data.tax_amount * parent.data.quantity, parent.data.dec_num )
				};
				order_data.purchase_units[ 0 ].items[ 0 ].tax = {
					currency_code: parent.data.currency,
					value: parent.data.tax_amount
				};
			}
			if ( parent.data.shipping ) {
				order_data.purchase_units[ 0 ].amount.breakdown.shipping = {
					currency_code: parent.data.currency,
					value: parent.getTotalShippingCost(),
				};
			}
			if ( parent.data.discount ) {
				order_data.purchase_units[ 0 ].amount.breakdown.discount = {
					currency_code: parent.data.currency,
					value: parseFloat( parent.data.discountAmount )
				};
			}
			console.log('Order data: ' + JSON.stringify(order_data));
			//End of create order_data object.

			let nonce = wpec_create_order_vars.nonce;

			let wpec_data_for_create = parent.data;//parent.data is the data object that was passed to the ppecHandler constructor.
			console.log('WPEC data for create-order: ' + JSON.stringify(wpec_data_for_create));

			let post_data = 'action=wpec_pp_create_order&data=' + encodeURIComponent(JSON.stringify(order_data)) + '&wpec_data=' + encodeURIComponent(JSON.stringify(wpec_data_for_create)) + '&_wpnonce=' + nonce;
			try {
				// Using fetch for AJAX request. This is supported in all modern browsers.
				const response = await fetch( ppecFrontVars.ajaxUrl, {
					method: "post",
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: post_data
				});

				const response_data = await response.json();

				if (response_data.order_id) {
					console.log('Create-order API call to PayPal completed successfully.');
					return response_data.order_id;
				} else {
					const error_message = response_data.err_msg
					console.error('Error occurred during create-order call to PayPal. ' + error_message);
					throw new Error(error_message);//This will trigger the alert in the "catch" block below.
				}
			} catch (error) {
				console.error(error.message);
				alert('Could not initiate PayPal Checkout...\n\n' + error.message);
			}
		},
		onApprove: async function( data, actions ) {
			jQuery( 'div.wp-ppec-overlay[data-ppec-button-id="' + parent.data.id + '"]' ).css( 'display', 'flex' );

			console.log('Setting up the AJAX request for capture-order call.');
			
			// Create the data object to be sent to the server.
			let pp_bn_data = {};
			pp_bn_data.order_id = data.orderID;//The orderID is the ID of the order that was created in the createOrder method.
			let wpec_data = parent.data;//parent.data is the data object that was passed to the ppecHandler constructor.
			//console.log('WPEC data (JSON): ' + JSON.stringify(wpec_data));

			let nonce = wpec_on_approve_vars.nonce;
			let post_data = 'action=wpec_pp_capture_order&data=' + encodeURIComponent(JSON.stringify(pp_bn_data)) + '&wpec_data=' + encodeURIComponent(JSON.stringify(wpec_data)) + '&_wpnonce=' + nonce;
			try {
				const response = await fetch( ppecFrontVars.ajaxUrl, {
					method: "post",
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: post_data
				});

				const response_data = await response.json();

				console.log('Capture-order API call to PayPal completed successfully.');

				// Call the completePayment method to do any redirection or display a message to the user.
				parent.completePayment(response_data); 

			} catch (error) {
				console.error(error);
				alert('PayPal returned an error! Transaction could not be processed. Enable the debug logging feature to get more details...\n\n' + JSON.stringify(error));
			}

		},
		onError: function( err ) {

			jQuery("#place-order-"+parent.data.id).find("button>svg").hide();
			jQuery("#place-order-"+parent.data.id).find("button").attr("disabled",false);

			alert( err );
		}
	};

	this.formatMoney = function( n ) {
	var decimalPlaces = isNaN(decimalPlaces = Math.abs(parent.data.dec_num)) ? 2 : parent.data.dec_num;
    var decimalSeparator = parent.data.dec_sep || ".";
    var thousandSeparator = parent.data.thousand_sep || ",";
    var sign = n < 0 ? "-" : "";
    var integerPart = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(decimalPlaces)));
    var integerPartLength = integerPart.length;
    var remainderLength = integerPartLength > 3 ? integerPartLength % 3 : 0;

    var formattedIntegerPart = sign +
        (remainderLength ? integerPart.substr(0, remainderLength) + thousandSeparator : "") +
        integerPart.substr(remainderLength).replace(/(\d{3})(?=\d)/g, "$1" + thousandSeparator) +
        (decimalPlaces ? decimalSeparator + Math.abs(n - integerPart).toFixed(decimalPlaces).slice(2) : "");

    var formats = {
        left: '{symbol}{price}',
        left_space: '{symbol} {price}',
        right: '{price}{symbol}',
        right_space: '{price} {symbol}'
    };

	var formattedPrice = formats[parent.data.curr_pos]
        .replace('{symbol}', parent.data.currency_symbol)
        .replace('{price}', formattedIntegerPart);

    return formattedPrice;
	};

	this.getPriceContainer = function() {
		return jQuery( '.' + parent.scCont.data( 'price-class' ) );
	};

	this.updateAllAmounts = function() {
		parent.calcTotal();
		var price_cont = parent.getPriceContainer();
		if ( price_cont.length > 0 ) {
			var price = price_cont.find( '.wpec-price-amount' );
			if ( price.length > 0 ) {
				price.html( parent.formatMoney( parent.data.price ) );
			}
			if ( parent.data.quantity > 1 ) {
				price_cont.find( '.wpec-quantity' ).show();
				price_cont.find( '.wpec-quantity-val' ).html( parent.data.quantity );
			} else {
				price_cont.find( '.wpec-quantity' ).hide();
			}
			var total = price_cont.find( '.wpec_tot_current_price' );
			var tot_new = price_cont.find( '.wpec_tot_new_price' );
			var price_new = price_cont.find( '.wpec-new-price-amount' );
			var shipping_new = price_cont.find( '.wpec_price_shipping_amount' );
			if ( typeof parent.data.discountAmount !== "undefined" ) {
				price_new.html( parent.formatMoney( parent.data.newPrice ) );
				tot_new.html( parent.formatMoney( parent.data.total ) );
				total.html( parent.formatMoney( parent.data.subtotal ) );
			} else if ( total.length > 0 ) {
				total.html( parent.formatMoney( parent.data.total ) );
			}
	
			// Update the total shipping cost.
			shipping_new.html(parent.formatMoney(parent.getTotalShippingCost()));
			
			var tax_val = price_cont.find( '.wpec-tax-val' );
			if ( tax_val.length > 0 ) {
				tax_val.html( parent.formatMoney( parent.data.tax_amount * parent.data.quantity ) );
			}
		}

		jQuery( document ).trigger( 'wpec_after_update_all_amounts', [ parent ] );
	};

	this.calcTotal = function() {
		var itemSubt = parseFloat( parent.data.price );
		var quantity = parseInt( parent.data.quantity );
		var tAmount = itemSubt * quantity;
		//We need to round to 2 decimal places to make sure that the API call will not fail.
		let roundedTotal = tAmount.toFixed(2);//round to 2 decimal places
		let roundedTotalAsNumber = parseFloat(roundedTotal);//convert to number
		tAmount = roundedTotalAsNumber;
		var subtotal = tAmount;

		parent.data.newPrice = itemSubt;

		if ( typeof parent.data.discount !== "undefined" ) {
			var discountAmount = 0;
			if ( parent.data.discountType === 'perc' ) {
				discountAmount = parent.PHP_round( tAmount * parent.data.discount / 100, parent.data.dec_num );
			} else {
				discountAmount = data.discount;
			}
			if ( discountAmount > tAmount ) {
				discountAmount = tAmount;
			}
			tAmount = tAmount - discountAmount;
			parent.data.discountAmount = parent.PHP_round( discountAmount, parent.data.dec_num );
			parent.data.newPrice = parent.PHP_round( itemSubt - discountAmount / quantity, parent.data.dec_num );
		}

		if ( parent.data.tax ) {
			var tax = parent.PHP_round( tAmount / quantity * parent.data.tax / 100, parent.data.dec_num );
			parent.data.tax_amount = tax;
			tAmount += tax * parent.data.quantity;
			subtotal += parent.PHP_round( subtotal / quantity * parent.data.tax / 100, parent.data.dec_num ) * parent.data.quantity;
		}

		const totalShippingCost = parent.getTotalShippingCost();
		if ( totalShippingCost ) {
			tAmount += parseFloat( totalShippingCost );			
			subtotal += parseFloat( totalShippingCost );
		}
		parent.data.total = parent.PHP_round( tAmount, parent.data.dec_num );
		parent.data.subtotal = parent.PHP_round( subtotal, parent.data.dec_num );
	};

	this.PHP_round = function( num, dec ) {
		return Math.round( num * Math.pow( 10, dec ) ) / Math.pow( 10, dec );
	};

	this.applyVariations = function( amount ) {
		var grpId;
		if ( parent.data.variations.applied ) {
			for ( grpId = 0; grpId < parent.data.variations.applied.length; ++grpId ) {
				var variation = parent.data.variations[ grpId ];
				amount = amount + parseFloat( variation.prices[ parent.data.variations.applied[ grpId ] ] );
			}
		}
		return parent.PHP_round( amount, parent.data.dec_num );
	};

	/**
	 * Calculates the total shipping cost.
	 * It calculates the per quantity shipping cost along with the base shipping cost of that product.
	 * 
	 * @returns {float} The total shipping cost in float of two decimal places. 
	 */
	this.getTotalShippingCost = function() {
		let total = 0;
		const quantity = parent.data.quantity ? parseInt(parent.data.quantity) : 1;
		const baseShipping = parent.data.shipping ? parseFloat( parent.data.shipping ) : 0;
		const shippingPerQuantity = parent.data.shipping_per_quantity ? parseFloat( parent.data.shipping_per_quantity ) : 0;
	
		total = baseShipping + (shippingPerQuantity * quantity);
		// Round to 2 decimal places.
		total = parseFloat(total.toFixed(2))
		return total;
	}

	jQuery( document ).trigger( 'wpec_before_render_button', [ this ] );

	paypal.Buttons( this.buttonArgs ).render( '#' + parent.data.id );
};

var wpecModal = function( $ ) {
	var openModalId = false;

	$( '.wpec-modal-open, .wpec-modal-overlay, .wpec-modal-close' ).on( 'click', function( e ) {
		e.preventDefault();
		toggleModal( $( this ).data( 'wpec-modal' ) );
	} );

	document.onkeydown = function( evt ) {
		evt = evt || window.event;
		var isEscape = false;
		if ( "key" in evt ) {
			isEscape = ( evt.key === "Escape" || evt.key === "Esc" );
		} else {
			isEscape = ( evt.keyCode === 27 );
		}
		if ( isEscape && openModalId ) {
			toggleModal();
		}
	};

	function toggleModal( modalId ) {
		modalId = modalId ? modalId : openModalId;
		openModalId = modalId;
		var modal = document.getElementById( modalId );
		modal.classList.toggle( 'wpec-opacity-0' );
		modal.classList.toggle( 'wpec-pointer-events-none' );
	}

	$( '.wpec-custom-number-input button' ).on( 'click', function() {
		var increment = $( this ).data( 'action' ) === 'increment' ? 1 : -1;
		var input = $( this ).parent().find( 'input' );
		var step = input.attr( 'step' ) ? Number( input.attr( 'step' ) ) : 1;
		input.val( Number( input.val() ) + increment * step ).change();
	} );

	//shortcode_wpec_show_all_products
	$("#wpec-sort-by").change(function(){
		$("#wpec-sort-by-form").submit();
	});
};

jQuery( wpecModal );
