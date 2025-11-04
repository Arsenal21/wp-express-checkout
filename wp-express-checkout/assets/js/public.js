var ppecHandler = function( data ) {
	this.data = data;
	this.actions = {};

	const parent = this;

	// TODO: Need to convert this to vanilla js
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
		let ret = true;
		let dlgTitle = ppecFrontVars.str.paymentCompleted;
		let dlgMsg = ppecFrontVars.str.redirectMsg;
		let redirect_url;
		if ( data.redirect_url ) {
			redirect_url = data.redirect_url;
		} else {
			dlgTitle = ppecFrontVars.str.errorOccurred;
			dlgMsg = data;
			ret = false;
		}
		document.querySelector( '.wp-ppec-overlay[data-ppec-button-id="' + parent.data.id + '"]' ).style.display = 'none';

		const dialogMsgParagraph = document.createElement('p');
		dialogMsgParagraph.id = 'wp-ppdg-dialog-msg';
		dialogMsgParagraph.textContent = dlgMsg;
		const dialog = document.createElement('div');
		dialog.id = 'wp-ppdg-dialog-message';
		dialog.title = dlgTitle;
		dialog.appendChild(dialogMsgParagraph);

		parent.scCont?.querySelector('.wp-ppec-button-container')?.insertAdjacentElement('afterend', dialog);

		// Fade in dialog
		dialog.style.display = 'none';
		dialog.style.opacity = 0;
		dialog.style.transition = 'opacity 0.5s';
		dialog.style.display = 'block';
		setTimeout(() => {
			dialog.style.opacity = 1;
		}, 10);

		if ( redirect_url ) {
			location.href = redirect_url;
		}
		return ret;
	};

	this.ValidatorTos = function( input ) {
		return input.checked !== true ? ppecFrontVars.str.acceptTos : '';
	};

	this.ValidatorBilling = function( input ) {
		let val;
		if (input.type === 'checkbox'){
			val = input.checked;
		} else {
			val = Boolean(input.value.trim())
		}

		return parent.isElementVisible(input) && !val ? ppecFrontVars.str.required : null;
	};

	this.isValidTotal = function() {
		parent.calcTotal();
		return !!parent.data.total;
	};

	this.ValidatorQuantity = function( input ) {
		const val_orig = input.value;
		const val = parseInt( val_orig );
		let error = false;
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
		const val_orig = input.value;
		const val = parseFloat( val_orig );
		const min_val = input.getAttribute( 'min' );
		let error = false;
		const errMsg = ppecFrontVars.str.enterAmount;
		if ( !isNaN( val ) && min_val <= val ) {
			parent.data.orig_price = val;
			parent.data.price = parent.applyVariations( val );
		} else {
			error = errMsg;
		}
		return error;
	};

	// if ( this.data.btnStyle.layout === 'horizontal' ) {
	// 	this.data.btnStyle.tagline = false;
	// }
	//
	// this.clientVars = {};
	//
	// this.clientVars[ this.data.env ] = this.data.client_id;

	this.inputs = [];

	this.validateInput = function( input, validator ) {
		if ( !input ) {
			return false;
		}

		this.inputs.push( [ input, validator ] );

		input.addEventListener('change', function(e) {
			parent.displayInputError( this, validator );
			parent.validateOrder();
		})
	};

	this.validateOrder = function() {
		let isValid = true;

		document.querySelectorAll('#wpec_billing_' + parent.data.id + ', #place-order-' + parent.data.id ).forEach(el => {
			parent.toggleVisibility(el, 'block', !parent.isValidTotal());
		})

		parent.toggleVisibility(
			document.getElementById( parent.data.id ),
			'block',
			!parent.isElementVisible(document.getElementById( 'place-order-' + parent.data.id ))
		);

		parent.inputs.forEach( function( inputArr ) {
			const input = inputArr[ 0 ];
			const validator = inputArr[ 1 ];
			if ( validator( input ) ) {
				isValid = false;
			}
		} );

		if ( !parent.isValidTotal() ) {
			isValid = false;
		}

		// TODO: this may not needed any more.
		// if ( isValid ) {
		// 	parent.actions?.enable();
		// } else {
		// 	parent.actions?.disable();
		// }

		parent.updateAllAmounts();

		// Dispatch a custom event for addons to listen on 'validateOrder' function run.
		document.dispatchEvent(new CustomEvent('wpec_validate_order', {
			detail: {
				ppecHandler: parent,
				isValid: isValid,
			}
		}))
		jQuery( document ).trigger( 'wpec_validate_order', [ parent ] ); // TODO: Need to remove this jquery version after all addon code is updated to the vanilla version.

		return isValid;
	};

	this.displayInputError = function( input, validator ) {
		const error = validator( input );
		const errMsgCont = input.type === 'checkbox' || input.type === 'number' ? input.parentNode.parentNode.querySelector( '.wp-ppec-form-error-msg' ) : input.parentNode.querySelector( '.wp-ppec-form-error-msg' );

		if (error && error.length){
			input.classList.add('hasError');
			errMsgCont.style.display = 'block';
			errMsgCont.innerText = error;
		}else {
			input.classList.remove('hasError');
			errMsgCont.style.display = 'none';
			errMsgCont.innerText = '';
		}
	};

	this.displayErrors = function() {
		parent.inputs.forEach( function( input ) {
			parent.displayInputError( input[ 0 ], input[ 1 ] );
		} );
	};

	/**
	 * @deprecated Use `getScCont()` instead.
	 */
	this.scCont = document.querySelector( '.wp-ppec-shortcode-container[data-ppec-button-id="' + parent.data.id + '"]' );

	this.getScCont = function(){
		return document.querySelector( '.wp-ppec-shortcode-container[data-ppec-button-id="' + parent.data.id + '"]' );
	}

	// TODO: Need to convert this to vanilla js
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
		// Here, we're using 'querySelectorAll' instead of 'querySelector', because there is more than one element.
		return document.querySelectorAll( '.' + parent.scCont.getAttribute( 'data-price-class' ) );
	};

	this.getProductItemContainer = function() {
		// Get the product item container using the price container, so that we don't select other product item container when more than one same product shortcode in a page.
		const priceContainer = document.querySelector( '.' + parent.scCont.getAttribute( 'data-price-class' ) );

		// Selector of the container may vary across different templates. Select the one it can find.

		// For template 1,3
		let productItemContainer = parent.closestParent(priceContainer, '.wpec-product-item-' + parent.data.product_id );

		// For template 2
		if (!productItemContainer){
			productItemContainer = parent.closestParent(priceContainer, '.wpec-post-item-' + parent.data.product_id );
		}

		// For url payment form
		if (!productItemContainer){
			productItemContainer = parent.closestParent(priceContainer, '.wpec-modal-product-' + parent.data.product_id );
		}

		return productItemContainer;
	};

	this.updateAllAmounts = function() {
		parent.calcTotal();

		const productItemContainer = parent.getProductItemContainer();

		if ( productItemContainer ){
			productItemContainer.querySelectorAll( '.wpec-price-amount' ).forEach(el => el.textContent = parent.formatMoney( parent.data.price ))

			if ( parent.data.quantity > 1 ) {
				productItemContainer.querySelectorAll( '.wpec-quantity' ).forEach(el => el.style.display = 'inline')
				productItemContainer.querySelectorAll( '.wpec-quantity-val' ).forEach(el => el.textContent = parent.data.quantity)
			} else {
				productItemContainer.querySelectorAll( '.wpec-quantity' ).forEach(el => el.style.display = 'none');
			}

			const total = productItemContainer.querySelectorAll( '.wpec_tot_current_price' );
			const tot_new = productItemContainer.querySelectorAll( '.wpec_tot_new_price' );
			const price_new = productItemContainer.querySelectorAll( '.wpec-new-price-amount' );

			if ( typeof parent.data.discountAmount !== "undefined" ) {
				price_new.forEach(el => el.textContent = parent.formatMoney( parent.data.newPrice ) );
				tot_new.forEach(el => el.textContent = parent.formatMoney( parent.data.total ) );
				total.forEach(el => el.textContent = parent.formatMoney( parent.data.subtotal ));
			} else if ( total.length > 0 ) {
				total.forEach(el => el.textContent = parent.formatMoney( parent.data.total ));
			}

			// Update the total shipping cost.
			const shipping_new = productItemContainer.querySelectorAll( '.wpec_price_shipping_amount' );
			shipping_new.forEach(el => el.textContent = parent.formatMoney(parent.getTotalShippingCost()));

			// Update the total tax cost.
			const tax_val = productItemContainer.querySelectorAll( '.wpec-tax-val' );
			tax_val.forEach(el => el.textContent = parent.formatMoney( parent.data.tax_amount * parent.data.quantity ) );
		}

		// TODO: Need to convert this to vanilla js
		jQuery( document ).trigger( 'wpec_after_update_all_amounts', [ parent ] );
	};

	this.calcTotal = function() {
		let itemSubt = parseFloat( parent.data.price );
		let quantity = parseInt( parent.data.quantity );
		let tAmount = itemSubt * quantity;
		//We need to round to 2 decimal places to make sure that the API call will not fail.
		let roundedTotal = tAmount.toFixed(2);//round to 2 decimal places
		let roundedTotalAsNumber = parseFloat(roundedTotal);//convert to number
		tAmount = roundedTotalAsNumber;
		let subtotal = tAmount;

		parent.data.newPrice = itemSubt;

		if ( typeof parent.data.discount !== "undefined" ) {
			let discountAmount = 0;
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
			let tax = parent.PHP_round( tAmount / quantity * parent.data.tax / 100, parent.data.dec_num );
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
		let grpId;
		if ( parent.data.variations.applied ) {
			for ( grpId = 0; grpId < parent.data.variations.applied.length; ++grpId ) {
				let variation = parent.data.variations[ grpId ];
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

	this.toggleVisibility = function (element, displayVisibilityType = 'block', condition = null) {
		if (!element){
			return;
		}

		// if no condition provided, then the condition will be based on whether the element is visible or not.
		if (condition === null) {
			// No specific condition provided.
			// The content will only visible/invisible if its currently invisible/visible respectively.
			condition = !parent.isElementVisible(element);
		}

		// Show / hide the element based on condition.
		if (condition === true) {
			element.style.display = displayVisibilityType;
		} else {
			element.style.display = 'none';
		}
	}

	this.isElementVisible = function (element) {
		// Check if element exists in the DOM and is an Element node
		if (!(element instanceof Element)) {
			return false;
		}

		// Check if the element has a non-zero bounding box (displayed and has size)
		const style = getComputedStyle(element);
		const hasVisibleDisplay = style.display !== 'none';
		const hasVisibleVisibility = style.visibility !== 'hidden';
		const hasNonZeroSize = element.offsetWidth > 0 && element.offsetHeight > 0;

		return hasVisibleDisplay && hasVisibleVisibility && hasNonZeroSize;
	}

	this.closestParent = function (element, selector) {
		// Traverse up the DOM tree
		while (element) {
			// Check if the current element matches the selector
			if (element.matches(selector)) {
				return element;
			}
			// Move to the parent element
			element = element.parentElement;
		}
		// Return null if no matching parent is found
		return null;
	}

	this.construct = function(){
		parent.validateInput( document.getElementById( 'wpec-tos-' + parent.data.id ), parent.ValidatorTos );
		parent.validateInput( document.querySelector( '#wp-ppec-custom-quantity[data-ppec-button-id="' + parent.data.id + '"]' ), parent.ValidatorQuantity );
		parent.validateInput( document.querySelector( '#wp-ppec-custom-amount[data-ppec-button-id="' + parent.data.id + '"]' ), parent.ValidatorAmount );
		document.querySelectorAll( '#wpec_billing_' + parent.data.id + ' .wpec_required' ).forEach( function(element) {
			parent.validateInput( element, parent.ValidatorBilling );
		} );
		parent.data.orig_price = parseFloat( parent.data.price );
		parent.scCont.querySelectorAll( 'select.wpec-product-variations-select, input.wpec-product-variations-select-radio' )?.forEach(function (item){
			item.addEventListener('change', function (e){
				const grpId = e.currentTarget.getAttribute( 'data-wpec-variations-group-id' );
				const varId = e.currentTarget.value;
				if ( Object.getOwnPropertyNames( parent.data.variations ).length !== 0 ) {
					if ( !parent.data.variations.applied ) {
						parent.data.variations.applied = [];
					}
					parent.data.variations.applied[ grpId ] = varId;
					parent.data.price = parent.applyVariations( parent.data.orig_price );
					parent.validateOrder();
				}
			})
		});
		parent.scCont.querySelectorAll( 'select.wpec-product-variations-select, input.wpec-product-variations-select-radio:checked' )?.forEach(el => {
			// Dispatch change event to trigger any 'change' event listeners on this element
			el.dispatchEvent( new Event('change') );
		})

		parent.scCont.querySelector( '.wpec_product_shipping_enable' )?.addEventListener( 'change', function() {
			parent.toggleVisibility(parent.scCont.querySelector( '.wpec_shipping_address_container' ), 'inherit');
			parent.scCont.querySelector( '.wpec_address_wrap' )?.classList.toggle('shipping_enabled');
		} );

		document.getElementById('place-order-' + parent.data.id)?.addEventListener('click', () => {
			const hasInputErrors = this.checkAndShowInputErrors();
			if (hasInputErrors){
				return;
			}

			if ( !parent.data.total ) {
				const same_shipping_billing_address_enabled = parent.scCont.querySelector( '.wpec_product_shipping_enable' )?.checked;
				const billing_address = {
					address_line_1: document.getElementById( 'wpec_billing_address-' + parent.data.id )?.value,
					admin_area_1: document.getElementById( 'wpec_billing_state-' + parent.data.id )?.value,
					admin_area_2: document.getElementById( 'wpec_billing_city-' + parent.data.id )?.value,
					postal_code: document.getElementById( 'wpec_billing_postal_code-' + parent.data.id )?.value,
					country_code: document.getElementById( 'wpec_billing_country-' + parent.data.id )?.value,
				};
				const shipping_address = same_shipping_billing_address_enabled ? billing_address : {
					address_line_1: document.getElementById( 'wpec_shipping_address-' + parent.data.id )?.value,
					admin_area_1: document.getElementById( 'wpec_shipping_state-' + parent.data.id )?.value,
					admin_area_2: document.getElementById( 'wpec_shipping_city-' + parent.data.id )?.value,
					postal_code: document.getElementById( 'wpec_shipping_postal_code-' + parent.data.id )?.value,
					country_code: document.getElementById( 'wpec_shipping_country-' + parent.data.id )?.value,
				};

				const paymentData = {
					payer: {
						name: {
							given_name: document.getElementById( 'wpec_billing_first_name-' + parent.data.id )?.value,
							surname: document.getElementById( 'wpec_billing_last_name-' + parent.data.id )?.value,
						},
						email_address: document.getElementById( 'wpec_billing_email-' + parent.data.id )?.value,
						address: billing_address,
						shipping_address: shipping_address,
					}
				}

				document.dispatchEvent(new CustomEvent('wpec_process_free_payment', {
					detail: {
						paymentData,
						data: parent.data,
					}
				}));

				parent.processPayment(paymentData, 'wpec_process_empty_payment' );
			}
		});

		document.getElementById( 'wpec-redeem-coupon-btn-' + parent.data.id )?.addEventListener('click', async function (e) {
			e.preventDefault();
			const wpecCouponBtn = e.currentTarget;
			const wpecCouponInputField = document.getElementById('wpec-coupon-field-' + parent.data.id)
			const couponCode = wpecCouponInputField?.value;
			if (couponCode.trim() === '') {
				return false;
			}
			const wpecCouponSpinner = wpecCouponBtn.querySelector('svg');
			wpecCouponBtn.setAttribute('disabled', true);
			wpecCouponSpinner.style.display = 'inline';

			const ajaxData = new URLSearchParams({
				'action': 'wpec_check_coupon',
				'product_id': parent.data.product_id,
				'coupon_code': couponCode,
				'curr': parent.data.currency,
				/*'amount': parent.data.item_price,
				'tax': parent.data.tax,
				'shipping': parent.data.shipping*/
			});
			try {
				const response = await fetch(ppecFrontVars.ajaxUrl, {
					method: "post",
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: ajaxData
				});

				const response_data = await response.json();

				if (response_data.success) {
					parent.data.discount = response_data.discount;
					parent.data.discountType = response_data.discountType;
					parent.data.couponCode = response_data.code;

					document.getElementById('wpec-coupon-info-' + parent.data.id).innerHTML = '<span class="wpec_coupon_code">' + response_data.discountStr + '</span><button class="wpec_coupon_apply_btn" id="wpec-remove-coupon-' + parent.data.id + '" title="' + ppecFrontVars.str.strRemoveCoupon + '">' + ppecFrontVars.str.strRemove + '</button>'
					document.getElementById('wpec-redeem-coupon-btn-' + parent.data.id).style.display = 'none';
					document.getElementById('wpec-coupon-field-' + parent.data.id).style.display = 'none';

					const totalContainers = parent.getPriceContainer();
					// Note: there is more than one price container in a single product shortcode.
					totalContainers.forEach(function (totalCont){
						let totCurr;
						let totNew;
						if (totalCont.querySelector('.wpec_price_full_total')) {
							totCurr = totalCont.querySelector('span.wpec_tot_current_price')
							totCurr?.classList.add('wpec_line_through');
							totNew = totalCont.querySelector('span.wpec_tot_new_price');
						} else {
							totCurr = totalCont.querySelector('span.wpec_price_amount')
							totCurr?.classList.add('wpec_line_through');
							totNew = totalCont.querySelector('span.wpec_new_price_amount');
						}

						const priceCurr = totalCont.querySelector('span.wpec-price-amount')
						priceCurr?.classList.add('wpec_line_through');

						const priceNew = totalCont.querySelector('span.wpec-new-price-amount');
						parent.validateOrder();

						document.getElementById('wpec-remove-coupon-' + parent.data.id)?.addEventListener('click', function (e) {
							e.preventDefault();
							document.getElementById('wpec-coupon-info-' + parent.data.id).innerHTML = '';
							document.getElementById('wpec-coupon-field-' + parent.data.id).value = '';
							document.getElementById('wpec-coupon-field-' + parent.data.id).style.display = 'block';
							document.getElementById('wpec-redeem-coupon-btn-' + parent.data.id).style.display = 'block';
							totCurr?.classList.remove('wpec_line_through');
							priceCurr?.classList.remove('wpec_line_through');
							if (totNew) {
								totNew.innerHTML = '';
							}
							if (priceNew) {
								priceNew.innerHTML = '';
							}
							delete parent.data.discount;
							delete parent.data.discountType;
							delete parent.data.couponCode;
							delete parent.data.discountAmount;
							delete parent.data.newPrice;
							parent.validateOrder();
						});
					})
				} else {
					document.getElementById('wpec-coupon-info-' + parent.data.id).innerHTML = response_data.msg
				}
				wpecCouponSpinner.style.display = 'none';
				wpecCouponBtn.removeAttribute('disabled');

			} catch (err) {
				alert(err.message);
			}
		})

		const couponField = document.getElementById( 'wpec-coupon-field-' + parent.data.id );
		couponField?.addEventListener( 'keydown', function( keyboardEvent ) {
			if ( keyboardEvent.code === 13 ) {
				keyboardEvent.preventDefault();
				document.getElementById( 'wpec-redeem-coupon-btn-' + parent.data.id ).click();
				return false;
			}
		} );

		parent.validateOrder();

		if ( parent.data.errors ) {
			const errorEl = document.createElement('div');
			errorEl.className = 'wp-ppec-form-error-msg';
			errorEl.innerHTML = parent.data.errors;

			parent.getScCont().appendChild( errorEl );
			errorEl.style.display = 'block';
		}

		document.querySelectorAll( '.wpec-button-placeholder' ).forEach( function (el) {
			el.remove()
		})

	}
	this.construct(); // TODO: Need to move it to class constructor.


	this.checkAndShowInputErrors = () => {
		let result = false;

		const wpecPlaceOrderButtonSection = document.getElementById("place-order-" + parent.data.id);
		if (wpecPlaceOrderButtonSection) {
			wpecPlaceOrderButtonSection.querySelector("button>svg").style.display = 'inline';
			wpecPlaceOrderButtonSection.querySelector("button").setAttribute("disabled", true);
		}
		parent.displayErrors();
		// Get first error input if there is any.
		const errInput = parent.getScCont().querySelector('.hasError');
		if (errInput) {
			errInput.focus();
			errInput.dispatchEvent(new Event('change'));

			if (wpecPlaceOrderButtonSection) {
				wpecPlaceOrderButtonSection.querySelector("button>svg").style.display = 'none';
				wpecPlaceOrderButtonSection.querySelector("button").removeAttribute("disabled");
			}

			result = true;
		}

		return result;
	}
};

const wpecModal = function( $ ) {
	let openModalId = false;

	document.querySelectorAll( '.wpec-modal-open, .wpec-modal-overlay, .wpec-modal-close' ).forEach(function (el){
		el.addEventListener( 'click', function( e ) {
			e.preventDefault();
			toggleModal( this.getAttribute( 'data-wpec-modal' ) );
		} )
	});

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

	document.querySelectorAll('.wpec-custom-number-input button').forEach(button => {
		button.addEventListener('click', function() {
			const increment = this.getAttribute('data-action') === 'increment' ? 1 : -1;
			const customQuantityInput = this.parentElement.querySelector('input');
			const step = customQuantityInput.getAttribute('step') ? Number(customQuantityInput.getAttribute('step')) : 1;
			customQuantityInput.value = Number(customQuantityInput.value) + increment * step;
			customQuantityInput.dispatchEvent(new Event('change'));
		});
	});

	// TODO: Need to convert this to vanilla js
	//shortcode_wpec_show_all_products
	$("#wpec-sort-by").change(function(){
		$("#wpec-sort-by-form").submit();
	});
};

jQuery( wpecModal );
