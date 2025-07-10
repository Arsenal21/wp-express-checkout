<?php

namespace WP_Express_Checkout\Integrations;

use WC_Logger;
use WC_Order;
use WC_Payment_Gateway;
use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Main;

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

class WooCommerce_Gateway extends WC_Payment_Gateway {

	/** @var bool Whether or not logging is enabled */
	public static $log_enabled = false;

	/** @var WC_Logger Logger instance */
	public static $log = false;

	/** @var WC_Order */
	public $wpec_wc_order = false;

	/** @var Main */
	public $wpec;

	public $notify_url;

	public function __construct() {
		$this->id                 = 'wp-express-checkout';
		$this->method_title       = __( 'WP Express Checkout Gateway', 'wp-express-checkout' );
		$this->method_description = __( 'Use the WP Express Checkout plugin to process payments via PayPal Checkout API.', 'wp-express-checkout' );
		$this->notify_url         = WC()->api_request_url( 'wp_express_checkout' );

		$this->wpec = Main::get_instance();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->has_fields  = true;
		$this->supports    = array( 'products' );

		self::$log_enabled = $this->wpec->get_setting( 'enable_debug_logging' );

		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
		//add_action( 'woocommerce_api_' . strtolower( __CLASS__ ), array( $this, 'check_response' ) );
		//add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
	}

	/**
	 * Add the gateway to WooCommerce
	 *
	 * @param array $methods The WC payment methods.
	 *
	 * @return array
	 */
	public static function add_wc_gateway_class( $methods ) {
		$methods[] = 'WP_Express_Checkout\Integrations\WooCommerce_Gateway';
		return $methods;
	}

	/**
	 * Logging method
	 *
	 * @param  string $message
	 * @param  string $order_id
	 */
	public static function log( $message, $order_id = '' ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}
			if ( ! empty( $order_id ) ) {
				$message = 'Order: ' . $order_id . '. ' . $message;
			}
			self::$log->add( 'wpec', $message );
			Logger::log( $message );
		}
	}

	/**
	 * Initialize gateway settings form fields
	 *
	 * @access public
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'         => array(
				'title'    => __( 'Enable/Disable', 'wp-express-checkout' ),
				'type'     => 'checkbox',
				'label'    => __( 'Enable WP Express Checkout gateway', 'wp-express-checkout' ),
				'default'  => 'false',
				'desc_tip' => true,
			),
			'title'           => array(
				'title'       => __( 'Title', 'wp-express-checkout' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wp-express-checkout' ),
				'default'     => __( 'PayPal', 'wp-express-checkout' ),
				'desc_tip'    => true,
			),
			'description'     => array(
				'title'       => __( 'Description', 'wp-express-checkout' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the description which the user sees during checkout.', 'wp-express-checkout' ),
				'default'     => __( 'Pay by PayPal Express Form.', 'wp-express-checkout' ),
			),
			'popup_title'     => array(
				'title'       => __( 'Checkout Popup Title', 'wp-express-checkout' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the popup window title which the user sees during checkout.', 'wp-express-checkout' ),
				'default'     => __( 'PayPal Express Checkout', 'wp-express-checkout' ),
			),
		);
	}

	public function paypal_sdk_args( $args ) {
		$args['currency'] = get_woocommerce_currency();
		return $args;
	}

	public function payment_fields() {
		echo $this->get_option( 'description' );

		if ( ! is_ajax() ) {
			return;
		}

        add_filter( 'wpec_paypal_sdk_args', array( $this, 'paypal_sdk_args' ), 10 );
        $this->wpec->load_paypal_sdk();

		?>
        <style>
            .wpec-modal-open {
                display: none;
            }
        </style>
        <div class="wpec-wc-button-container"></div>
        <script>
            jQuery( function( $ ) {
                const wp_ajax_url = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
                $( '.checkout.woocommerce-checkout' ).on( 'checkout_place_order_success', function( e, result ) {
                    const get_payment_method = function() {
                        const selectedPaymentMethodInput = document.querySelector('#payment input[name="payment_method"]:checked');
                        return selectedPaymentMethodInput.value;
                    };

                    if ( result.result !== 'success' || get_payment_method() !== 'wp-express-checkout') {
                        return result;
                    }

                    const order_id = result.order_id;

                    fetch( ppecFrontVars.ajaxUrl, {
                        method: "post",
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'wpec_wc_generate_button',
                            order_id: order_id,
                            modal_title: "<?php echo $this->get_option( 'popup_title' ); ?>",
                            nonce: "<?php echo esc_js( wp_create_nonce( 'wpec-wc-render-button-nonce' ) ); ?>",
                        }).toString()
                    }).then((response) => {
                        return response.json()
                    }).then(response => {
                        if ( response.success !== true ) {
                            result.result = 'failure';
                            result.messages = '<div class="woocommerce-error">' + response.data + '</div>';
                            throw new Error(response.message);
                        }

                        //$( '#place_order' ).before( response.data );
                        $( '.wpec-wc-button-container' ).html( response.data );
                        //$( 'form.processing' ).unblock();
                        new ppecWoocommerceHandler( wpec_paypal_button_0_data );
                        new wpecModal( $ ); // TODO: This need to be replaced.

                        $( '.wpec-modal-open' ).trigger( 'click' );
                        $( 'form.processing' ).removeClass( 'processing' ).unblock();
                    }).catch(error => {
                        console.log(error.messages);
                        alert(error.messages);
                    })

                    // Do not redirect, just focus on popup window.
                    // result.redirect = '#';

                    if ( result.result === 'failure' ) {
                        throw 'Result failure';
                    }
                } );

                class ppecWoocommerceHandler {
                    constructor(data) {
                        this.data = data;
                        this.renderIn = '#' + data.id;
                        this.generate_ppec_woocommerce_button();
                    }

                    generate_ppec_woocommerce_button() {
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

                                // console.log("Ispect order_data: ", order_data);
                                // console.log("Ispect wpec_data: ", wpec_data);

                                try {
                                    let response = await fetch(wp_ajax_url, {
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
                                        const error_message = response.message;
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
                                // const ppec_overlay = document.querySelector('div.wp-ppec-overlay[data-ppec-button-id="' + handler.data.id + '"]');
                                // ppec_overlay.style.display = 'flex';

                                // Create the data object to be sent to the server.
                                let pp_bn_data = {};
                                // The orderID is the ID of the order that was created in the createOrder method.
                                pp_bn_data.order_id = data.orderID;
                                // parent.data is the data object that was passed to the constructor.
                                const wpec_data = parent.data;

                                try {
                                    let response = await fetch( wp_ajax_url, {
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

                                        // Call the completePayment method to do any redirection or display a message to the user.
                                        parent.completePayment(response.data);
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
                                console.error('An error prevented the user from checking out with PayPal. ' + JSON.stringify(err));
                                alert( '<?php echo esc_js(__("Error occurred during PayPal checkout process.", "wp-express-checkout")); ?>\n\n' + JSON.stringify(err) );
                            },

                            /**
                             * Handles onCancel event.
                             *
                             * See documentation: https://developer.paypal.com/sdk/js/reference/#link-oncancel
                             */
                            onCancel: function (data) {
                                console.log('Checkout operation canceled by the customer.');
                            },

                        }).render(this.renderIn)
                            .catch((err) => {
                                console.error('PayPal Buttons failed to render!');
                            });
                    }

                    // TODO: Need to optimize that for woocommerce.
                    completePayment( data ) {
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
                        jQuery( '.wp-ppec-overlay[data-ppec-button-id="' + this.data.id + '"]' ).hide();
                        var dialog = jQuery( '<div id="wp-ppdg-dialog-message" title="' + dlgTitle + '"><p id="wp-ppdg-dialog-msg">' + dlgMsg + '</p></div>' );
                        jQuery( '#' + this.data.id ).before( dialog ).fadeIn();
                        if ( redirect_url ) {
                            location.href = redirect_url;
                        }
                        return ret;
                    };
                }
            } );
        </script>
		<?php
	}

	/**
	 * Send payment request to gateway
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		$this->wpec_wc_order = $order;
		$order->update_status( 'pending-payment', __( 'Awaiting payment', 'wp-express-checkout' ) );

		return array(
			'result'   => 'success',
			// 'redirect' => $order->get_checkout_payment_url( true ),
			'redirect' => '#',
		);
	}
}