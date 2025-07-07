<?php
/**
 * This class is used to process the payments with 0 total.
 *
 * Sends notification emails.
 * Triggers after payment processed hook: wpec_payment_completed
 * Sends to Thank You page.
 */

namespace WP_Express_Checkout;

use WP_Express_Checkout\Debug\Logger;

/**
 * Process Free payment class
 */
class Payment_Processor_Manual extends Payment_Processor {

	private $order_data;

	/**
	 * Construct the instance.
	 */
	public function __construct() {
		if ( ! empty( Main::get_instance()->get_setting( 'enable_manual_checkout' ) ) ) {
			add_action( 'wp_ajax_wpec_process_manual_checkout', array( $this, 'wpec_process_manual_checkout' ) );
			add_action( 'wp_ajax_nopriv_wpec_process_manual_checkout', array( $this, 'wpec_process_manual_checkout' ) );
		}
	}

	/**
	 * Processes the payment on AJAX call.
	 */
	public function wpec_process_manual_checkout() {
		add_filter( 'wpec_create_order_final_status', array( $this, 'set_order_status' ) );

		$this->order_data = $this->get_order_data();
		// Logger::log_array_data($this->order_data, false);

		parent::wpec_process_payment();
	} // @codeCoverageIgnore

	public function set_order_status($status) {
		return 'pending';
	}

	protected function get_payment_data() {
		$payment = array_merge( parent::get_payment_data(), array(
			'id'          => $this->get_transaction_id( array() ),
			'intent'      => 'CAPTURE',
			'status'      => $this->get_transaction_status( array() ),
			'create_time' => current_time( 'mysql' ),
			'update_time' => current_time( 'mysql' ),
		) );

		return $payment;
	}

	/**
	 * Retrieves the item name from transaction data.
	 */
	protected function get_item_name( $payment ) {
		return isset($this->order_data['name']) ? $this->order_data['name'] : '';
	}

	/**
	 * Retrieves peoduct queantity from transaction data.
	 */
	protected function get_quantity( $payment ) {
		return isset($this->order_data['quantity']) ? $this->order_data['quantity'] : '';
	}

	/**
	 * Retrieves item price from transaction data.
	 */
	protected function get_price( $payment, $trans, $data = array() ) {
		$product_id = $data['product_id'];
		$product_type = get_post_meta($product_id, 'wpec_product_type', true);
		if ($product_type == 'subscription'){
			return isset($this->order_data['price']) ? $this->order_data['price'] : '';
		}

		return parent::get_price($payment, $trans, $data);
	}

	/**
	 * Retrieves order total from transaction data.
	 */
	protected function get_total( $payment ) {
		return isset($this->order_data['total']) ? $this->order_data['total'] : '';
	}

	/**
	 * Retrieves currency from transaction data.
	 */
	protected function get_currency( $payment ) {
		return isset($this->order_data['currency']) ? $this->order_data['currency'] : '';
	}

	/**
	 * Generate a custom transaction id for manual checkout.
	 */
	protected function get_transaction_id( $payment ) {
		return 'manual_' . strtoupper(substr( sha1( time() . mt_rand( 0, 1000 ) ), 0, 20 ));
	}

	/**
	 * Generate a custom capture id for manual checkout.
	 */
	protected function get_capture_id( $payment ) {
		return 'manual_' . strtoupper(substr( sha1( time() . mt_rand( 0, 1000 ) ), 0, 20 ));
	}

	/**
	 * Retrieves transaction status.
	 */
	protected function get_transaction_status( $payment ) {
		return 'COMPLETED';
	}
}
