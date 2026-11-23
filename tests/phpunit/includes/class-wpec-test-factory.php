<?php
/**
 * Helpers that create the plugin's products and coupons, change the settings, and build checkout request data
 * the same way the frontend JavaScript does.
 */
class WPEC_Test_Factory {

	const SETTINGS_OPTION = 'ppdg-settings';

	/**
	 * Creates a published one-time product.
	 *
	 * @param string|float $price Product price (as entered on the edit product page, for example '10' or '19.99').
	 * @param array        $meta  Other product meta, for example array( 'wpec_product_tax' => '10' ).
	 * @param string       $title Product name.
	 *
	 * @return int Product ID.
	 */
	public static function create_product( $price, $meta = array(), $title = 'Test Product' ) {
		$product_id = wp_insert_post(
			array(
				'post_type'   => 'ppec-products',
				'post_title'  => $title,
				'post_status' => 'publish',
			)
		);
		$meta = array_merge(
			array(
				'wpec_product_type'  => 'one_time',
				'ppec_product_price' => (string) $price,
				//The edit product page saves these as empty strings when they are not entered.
				'wpec_product_tax'   => '',
				'wpec_product_shipping' => '',
			),
			$meta
		);
		foreach ( $meta as $key => $value ) {
			update_post_meta( $product_id, $key, $value );
		}
		return $product_id;
	}

	/**
	 * Creates a published donation product (the buyer enters the amount).
	 *
	 * @param string|float $min_amount Minimum donation amount (0 = no minimum).
	 * @param array        $meta       Other product meta.
	 *
	 * @return int Product ID.
	 */
	public static function create_donation_product( $min_amount, $meta = array(), $title = 'Test Donation' ) {
		return self::create_product(
			'',
			array_merge(
				array(
					'wpec_product_type'       => 'donation',
					'wpec_product_min_amount' => $min_amount,
				),
				$meta
			),
			$title
		);
	}

	/**
	 * Adds price variations to a product, the way the edit product page saves them.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $groups     Group name => array( variation name => price ), for example
	 *                          array( 'Size' => array( 'Small' => 0, 'Large' => 5 ) ).
	 */
	public static function add_variations( $product_id, $groups ) {
		$group_names = array();
		$names       = array();
		$prices      = array();
		$urls        = array();
		foreach ( array_values( $groups ) as $grp_id => $variations ) {
			$group_names[ $grp_id ] = array_keys( $groups )[ $grp_id ];
			$names[ $grp_id ]       = array_map( 'strval', array_keys( $variations ) );
			$prices[ $grp_id ]      = array_map( 'strval', array_values( $variations ) );
			$urls[ $grp_id ]        = array_fill( 0, count( $variations ), '' );
		}
		update_post_meta( $product_id, 'wpec_variations_groups', $group_names );
		update_post_meta( $product_id, 'wpec_variations_names', $names );
		update_post_meta( $product_id, 'wpec_variations_prices', $prices );
		update_post_meta( $product_id, 'wpec_variations_urls', $urls );
	}

	/**
	 * Creates an active coupon that started yesterday (the meta is saved the same way as the admin coupon form saves it).
	 *
	 * @param string       $code          Coupon code.
	 * @param string|float $discount      Discount (percent or fixed amount).
	 * @param string       $discount_type 'perc' or 'fixed'.
	 * @param array        $meta          Other coupon meta without the 'wpec_coupon_' prefix, for example array( 'exp_date' => '2020-01-01' ).
	 *
	 * @return int Coupon ID.
	 */
	public static function create_coupon( $code, $discount, $discount_type = 'perc', $meta = array() ) {
		$coupon_id = wp_insert_post(
			array(
				'post_type'   => 'wpec_coupons',
				'post_title'  => '',
				'post_status' => 'publish',
			)
		);
		$meta = array_merge(
			array(
				'code'                      => $code,
				'active'                    => 1,
				'discount'                  => (string) $discount,
				'discount_type'             => $discount_type,
				'start_date'                => gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
				'exp_date'                  => '',
				'red_limit'                 => '',
				'red_count'                 => '',
				'only_for_allowed_products' => 0,
				'allowed_products'          => array(),
			),
			$meta
		);
		foreach ( $meta as $key => $value ) {
			update_post_meta( $coupon_id, 'wpec_coupon_' . $key, $value );
		}
		return $coupon_id;
	}

	/**
	 * Changes some of the plugin settings.
	 */
	public static function update_settings( $changes ) {
		$settings = (array) get_option( self::SETTINGS_OPTION, array() );
		update_option( self::SETTINGS_OPTION, array_merge( $settings, $changes ) );
	}

	/**
	 * Rounds like the frontend's PHP_round() (Math.round() based).
	 */
	public static function js_round( $num, $dec ) {
		$factor = pow( 10, $dec );
		return floor( $num * $factor + 0.5 ) / $factor;
	}

	/**
	 * Builds the purchase data ('wpec_data') the way the frontend builds it (the shortcode's JS data, then ppecHandler.calcTotal()
	 * in assets/js/public.js).
	 *
	 * @param int   $product_id Product ID.
	 * @param array $args       What the buyer chose: quantity, amount (donations), variations (array of the applied variation index per group),
	 *                          coupon (array( 'code', 'discount', 'discountType' ) as the coupon check request returns it).
	 *
	 * @return array
	 */
	public static function frontend_data( $product_id, $args = array() ) {
		$args = array_merge(
			array(
				'quantity'   => 1,
				'amount'     => null,
				'variations' => null,
				'coupon'     => null,
			),
			$args
		);

		$product = \WP_Express_Checkout\Products::retrieve( $product_id );
		$wpec    = \WP_Express_Checkout\Main::get_instance();
		$dec     = intval( $wpec->get_setting( 'price_decimals_num' ) );

		$price = null !== $args['amount'] ? floatval( $args['amount'] ) : floatval( $product->get_price() );
		$data  = array(
			'id'                    => 'wp_express_checkout_0',
			'name'                  => get_the_title( $product_id ),
			'product_id'            => $product_id,
			'orig_price'            => $price,
			'price'                 => $price,
			'quantity'              => intval( $args['quantity'] ),
			'tax'                   => $product->get_tax(),
			'shipping'              => $product->get_shipping(),
			'shipping_per_quantity' => $product->get_shipping_per_quantity(),
			'shipping_enable'       => $product->is_physical(),
			'dec_num'               => $dec,
			'currency'              => $wpec->get_setting( 'currency_code' ),
			'custom_amount'         => 'donation' === $product->get_type(),
			'variations'            => $product->get_variations(),
		);

		return self::apply_buyer_choices( $data, $args );
	}

	/**
	 * Applies what the buyer chose to the shortcode's JS data, then runs calcTotal(). It doesn't need WordPress
	 * (the endpoint tests use it with the JS data they read from the product page).
	 *
	 * @param array $data The JS data (as the shortcode prints it for ppecHandler, with 'quantity' set to the buyer's quantity).
	 * @param array $args 'variations' and 'coupon' (see frontend_data()).
	 */
	public static function apply_buyer_choices( $data, $args ) {
		$dec = intval( $data['dec_num'] );

		//Variations (applyVariations(): the base price plus each applied variation's price).
		if ( isset( $args['variations'] ) ) {
			$data['variations']['applied'] = $args['variations'];
			foreach ( $args['variations'] as $grp_id => $var_index ) {
				$data['price'] += floatval( $data['variations'][ $grp_id ]['prices'][ $var_index ] );
			}
			$data['price'] = self::js_round( $data['price'], $dec );
		}

		//Coupon (the coupon check request's response is merged into the data).
		if ( isset( $args['coupon'] ) ) {
			$data['couponCode']   = $args['coupon']['code'];
			$data['discount']     = $args['coupon']['discount'];
			$data['discountType'] = $args['coupon']['discountType'];
		}

		return self::calc_total( $data );
	}

	/**
	 * The frontend's calcTotal() (assets/js/public.js): sets newPrice (the unit price after the coupon), discountAmount,
	 * tax_amount (per item), total and subtotal. It doesn't need WordPress.
	 */
	public static function calc_total( $data ) {
		$dec       = intval( $data['dec_num'] );
		$item_subt = floatval( $data['price'] );
		$quantity  = intval( $data['quantity'] );
		$t_amount  = floatval( number_format( $item_subt * $quantity, 2, '.', '' ) );
		$subtotal  = $t_amount;

		$data['newPrice'] = $item_subt;

		if ( isset( $data['discount'] ) ) {
			if ( 'perc' === $data['discountType'] ) {
				$discount_amount = self::js_round( $t_amount * $data['discount'] / 100, $dec );
			} else {
				$discount_amount = floatval( $data['discount'] );
			}
			if ( $discount_amount > $t_amount ) {
				$discount_amount = $t_amount;
			}
			$t_amount              -= $discount_amount;
			$data['discountAmount'] = self::js_round( $discount_amount, $dec );
			$data['newPrice']       = self::js_round( $item_subt - $discount_amount / $quantity, $dec );
		}

		if ( ! empty( $data['tax'] ) ) {
			$tax                = self::js_round( $t_amount / $quantity * $data['tax'] / 100, $dec );
			$data['tax_amount'] = $tax;
			$t_amount          += $tax * $quantity;
			$subtotal          += self::js_round( $subtotal / $quantity * $data['tax'] / 100, $dec ) * $quantity;
		}

		$shipping_total = self::frontend_shipping_total( $data );
		if ( $shipping_total ) {
			$t_amount += $shipping_total;
			$subtotal += $shipping_total;
		}

		$data['total']    = self::js_round( $t_amount, $dec );
		$data['subtotal'] = self::js_round( $subtotal, $dec );

		return $data;
	}

	/**
	 * The frontend's getTotalShippingCost().
	 */
	public static function frontend_shipping_total( $data ) {
		$quantity     = ! empty( $data['quantity'] ) ? intval( $data['quantity'] ) : 1;
		$base         = ! empty( $data['shipping'] ) ? floatval( $data['shipping'] ) : 0;
		$per_quantity = ! empty( $data['shipping_per_quantity'] ) ? floatval( $data['shipping_per_quantity'] ) : 0;
		return floatval( number_format( $base + $per_quantity * $quantity, 2, '.', '' ) );
	}

	/**
	 * The POST fields of the Stripe checkout session request (assets/js/wpec-stripe.js), slashed like WordPress slashes $_POST.
	 *
	 * @param array $wpec_data  The purchase data (see frontend_data()).
	 * @param array $order_data Changes to the 'data' field (total, quantity, currency_code).
	 */
	public static function stripe_request( $wpec_data, $order_data = array() ) {
		$order_data = array_merge(
			array(
				'total'         => $wpec_data['total'],
				'quantity'      => $wpec_data['quantity'],
				'currency_code' => $wpec_data['currency'],
			),
			$order_data
		);
		return wp_slash(
			array(
				'action'    => 'wpec_stripe_create_checkout_session',
				'wpec_data' => wp_json_encode( $wpec_data ),
				'data'      => wp_json_encode( $order_data ),
				'nonce'     => wp_create_nonce( 'wpec-stripe-create-order-ajax-nonce' ),
			)
		);
	}

	/**
	 * The PayPal create order request's order data (createOrderCallback() in assets/js/wpec-paypal.js).
	 *
	 * @param array $wpec_data The purchase data (see frontend_data()).
	 */
	public static function paypal_order_data( $wpec_data ) {
		$currency   = $wpec_data['currency'];
		$item_total = floatval( number_format( $wpec_data['price'] * $wpec_data['quantity'], 2, '.', '' ) );
		$shipping   = ( '' !== $wpec_data['shipping'] || true === $wpec_data['shipping_enable'] ) ? 'GET_FROM_FILE' : 'NO_SHIPPING';

		$order = array(
			'intent'         => 'CAPTURE',
			'payment_source' => array(
				'paypal' => array(
					'experience_context' => array(
						'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
						'shipping_preference'       => $shipping,
						'user_action'               => 'PAY_NOW',
					),
				),
			),
			'purchase_units' => array(
				array(
					'amount' => array(
						'value'         => $wpec_data['total'],
						'currency_code' => $currency,
						'breakdown'     => array(
							'item_total' => array(
								'currency_code' => $currency,
								'value'         => $item_total,
							),
						),
					),
					'items'  => array(
						array(
							'name'        => $wpec_data['name'],
							'quantity'    => $wpec_data['quantity'],
							'unit_amount' => array(
								'value'         => $wpec_data['price'],
								'currency_code' => $currency,
							),
						),
					),
				),
			),
		);
		if ( ! empty( $wpec_data['tax'] ) ) {
			$order['purchase_units'][0]['amount']['breakdown']['tax_total'] = array(
				'currency_code' => $currency,
				'value'         => self::js_round( $wpec_data['tax_amount'] * $wpec_data['quantity'], $wpec_data['dec_num'] ),
			);
		}
		if ( ! empty( $wpec_data['shipping'] ) ) {
			$order['purchase_units'][0]['amount']['breakdown']['shipping'] = array(
				'currency_code' => $currency,
				'value'         => self::frontend_shipping_total( $wpec_data ),
			);
		}
		if ( ! empty( $wpec_data['discount'] ) ) {
			$order['purchase_units'][0]['amount']['breakdown']['discount'] = array(
				'currency_code' => $currency,
				'value'         => floatval( $wpec_data['discountAmount'] ),
			);
		}
		return $order;
	}

	/**
	 * The POST fields of the PayPal create order request, slashed like WordPress slashes $_POST.
	 */
	public static function paypal_create_order_request( $wpec_data, $order_data = null ) {
		$order_data = null === $order_data ? self::paypal_order_data( $wpec_data ) : $order_data;
		return wp_slash(
			array(
				'action'    => 'wpec_pp_create_order',
				'data'      => wp_json_encode( $order_data ),
				'wpec_data' => wp_json_encode( $wpec_data ),
				'_wpnonce'  => wp_create_nonce( 'wpec-create-order-js-ajax-nonce' ),
			)
		);
	}
}
