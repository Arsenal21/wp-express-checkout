<?php

namespace WP_Express_Checkout;

use WP_Express_Checkout\Debug\Logger;

class Stripe_Payment_Button_Ajax_Handler {
	public function __construct() {
		add_action( 'wp_ajax_wpec_stripe_create_checkout_session', array( $this, 'stripe_create_checkout_session' ) );
		add_action( 'wp_ajax_nopriv_wpec_stripe_create_checkout_session', array( $this, 'stripe_create_checkout_session' ) );
	}

	/**
	 * Handle the stripe_create_checkout_session ajax request.
	 */
	public function stripe_create_checkout_session() {
		// Nonce verification
		if ( ! check_ajax_referer( 'wpec-stripe-create-order-ajax-nonce', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Nonce Verification Failed!', 'wp-express-checkout' ),
				)
			);
		}

		$wpec_data  = isset( $_POST['wpec_data'] ) ? json_decode( stripcslashes( $_POST['wpec_data'] ), true ) : array();
		$order_data = isset( $_POST['data'] ) ? json_decode( stripcslashes( $_POST['data'] ), true ) : array(); // Currently only used for validation purpose.

		$product_name = isset($wpec_data['name']) ? $wpec_data['name'] : '';
		$product_id   = isset($wpec_data['product_id']) ? $wpec_data['product_id'] : '';
		$currency     = isset($wpec_data['currency']) ? $wpec_data['currency'] : '';
		$quantity     = isset($wpec_data['quantity']) ? $wpec_data['quantity'] : '';
		$item_price   = isset($wpec_data['newPrice']) ? $wpec_data['newPrice'] : '';

		// $client_reference_id = $product_id;
		$client_reference_id = "{CHECKOUT_SESSION_ID}";

		$product             = Products::retrieve( intval( $product_id ) );
		$product_thumbnail   = sanitize_url( $product->get_thumbnail_url() );
		$product_description = '';

		// Get the shortcode data transient.
		$sc_data_transient = get_transient( 'wp-ppdg-' . sanitize_title_with_dashes( $product_name ) );

		$this->do_api_pre_submission_validation( $order_data, $wpec_data );

		$stripe_ipn_url = add_query_arg( array(
			'wpec_process_stripe_ipn' => '1',
			'ref_id'                  => $client_reference_id
		), site_url() );

		$success_url = $stripe_ipn_url;
		$cancel_url  = Main::get_instance()->get_setting( 'shop_page_url' );

		$stripe_locale = explode( '_', get_locale() )[0];

		try {
			$stripe_client = Utils::get_stripe_client();

			if ( ! Utils::is_zero_cents_currency( $currency ) ) {
				$item_price = Utils::amount_in_cents( $item_price );
			} else {
				$item_price = round( $item_price ); // To make sure there is no decimal place number for zero cents currency.
			}

			$line_items = array();

			$line_item = array(
				'price_data' => array(
					'currency'     => $currency,
					'product_data' => array(
						'name'     => $product_name,
						'metadata' => array(
							'wpec_product_id' => $product_id,
							'wpec_item_key' => 'main',
						),
					),
					'unit_amount'  => $item_price,
				),
				'quantity'   => $quantity,
			);

			if ( ! empty( $product_thumbnail ) ) {
				$line_item['price_data']['product_data']['images'] = array(
					$product_thumbnail
				);
			}
			if ( ! empty( $product_description ) ) {
				$line_item['price_data']['product_data']['description'] = $product_description;
			}

			/**
			 * Check and add tax information.
			 */
			$tax_rate       = null;
			$tax_percentage = $product->get_tax(); // TODO:
			if ( ! empty( $tax_percentage ) ) {
				// Retrieve the existing tax rate for this tax percentage if there is any.
				$existing_tax_rate_id = Utils::get_saved_stripe_tax_rate_id( $tax_percentage );
				if ( ! empty( $existing_tax_rate_id ) ) {
					try {
						$existing_tax_rate = $stripe_client->taxRates->retrieve( $existing_tax_rate_id );

						if ( $existing_tax_rate->active ) {
							$tax_rate = $existing_tax_rate;
							Logger::log( 'Existing stripe tax rate found for tax percentage ' . $tax_rate->percentage . ' and tax rate ID ' . $tax_rate->id, );
						} else {
							Logger::log( 'Existing stripe tax rate ' . $tax_rate->id . ' is not active.', false );
						}
					} catch ( \Exception $e ) {
						Logger::log( $e->getMessage(), false );
					}
				}

				if ( empty( $tax_rate ) ) {
					$tax_rate = $stripe_client->taxRates->create( [
						'display_name' => 'Tax',
						'percentage'   => $tax_percentage,
						'inclusive'    => false,
					] );

					Logger::log( 'Created a new stripe tax rate for tax percentage ' . $tax_rate->percentage . '. Tax rate ID ' . $tax_rate->id, true );

					// Save the tax rate id, so it can be used for later transaction.
					Utils::save_stripe_tax_rate_id( $tax_percentage, $tax_rate->id );
				}

				if ( ! empty( $tax_rate ) ) {
					$line_item['tax_rates'] = array( $tax_rate->id );
				}
			}

			$line_items[] = $line_item;

			// Add necessary metadata here
			$metadata = array();

			$opts = array(
				'client_reference_id'        => $client_reference_id,
				'line_items'                 => $line_items,
				'metadata'                   => $metadata,
				'mode'                       => 'payment',
				'success_url'                => $success_url,
				'cancel_url'                 => $cancel_url,
				'billing_address_collection' => 'required',
				'customer_creation'          => 'if_required',
				// 'phone_number_collection' => array( 'enabled' => true ), // Uncomment if phone no collection needed.
			);

			/**
			 * Check and add shipping information.
			 */
			if ( $product->is_physical() ) {
				$country_codes_array                 = $this->get_stripe_allowed_countries();

				$opts['shipping_address_collection'] = array(
					'allowed_countries' => $country_codes_array
				);

				$total_shipping_amount = Utils::get_total_shipping_cost( array(
					'shipping'              => $product->get_shipping(),
					'shipping_per_quantity' => $product->get_shipping_per_quantity(),
					'quantity'              => $quantity,
				) );
				$total_shipping_amount = Utils::is_zero_cents_currency( $currency ) ? round( $total_shipping_amount ) : Utils::amount_in_cents( $total_shipping_amount );

				// Add shipping options
				if ( $total_shipping_amount > 0 ) {
					$opts["shipping_options"] = array(
						array(
							'shipping_rate_data' => array(
								'type'         => 'fixed_amount',
								'fixed_amount' => array(
									'amount'   => $total_shipping_amount,
									'currency' => $currency,
								),
								'display_name' => 'shipping',
							),
						),
					);
				}
			}

			if ( ! empty( $stripe_locale ) ) {
				$opts['locale'] = $stripe_locale;
			}

			$opts = apply_filters( 'wpec_stripe_checkout_session_opts', $opts, $wpec_data, $order_data );

			$session = $stripe_client->checkout->sessions->create( $opts );

			$session_id = $session->id;

			$checkout_session_trans_data = wp_parse_args(
				array(
					'wpec_data' => $wpec_data,
				),
				$sc_data_transient
			);

			set_transient( 'wpec_checkout_session_' . $session_id, $checkout_session_trans_data );

			wp_send_json_success(
				array(
					'message'      => __( 'Checkout session created successfully.', 'wp-express-checkout' ),
					'redirect_url' => $session->url,
				)
			);
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}

	}

	/**
	 * Do the API pre-submission validation. It will send the error message back to the client if there is an error.
	 * return nothing if the validation is successful, or the error message if there is an error.
	 */
	public function do_api_pre_submission_validation( $order_data_array, $array_wpec_data ) {
		$amount             = $order_data_array['total'];
		$quantity           = $order_data_array['quantity'];
		$submitted_currency = $order_data_array['currency_code'];

		$product_id = $array_wpec_data['product_id'];

		// Retrieve product item.
		try {
			$item_for_validation = Products::retrieve( intval( $product_id ) );
		} catch ( \Exception $exception ) {
			Logger::log( 'API pre-submission validation failed. ' . $exception->getMessage() );
			wp_send_json_error(
				array(
					'message' => __( 'Validation Error: ', 'wp-express-checkout' ) . $exception->getMessage(),
				)
			);
		}

		//Trigger action hook that can be used to do additional API pre-submission validation from an addon.
		do_action( 'wpec_before_api_pre_submission_validation', $item_for_validation, $order_data_array, $array_wpec_data );

		$validated = true; // We will set it to false if the validation fails in the following code.
		$error_msg = '';

		$product_type = $item_for_validation->get_type();

		switch ( $product_type ) {
			case 'subscription':
				//It's a subscription payment product. The extension will handle the validation using filter.
				break;
			case 'donation':
				//It's a donation product.
				Logger::log( "This is a donation type product. API pre-submission amount validation is not required.", true );
				break;
			default:
				//It's a one-time product. API pre-submission amount validation is required.

				// Calculate product price amount.
				$product_price = $item_for_validation->get_price();

				$variations               = ( new Variations( $item_for_validation->get_id() ) )->variations;
				$variation_price_total    = 0;
				$price_variations_applied = isset( $array_wpec_data['variations']['applied'] ) ? $array_wpec_data['variations']['applied'] : array();
				if ( is_array( $variations ) && ! empty( $price_variations_applied ) ) {
					foreach ( $variations as $index => $variation ) {
						$applied_var_index     = (int) $price_variations_applied[ $index ];
						$variation_price       = Utils::round_price( $variation['prices'][ $applied_var_index ] );
						$variation_price_total += $variation_price;
					}
					if ( ! empty( $variation_price_total ) ) {
						$construct_final_price = get_post_meta( $product_id, 'wpec_product_hide_amount_input', true );
						$product_price         = ! empty( $construct_final_price ) ? $variation_price_total : $product_price + $variation_price_total;
					}
				}

				// Calculate total product price amount.
				$total_product_price = Utils::round_price( $product_price * $quantity );
				// Logger::log( "Total product price: " . $total_product_price, true );

				// Calculate coupon discount amount.
				if ( isset( $array_wpec_data['couponCode'] ) ) {
					$coupon_code = $array_wpec_data['couponCode'];
					$coupon      = Coupons::get_coupon( $coupon_code );
					$discount    = 0;
					if ( $coupon['valid'] && Coupons::is_coupon_allowed_for_product( $coupon['id'], $product_id ) ) {
						// Get the discount amount.
						if ( $coupon['discountType'] === 'perc' ) {
							// Discount type percentage.
							$discount = Utils::round_price( $total_product_price * ( $coupon['discount'] / 100 ) );
						} else {
							// Discount type fixed.
							$discount = $coupon['discount'];
						}
					}

					$total_product_price = $total_product_price - $discount;
				}

				// Calculate tax amount.
				$tax_percentage = $item_for_validation->get_tax();
				$tax_amount     = Utils::get_tax_amount( $total_product_price, $tax_percentage );
				$tax_amount     = Utils::round_price( $tax_amount );

				// Logger::log( "Tax percentage: " . $tax_percentage, true );
				// Logger::log( "Tax amount: " . $tax_amount, true );

				// Calculate shipping amount.
				$shipping              = $item_for_validation->get_shipping();
				$shipping_per_quantity = $item_for_validation->get_shipping_per_quantity();
				$total_shipping        = Utils::get_total_shipping_cost(
					array(
						'shipping'              => $shipping,
						'shipping_per_quantity' => $shipping_per_quantity,
						'quantity'              => $quantity,
					)
				);
				// Logger::log( "Base Shipping: " . $shipping, true );
				// Logger::log( "Shipping per quantity: " . $shipping_per_quantity, true );
				// Logger::log( "Total shipping cost: " . $total_shipping, true );

				// Calculate the expected total amount.
				$expected_total_amount = $total_product_price + $tax_amount + $total_shipping;

				// Logger::log("Expected amount: ". $expected_total_amount . ", Submitted amount: " . $amount, false);

				// Check if the expected total amount matches the given amount.
				// We mainly check for underpayment (customer paying less than expected).
				// Allow a small tolerance for floating-point rounding differences.
				if ( $amount < $expected_total_amount && !Utils::almost_equal($amount, $expected_total_amount) ) {
					Logger::log( "API pre-submission validation amount mismatch. Expected amount: " . $expected_total_amount . ", Submitted amount: " . $amount, false );

					// Set the last error message that will be displayed to the user.
					$error_msg .= __( "Price validation failed. The submitted amount does not match the product's configured price. ", 'wp-express-checkout' );
					$error_msg .= "Expected: " . $expected_total_amount . ", Submitted: " . $amount;

					//Set the validation failed flag.
					$validated = false;
				}

				// Check if the expected currency matches the given currency.
				$configured_currency = Main::get_instance()->get_setting( 'currency_code' );
				if ( $submitted_currency != $configured_currency ) {
					Logger::log( "API pre-submission validation currency mismatch. Expected currency: " . $configured_currency . ", Submitted currency: " . $submitted_currency . "\n", false );

					// Set the last error message that will be displayed to the user.
					$error_msg .= __( "Currency validation failed. The submitted currency does not match the configured currency. ", 'wp-express-checkout' );
					$error_msg .= "Expected: " . $configured_currency . ", Submitted: " . $submitted_currency;

					//Set the validation failed flag.
					$validated = false;
				}

				break;
		}

		//Trigger action hook that can be used to do additional API pre-submission validation from an addon.
		$validated = apply_filters( 'wpec_pre_api_submission_validation', $validated, $item_for_validation, $order_data_array, $array_wpec_data );

		//If the validation failed, send the error message back to the client.
		if ( ! $validated ) {
			//Error condition. The validation function will set the error message which we will use to send back to the client in the next stage of the code.
			Logger::log( "API pre-submission validation failed. Stopping the process.", false );

			wp_send_json_error(
				array(
					'message' => __( 'Validation Error: ', 'wp-express-checkout' ) . $error_msg,
				)
			);

		}
		//Validation is successful, return nothing.
	}

	public function get_stripe_allowed_countries() {
		$country_codes_str = Main::get_instance()->get_setting( 'stripe_allowed_countries' );
		$country_codes_arr = explode(',', $country_codes_str);
		$country_codes_arr = array_map('trim', $country_codes_arr);
		$country_codes_arr = array_filter($country_codes_arr);
		$country_codes_arr = array_values($country_codes_arr);
		return $country_codes_arr;
	}
}
