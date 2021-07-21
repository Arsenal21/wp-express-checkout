<?php

namespace WP_Express_Checkout;

use Exception;

/**
 * This mock throws exeptions instead of calling wp_send_json() function.
 */
class Mock_Payment_Processor extends Payment_Processor {

	public $_payment_data = [];
	public $_order_data = [
		'id' => 0,
		'product_id' => 0,
		'orig_price' => 10,
	];
	public $_transient = [
		'price'           => 20,
		'currency'        => 'USD',
		'quantity'        => 5,
		'tax'             => 20,
		'shipping'        => 10,
		'shipping_enable' => 1,
		'url'             => 'http://example.com',
		'custom_quantity' => 1,
		'custom_amount'   => 0,
		'product_id'      => 0,
		'coupons_enabled' => 1,
		'thank_you_url'   => 'http://example.com/thank_you',
	];

	protected function send_error( $msg, $code ) {
		throw new Exception( $msg, $code );
	}

	protected function get_payment_data() {
		return $this->_payment_data;
	}

	protected function get_order_data() {
		return $this->_order_data;
	}

	protected function get_transient_data( $payment ) {
		return $this->_transient;
	}

}
