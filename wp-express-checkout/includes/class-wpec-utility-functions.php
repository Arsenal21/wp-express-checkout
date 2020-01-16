<?php

class WPEC_Utility_Functions {

	public function __construct() {

	}

	/**
	 * Returns the price given the arguments in settings
	 *
	 * @param  int    $price             The numerical value to format.
	 * @param  string $override_currency The currency the value is in.
	 * @return string                    The formatted price.
	 */
	public static function price_format( $price, $override_currency = '' ) {
		$ppdg = WPEC_Main::get_instance();

		$formatted_price = number_format( $price, 2, '.', '' );
		$currency_code   = ( empty( $override_currency ) ) ? $ppdg->get_setting( 'currency_code' ) : $override_currency;
		$position        = 'right_space'; // Expected to be a setting.

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

	/*
	 * Replaced the dynamic tags with order details (given the order_id)
	 */
	public static function replace_dynamic_order_tags( $text, $order_id ) {
		$payment_details = get_post_meta( $order_id, 'ppec_payment_details', true );
		$payer_details   = get_post_meta( $order_id, 'ppec_payer_details', true );

		$formatted_amount = number_format( $payment_details['amount'], 2, '.', '' );
		$product_details  = $payment_details['item_name'] . ' x ' . $payment_details['quantity'] . ' - ' . self::price_format( $payment_details['amount'], $payment_details['currency'] ) . "\n";

		$tags_vals = array(
			'first_name'      => $payer_details['name']['given_name'],
			'last_name'       => $payer_details['name']['surname'],
			'product_details' => $product_details,
			'payer_email'     => $payer_details['email_address'],
			'transaction_id'  => $payment_details['id'],
			'purchase_amt'    => $formatted_amount,
			'purchase_date'   => date( 'Y-m-d' ),
			'coupon_code'     => '', // Seems like not implemented yet.
			'address'         => '', // Not implemented yet.
			'order_id'        => $order_id,
		);

		$tags = array();
		$vals = array();
		foreach ( $tags_vals as $key => $value ) {
			$tags[] = "{{$key}}";
			$vals[] = ( isset( $tags_vals[ $key ] ) ) ? $tags_vals[ $key ] : '';
		}

		$text = stripslashes( str_replace( $tags, $vals, $text ) );
		return $text;
	}

}
