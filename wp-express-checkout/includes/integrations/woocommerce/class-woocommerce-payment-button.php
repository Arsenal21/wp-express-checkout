<?php

namespace WP_Express_Checkout\Integrations;

use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Shortcodes;

class WooCommerce_Payment_Button {

	public $wpec;

	public $gateway;

	public $cart;

	public $button_id;

	public function __construct(  $wpec, $cart, $gateway ) {
		$this->wpec      = $wpec;
		$this->cart      = $cart;
		$this->gateway     = $gateway;
		$this->button_id = 'paypal_button_0';
	}

    public function generate_wpec_payment_button() {
	    $button_id = $this->button_id;
	    $is_live = $this->wpec->get_setting( 'is_live' );
	    if ( $is_live ) {
		    $env       = 'production';
		    $client_id = $this->wpec->get_setting( 'live_client_id' );
	    } else {
		    $env       = 'sandbox';
		    $client_id = $this->wpec->get_setting( 'sandbox_client_id' );
	    }

	    if ( empty( $client_id ) ) {
		    $err_msg = sprintf( __( "Please enter %s Client ID in the settings.", 'wp-express-checkout' ), $env );
		    $err     = $this->show_err_msg( $err_msg, 'client-id' );

		    return $err;
	    }
	    $currency = get_woocommerce_currency();
	    $btn_sizes = array(
                'small' => 25,
                'medium' => 35,
                'large' => 45,
                'xlarge' => 55
        );
	    $nonce = wp_create_nonce( 'wpec-woocommerce-create-order-js-ajax-nonce' );
	    $paypal_btn_data = array(
		    'id'                    => $button_id,
		    'nonce'                 => $nonce,
		    'env'                   => $env,
		    'client_id'             => $client_id,
		    'price'                 => $this->cart->get_cart_contents_total(),
		    'quantity'              => 1,
		    'tax'                   => 0,
		    'shipping'              => 0,
		    'shipping_enable'       => 0,
		    'shipping_per_quantity' => 0,
		    'dec_num'               => intval( $this->wpec->get_setting( 'price_decimals_num' ) ),
		    'thousand_sep'          => $this->wpec->get_setting( 'price_thousand_sep' ),
		    'dec_sep'               => $this->wpec->get_setting( 'price_decimal_sep' ),
		    'curr_pos'              => $this->wpec->get_setting( 'price_currency_pos' ),
		    'tos_enabled'           => $this->wpec->get_setting( 'tos_enabled' ),
		    'custom_quantity'       => 0,
		    'custom_amount'         => 0,
		    'currency'              => $currency,
		    'currency_symbol'       => ! empty( $this->wpec->get_setting( 'currency_symbol' ) ) ? $this->wpec->get_setting( 'currency_symbol' ) : $currency,
		    'coupons_enabled'       => false,
		    'product_id'            => 0,
		    'name'                  => $button_id, // TODO: Need TO FIX it; This property is used to create transient.
		    'stock_enabled'         => 0, // TODO: Line remove
		    'stock_items'           => 100, // TODO: Line remove
		    'variations'            => array(), // TODO: Line remove
		    'btnStyle'              => array(
			    'height'    => ! empty( $btn_sizes[ $this->wpec->get_setting( 'btn_height' ) ] ) ? $btn_sizes[ $this->wpec->get_setting( 'btn_height' ) ] : 25,
			    'shape'     => $this->wpec->get_setting( 'btn_shape' ),
			    'label'     => $this->wpec->get_setting( 'btn_type' ),
			    'color'     => $this->wpec->get_setting( 'btn_color' ),
			    'layout'    => $this->wpec->get_setting( 'btn_layout' ),
		    ),
	    );

	    ?>

        <div class="wpec-wc-button-container">
            <div id="paypal_button_0">
                <!-- PayPal Button Will Render here -->
            </div>
        </div>

        <script type="text/javascript">
            jQuery(function ($){
                const pp_btn_data = <?php echo json_encode($paypal_btn_data); ?>;
                console.log(pp_btn_data);
                $(document).on('wpec_paypal_sdk_loaded', function (){
                    console.log('WPEC PayPal SDK Loaded');
                    new ppecWoocommerceHandler(
                        pp_btn_data
                    );
                });

                class ppecWoocommerceHandler {
                    constructor(data) {
                        this.data = data;
                        this.renderIn = '#' + data.id;
                        // document.addEventListener( "wpec_paypal_sdk_loaded", this.generate_ppec_woocommerce_button);
                        this.generate_ppec_woocommerce_button();
                    }

                    generate_ppec_woocommerce_button() {
                        //Anything that goes here will only be executed after the PayPal SDK is loaded.
                        // console.log('PayPal JS SDK is loaded. WooCommerce');
                        let parent = this;

                        paypal.Buttons({
                            /**
                             * Optional styling for buttons.
                             *
                             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-style
                             */
                            style: {
                                color: parent.data.btnStyle.color,
                                shape: parent.data.btnStyle.shape,
                                height: parent.data.btnStyle.height,
                                label: parent.data.btnStyle.type,
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
                                console.log('Setting up the AJAX request for create-order call.');

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

                                // console.log("Ispect order_data: ", order_data);
                                // console.log("Ispect wpec_data: ", wpec_data);

                                // let post_data = 'action=wpec_woocommerce_pp_create_order&data=' + encodeURIComponent(JSON.stringify(order_data)) + '&wpec_data=' + encodeURIComponent(JSON.stringify(wpec_data)) + '&_wpnonce=' + parent.data.nonce;
                                let post_data = new URLSearchParams({
                                    action: 'wpec_woocommerce_pp_create_order',
                                    data: JSON.stringify(order_data),
                                    wpec_data: JSON.stringify(wpec_data),
                                    _wpnonce: parent.data.nonce,
                                }).toString();
                                try {
                                    const response = await fetch("<?php echo admin_url( 'admin-ajax.php' ); ?>", {
                                        method: "post",
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded'
                                        },
                                        body: post_data
                                    });
                                    console.log('Code came here, after ajax request') //TODO: Need to remove
                                    const response_data = await response.json();

                                    if (response_data.order_id) {
                                        console.log('Create-order API call to PayPal completed successfully.');
                                        //If we need to see the order details, uncomment the following line.
                                        //const order_data = response_data.order_data;
                                        //console.log('Order data: ' + JSON.stringify(order_data));
                                        return response_data.order_id;
                                    } else {
                                        const error_message = response_data.err_msg;
                                        console.error('Error occurred during create-order call to PayPal. ', error_message);
                                        throw new Error(error_message);
                                    }
                                } catch (error) {
                                    console.error(error.message);
                                    alert('Could not initiate PayPal Checkout...\n\n' + error.message);
                                }
                            },

                            /**
                             * Captures the funds from the transaction and shows a message to the buyer to let them know the
                             * transaction is successful. The method is called after the buyer approves the transaction on paypal.com.
                             *
                             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-onapprove
                             */
                            onApprove: async function (data, actions) {
                                // TODO: Need to wonk on.
                                console.log('Successfully created a transaction.');
                                // console.log(data, actions);

                                // const ppec_overlay = document.querySelector('div.wp-ppec-overlay[data-ppec-button-id="' + handler.data.id + '"]');
                                // ppec_overlay.style.display = 'flex';

                                // Create the data object to be sent to the server.
                                let pp_bn_data = {};
                                // The orderID is the ID of the order that was created in the createOrder method.
                                pp_bn_data.order_id = data.orderID;
                                // parent.data is the data object that was passed to the constructor.
                                const wpec_data = parent.data;

                                // const post_data = 'action=wpec_woocommerce_pp_capture_order&data=' + encodeURIComponent(JSON.stringify(pp_bn_data)) + '&wpec_data=' + encodeURIComponent(JSON.stringify(wpec_data)) + '&_wpnonce=' + parent.data.nonce;
                                let post_data = new URLSearchParams({
                                    action: 'wpec_woocommerce_pp_capture_order',
                                    data: JSON.stringify(pp_bn_data),
                                    wpec_data: JSON.stringify(wpec_data),
                                    _wpnonce: parent.data.nonce,
                                }).toString();
                                try {
                                    let capture_order_response = await fetch( "<?php echo admin_url( 'admin-ajax.php' ); ?>", {
                                        method: "post",
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded'
                                        },
                                        body: post_data
                                    });

                                    capture_order_response = await capture_order_response.json();
                                    if (capture_order_response.success){

                                        console.log('Capture-order API call to PayPal completed successfully.');
                                        console.log('Capture order response data: ', capture_order_response);

                                        window.location.href = capture_order_response.data.redirect_url;

                                        // Call the completePayment method to do any redirection or display a message to the user.
                                        // parent.completePayment(response_data);
                                    }

                                } catch (error) {
                                    console.error(error.message);
                                    alert('PayPal returned an error! Transaction could not be processed.\n\n' + error.message);
                                }
                            },

                            /**
                             * If an error prevents buyer checkout, alert the user that an error has occurred with the buttons using this callback.
                             *
                             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-onerror
                             */
                            onError: function (err) {
                                // TODO: Need to extract the proper error message.
                                console.error('An error prevented the user from checking out with PayPal. ' + JSON.stringify(err));
                                alert( '<?php _e("Error occurred during PayPal checkout process.", "wp-express-checkout"); ?>\n\n' + JSON.stringify(err) );
                            },

                            /**
                             * Handles onCancel event.
                             *
                             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-oncancel
                             */
                            onCancel: function (data) {
                                console.log('Checkout operation cancelled by the customer.');
                                //Return to the parent page which the button does by default.
                            },

                        }).render(this.renderIn)
                            .catch((err) => {
                                console.error('PayPal Buttons failed to render');
                            });
                    }
                }

                const wcPaymentMethodsRadioInputs = document.querySelectorAll('ul.wc_payment_methods input.input-radio');
                // Add onChange event listener to each radio input
                wcPaymentMethodsRadioInputs.forEach(function(input) {
                    input.addEventListener('change', function(event) {
                        // Your code to execute when the radio input changes
                        console.log("Selected Payment Method is:", event.target.value);
                        const placeOrderBtn = document.getElementById("place_order")
                        if (event.target.value === 'wp-express-checkout'){
                            placeOrderBtn.setAttribute('disabled', 'true');
                            placeOrderBtn.style.display = 'none';
                        }else{
                            placeOrderBtn.removeAttribute('disabled');
                            placeOrderBtn.style.display = '';
                        }
                    });
                });
            })
        </script>
	    <?php
    }

	private function show_err_msg( $msg, $code = 0 ) {
		return sprintf( '<div class="wpec-error-message wpec-error-message-' . esc_attr( $code ) . '">%s</div>', $msg );
	}
}