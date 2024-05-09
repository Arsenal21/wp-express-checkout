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
//		add_action( 'wp_ajax_wpec_process_wc_payment', array( $this, 'wpec_process_payment' ) );
//		add_action( 'wp_ajax_nopriv_wpec_process_wc_payment', array( $this, 'wpec_process_payment' ) );
	}

	/**
	 * Processes the payment on AJAX call.
	 */
	public function wpec_woocommerce_process_payment($txn_data, $order_data) {
		Logger::log( 'Code came here: wpec_process_payment' );
//		$payment = $this->get_payment_data(); // wp_ppdg_payment
//		$data    = $this->get_order_data(); // data
		$payment = $txn_data; // wp_ppdg_payment
		$data    = $order_data; // data

		Logger::log('on wpec_woocommerce_process_payment: $txn_data', true);
		Logger::log_array_data( $txn_data, true ); // Debug purpose.
		Logger::log('on wpec_woocommerce_process_payment: $order_data', true);
		Logger::log_array_data( $order_data, true ); // Debug purpose.

		if ( empty( $payment ) ) {
			// no payment data provided.
			$msg = __( 'No payment data received.', 'wp-express-checkout' );
			Logger::log( $msg, false );
			$this->send_json_response($msg, false);
		}

		if ( empty( $data ) ) {
			// no order data provided.
			$msg = __( 'No order data received.', 'wp-express-checkout' );
			Logger::log( $msg, false );
			$this->send_json_response($msg, false);
		}

		if ( ! check_ajax_referer( 'wpec-woocommerce-create-order-js-ajax-nonce', 'nonce', false ) ) {
			// nonce verification failed.
			$msg = __( 'Error! Nonce value is missing in the URL or Nonce verification failed.', 'wp-express-checkout' );
			Logger::log( $msg, false );
			$this->send_json_response($msg, false);
		}

		$status =  $payment['status'];
		if ( strtoupper( $status ) !== 'COMPLETED' ) {
			// payment is not successful.
			$msg =  sprintf( __( 'Payment status is not completed. Status: %s', 'wp-express-checkout' ), $status );
			Logger::log( $msg, false );
			$this->send_json_response($msg, false);
		}

		// Log debug (if enabled).
		Logger::log( 'Payment Captured. Doing post payment processing tasks ...' );

		// get item name.
		$trans_name  = 'wp-ppdg-' . sanitize_title_with_dashes( $order_data['name'] );
		$trans = get_transient($trans_name);

		// let's check if the payment matches transient data.
		if ( ! $trans ) {
			// no price set.
			$msg =  __( 'No transaction info found in transient.', 'wp-express-checkout' );
			Logger::log( $msg, false );
			$this->send_json_response($msg, false);
		}
		$currency = $trans['currency'];

		$order = new \WC_Order( $trans['wc_id'] );

		// Get received amount.
		$received_amount = Utils::round_price( floatval( $payment['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ) );
		Logger::log( 'Check received amount: '. $received_amount, true );
		// check if amount paid is less than original price x quantity. This has better fault tolerant than checking for equal (=).
		if ( $received_amount < $order->get_total() ) {
			// payment amount mismatch. Amount paid is less.
			$msg = __( 'Payment amount mismatch with the original price.', 'wp-express-checkout' );
			Logger::log( 'Error! Payment amount mismatch. Original: ' . $order->get_total() . ', Received: ' . $received_amount, false );
			$this->send_json_response($msg, false );
		}

		// check if payment currency matches.
		$received_currency = $payment['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'];
		Logger::log( 'Check received currency: '. $received_currency, true );
		if ( $received_currency !== $order->get_currency() ) {
			// payment currency mismatch.
			$msg =  __( 'Payment currency mismatch.', 'wp-express-checkout' );
			Logger::log( $msg, false );
			$this->send_json_response($msg, false);
		}

		// If code execution got this far, it means everything is ok with payment
		// let's insert order into WooCommerce.
		$paypal_capture_id = isset($txn_data['purchase_units'][0]['payments']['captures'][0]['id']) ? $txn_data['purchase_units'][0]['payments']['captures'][0]['id'] : '';
		Logger::log( 'PayPal transaction id is: '. $paypal_capture_id, true );

		$wc_payment_complete = true;
		$order->payment_complete( $paypal_capture_id );
		Logger::log( "Executed the payment_complete() function.", true );

		// Clear cart.
		WC()->cart->empty_cart();

		$res = array();

		$thank_you_url = $trans['thank_you_url'];

		if ( wp_http_validate_url( $thank_you_url ) ) {
			$redirect_url = $thank_you_url;
			$res['redirect_url'] = esc_url_raw( $redirect_url );
		} else {
			$msg =  __( 'Error! Thank you page URL configuration is wrong in the plugin settings.', 'wp-express-checkout' );
			Logger::log( $msg, false );
			$this->send_json_response($msg, false);
		}

		Logger::log( "Order captured successfully", true );
		$this->send_json_response(__('Order captured successfully', 'simple-membership'), true, $res);

	}


	public function send_json_response($message, $success = true, $data = null) {
		$payload = 	array(
			'success' => $success,
			'message' => $message,
		);

		if (!empty($data)){
			$payload['data'] = $data;
		}

		wp_send_json($payload);
		exit;
	}

}
