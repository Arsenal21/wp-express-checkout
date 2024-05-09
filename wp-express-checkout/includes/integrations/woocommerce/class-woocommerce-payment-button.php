<?php

namespace WP_Express_Checkout\Integrations;

use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Shortcodes;

class WooCommerce_Payment_Button {

	public $wpec;

	public $order;

	public $button_id;

	public function __construct( $order, $wpec ) {
		$this->wpec      = $wpec;
		$this->order     = $order;
		$this->button_id = 'paypal_button_0';
	}

	public function wpec_generate_woo_payment_button( $args ) {
		extract( $args );

		$button_id = $this->button_id;

		$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $name ); // Create key using the item name.

		$trans_data = array(
			'price'           => $price,
			'currency'        => $currency,
			'quantity'        => $quantity,
			'tax'             => $tax,
			'shipping'        => $shipping,
            // 'shipping_per_quantity' => $shipping_per_quantity,
			'shipping_enable' => $shipping_enable,
			'url'             => $url,
			'custom_quantity' => $custom_quantity,
			'custom_amount'   => $custom_amount,
			'product_id'      => $product_id,
			'coupons_enabled' => $coupons_enabled,
			'thank_you_url'   => $thank_you_url,
			'wc_id'           => $this->order->get_id(),
		);

        Logger::log('Transient name: ' . $trans_name, true);
        Logger::log_array_data( $trans_data, true ); // Debug purpose.

        set_transient( $trans_name, $trans_data, 2 * 3600 );

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

		$button_html = $this->get_button_tpl_html( $args );

		if ( $use_modal ) {
			$output = $this->get_modal_html( array(
				'modal_title' => $modal_title,
				'product_id'  => $product_id
			), $button_html );
		}
        $nonce = wp_create_nonce( 'wpec-woocommerce-create-order-js-ajax-nonce' );
		$data = array(
			'id'                    => $button_id,
			'nonce'                 => $nonce,
			'env'                   => $env,
			'client_id'             => $client_id,
			'price'                 => $price,
			'quantity'              => $quantity,
			'tax'                   => $tax,
			'shipping'              => $shipping,
            // 'shipping_per_quantity' => $shipping_per_quantity,
			'shipping_per_quantity' => 0,
			'shipping_enable'       => $shipping_enable,
			'dec_num'               => intval( $this->wpec->get_setting( 'price_decimals_num' ) ),
			'thousand_sep'          => $this->wpec->get_setting( 'price_thousand_sep' ),
			'dec_sep'               => $this->wpec->get_setting( 'price_decimal_sep' ),
			'curr_pos'              => $this->wpec->get_setting( 'price_currency_pos' ),
			'tos_enabled'           => $this->wpec->get_setting( 'tos_enabled' ),
			'custom_quantity'       => $custom_quantity,
			'custom_amount'         => $custom_amount,
			'currency'              => $currency,
			'currency_symbol'       => ! empty( $this->wpec->get_setting( 'currency_symbol' ) ) ? $this->wpec->get_setting( 'currency_symbol' ) : $currency,
			'coupons_enabled'       => $coupons_enabled,
			'product_id'            => $product_id,
			'name'                  => $name,
			'stock_enabled'         => 0, // TODO: Line remove
			'stock_items'           => 100, // TODO: Line remove
			'variations'            => array(), // TODO: Line remove
			'btnStyle'              => array(
				'height' => $btn_height,
				'shape'  => $btn_shape,
				'label'  => $btn_type,
				'color'  => $btn_color,
				'layout' => $btn_layout,
			),
		);

		// Logger::log_array_data( $data, true ); // Debug purpose.

        ob_start();
        ?>
        <script type="text/javascript">
            var wpec_paypal_button_0_data = <?php echo json_encode( $data )?>;

            class ppecWoocommerceHandler {
                constructor(data) {
                    this.data = data;
                    this.renderIn = '#' + data.id;
                    // document.addEventListener( "wpec_paypal_sdk_loaded", this.generate_ppec_woocommerce_button);
                    this.generate_ppec_woocommerce_button();
                }

                generate_ppec_woocommerce_button() {
                    //Anything that goes here will only be executed after the PayPal SDK is loaded.
                    console.log('PayPal JS SDK is loaded. WooCommerce');
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

                            let post_data = 'action=wpec_woocommerce_pp_create_order&data=' + encodeURIComponent(JSON.stringify(order_data)) + '&wpec_data=' + encodeURIComponent(JSON.stringify(wpec_data)) + '&_wpnonce=' + parent.data.nonce;
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
                            const post_data = 'action=wpec_woocommerce_pp_capture_order&data=' + encodeURIComponent(JSON.stringify(pp_bn_data)) + '&wpec_data=' + encodeURIComponent(JSON.stringify(wpec_data)) + '&_wpnonce=' + parent.data.nonce;
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

                            //actions.order.capture().then( function( payment ) {
                            //    jQuery.post( "<?php //echo admin_url( 'admin-ajax.php' ); ?>//", {
                            //        action:  "wpec_process_wc_payment",
                            //        wp_ppdg_payment: payment,
                            //        data: parent.data,
                            //        nonce: parent.data.nonce
                            //    } ).done( function( data ) {
                            //        parent.completePayment( data );
                            //    } );
                            //
                            //} ).catch(function (error){
                            //    console.log("Some thing went wrong");
                            //    console.log(error.message);
                            //    console.log(error);
                            //} );
                        },

                        /**
                         * If an error prevents buyer checkout, alert the user that an error has occurred with the buttons using this callback.
                         *
                         * See documentation: https://developer.paypal.com/sdk/js/reference/#link-onerror
                         */
                        onError: function (err) {
                            console.error('An error prevented the user from checking out with PayPal. ' + JSON.stringify(err));
                            alert( '<?php echo esc_js(__("Error occurred during PayPal checkout process.", "wordpress-simple-paypal-shopping-cart")); ?>\n\n' + JSON.stringify(err) );
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
                    jQuery( '.wp-ppec-overlay[data-ppec-button-id="' + parent.data.id + '"]' ).hide();
                    var dialog = jQuery( '<div id="wp-ppdg-dialog-message" title="' + dlgTitle + '"><p id="wp-ppdg-dialog-msg">' + dlgMsg + '</p></div>' );
                    jQuery( '#' + parent.data.id ).before( dialog ).fadeIn();
                    if ( redirect_url ) {
                        location.href = redirect_url;
                    }
                    return ret;
                };


            }
        </script>
        <?php
		$output .= ob_get_clean();

		add_action( 'wp_footer', array( $this->wpec, 'load_paypal_sdk' ) );

		return $output;
	}

	private function show_err_msg( $msg, $code = 0 ) {
		return sprintf( '<div class="wpec-error-message wpec-error-message-' . esc_attr( $code ) . '">%s</div>', $msg );
	}

	public function get_button_tpl_html( $args ) {
		ob_start();
		?>

        <div style="position: relative;"
             class="wp-ppec-shortcode-container wpec-shortcode-container-product-<?php echo esc_attr( $args['product_id'] ); ?>"
             data-ppec-button-id="<?php echo esc_attr( $this->button_id ); ?>"
             data-price-class="<?php echo esc_attr( $args['price_class'] ); ?>">

            <div class="wp-ppec-overlay" data-ppec-button-id="<?php echo esc_attr( $this->button_id ); ?>">
                <div class="wp-ppec-spinner">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </div>

            <div class="wp-ppec-button-container">

				<?php if ( $args['use_modal'] ) { ?>
                    <div class="wpec-price-container <?php echo esc_attr( $args['price_class'] ); ?>">
						<?php echo Shortcodes::get_instance()->generate_price_tag( $args ); ?>
                    </div>
				<?php } ?>

                <div id="place-order-<?php echo esc_attr( $this->button_id );?>" style="display:none;">
                    <button class="wpec-place-order-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
						<?php esc_html_e( 'Place Order', 'wp-express-checkout' ); ?>
                    </button>
                </div>

                <div id="<?php echo esc_attr( $this->button_id ); ?>" style="max-width:<?php echo esc_attr( $args['btn_width'] ); ?>"></div>

                <div class="wpec-button-placeholder" style="display: none; border: 1px solid #E7E9EB; padding:1rem;">
                    <i><?php esc_html_e( 'This is where the Express Checkout Button will show. View it on the front-end to see how it will look to your visitors', 'wp-express-checkout' ); ?></i>
                </div>

            </div>

        </div>

		<?php
		return ob_get_clean();
	}

	public function get_modal_html( $args, $button_html ) {
		ob_start();
		?>

        <!--Modal-->
        <div id="wpec-modal-<?php echo esc_attr( $this->button_id ); ?>"
             class="wpec-modal wpec-opacity-0 wpec-pointer-events-none wpec-modal-product-<?php echo esc_attr( $args['product_id'] ); ?>">

            <div class="wpec-modal-overlay"></div>

            <div class="wpec-modal-container">
                <div class="wpec-modal-content">
                    <!--Title-->
                    <div class="wpec-modal-content-title">
                        <p><?php echo esc_html( $args['modal_title'] ); ?></p>
                        <div class="wpec-modal-close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                                <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                            </svg>
                        </div>
                    </div>
					<?php echo $button_html; ?>
                </div>
            </div>
        </div>

        <button data-wpec-modal="wpec-modal-<?php echo esc_attr( $this->button_id ); ?>"
                class="wpec-modal-open wpec-modal-open-product-<?php echo esc_attr( $args['product_id'] ); ?>">
			<?php
			//				echo esc_html( $button_text );
			echo "Sample Btn Text (need to fix)";
			?>
        </button>
		<?php
		return ob_get_clean();
	}
}