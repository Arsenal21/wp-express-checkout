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
class Payment_Processor_Free extends Payment_Processor {

	private $order_data;

	/**
	 * Construct the instance.
	 */
	public function __construct() {

		parent::__construct();

		add_action( 'wp_ajax_wpec_process_empty_payment', array( $this, 'wpec_process_payment' ) );
		add_action( 'wp_ajax_nopriv_wpec_process_empty_payment', array( $this, 'wpec_process_payment' ) );
	}

	/**
	 * Processes the payment on AJAX call.
	 */
	public function wpec_process_payment() {
		$this->order_data = $this->get_order_data();

		add_action('wpec_process_payment', array( $this, 'prevalidate_free_checkout' ), 10, 2 );

		parent::wpec_process_payment();
	} // @codeCoverageIgnore

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
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_item_name( $payment ) {
		return $this->order_data['name'];
	}

	/**
	 * Retrieves peoduct queantity from transaction data.
	 *
	 * @param array $payment
	 * @return int
	 */
	protected function get_quantity( $payment ) {
		return $this->order_data['quantity'];
	}

	/**
	 * Retrieves order total from transaction data.
	 *
	 * This handler exists only to process genuinely zero-total orders (e.g. a
	 * 100%-off coupon). The "amount" it reports must never be trusted from the
	 * client, otherwise an unauthenticated user could submit any total and have
	 * an order marked as paid without any payment taking place. Instead, we
	 * always report 0 here so that the base class's amount-mismatch check
	 * (comparing this value against the server-computed $order->get_total(),
	 * which is derived from the trusted transient price plus a server-verified
	 * coupon discount, tax and shipping) rejects the request unless the real
	 * order total is actually zero.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_total( $payment ) {
		return 0;
	}

	/**
	 * Retrieves currency from transaction data.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_currency( $payment ) {
		return $this->order_data['currency'];
	}

	/**
	 * Retrieves transaction id.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_transaction_id( $payment ) {
		return substr( sha1( time() . mt_rand( 0, 1000 ) ), 0, 20 );
	}

	/**
	 * Retrieves capture id.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_capture_id( $payment ) {
		return substr( sha1( time() . mt_rand( 0, 1000 ) ), 0, 20 );
	}

	/**
	 * Retrieves transaction status.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_transaction_status( $payment ) {
		return 'COMPLETED';
	}

	/**
	 * Validate the zero-total checkout before the parent flow creates an order.
	 *
	 * This keeps invalid requests from persisting an incomplete order post.
	 *
	 * @param array $payment
	 * @param array $data
	 * @return void
	 */
	public function prevalidate_free_checkout($payment, $data){
		$trans = $this->get_transient_data( $payment );

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
			$quantity = $this->get_quantity( $payment );
			if (empty($quantity)) {
				$this->send_error( __( 'Quantity is missing in the payment data.', 'wp-express-checkout' ), 3005 );
			}
		}
		
		if (isset($trans['shipping_per_quantity']) && !empty($trans['shipping_per_quantity'])) {
			// $product_args = array(...$trans);
			$product_args['quantity'] = $quantity;
			$product_args['shipping'] = $trans['shipping'];
			$product_args['shipping_per_quantity'] = $trans['shipping_per_quantity'];
			$shipping = Utils::get_total_shipping_cost( $product_args ); // Get the total shipping cost including per quantity shipping cost.
		}

		try {
			$product = Products::retrieve( $item_id );
		} catch ( \Exception $exc ) {
			$this->send_error( $exc->getMessage(), $exc->getCode() );
		}

		// check if payment currency matches.
		if ( $this->get_currency( $payment ) !== $currency ) {
			// payment currency mismatch.
			$this->send_error( __( 'Payment currency mismatch.', 'wp-express-checkout' ), 3006 );
		}

		// stock control.
		if ( $product->is_stock_control_enabled() && $product->get_stock_items() < $quantity ) {
			$this->send_error( __( 'There are not enough product items in stock.', 'wp-express-checkout' ), 3009 );
		}

		$expected_total = $this->calculate_free_checkout_total( $price, $quantity, $tax, $shipping, $item_id, $data );

		// Check if expected total amount is not zero. Expected amount must be zero for free/100% discount checkout.
		if ( !empty($expected_total) ) {
			Logger::log( 'Error! Payment amount mismatch. Expected: ' . $expected_total . ', Received: 0', false );
			$this->send_error( __( 'Payment amount mismatch with the original price.', 'wp-express-checkout' ), 3005 );
		}

	}

	/**
	 * Calculate the expected total for a free checkout before order creation.
	 *
	 * @param float  $price
	 * @param int    $quantity
	 * @param float  $tax
	 * @param float  $shipping
	 * @param int    $item_id
	 * @param array  $data
	 * @return float
	 */
	protected function calculate_free_checkout_total( $price, $quantity, $tax, $shipping, $item_id, $data ) {
		$quantity = max( 1, (int) $quantity );
		$subtotal = floatval( $price ) * $quantity;
		$subtotal = $subtotal + $this->get_variation_total( $item_id, $data, $quantity );
		$subtotal = $subtotal - $this->get_coupon_discount( $subtotal, $data, $item_id );
		$subtotal = max( $subtotal, 0 );
		$tax_total = 0;
		if ( $tax ) {
			$item_tax_amount = $this->get_item_tax_amount( $subtotal, $quantity, $tax );
			$tax_total       = $item_tax_amount * $quantity;
		}
		$shipping_total = $shipping ? floatval( $shipping ) : 0;
		return Utils::round_price( $subtotal + $tax_total + $shipping_total );
	}

	/**
	 * Calculate selected variation price total.
	 *
	 * @param int   $item_id
	 * @param array $data
	 * @param int   $quantity
	 * @return float
	 */
	protected function get_variation_total( $item_id, $data, $quantity ) {
		$variation_price_total = 0;

		$price_variations_applied = isset( $data['variations']['applied'] ) ? $data['variations']['applied'] : array();
		if ( empty( $price_variations_applied ) ) {
			return 0;
		}

		$variations = ( new Variations( $item_id ) )->variations;
		if ( ! is_array( $variations ) || empty( $variations ) ) {
			return 0;
		}

		foreach ( $variations as $index => $variation ) {
			$applied_var_index = isset( $price_variations_applied[ $index ] ) ? (int) $price_variations_applied[ $index ] : -1;
			if ( $applied_var_index < 0 || empty( $variation['prices'][ $applied_var_index ] ) ) {
				continue;
			}

			$variation_price_total += $variation['prices'][ $applied_var_index ];
		}

		return Utils::round_price( $variation_price_total * max( 1, (int) $quantity ) );
	}

	/**
	 * Calculate the coupon discount that would be applied to the subtotal.
	 *
	 * @param float $subtotal
	 * @param array $data
	 * @param int   $item_id
	 * @return float
	 */
	protected function get_coupon_discount( $subtotal, $data, $item_id ) {
		if ( empty( $data['couponCode'] ) ) {
			return 0;
		}

		$coupon = Coupons::get_coupon( $data['couponCode'] );
		if ( ! $coupon || ! $coupon['valid'] || ! Coupons::is_coupon_allowed_for_product( $coupon['id'], $item_id ) ) {
			return 0;
		}

		if ( $coupon['discountType'] === 'perc' ) {
			return Utils::round_price( $subtotal * ( $coupon['discount'] / 100 ) );
		}

		return Utils::round_price( abs( $coupon['discount'] ) );
	}
}
