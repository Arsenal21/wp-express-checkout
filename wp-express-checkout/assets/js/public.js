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
		try {
			var res = JSON.parse( data );
			var redirect_url = res.redirect_url;
		} catch ( e ) {
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

	this.isValidTos = function( hide_errors ) {
		var enable_actions = false;
		var tos_input = jQuery( '#wpec-tos-' + parent.data.id );
		var errMsgCont = jQuery( '.wpec_product_tos_input_container' ).find( '.wp-ppec-form-error-msg' );
		var errMsg = ppecFrontVars.str.acceptTos;
		tos_input.addClass( 'hasError' );
		if ( tos_input.prop( 'checked' ) ) {
			enable_actions = true;
			tos_input.removeClass( 'hasError' );
			errMsgCont.fadeOut( 'fast' );
		} else if ( !hide_errors ) {
			tos_input.addClass( 'hasError' );
			errMsgCont.html( errMsg );
			errMsgCont.fadeIn( 'slow' );
		}

		return enable_actions;
	};

	this.isValidTotal = function() {
		parent.calcTotal();
		return !!parent.data.total;
	};

	this.isValidCustomQuantity = function() {
		var input = jQuery( '#wp-ppec-custom-quantity[data-ppec-button-id="' + parent.data.id + '"]' );
		var errMsgCont = input.parent().siblings( '.wp-ppec-form-error-msg' );
		var val_orig = input.val();
		var val = parseInt( val_orig );
		var error = false;
		var errMsg = ppecFrontVars.str.enterQuantity;

		if ( isNaN( val ) ) {
			error = true;
		} else if ( val_orig % 1 !== 0 ) {
			error = true;
		} else if ( val <= 0 ) {
			error = true;
		} else {
			input.removeClass( 'hasError' );
			errMsgCont.fadeOut( 'fast' );
			parent.data.quantity = val;
		}
		if ( error ) {
			input.addClass( 'hasError' );
			errMsgCont.html( errMsg );
			errMsgCont.fadeIn( 'slow' );
		}
		return !error;
	};

	this.isValidCustomAmount = function() {
		var input = jQuery( '#wp-ppec-custom-amount[data-ppec-button-id="' + parent.data.id + '"]' );
		var errMsgCont = input.siblings( '.wp-ppec-form-error-msg' );
		var val_orig = input.val();
		var val = parseFloat( val_orig );
		var error = false;
		var errMsg = ppecFrontVars.str.enterAmount;
		if ( !isNaN( val ) && 0 < val ) {
			input.removeClass( 'hasError' );
			errMsgCont.fadeOut( 'fast' );
			parent.data.orig_price = val;
			parent.data.price = parent.applyVariations( val );
		} else {
			input.addClass( 'hasError' );
			errMsgCont.html( errMsg );
			errMsgCont.fadeIn( 'slow' );
			error = true;
		}
		return !error;
	};

	if ( this.data.btnStyle.layout === 'horizontal' ) {
		this.data.btnStyle.tagline = false;
	}

	this.clientVars = {};

	this.clientVars[ this.data.env ] = this.data.client_id;

	this.validateOrder = function( e, hide_errors ) {
		var enable_actions = true;
		hide_errors = ( typeof hide_errors !== 'undefined' ) ? hide_errors : false;
		if (
			( parent.data.custom_quantity === "1" && !parent.isValidCustomQuantity() ) ||
			( parent.data.custom_amount === "1" && !parent.isValidCustomAmount() ) ||
			( parent.data.tos_enabled === 1 && !parent.isValidTos( hide_errors ) ) ||
			!parent.isValidTotal()
		) {
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

	this.scCont = jQuery( '.wp-ppec-shortcode-container[data-ppec-button-id="' + parent.data.id + '"]' );

	this.buttonArgs = {
		env: parent.data.env,
		client: parent.clientVars,
		style: parent.data.btnStyle,
		commit: true,
		onInit: function( data, actions ) {
			parent.actions = actions;
			if ( parent.data.tos_enabled === 1 ) {
				jQuery( '#wpec-tos-' + parent.data.id ).change( parent.validateOrder );
			}
			if ( parent.data.custom_quantity === "1" ) {
				jQuery( '#wp-ppec-custom-quantity[data-ppec-button-id="' + parent.data.id + '"]' ).change( parent.validateOrder );
			}
			if ( parent.data.custom_amount === "1" ) {
				jQuery( '#wp-ppec-custom-amount[data-ppec-button-id="' + parent.data.id + '"]' ).change( parent.validateOrder );
			}
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
					parent.validateOrder( null, true );
				}
			} );
			parent.scCont.find( 'select.wpec-product-variations-select, input.wpec-product-variations-select-radio:checked' ).change();

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
						var totalCont = jQuery( '.wp-ppec-shortcode-container[data-ppec-button-id="' + parent.data.id + '"]' ).closest( '.wpec-product-item, .wpec-post-item' ).find( '.wpec-price-container' );
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

			parent.validateOrder( null, true );

			if ( parent.data.errors ) {
				var errorEl = jQuery( '<div class="wp-ppec-form-error-msg">' + parent.data.errors + '</div>' );
				parent.scCont.append( errorEl );
				errorEl.show();
			}

			jQuery( '.wpec-button-placeholder' ).remove();
		},
		onClick: function() {
			var errInput = parent.scCont.find( '.hasError' ).first();
			if ( errInput ) {
				errInput.focus();
				errInput.trigger( 'change' );
			}

			if ( !parent.data.total ) {
				parent.processPayment( {}, 'wpec_process_empty_payment' );
			}
		},
		createOrder: function( data, actions ) {
			parent.calcTotal();
			var order_data = {
				application_context: {
					shipping_preference: parent.data.shipping_enable ? 'GET_FROM_FILE' : 'NO_SHIPPING'
				},
				purchase_units: [ {
					amount: {
						value: parent.data.total,
						currency_code: parent.data.currency,
						breakdown: {
							item_total: {
								currency_code: parent.data.currency,
								value: parent.data.price * parent.data.quantity
							}
						}
					},
					items: [ {
						name: parent.data.name,
						quantity: parent.data.quantity,
						category: parent.data.shipping_enable ? 'PHYSICAL_GOODS' : 'DIGITAL_GOODS',
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
					value: parseFloat( parent.data.shipping )
				};
			}
			if ( parent.data.discount ) {
				order_data.purchase_units[ 0 ].amount.breakdown.discount = {
					currency_code: parent.data.currency,
					value: parseFloat( parent.data.discountAmount )
				};
			}

			return actions.order.create( order_data );
		},
		onApprove: function( data, actions ) {
			jQuery( 'div.wp-ppec-overlay[data-ppec-button-id="' + parent.data.id + '"]' ).css( 'display', 'flex' );
			return actions.order.capture().then( function( details ) {
				parent.processPayment( details, "wpec_process_payment" );
			} );
		},
		onError: function( err ) {
			alert( err );
		}
	};

	this.formatMoney = function( n ) {
		var c = isNaN( c = Math.abs( parent.data.dec_num ) ) ? 2 : parent.data.dec_num,
			d = d == undefined ? "." : parent.data.dec_sep,
			t = t == undefined ? "," : parent.data.thousand_sep,
			s = n < 0 ? "-" : "",
			i = String( parseInt( n = Math.abs( Number( n ) || 0 ).toFixed( c ) ) ),
			j = ( j = i.length ) > 3 ? j % 3 : 0;

		var result = s + ( j ? i.substr( 0, j ) + t : "" ) + i.substr( j ).replace( /(\d{3})(?=\d)/g, "$1" + t ) + ( c ? d + Math.abs( n - i ).toFixed( c ).slice( 2 ) : "" );
		var formats = {
			left: '{symbol}{price}',
			left_space: '{symbol} {price}',
			right: '{price}{symbol}',
			right_space: '{price} {symbol}'
		};

		result = formats[ parent.data.curr_pos ]
			.replace( '{symbol}', parent.data.currency_symbol )
			.replace( '{price}', result );

		return result;
	};

	this.updateAllAmounts = function() {
		parent.calcTotal();
		var price_cont = jQuery( '.wp-ppec-shortcode-container[data-ppec-button-id="' + parent.data.id + '"]' ).closest( '.wpec-product-item, .wpec-post-item' ).find( '.wpec-price-container' );
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

			if ( typeof parent.data.discountAmount !== "undefined" && total.length > 0 ) {
				price_new.html( parent.formatMoney( parent.data.newPrice ) );
				tot_new.html( parent.formatMoney( parent.data.total ) );
				total.html( parent.formatMoney( parent.data.subtotal ) );
			} else if ( total.length > 0 ) {
				total.html( parent.formatMoney( parent.data.total ) );
			}
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
		var subtotal = tAmount;

		parent.data.newPrice = itemSubt;

		if ( typeof parent.data.discount !== "undefined" ) {
			var discountAmount = 0;
			if ( parent.data.discountType === 'perc' ) {
				discountAmount = parent.PHP_round( tAmount * parent.data.discount / 100, parent.data.dec_num );
			} else {
				discountAmount = data.discount;
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

		if ( parent.data.shipping ) {
			tAmount += parseFloat( parent.data.shipping );
			subtotal += parseFloat( parent.data.shipping );
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
				amount = amount + parseFloat( parent.data.variations.prices[ grpId ][ parent.data.variations.applied[ grpId ] ] );
			}
		}
		return amount;
	};

	jQuery( document ).trigger( 'wpec_before_render_button', [ this ] );

	paypal.Buttons( this.buttonArgs ).render( '#' + parent.data.id );
};

jQuery( function( $ ) {
	var openModalId = false;

	$( '.wpec-modal-open, .wpec-modal-overlay, .wpec-modal-close' ).on( 'click', function() {
		event.preventDefault();
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
} );
