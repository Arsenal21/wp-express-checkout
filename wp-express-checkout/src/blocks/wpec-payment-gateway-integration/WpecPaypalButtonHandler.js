import {__} from "@wordpress/i18n";

export default class WpecPaypalButtonHandler {
    constructor(data, {ajaxUrl, renderTo}) {
        this.data = data;
        this.renderTo = renderTo;
        this.ajaxUrl = ajaxUrl;
    }

    generate_ppec_woocommerce_button() {
        let parent = this;

        return paypal.Buttons({
            /**
             * Optional styling for buttons.
             *
             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-style
             */
            style: {
                color: parent.data.btnStyle.color,
                shape: parent.data.btnStyle.shape,
                height: parent.data.btnStyle.height,
                label: parent.data.btnStyle.label,
                layout: parent.data.btnStyle.layout,
            },

            /**
             * OnInit is called when the button first renders.
             *
             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-oninitonclick
             */
            onInit: function (data, actions)  {
                actions.enable();
            },

            /**
             * OnClick is called when the button is clicked
             *
             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-oninitonclick
             */
            onClick: function (){},

            /**
             * This is called when the buyer clicks the PayPal button, which launches the PayPal Checkout
             * window where the buyer logs in and approves the transaction on the paypal.com website.
             *
             * The server-side Create Order API is used to generate the Order. Then the Order-ID is returned.
             *
             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-createorder
             */
            createOrder: async function () {
                // Create order_data object to be sent to the server.
                let price_amount = parseFloat( parent.data.price );
                //round to 2 decimal places, to make sure that the API call dont fail.
                price_amount = parseFloat(price_amount.toFixed(2));

                let itemTotalValueRoundedAsNumber = price_amount;

                const order_data = {
                    intent: 'CAPTURE',
                    payment_source: {
                        paypal: {
                            experience_context: {
                                payment_method_preference: 'IMMEDIATE_PAYMENT_REQUIRED',
                                shipping_preference: 'NO_SHIPPING',
                                user_action: 'PAY_NOW',
                            }
                        }
                    },
                    purchase_units: [ {
                        amount: {
                            value: price_amount,
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
                                value: price_amount,
                                currency_code: parent.data.currency
                            }
                        } ]
                    } ]
                };

                const wpec_data = parent.data;

                try {
                    let response = await fetch(parent.ajaxUrl, {
                        method: "post",
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'wpec_woocommerce_pp_create_order',
                            data: JSON.stringify(order_data),
                            wpec_data: JSON.stringify(wpec_data),
                            _wpnonce: parent.data.nonce,
                        }).toString()
                    });
                    response = await response.json();
                    if (response.order_id) {
                        console.log('Create-order API call to PayPal completed successfully.');
                        return response.order_id;
                    } else {
                        console.error('Error occurred during create-order call to PayPal. ', response.message);
                        throw new Error(response.message);
                    }
                } catch (error) {
                    console.error(error.message);
                    alert( __('Could not initiate PayPal Checkout...', 'wp-express-checkout') + '\n\n' + error.message);
                }
            },

            /**
             * Captures the funds from the transaction and shows a message to the buyer to let them know the
             * transaction is successful. The method is called after the buyer approves the transaction on paypal.com.
             *
             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-onapprove
             */
            onApprove: async function (data, actions) {
                // Create the data object to be sent to the server.
                let pp_bn_data = {};
                // The orderID is the ID of the order that was created in the createOrder method.
                pp_bn_data.order_id = data.orderID;
                // parent.data is the data object that was passed to the constructor.
                const wpec_data = parent.data;

                try {
                    let response = await fetch( parent.ajaxUrl, {
                        method: "post",
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'wpec_woocommerce_pp_capture_order',
                            data: JSON.stringify(pp_bn_data),
                            wpec_data: JSON.stringify(wpec_data),
                            _wpnonce: parent.data.nonce,
                        }).toString()
                    });

                    response = await response.json();

                    if (response.success){
                        console.log('Capture-order API call to PayPal completed successfully.');

                        window.location.href = response.data.redirect_url;
                    }
                } catch (error) {
                    console.error(error.message);
                    alert( __('PayPal returned an error! Transaction could not be processed.', 'wp-express-checkout') +'\n\n' + error.message);
                }
            },

            /**
             * If an error prevents buyer checkout, alert the user that an error has occurred with the buttons using this callback.
             *
             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-onerror
             */
            onError: function (err) {
                console.error('An error prevented the user from checking out with PayPal. ', err.message);
                alert( __('Error occurred during PayPal checkout process.', 'wp-express-checkout')+"\n\n" + err.message );
            },

            /**
             * Handles onCancel event.
             *
             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-oncancel
             */
            onCancel: function (data) {
                console.log('Checkout operation cancelled by the customer.');
            },

        }).render(this.renderTo)
            .catch((err) => {
                console.log('PayPal Buttons failed to render!', err.message);
            });
    }
}