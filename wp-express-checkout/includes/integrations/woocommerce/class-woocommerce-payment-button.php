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
		Logger::log( "Code Come here >>>", true );
		extract( $args );

		// TODO: Code Remove
//		if ( $stock_enabled && empty( $stock_items ) ) {
//			return '<div class="wpec-out-of-stock">' . esc_html( 'Out of stock', 'wp-express-checkout' ) . '</div>';
//		}

		// The button ID.
//		$button_id = 'paypal_button_' . count( self::$payment_buttons ); // TODO: Line Replaced
		$button_id = $this->button_id;

//		self::$payment_buttons[] = $button_id; // TODO: Line Removed
		Logger::log( "Code Come here >>> Prep trans name", true );
		$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $name ); // Create key using the item name.
		Logger::log( "Code Come here >>> trans name" . $trans_name, true );

		$trans_data = array(
			'price'           => $price,
			'currency'        => $currency,
			'quantity'        => $quantity,
			'tax'             => $tax,
			'shipping'        => $shipping,
//			'shipping_per_quantity' => $shipping_per_quantity,
			'shipping_enable' => $shipping_enable,
			'url'             => $url,
			'custom_quantity' => $custom_quantity,
			'custom_amount'   => $custom_amount,
			'product_id'      => $product_id,
			'coupons_enabled' => $coupons_enabled,
			'thank_you_url'   => $thank_you_url,
			'wc_id'           => $this->order->get_id(),
		);
		Logger::log_array_data( $trans_data, true );
		Logger::log( "Code Come here >>> Prep trans data", true );

		set_transient( $trans_name, $trans_data, 2 * 3600 );

		$is_live = $this->wpec->get_setting( 'is_live' );
		Logger::log( "Code Come here >>> is live settings", true );
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

//		$output  = '';
//		$located = Shortcodes::locate_template( 'payment-form.php' );
//		Logger::log("Code Come here >>> Locate Template", true);
//		if ( $located ) {
//			Logger::log("Code Come here >>> Template Located", true);
//			ob_start();
//			require $located;
//			$output = ob_get_clean();
//			Logger::log('Code came here', true);
//			Logger::log($output, true);
//		}

//		$modal = Shortcodes::locate_template( 'modal.php' );
//		Logger::log("Code Come here >>> Locate modal", true);
//		if ( $modal && $use_modal ) {
//			Logger::log("Code Come here >>> Modal Located", true);
//			$modal_title = apply_filters( 'wpec_modal_window_title', get_the_title( $product_id ), $args );
//			ob_start();
//			require $modal;
//			$output = ob_get_clean();
//			Logger::log($output, true);
//		}

		$button_html = $this->get_button_tpl_html( $args );

		if ( $use_modal ) {
			$output      = $this->get_modal_html( array(
				'modal_title' => $modal_title,
				'product_id'  => $product_id
			), $button_html );
		}
        $nonce = wp_create_nonce( $button_id . $product_id );
		Logger::log( "Code Come here >>> Before data process", true );
		$data = array(
			'id'                    => $button_id,
			'nonce'                 => $nonce,
			'env'                   => $env,
			'client_id'             => $client_id,
			'price'                 => $price,
			'quantity'              => $quantity,
			'tax'                   => $tax,
			'shipping'              => $shipping,
//			'shipping_per_quantity' => $shipping_per_quantity,
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

		Logger::log_array_data( $data, true );
		Logger::log( "Code Come here >>> Before scripting", true );
//		$output .= '<script type="text/javascript">var wpec_' . $button_id . '_data=' . json_encode( $data ) . ';jQuery( function( $ ) {$( document ).on( "wpec_paypal_sdk_loaded", function() { new ppecHandler(wpec_' . $button_id . '_data) } );} );</script>';
        ob_start();
        ?>
        <script type="text/javascript">
            var wpec_<?php echo $this->button_id; ?>_data = <?php echo json_encode( $data )?>;
            jQuery( function( $ ) {
                $( document ).on( "wpec_paypal_sdk_loaded", function() {
                    new ppecHandler( wpec_<?php echo $this->button_id; ?>_data )
                } );
            } );
        </script>
        <script type="text/javascript">
            document.addEventListener( "wpec_paypal_sdk_loaded", function() {
                //Anything that goes here will only be executed after the PayPal SDK is loaded.
                console.log('PayPal JS SDK is loaded.');

                /**
                 * See documentation: https://developer.paypal.com/sdk/js/reference/
                 */
                paypal.Buttons({
                    /**
                     * Optional styling for buttons.
                     *
                     * See documentation: https://developer.paypal.com/sdk/js/reference/#link-style
                     */
                    style: {
                        color: '<?php echo esc_js($args['btn_color']); ?>',
                        shape: '<?php echo esc_js($args['btn_shape']); ?>',
                        height: <?php echo esc_js($args['btn_height']); ?>,
                        label: '<?php echo esc_js($args['btn_type']); ?>',
                        layout: '<?php echo esc_js($args['btn_layout']); ?>',
                    },

                    // Triggers when the button first renders.
                    onInit: onInitHandler,

                    // Triggers when the button is clicked.
                    onClick: onClickHandler,

                    // Setup the transaction.
                    createOrder: createOrderHandler,

                    // Handle the onApprove event.
                    onApprove: onApproveHandler,

                    // Handle unrecoverable errors.
                    onError: onErrorHandler,

                    // Handles onCancel event.
                    onCancel: onCancelHandler,

                })
                    .render('#<?php echo esc_js($button_id); ?>')
                    .catch((err) => {
                        console.error('PayPal Buttons failed to render');
                    });

                /**
                 * OnInit is called when the button first renders.
                 *
                 * See documentation: https://developer.paypal.com/sdk/js/reference/#link-oninitonclick
                 */
                function onInitHandler(data, actions)  {
                    actions.enable();
                }

                /**
                 * OnClick is called when the button is clicked
                 *
                 * See documentation: https://developer.paypal.com/sdk/js/reference/#link-oninitonclick
                 */
                function onClickHandler(){

                }

                /**
                 * This is called when the buyer clicks the PayPal button, which launches the PayPal Checkout
                 * window where the buyer logs in and approves the transaction on the paypal.com website.
                 *
                 * See documentation: https://developer.paypal.com/sdk/js/reference/#link-createorder
                 */
                async function createOrderHandler() {
                    // Create the order in PayPal using the PayPal API.
                    // https://developer.paypal.com/docs/checkout/standard/integrate/
                    // The server-side Create Order API is used to generate the Order. Then the Order-ID is returned.
                    console.log('Setting up the AJAX request for create-order call.');

                    // Create order_data object to be sent to the server.
                    let price_amount = parseFloat( <?php echo $args['price']; ?> );
                    let roundedTotal = price_amount.toFixed(2); //round to 2 decimal places, to make sure that the API call dont fail.
                    price_amount = parseFloat(roundedTotal); //convert to number
                    let itemTotalValueRoundedAsNumber = price_amount;

                    const order_data = { // TODO: Need to Fix This.
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
                                currency_code: '<?php echo $args['currency']; ?>',
                                breakdown: {
                                    item_total: {
                                        currency_code: '<?php echo $args['currency']; ?>',
                                        value: itemTotalValueRoundedAsNumber
                                    }
                                }
                            },
                            items: [ {
                                name: '<?php echo $args['name']; ?>',
                                quantity: <?php echo $args['quantity']; ?>,
                                unit_amount: {
                                    value: price_amount,
                                    currency_code: '<?php echo $args['currency']; ?>'
                                }
                            } ]
                        } ]
                    };

                    const wpec_data_for_create = <?php echo json_encode($args); ?>;
                    console.log("Ispect order_data: ", order_data);
                    console.log("Ispect wpec_data: ", JSON.parse(wpec_data_for_create));
                    let post_data = 'action=wpec_woocommerce_pp_create_order&data=' + encodeURIComponent(JSON.stringify(order_data)) + '&wpec_data=' + encodeURIComponent(wpec_data_for_create) + '&_wpnonce=' + nonce;
                    try {
                        // Using fetch for AJAX request. This is supported in all modern browsers.
                        const response = await fetch("<?php echo admin_url( 'admin-ajax.php' ); ?>", {
                            method: "post",
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: post_data
                        });

                        const response_data = await response.json();

                        if (response_data.order_id) {
                            console.log('Create-order API call to PayPal completed successfully.');
                            //If we need to see the order details, uncomment the following line.
                            //const order_data = response_data.order_data;
                            //console.log('Order data: ' + JSON.stringify(order_data));
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
                }

                /**
                 * Captures the funds from the transaction and shows a message to the buyer to let them know the
                 * transaction is successful. The method is called after the buyer approves the transaction on paypal.com.
                 *
                 * See documentation: https://developer.paypal.com/sdk/js/reference/#link-onapprove
                 */
                async function onApproveHandler(data, actions) {
                    console.log('Successfully created a transaction.');

                    //Show the spinner while we process this transaction.
                    const pp_button_container = document.getElementById('<?php echo esc_js($on_page_embed_button_id); ?>');
                    const pp_button_spinner_container = wspsc_getClosestElement(pp_button_container, '.wpsc-pp-button-spinner-container', '.shopping_cart');
                    pp_button_container.style.display = 'none'; //Hide the buttons
                    pp_button_spinner_container.style.display = 'inline-block'; //Show the spinner.

                    // Capture the order in PayPal using the PayPal API.
                    // https://developer.paypal.com/docs/checkout/standard/integrate/
                    // The server-side capture-order API is used. Then the Capture-ID is returned.
                    console.log('Setting up the AJAX request for capture-order call.');
                    let pp_bn_data = {};
                    pp_bn_data.order_id = data.orderID;
                    pp_bn_data.cart_id = '<?php echo esc_js($cart_id); ?>';
                    pp_bn_data.on_page_button_id = '<?php echo esc_js($on_page_embed_button_id); ?>';
                    //Add custom_field data. It is important to encode the custom_field data so it doesn't mess up the data with & character.
                    //const custom_data = document.getElementById('<?php echo esc_attr($on_page_embed_button_id."-custom-field"); ?>').value;
                    //pp_bn_data.custom_field = encodeURIComponent(custom_data);

                    //Ajax action: <prefix>_pp_capture_order
                    let post_data = 'action=wpec_woocommerce_pp_capture_order&data=' + JSON.stringify(pp_bn_data) + '&_wpnonce=<?php echo $wp_nonce; ?>';
                    try {
                        const response = await fetch("<?php echo admin_url( 'admin-ajax.php' ); ?>", {
                            method: "post",
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: post_data
                        });

                        const response_data = await response.json();
                        const txn_data = response_data.txn_data;
                        const error_detail = txn_data?.details?.[0];
                        const error_msg = response_data.error_msg;//Our custom error message.
                        // Three cases to handle:
                        // (1) Recoverable INSTRUMENT_DECLINED -> call actions.restart()
                        // (2) Other non-recoverable errors -> Show a failure message
                        // (3) Successful transaction -> Show confirmation or thank you message

                        if (response_data.capture_id) {
                            // Successful transaction -> Show confirmation or thank you message
                            console.log('Capture-order API call to PayPal completed successfully.');

                            //Redirect to the Thank you page URL if it is set.
                            return_url = '<?php echo esc_url_raw($return_url); ?>';
                            if( return_url ){
                                //redirect to the Thank you page URL.
                                console.log('Redirecting to the Thank you page URL: ' + return_url);
                                window.location.href = return_url;
                                return;
                            } else {
                                //No return URL is set. Just show a success message.
                                console.log('No return URL is set in the settings. Showing a success message.');

                                //We are going to show the success message in the shopping_cart's container.
                                txn_success_msg = '<?php echo esc_attr($txn_success_message).' '.esc_attr($txn_success_extra_msg); ?>';
                                // Select all elements with the class 'shopping_cart'
                                var shoppingCartDivs = document.querySelectorAll('.shopping_cart');

                                // Loop through the NodeList and update each element
                                shoppingCartDivs.forEach(function(div, index) {
                                    div.innerHTML = '<div id="wpsc-cart-txn-success-msg-' + index + '" class="wpsc-cart-txn-success-msg">' + txn_success_msg + '</div>';
                                });

                                //Note: We need to use on_page_cart_div_ids for compact carts.
                                //Then we will be able to use the on_page_cart_div_ids array to get the cart div ids of the page (including the compact carts)

                                // Scroll to the success message container of the cart we are interacting with.
                                const interacted_cart_element = document.getElementById('wpsc_shopping_cart_' + <?php echo esc_attr($carts_cnt); ?>);
                                if (interacted_cart_element) {
                                    interacted_cart_element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                }
                                return;
                            }

                        } else if (error_detail?.issue === "INSTRUMENT_DECLINED") {
                            // Recoverable INSTRUMENT_DECLINED -> call actions.restart()
                            console.log('Recoverable INSTRUMENT_DECLINED error. Calling actions.restart()');
                            return actions.restart();
                        } else if ( error_msg && error_msg.trim() !== '' ) {
                            //Our custom error message from the server.
                            console.error('Error occurred during PayPal checkout process.');
                            console.error( error_msg );
                            alert( error_msg );
                        } else {
                            // Other non-recoverable errors -> Show a failure message
                            console.error('Non-recoverable error occurred during PayPal checkout process.');
                            console.error( error_detail );
                            //alert('Unexpected error occurred with the transaction. Enable debug logging to get more details.\n\n' + JSON.stringify(error_detail));
                        }

                        //Return the button and the spinner back to their orignal display state.
                        pp_button_container.style.display = 'block'; // Show the buttons
                        pp_button_spinner_container.style.display = 'none'; // Hide the spinner

                    } catch (error) {
                        console.error(error);
                        alert('PayPal returned an error! Transaction could not be processed. Enable the debug logging feature to get more details...\n\n' + JSON.stringify(error));
                    }
                }

                /**
                 * If an error prevents buyer checkout, alert the user that an error has occurred with the buttons using this callback.
                 *
                 * See documentation: https://developer.paypal.com/sdk/js/reference/#link-onerror
                 */
                function onErrorHandler(err) {
                    console.error('An error prevented the user from checking out with PayPal. ' + JSON.stringify(err));
                    alert( '<?php echo esc_js(__("Error occurred during PayPal checkout process.", "wordpress-simple-paypal-shopping-cart")); ?>\n\n' + JSON.stringify(err) );
                }

                /**
                 *
                 * See documentation: https://developer.paypal.com/sdk/js/reference/#link-oncancel
                 */
                function onCancelHandler (data) {
                    console.log('Checkout operation cancelled by the customer.');
                    //Return to the parent page which the button does by default.
                }

            });

            /**
             * Checks if any input element has required attribute with empty value
             * @param cart_no Target cart no.
             * @returns {boolean} TRUE if empty required field found, FALSE otherwise.
             */
            function has_empty_required_input(cart_no) {
                let has_any = false;
                const target_input = '.wpspsc_cci_input';
                const currentPPCPButtonWrapper = '#wpsc_paypal_button_'+cart_no;
                const target_form = wspsc_getClosestElement(currentPPCPButtonWrapper, 'table', '.shopping_cart');
                const cciInputElements = target_form.querySelectorAll(target_input);
                cciInputElements.forEach(function (inputElement) {
                    if (inputElement.required && !inputElement.value.trim()) {
                        // Empty required field found!
                        has_any = true;
                    }
                });

                return has_any;
            }
        </script>
        <?php
		$output .= ob_get_clean();

		add_action( 'wp_footer', array( $this->wpec, 'load_paypal_sdk' ) );
		Logger::log( "Code Come here >>> After add action", true );

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