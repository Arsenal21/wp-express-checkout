/* global ppecFrontVars, wpec_create_order_vars, wpec_on_approve_vars */

class WPECPayPalHandler {

    constructor(btnData, wpecHandler) {
        this.btnData = btnData;

        this.wpecHandler = wpecHandler;

        if (this.btnData.btnStyle.layout === 'horizontal') {
            this.btnData.btnStyle.tagline = false;
        }

        this.clientVars = {};

        this.clientVars[this.btnData.env] = this.btnData.client_id;

        this.buttonArgs = {
            env: this.btnData.env,
            client: this.clientVars,
            style: this.btnData.btnStyle,
            commit: true,
            onInit: this.onInitCallback,
            onClick: this.onClickCallback,
            createOrder: this.createOrderCallback,
            onApprove: this.onApproveCallback,
            onError: this.onErrorCallback,
        };

        // TODO: For addon backward compatibility. A vanilla js version has been added.
        jQuery( document ).trigger( 'wpec_before_render_button', [
            new Proxy(this, {
                get(target, prop, receiver) {
                    // 1. If prop exists on this 'WPECStripeHandler' class, return that
                    if (prop in target) {
                        return Reflect.get(target, prop, receiver);
                    }
                    // 2. If prop exists on the 'ppecHandler' (i.e. wpecHandler), return that
                    if (prop in target.wpecHandler) {
                        const value = target.wpecHandler[prop];
                        return typeof value === "function" ? value.bind(target.wpecHandler) : value;
                    }

                    return undefined;
                },
            })
        ] );

        document.dispatchEvent(
            new CustomEvent('wpec_before_render_paypal_button', {
                detail: {
                    handler: this,
                }
            })
        );

        try {
            paypal.Buttons( this.buttonArgs ).render( '#' + this.btnData.id );
        } catch (error){
            console.log(error)
        }
    }

    onInitCallback = (data, actions) => {
        this.wpecHandler.actions = actions;

        document.addEventListener('wpec_validate_order', (e) => {
            if ( e.detail.isValid ) {
                this.wpecHandler.actions?.enable();
            } else {
                this.wpecHandler.actions?.disable();
            }
        })

        this.wpecHandler.validateOrder();
    }

    onClickCallback = () => {
        this.wpecHandler.checkAndShowInputErrors();
    }

    createOrderCallback = async (data, actions) => {
        this.wpecHandler.calcTotal();

        //We need to round to 2 decimal places to make sure that the API call will not fail.
        let itemTotalValueRounded = (this.wpecHandler.data.price * this.wpecHandler.data.quantity).toFixed(2);
        let itemTotalValueRoundedAsNumber = parseFloat(itemTotalValueRounded);
        //console.log('Item total value rounded: ' + itemTotalValueRoundedAsNumber);
        //console.log(this.wpecHandler.data);

        // Checking if shipping will be required
        let shipping_pref = 'NO_SHIPPING'; // Default value
        if (this.wpecHandler.data.shipping !== "" || this.wpecHandler.data.shipping_enable === true) {
            console.log("The physical product checkbox is enabled or there is shipping value has been configured. Setting the shipping preference to collect shipping.");
            shipping_pref = 'GET_FROM_FILE';
        }

        // Create order_data object to be sent to the server.
        const order_data = {
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
            purchase_units: [{
                amount: {
                    value: this.wpecHandler.data.total,
                    currency_code: this.wpecHandler.data.currency,
                    breakdown: {
                        item_total: {
                            currency_code: this.wpecHandler.data.currency,
                            value: itemTotalValueRoundedAsNumber
                        }
                    }
                },
                items: [{
                    name: this.wpecHandler.data.name,
                    quantity: this.wpecHandler.data.quantity,
                    unit_amount: {
                        value: this.wpecHandler.data.price,
                        currency_code: this.wpecHandler.data.currency
                    }
                }]
            }]
        };

        if (this.wpecHandler.data.tax) {
            order_data.purchase_units[0].amount.breakdown.tax_total = {
                currency_code: this.wpecHandler.data.currency,
                value: this.wpecHandler.PHP_round(this.wpecHandler.data.tax_amount * this.wpecHandler.data.quantity, this.wpecHandler.data.dec_num)
            };
            order_data.purchase_units[0].items[0].tax = {
                currency_code: this.wpecHandler.data.currency,
                value: this.wpecHandler.data.tax_amount
            };
        }
        if (this.wpecHandler.data.shipping) {
            order_data.purchase_units[0].amount.breakdown.shipping = {
                currency_code: this.wpecHandler.data.currency,
                value: this.wpecHandler.getTotalShippingCost(),
            };
        }
        if (this.wpecHandler.data.discount) {
            order_data.purchase_units[0].amount.breakdown.discount = {
                currency_code: this.wpecHandler.data.currency,
                value: parseFloat(this.wpecHandler.data.discountAmount)
            };
        }

        //End of create order_data object.
        //console.log('Order data: ', order_data);

        let nonce = wpec_create_order_vars.nonce;

        let wpec_data_for_create = this.wpecHandler.data;//this.wpecHandler.data is the data object that was passed to the ppecHandler constructor.
        //console.log('WPEC data for create-order: ', wpec_data_for_create);

        let post_data = 'action=wpec_pp_create_order&data=' + encodeURIComponent(JSON.stringify(order_data)) + '&wpec_data=' + encodeURIComponent(JSON.stringify(wpec_data_for_create)) + '&_wpnonce=' + nonce;
        try {
            // Using fetch for AJAX request. This is supported in all modern browsers.
            const response = await fetch(ppecFrontVars.ajaxUrl, {
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
                console.error('Error occurred during create-order call to PayPal. ', error_message);
                throw new Error(error_message);//This will trigger the alert in the "catch" block below.
            }
        } catch (error) {
            console.error(error.message);
            alert('Could not initiate PayPal Checkout...\n\n' + error.message);
        }
    }

    onApproveCallback = async (data, actions) => {
        document.querySelector('div.wp-ppec-overlay[data-ppec-button-id="' + this.wpecHandler.data.id + '"]').style.display = 'flex';

        console.log('Setting up the AJAX request for capture-order call.');

        // Create the data object to be sent to the server.
        let pp_bn_data = {};
        pp_bn_data.order_id = data.orderID;//The orderID is the ID of the order that was created in the createOrder method.
        let wpec_data = this.wpecHandler.data;//this.wpecHandler.data is the data object that was passed to the ppecHandler constructor.
        //console.log('WPEC data (JSON): ' + JSON.stringify(wpec_data));

        let nonce = wpec_on_approve_vars.nonce;
        let post_data = 'action=wpec_pp_capture_order&data=' + encodeURIComponent(JSON.stringify(pp_bn_data)) + '&wpec_data=' + encodeURIComponent(JSON.stringify(wpec_data)) + '&_wpnonce=' + nonce;
        try {
            const response = await fetch(ppecFrontVars.ajaxUrl, {
                method: "post",
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: post_data
            });

            const response_data = await response.json();

            if (response_data.hasOwnProperty('success') && !response_data.success) {
                alert(response_data.err_msg);
                return;
            }

            console.log('Capture-order API call to PayPal completed successfully.');

            // Call the completePayment method to do any redirection or display a message to the user.
            this.wpecHandler.completePayment(response_data);

        } catch (error) {
            console.error(error);
            alert('PayPal returned an error! Transaction could not be processed. Enable the debug logging feature to get more details...\n\n' + JSON.stringify(error));
        }

    }

    onErrorCallback = (err) => {
        document.getElementById("place-order-" + this.wpecHandler.data.id).querySelector("button>svg").style.display = 'none';
        document.getElementById("place-order-" + this.wpecHandler.data.id).querySelector("button").removeAttribute("disabled");
        alert(err);
    }
}
