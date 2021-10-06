<?php
/**
 * This class is used to process the payment after successful charge.
 *
 * Validates payment.
 * Completes WC_Order.
 * Sends to Thank You page.
 */

namespace WP_Express_Checkout\Integrations;

use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Main;
use WP_Express_Checkout\Payment_Processor;
use WP_Express_Checkout\Utils;

/**
 * Process IPN class
 */
class WooCommerce_Payment_Processor extends Payment_Processor {

	/**
	 * Construct the instance.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wpec_process_wc_payment', array( $this, 'wpec_process_payment' ) );
		add_action( 'wp_ajax_nopriv_wpec_process_wc_payment', array( $this, 'wpec_process_payment' ) );
		add_action( 'wp_ajax_wpec_wc_render_button', array( $this, 'render_button' ) );
		add_action( 'wp_ajax_nopriv_wpec_wc_render_button', array( $this, 'render_button' ) );
	}

	/**
	 * Processes the payment on AJAX call.
	 */
	public function wpec_process_payment() {

		$payment = $this->get_payment_data();
		$data    = $this->get_order_data();

		if ( empty( $payment ) ) {
			// no payment data provided.
			$this->send_error( __( 'No payment data received.', 'wp-express-checkout' ), 3001 );
		}

		if ( empty( $data ) ) {
			// no order data provided.
			$this->send_error( __( 'No order data received.', 'wp-express-checkout' ), 3002 );
		}

		if ( ! check_ajax_referer( $data['id'] . $data['product_id'], 'nonce', false ) ) {
			$this->send_error( __( 'Error! Nonce value is missing in the URL or Nonce verification failed.', 'wp-express-checkout' ), 3003 );
		}

		$this->check_status( $payment );

		// Log debug (if enabled).
		Logger::log( 'Received IPN. Processing payment ...' );

		// get item name.
		$trans = $this->get_transient_data( $payment );
		// let's check if the payment matches transient data.
		if ( ! $trans ) {
			// no price set.
			$this->send_error( __( 'No transaction info found in transient.', 'wp-express-checkout' ), 3004 );
		}
		$currency = $trans['currency'];

		$order = new \WC_Order( $trans['wc_id'] );

		$amount = Utils::round_price( floatval( $this->get_total( $payment ) ) );
		// check if amount paid is less than original price x quantity. This has better fault tolerant than checking for equal (=).
		if ( $amount < $order->get_total() ) {
			// payment amount mismatch. Amount paid is less.
			Logger::log( 'Error! Payment amount mismatch. Original: ' . $order->get_total() . ', Received: ' . $amount, false );
			$this->send_error( __( 'Payment amount mismatch with the original price.', 'wp-express-checkout' ), 3005 );
		}

		// check if payment currency matches.
		if ( $this->get_currency( $payment ) !== $currency ) {
			// payment currency mismatch.
			$this->send_error( __( 'Payment currency mismatch.', 'wp-express-checkout' ), 3006 );
		}

		// If code execution got this far, it means everything is ok with payment
		// let's insert order.
		$order->payment_complete( $this->get_transaction_id( $payment ) );
		WC()->cart->empty_cart();
		$order->reduce_order_stock();

		$res = array();

		$thank_you_url = $trans['thank_you_url'];

		if ( wp_http_validate_url( $thank_you_url ) ) {
			$redirect_url = $thank_you_url;
			$res['redirect_url'] = esc_url_raw( $redirect_url );
		} else {
			$this->send_error( __( 'Error! Thank you page URL configuration is wrong in the plugin settings.', 'wp-express-checkout' ), 3007 );
		}

		$this->send_response( $res );
	} // @codeCoverageIgnore


	public function render_button() {
		if ( ! check_ajax_referer( 'wpec-wc-render-button-nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Error! Nonce value is missing in the URL or Nonce verification failed.', 'wp-express-checkout' ) );
		}

		if ( empty( $_POST['order_id'] ) ) {
			wp_send_json_error( __( 'No order data received.', 'wp-express-checkout' ) );
		}

		$gateway = array_shift( wp_list_filter( WC()->payment_gateways()->payment_gateways, array( 'id' => 'wp-express-checkout' ) ) );

		// Get our WC gateway and call receipt_page()
		ob_start();
		$gateway->receipt_page( intval( $_POST['order_id'] ) );
		$output = ob_get_clean();
		wp_send_json_success( $output );
	}

}
