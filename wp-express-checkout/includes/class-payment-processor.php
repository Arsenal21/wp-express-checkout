<?php
/**
 * This class is used to process the payment after successful charge.
 *
 * Inserts the payment date to the orders menu
 * Sends notification emails.
 * Triggers after payment processed hook: wpec_payment_completed
 * Sends to Thank You page.
 */

namespace WP_Express_Checkout;

use Exception;
use WP_Express_Checkout\Debug\Logger;

/**
 * Process IPN class
 */
class Payment_Processor {

	/**
	 * The class instance.
	 *
	 * @var Payment_Processor
	 */
	protected static $instance = null;

	/**
	 * Construct the instance.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wpec_process_payment', array( $this, 'wpec_process_payment' ) );
		add_action( 'wp_ajax_nopriv_wpec_process_payment', array( $this, 'wpec_process_payment' ) );
	}

	/**
	 * Retrieves the instance.
	 *
	 * @return Payment_Processor
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
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
		$item_name  = $this->get_item_name( $payment );
		$trans      = $this->get_transient_data( $payment );
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

		$wpec_plugin = Main::get_instance();

		if ( $trans['custom_quantity'] ) {
			// custom quantity enabled. let's take quantity from PayPal results.
			$quantity = $this->get_quantity( $payment );
		}

		try {
			$order = Orders::create();
		} catch ( Exception $exc ) {
			$this->send_error( $exc->getMessage(), $exc->getCode() );
		}

		/* translators: Order title: {Quantity} {Item name} - {Status} */
		$order->set_description( sprintf( __( '%1$d %2$s - %3$s', 'wp-express-checkout' ), $quantity, $item_name, $this->get_transaction_status( $payment ) ) );
		$order->set_currency( $currency );
		$order->set_resource_id( $this->get_transaction_id( $payment ) );
		$order->set_author_email( $payment['payer']['email_address'] );
		$order->add_item( Products::$products_slug, $item_name, $price, $quantity, $item_id, true );
		$order->add_data( 'state', $this->get_transaction_status( $payment ) );
		$order->add_data( 'payer', $payment['payer'] );

		if ( $trans['shipping_enable'] ) {
			$order->add_data( 'shipping_address', $this->get_address( $payment ) );
		}

		/**
		 * Runs after draft order created, but before adding items.
		 *
		 * @param Order $order   The order object.
		 * @param array      $payment The raw order data retrieved via API.
		 * @param array      $data    The purchase data generated on a client side.
		 */
		do_action( 'wpec_create_order', $order, $payment, $data );

		if ( $tax ) {
			$item_tax_amount = $this->get_item_tax_amount( $order->get_total(), $quantity, $tax );
			$order->add_item( 'tax', __( 'Tax', 'wp-express-checkout' ), $item_tax_amount * $quantity );
		}
		if ( $shipping ) {
			$order->add_item( 'shipping', __( 'Shipping', 'wp-express-checkout' ), $shipping );
		}

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
		$order->set_status( 'paid' );

		$order_id  = $order->get_id();

		$order->generate_search_index();

		// Send email to buyer if enabled.
		Emails::send_buyer_email( $order );

		// Send email to seller if needs.
		Emails::send_seller_email( $order );

		// Trigger the action hook.
		do_action( 'wpec_payment_completed', $payment, $order_id, $item_id );
		Logger::log( 'Payment processing completed' );

		$res = array();

		$thank_you_url = $trans['thank_you_url'];

		if ( wp_http_validate_url( $thank_you_url ) ) {
			$redirect_url = add_query_arg(
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
	} // @codeCoverageIgnore

	/**
	 * Logs error message and sends it as a JSON response back to an Ajax request.
	 *
	 * @param string $msg  Message to encode as JSON, then print and die.
	 * @param string $code Error code for recognizing an error.
	 */
	protected function send_error( $msg, $code ) {
		Logger::log( "Code $code - $msg", false );
		$this->send_response( $msg );
	} // @codeCoverageIgnore

	/**
	 * Logs error message and sends it as a JSON response back to an Ajax request.
	 *
	 * @param mixed $data Variable (usually an array or object) to encode as
	 *                    JSON, then print and die.
	 */
	protected function send_response( $data ) {
		wp_send_json( $data );
	} // @codeCoverageIgnore

	/**
	 * Retrieves the payment data from AJAX POST request
	 *
	 * @return array
	 */
	protected function get_payment_data() {
		$payment = isset( $_POST['wp_ppdg_payment'] ) ? stripslashes_deep( $_POST['wp_ppdg_payment'] ) : array();
		return $payment;
	}

	/**
	 * Retrieves the order data from AJAX POST request
	 *
	 * @return array
	 */
	protected function get_order_data() {
		$data = isset( $_POST['data'] ) ? stripslashes_deep( $_POST['data'] ) : array();
		return $data;
	}

	/**
	 * Filters the custom amount option.
	 *
	 * @param type $true
	 * @return bool
	 */
	public function is_custom_amount( $true ) {
		return !! $true;
	}

	/**
	 * Checks the payment status before processing.
	 *
	 * @param array $payment
	 */
	protected function check_status( $payment ) {
		$status = $this->get_transaction_status( $payment );
		if ( strtoupper( $status ) !== 'COMPLETED' ) {
			// payment is unsuccessful.
			$this->send_error( sprintf( __( 'Payment is not approved. Status: %s', 'wp-express-checkout' ), $status ), 3008 );
		}
	}

	/**
	 * Retrieves the item name from transaction data.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_item_name( $payment ) {
		return $payment['purchase_units'][0]['description'];
	}

	/**
	 * Retrieves transition name.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_transition_name( $payment ) {
		$item_name  = $this->get_item_name( $payment );
		$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $item_name );

		return $trans_name;
	}

	/**
	 * Retrieves transition data.
	 *
	 * @param array $payment
	 * @return array
	 */
	protected function get_transient_data( $payment ) {
		return get_transient( $this->get_transition_name( $payment ) );
	}

	/**
	 * Retrieves peoduct queantity from transaction data.
	 *
	 * @param array $payment
	 * @return int
	 */
	protected function get_quantity( $payment ) {
		return $payment['purchase_units'][0]['items'][0]['quantity'];
	}

	/**
	 * Retrieves item price from transaction data.
	 *
	 * @param array $payment
	 * @param array $trans
	 *
	 * @return string
	 */
	protected function get_price( $payment, $trans, $data = array() ) {
		$price = $trans['price'];
		if ( $this->is_custom_amount( $trans['custom_amount'] ) ) {
			// custom amount enabled. let's take amount from JS data, but not
			// less then allowed.
			$product = get_post( $trans['product_id'] );
			$price   = max( $product->wpec_product_min_amount, $data['orig_price'] );
		}
		return $price;
	}

	/**
	 * Retrieves order total from transaction data.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_total( $payment ) {
		return $payment['purchase_units'][0]['amount']['value'];
	}

	/**
	 * Retrieves currency from transaction data.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_currency( $payment ) {
		return $payment['purchase_units'][0]['amount']['currency_code'];
	}

	/**
	 * Retrieves payer address from transaction data.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_address( $payment ) {
		$address = '';
		if ( ! empty( $payment['purchase_units'][0]['shipping']['address'] ) ) {
			$address = implode( ', ', (array) $payment['purchase_units'][0]['shipping']['address'] );
		}
		return $address;
	}

	/**
	 * Retrieves transaction id.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_transaction_id( $payment ) {
		return $payment['id'];
	}

	/**
	 * Retrieves transaction status.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_transaction_status( $payment ) {
		return $payment['status'];
	}

	/**
	 * Retrieves the tax amount depending on the way the PayPal calcualtes it.
	 *
	 * For regular instant payments PayPal calculates it from the one item and
	 * then rounds, for subscriptions it calculates for a quantity and then rounds.
	 *
	 * This difference in approach creates a lot of difficulties in the total
	 * amount validation. This method allows override it.
	 *
	 * @param type $price
	 * @param type $quantity
	 * @param type $tax
	 * @return type
	 */
	protected function get_item_tax_amount( $price, $quantity, $tax ) {
		return Utils::round_price( Utils::get_tax_amount( $price / $quantity, $tax ) );
	}


}
