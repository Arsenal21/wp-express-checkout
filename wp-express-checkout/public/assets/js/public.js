var ppecHandler = function( data ) {
	this.data = data;
	var parent = this;

	this.processPayment = function( payment ) {
		jQuery.post( ppecFrontVars.ajaxUrl, { action: "wpec_process_payment", wp_ppdg_payment: payment } )
				.done( function( data ) {
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
					jQuery( 'div#wp-ppdg-dialog-message' ).attr( 'title', dlgTitle );
					jQuery( 'p#wp-ppdg-dialog-msg' ).html( dlgMsg );
					jQuery( 'div.wp-ppec-overlay[data-ppec-button-id="' + parent.data.id + '"]' ).hide();
					if ( redirect_url ) {
						location.href = redirect_url;
					}
					return ret;
				} );
	};

	this.isValidCustomQuantity = function() {
		var input = jQuery( 'input#wp-ppec-custom-quantity[data-ppec-button-id="' + parent.data.id + '"]' );
		var errMsgCont = input.siblings( '.wp-ppec-form-error-msg' );
		var val_orig = input.val();
		var val = parseInt( val_orig );
		var error = false;
		var errMsg = ppecFrontVars.str.enterQuantity;
		// Preserve original quantity.
		if ( parent.data.quantity && ! parent.data.orig_quantity ) {
			parent.data.orig_quantity = parent.data.quantity;
		}
		if ( isNaN( val ) ) {
			error = true;
		} else if ( val_orig % 1 !== 0 ) {
			error = true;
		} else if ( val <= 0 ) {
			error = true;
		} else if ( parent.data.orig_quantity && val > parent.data.orig_quantity ) {
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
		var input = jQuery( 'input#wp-ppec-custom-amount[data-ppec-button-id="' + parent.data.id + '"]' );
		var errMsgCont = input.siblings( '.wp-ppec-form-error-msg' );
		var val_orig = input.val();
		var val = parseFloat( val_orig );
		var error = false;
		var errMsg = ppecFrontVars.str.enterAmount;
		if ( ! isNaN( val ) && 0 < val ) {
			input.removeClass( 'hasError' );
			errMsgCont.fadeOut( 'fast' );
			parent.data.price = val;
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

	this.clientVars = { };

	this.clientVars[this.data.env] = this.data.client_id;

	this.scCont = jQuery( '.wp-ppec-shortcode-container[data-ppec-button-id="' + parent.data.id + '"]' );

	paypal.Buttons( {
		env: parent.data.env,
		client: parent.clientVars,
		style: parent.data.btnStyle,
		commit: true,
		onInit: function( data, actions ) {
			var enable_actions = true;
			if ( parent.data.custom_quantity === "1" ) {
				jQuery( 'input#wp-ppec-custom-quantity[data-ppec-button-id="' + parent.data.id + '"]' ).change( function() {
					if ( ! parent.isValidCustomQuantity() ) {
						enable_actions = false;
					} else {
						parent.updateAllAmounts();
					}
				} );
			}
			if ( parent.data.custom_amount === "1" ) {
				jQuery( 'input#wp-ppec-custom-amount[data-ppec-button-id="' + parent.data.id + '"]' ).change( function() {
					if ( ! parent.isValidCustomAmount() ) {
						enable_actions = false;
					} else {
						parent.updateAllAmounts();
					}
				} );
			}
			if ( enable_actions ) {
				actions.enable();
			} else {
				actions.disable();
			}
		},
		onClick: function() {
			var errInput = parent.scCont.find( '.hasError' ).first();
			if ( errInput ) {
				errInput.focus();
				errInput.trigger( 'change' );
			}
		},
		createOrder: function( data, actions ) {
			parent.calcTotal();
			var order_data = {
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
								unit_amount: {
									value: parent.data.price,
									currency_code: parent.data.currency
								}
							} ]
					} ]
			};
			if ( parent.data.tax ) {
				order_data.purchase_units[0].amount.breakdown.tax_total = {
					currency_code: parent.data.currency,
					value: parent.data.tax_amount * parent.data.quantity
				};
				order_data.purchase_units[0].items[0].tax = {
					currency_code: parent.data.currency,
					value: parent.data.tax_amount
				};
			}
			if ( parent.data.shipping ) {
				order_data.purchase_units[0].amount.breakdown.shipping = {
					currency_code: parent.data.currency,
					value: parseFloat( parent.data.shipping )
				};
			}
			return actions.order.create( order_data );
		},
		onApprove: function( data, actions ) {
			jQuery( 'div.wp-ppec-overlay[data-ppec-button-id="' + parent.data.id + '"]' ).css( 'display', 'flex' );
			return actions.order.capture().then( function( details ) {
				parent.processPayment( details );
			} );
		},
		onError: function( err ) {
			alert( err );
		}
	} ).render( '#' + parent.data.id );

	this.formatMoney = function (n) {
		var c = isNaN(c = Math.abs(parent.data.dec_num)) ? 2 : parent.data.dec_num,
			d = d == undefined ? "." : parent.data.dec_sep,
			t = t == undefined ? "," : parent.data.thousand_sep,
			s = n < 0 ? "-" : "",
			i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))),
			j = (j = i.length) > 3 ? j % 3 : 0;

		var result = s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
		var formats = {
			left        : '{symbol}{price}',
			left_space  : '{symbol} {price}',
			right       : '{price}{symbol}',
			right_space : '{price} {symbol}'
		};

		result = formats[parent.data.curr_pos]
			.replace( '{symbol}', parent.data.currency )
			.replace( '{price}', result );

		return result;
	};

	this.updateAllAmounts = function() {
		parent.calcTotal();
		var price_cont = jQuery( '.wp-ppec-shortcode-container[data-ppec-button-id="' + parent.data.id + '"]' ).closest( '.wpec-product-item' ).find( '.wpec-price-container' );
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
			if ( total.length > 0 ) {
				total.html( parent.formatMoney( parent.data.total ) );
			}
			var tax_val = price_cont.find( '.wpec-tax-val' );
			if ( tax_val.length > 0 ) {
				tax_val.html( parent.formatMoney( parent.data.tax_amount * parent.data.quantity ) );
			}
		}
	};

	this.calcTotal = function() {
		var itemSubt = parseFloat( parent.data.price );
		var tAmount  = itemSubt * parseInt( parent.data.quantity );

		if ( parent.data.tax ) {
			var tax = parent.PHP_round( itemSubt * parent.data.tax / 100, parent.data.dec_num );
			parent.data.tax_amount = tax;
			tAmount += tax * parent.data.quantity;
		}

		if ( parent.data.shipping ) {
			tAmount += parseFloat( parent.data.shipping );
		}

		parent.data.total = parent.PHP_round( tAmount, parent.data.dec_num );
	};

	this.PHP_round = function( num, dec ) {
		return Math.round( num * Math.pow( 10, dec ) ) / Math.pow( 10, dec );
	};
};
