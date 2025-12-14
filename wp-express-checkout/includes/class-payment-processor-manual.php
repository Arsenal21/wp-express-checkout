<?php

namespace WP_Express_Checkout;

use WP_Express_Checkout\Debug\Logger;

/**
 * Process Manual Checkout class
 */
class Payment_Processor_Manual extends Payment_Processor {

	private $order_data;
	private $transaction_id;
	private $capture_id;
	private $transaction_status;

	/**
	 * Construct the instance.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wpec_process_manual_checkout', array( $this, 'wpec_process_manual_checkout' ) );
		add_action( 'wp_ajax_nopriv_wpec_process_manual_checkout', array( $this, 'wpec_process_manual_checkout' ) );
	}

	/**
	 * Processes the payment on AJAX call.
	 */
	public function wpec_process_manual_checkout() {
		$wpec_plugin = Main::get_instance();

		if ( empty( $wpec_plugin->get_setting( 'enable_manual_checkout' ) ) ) {
			// Manual checkout is not enabled.
			return;
		}

		$this->order_data         = $this->get_order_data();
		$this->transaction_id     = 'manual_' . strtoupper( substr( sha1( time() . mt_rand( 0, 1000 ) ), 0, 20 ) );
		$this->capture_id         = 'manual_' . strtoupper( substr( sha1( time() . mt_rand( 0, 1000 ) ), 0, 20 ) );
		$this->transaction_status = 'COMPLETED';

		$payment = $this->get_payment_data();
		$data    = $this->order_data;

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

		/**
		 * Runs before processing anything.
		 *
		 * @param array $payment The raw order data retrieved via API.
		 * @param array $data    The purchase data generated on a client side.
		 */
		do_action( 'wpec_process_manual_checkout', $payment, $data );

		// Log debug (if enabled).
		Logger::log( 'Request received. Processing manual checkout...' );

		// get item name.
		$item_name = $this->get_item_name( $payment );
		$trans     = $this->get_transient_data( $payment );

		// let's check if the payment matches transient data.
		if ( ! $trans ) {
			// no price set.
			$this->send_error( __( 'No transaction info found in transient.', 'wp-express-checkout' ), 3004 );
		}
		$price    = $this->get_price( $payment, $trans, $data );
		$quantity = $trans['quantity'];
		$tax      = $trans['tax'];
		$shipping = $trans['shipping'];
		$currency = $trans['currency'];
		$item_id  = $trans['product_id'];

		if ( $trans['custom_quantity'] ) {
			// custom quantity enabled. let's take quantity from PayPal results.
			$quantity = isset( $data['quantity'] ) ? $data['quantity'] : '';
		}

		if ( isset( $trans['shipping_per_quantity'] ) && ! empty( $trans['shipping_per_quantity'] ) ) {
			$product_args['quantity']              = $quantity;
			$product_args['shipping']              = $trans['shipping'];
			$product_args['shipping_per_quantity'] = $trans['shipping_per_quantity'];
			$shipping = Utils::get_total_shipping_cost( $product_args ); // Get the total shipping cost including per quantity shipping cost.
		}

		try {
			$order   = Orders::create();
			$product = Products::retrieve( $item_id );
		} catch ( \Exception $exc ) {
			$this->send_error( $exc->getMessage(), $exc->getCode() );
		}

		$order->set_payment_gateway( 'manual_checkout' );
		/* translators: Order title: {Quantity} {Item name} - {Status} */
		$order->set_description( sprintf( __( '%1$d %2$s - %3$s', 'wp-express-checkout' ), $quantity, $item_name, $this->transaction_status ) );
		$order->set_currency( $currency );
		$order->set_resource_id( $this->transaction_id );
		$order->set_capture_id( $this->capture_id );
		$order->set_author_email( $payment['payer']['email_address'] );

		$product_item_meta = array(
			'product_type' => $product->get_type(),
		);
		$order->add_item( Products::$products_slug, $item_name, $price, $quantity, $item_id, true, $product_item_meta );
		$order->add_data( 'state', $this->transaction_status );
		$order->add_data( 'payer', $payment['payer'] );

		if ( $trans['shipping_enable'] || $trans['shipping'] ) {
			$order->add_data( 'shipping_address', $this->get_address( $payment ) );
		}

		/**
		 * Runs after draft order created, but before adding items.
		 *
		 * @param Order $order   The order object.
		 * @param array $payment The raw order data retrieved via API.
		 * @param array $data    The purchase data generated on a client side.
		 */
		do_action( 'wpec_create_order', $order, $payment, $data );

		if ( $tax ) {
			$item_tax_amount = $this->get_item_tax_amount( $order->get_total(), $quantity, $tax );
			$order->add_item( 'tax', __( 'Tax', 'wp-express-checkout' ), $item_tax_amount * $quantity );
		}
		if ( $shipping ) {
			$order->add_item( 'shipping', __( 'Shipping', 'wp-express-checkout' ), $shipping );
		}

		$total = isset( $data['total'] ) ? $data['total'] : '';
		$amount = Utils::round_price( floatval( $total ) );
		// check if amount paid is less than original price x quantity. This has better fault tolerant than checking for equal (=).
		if ( $amount < $order->get_total() ) {
			// payment amount mismatch. Amount paid is less.
			Logger::log( 'Error! Payment amount mismatch. Expected: ' . $order->get_total() . ', Received: ' . $amount, false );
			$this->send_error( __( 'Payment amount mismatch with the original price.', 'wp-express-checkout' ), 3005 );
		}

		$posted_currency = isset( $data['currency'] ) ? $data['currency'] : '';
		// check if payment currency matches.
		if ( $posted_currency !== $currency ) {
			// payment currency mismatch.
			$this->send_error( __( 'Payment currency mismatch.', 'wp-express-checkout' ), 3006 );
		}

		// stock control.
		if ( $product->is_stock_control_enabled() && $product->get_stock_items() < $quantity ) {
			$this->send_error( __( 'There are not enough product items in stock.', 'wp-express-checkout' ), 3009 );
		}

		// If code execution got this far, it means everything is ok with payment
		// let's insert order.
		$order->set_status( 'pending' );

		$product->update_stock_items( $quantity );

		$order_id = $order->get_id();

		$order->generate_search_index();

		// Send buyer instruction and seller notification emails
		$this->send_manual_checkout_emails( $payment, $order_id, $item_id );

		// Trigger the action hook.
		do_action( 'wpec_manual_checkout_completed', $payment, $order_id, $item_id );
		Logger::log( 'Manual checkout processing completed' );

		$res = array();

		$thank_you_url = $trans['thank_you_url'];

		if ( wp_http_validate_url( $thank_you_url ) ) {
			$redirect_url        = add_query_arg(
				array(
					'order_id' => $order_id,
					'_wpnonce' => wp_create_nonce( 'thank_you_url' . $order_id ),
				),
				$thank_you_url
			);
			$res['redirect_url'] = esc_url_raw( $redirect_url );
		} else {
			$this->send_error( __( 'Error! Thank you page URL configuration is wrong in the plugin settings.', 'wp-express-checkout' ), 3007 );
		}

		$this->send_response( $res );
	}

	protected function get_payment_data() {
		$payment = array_merge( parent::get_payment_data(), array(
			'id'          => $this->transaction_id,
			'intent'      => 'CAPTURE',
			'status'      => $this->transaction_status,
			'create_time' => current_time( 'mysql' ),
			'update_time' => current_time( 'mysql' ),
		) );

		return $payment;
	}

	public function get_item_name( $payment ) {
		return isset( $this->order_data['name'] ) ? $this->order_data['name'] : '';
	}

	/**
	 * Retrieves item price from transaction data.
	 */
	protected function get_price( $payment, $trans, $data = array() ) {
		$product_id   = $data['product_id'];
		$product_type = get_post_meta( $product_id, 'wpec_product_type', true );
		if ( $product_type == 'subscription' ) {
			return isset( $this->order_data['price'] ) ? $this->order_data['price'] : '';
		}

		return parent::get_price( $payment, $trans, $data );
	}

	public function send_manual_checkout_emails( $payment, $order_id, $item_id ) {
		try {
			$order = Orders::retrieve( $order_id );
		} catch ( \Exception $e ) {
			Logger::log( 'Filed to retrieve the order of order id: ' . $order_id, false );
			Logger::log( $e->getMessage(), false );
		}

		Emails::send_manual_checkout_buyer_instruction_email( $order );
		Emails::send_manual_checkout_seller_notification_email( $order );
	}
}
