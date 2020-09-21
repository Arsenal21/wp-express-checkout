<?php

class WPEC_Utility_Functions {

	public function __construct() {

	}

	/**
	 * Returns the price given the arguments in settings
	 *
	 * @param  int    $price             The numerical value to format.
	 * @param  string $override_currency The currency the value is in.
	 * @param  string $override_position The currency the position is in.
	 * @return string                    The formatted price.
	 */
	public static function price_format( $price, $override_currency = '', $override_position = '' ) {
		$ppdg = WPEC_Main::get_instance();

		$decimals        = ( ! $ppdg->get_setting( 'price_decimals_num' ) ) ? 0 : $ppdg->get_setting( 'price_decimals_num' );
		$formatted_price = number_format( $price, $decimals, $ppdg->get_setting( 'price_decimal_sep' ), $ppdg->get_setting( 'price_thousand_sep' ) );
		$position        = ( empty( $override_position ) ) ? $ppdg->get_setting( 'price_currency_pos' ) : $override_position;

		if ( ! empty( $override_currency ) && $override_currency !== $ppdg->get_setting( 'currency_code' ) ) {
			$currency_code = $override_currency;
		} elseif ( ! empty( $ppdg->get_setting( 'currency_symbol' ) ) ) {
			$currency_code = $ppdg->get_setting( 'currency_symbol' );
		} else {
			$currency_code = $ppdg->get_setting( 'currency_code' );
		}

		if ( empty( $position ) ) {
			$position = 'right_space';
		}

		$formats = array(
			'left'        => '{symbol}{price}',
			'left_space'  => '{symbol} {price}',
			'right'       => '{price}{symbol}',
			'right_space' => '{price} {symbol}',
		);

		$search  = array( '{price}', '{symbol}' );
		$replace = array( $formatted_price, $currency_code );

		return str_replace( $search, $replace, $formats[ $position ] );
	}

	public static function get_tax_amount( $price, $tax ) {
		$ppdg = WPEC_Main::get_instance();

		if ( ! empty( $tax ) ) {
			$prec       = ( ! $ppdg->get_setting( 'price_decimals_num' ) ) ? 0 : $ppdg->get_setting( 'price_decimals_num' );
			$tax_amount = round( ( $price * $tax / 100 ), $prec );
			return $tax_amount;
		} else {
			return 0;
		}
	}

	public static function round_price( $price ) {
		$ppdg = WPEC_Main::get_instance();

		$prec  = ( ! $ppdg->get_setting( 'price_decimals_num' ) ) ? 0 : $ppdg->get_setting( 'price_decimals_num' );
		$price = round( $price, $prec );

		return $price;
	}

	public static function apply_tax( $price, $tax ) {
		$ppdg = WPEC_Main::get_instance();

		if ( ! empty( $tax ) ) {
			$prec       = ( ! $ppdg->get_setting( 'price_decimals_num' ) ) ? 0 : $ppdg->get_setting( 'price_decimals_num' );
			$tax_amount = round( ( $price * $tax / 100 ), $prec );
			$price     += $tax_amount;
		}
		return $price;
	}

	public static function apply_shipping( $price, $shipping ) {
		$ppdg = WPEC_Main::get_instance();

		if ( ! empty( $shipping ) ) {
			$prec   = ( ! $ppdg->get_setting( 'price_decimals_num' ) ) ? 0 : $ppdg->get_setting( 'price_decimals_num' );
			$price += floatval( $shipping );
			$price  = round( $price, $prec );
		}
		return $price;
	}

	/**
	 * Replaces the dynamic tags with order details (given the order_id).
	 *
	 * @param string $text     The text to be processed.
	 * @param int    $order_id The order ID to retrieve default values.
	 * @param array  $args     The custom arguments to replace default order data.
	 *
	 * @return string
	 */
	public static function replace_dynamic_order_tags( $text, $order_id, $args = array() ) {
		$payment_details = get_post_meta( $order_id, 'ppec_payment_details', true );
		$payer_details   = get_post_meta( $order_id, 'ppec_payer_details', true );
		$product_details = self::get_product_details( $payment_details ) . "\n";

		$tags_vals = array(
			'first_name'      => $payer_details['name']['given_name'],
			'last_name'       => $payer_details['name']['surname'],
			'product_details' => $product_details,
			'payer_email'     => $payer_details['email_address'],
			'transaction_id'  => $payment_details['id'],
			'purchase_amt'    => $payment_details['amount'],
			'purchase_date'   => date( 'Y-m-d' ),
			'coupon_code'     => $payment_details['coupon_code'],
			'address'         => '', // Not implemented yet.
			'order_id'        => $order_id,
		);

		$tags_vals = array_merge( $tags_vals, $args );

		$text = self::apply_dynamic_tags( $text, $tags_vals );
		return $text;
	}

	/**
	 * Replaces tags in the text with appropriate values.
	 *
	 * @since 2.0
	 *
	 * @param string $text The text with tags to be replaced.
	 * @param array  $args The array of the tags values.
	 *
	 * @return string
	 */
	public static function apply_dynamic_tags( $text, $args ) {

		$white_list = array(
			'first_name',
			'last_name',
			'product_details',
			'payer_email',
			'transaction_id',
			'purchase_amt',
			'purchase_date',
			'coupon_code',
			'address',
			'order_id',
		);

		$tags = array();
		$vals = array();

		foreach ( $white_list as $item ) {
			$tags[] = "{{$item}}";
			$vals[] = ( isset( $args[ $item ] ) ) ? $args[ $item ] : '';
		}

		$body = stripslashes( str_replace( $tags, $vals, $text ) );

		return $body;
	}

	/**
	 * Generates plain product details for Order summary and emails by given
	 * order details.
	 *
	 * @since 2.0
	 *
	 * @param array $payment The order details stored in the
	 *                       `ppec_payment_details` meta field.
	 * @return string
	 */
	public static function get_product_details( $payment ) {
		$output   = '';

		/* translators: {Order Summary Item Name}: {Value} */
		$template = __( '%1$s: %2$s', 'wp-express-checkout' );

		$output .= sprintf( $template, __( 'Product Name', 'wp-express-checkout' ), $payment['item_name'] ) . "\n";
		$output .= sprintf( $template, __( 'Quantity', 'wp-express-checkout' ), $payment['quantity'] ) . "\n";
		$output .= sprintf( $template, __( 'Price', 'wp-express-checkout' ), self::price_format( $payment['price'] ) ) . "\n";

		foreach ( $payment['variations'] as $var ) {
			if ( $var[1] < 0 ) {
				$amnt_str = '-' . self::price_format( abs( $var[1] ) );
			} else {
				$amnt_str = self::price_format( $var[1] );
			}
			$output .= sprintf( $template, $var[0], $amnt_str ) . "\n";
		}

		if ( $payment['discount'] || $payment['tax_total'] || $payment['shipping'] ) {
			$output .= '--------------------------------' . "\n";
			$output .= sprintf( $template, __( 'Subtotal', 'wp-express-checkout' ), self::price_format( ( $payment['price'] + $payment['var_amount'] ) * $payment['quantity'] ) ) . "\n";
			$output .= '--------------------------------' . "\n";
		}

		if ( $payment['discount'] ) {
			$output .= ( $payment['coupon_code'] ) ? sprintf( $template, __( 'Coupon Code', 'wp-express-checkout' ), $payment['coupon_code'] ) . "\n" : '';
			$output .= ( $payment['discount'] ) ? sprintf( $template, __( 'Discount', 'wp-express-checkout' ), self::price_format( $payment['discount'] ) ) . "\n" : '';
		}

		$output .= ( $payment['tax_total'] ) ? sprintf( $template, __( 'Tax', 'wp-express-checkout' ), self::price_format( $payment['tax_total'] ) ) . "\n" : '';
		$output .= ( $payment['shipping'] ) ? sprintf( $template, __( 'Shipping', 'wp-express-checkout' ), self::price_format( $payment['shipping'] ) ) . "\n" : '';
		$output .= '--------------------------------' . "\n";
		$output .= sprintf( $template, __( 'Total Amount', 'wp-express-checkout' ), self::price_format( $payment['amount'] ) ) . "\n";

		return $output;
	}

	/*
	 * Use this function to redirect to a URL
	 */
	public static function redirect_to_url( $url, $delay = '0', $exit = '1' ) {
		$url = apply_filters( 'wpec_before_redirect_to_url', $url );
		if ( empty( $url ) ) {
			echo '<strong>';
			_e( 'Error! The URL value is empty. Please specify a correct URL value to redirect to!', 'wp-express-checkout' );
			echo '</strong>';
			exit;
		}
		if ( ! headers_sent() ) {
			header( 'Location: ' . $url );
		} else {
			echo '<meta http-equiv="refresh" content="' . $delay . ';url=' . $url . '" />';
		}

		if ( $exit == '1' ) {//exit
			exit;
		}
	}

}
