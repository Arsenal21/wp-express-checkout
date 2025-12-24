<?php

namespace WP_Express_Checkout;

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use WP_Express_Checkout\Debug\Logger;

/**
 * Process Stripe Checkout class
 */
class Payment_Processor_Stripe {

	/**
	 * @var array Shortcode transient data.
	 */
	private $trans = array();

	/**
	 * @var array The data came from initial ajax request.
	 */
	private $wpec_data = array();

	private StripeClient $stripe_client;

	public function __construct() {
		add_action( 'init', array( $this, 'process_stripe_payment' ), 999 ); // Lower priority is important
	}

	public function process_stripe_payment() {
		if ( ! isset( $_REQUEST["wpec_process_stripe_ipn"] ) ) {
			return;
		}

		$this->stripe_client = Utils::get_stripe_client();

		$ref_id = isset( $_GET["ref_id"] ) ? $_GET["ref_id"] : 0;

		$session_id = $ref_id;

		// Make sure fulfillment hasn't already been performed for this Checkout Session
		if ( self::check_if_checkout_session_processed( $session_id ) ) {
			wp_die( __( 'The order has captured already!', 'wp-express-checkout' ) );
		}

		$this->trans = get_transient( 'wpec_checkout_session_' . $session_id );

		do_action('wpec_process_stripe_payment', $session_id, $this->trans);

		if ( is_array( $this->trans ) && isset( $this->trans['wpec_data'] ) ) {
			$this->wpec_data = $this->trans['wpec_data'];
		}

		try {
			$order_id = $this->process_checkout_session_and_create_order( $session_id );
		} catch ( \Exception $e ) {
			Logger::log( $e->getMessage() , false );
			wp_die( __('Error: Stripe payment data could not be processed!' , 'wp-express-checkout') );
		}

		//Everything passed. Redirecting user to thank you page.
		$thank_you_url = isset($this->trans['thank_you_url']) ? $this->trans['thank_you_url'] : '';

		if ( wp_http_validate_url( $thank_you_url ) ) {
			$thank_you_url = add_query_arg(
				array(
					'order_id' => $order_id,
					'_wpnonce' => wp_create_nonce( 'thank_you_url' . $order_id ),
				),
				$thank_you_url
			);
			Logger::log( 'Redirecting to:' . $thank_you_url );
			Utils::redirect_to_url( $thank_you_url );
		} else {
			Logger::log( 'Error! Thank you page URL configuration is wrong in the plugin settings.', false );
			Logger::log( 'Redirecting to home page.' );
			Utils::redirect_to_url( site_url() );
		}
	}

	public function process_checkout_session_and_create_order( $session_id ) {
		Logger::log( 'Processing Stripe Checkout Session.' );

		try {
			$expand_items = apply_filters('wpec_stripe_session_obj_expand_items', array(
				'payment_intent',
				'payment_intent.latest_charge',
				'line_items',
			));

			// Retrieve the Checkout Session from the API with line_items expanded
			$sess = $this->stripe_client->checkout->sessions->retrieve(
				$session_id,
				array(
					'expand' => $expand_items,
				)
			);

			if ( empty($sess) ) {
				// Can't find session.
				$error_msg = sprintf( "Error! Payment with ref_id %s (Stripe Session ID) can't be found. This script should be accessed by Stripe's webhook only.", $session_id );
				Logger::log( $error_msg, false );
				wp_die( esc_html( $error_msg ) );
			}

			// Check the Checkout Session's payment_status property
			// to determine if fulfillment should be performed
			if ( $sess->payment_status != 'paid' ) {
				wp_die( __( "The payment for this order hasn't been paid already", 'wp-express-checkout' ) );
			}

		} catch ( ApiErrorException $e ) {
			throw new \Exception( $e->getMessage() );
		}

		// process and save order details form ipn_data.
		return $this->create_order( $sess );
	}

	public function create_order( $session_object ) {
		$session_id                = $session_object->id;
		$transaction_status        = $session_object->status;
		$payment_status            = $session_object->payment_status;
		$currency = $session_object->currency;
		
		$currency = strtoupper($currency);

		$product_id = $this->wpec_data['product_id'];

		$pi_array = array();
		$charge_array = array();
		$pi = $session_object->payment_intent;
		if (!empty( $pi )){
			//converting the payment intent object to array
			$pi_array = json_decode( json_encode( $pi ), true );
			$charge_array = isset($pi_array['latest_charge']) ? $pi_array['latest_charge'] : array();
		}

		$charge_id = isset($charge_array['id']) ? $charge_array['id'] : '';

		/**
		 * Retrieve Customer info.
		 */
		$customer_details = array();
		if ( !empty($session_object->customer_details) ){
			$customer_details = json_decode(json_encode($session_object->customer_details), true);
		}

		$stripe_email = isset($customer_details['email']) ? $customer_details['email'] : '';
		$name = isset($customer_details['name']) ? $customer_details['name'] : '';
		$phone = isset($customer_details['phone']) ? $customer_details['phone'] : '';

		$last_name    = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
		$first_name   = trim(preg_replace('#' . $last_name . '#', '', $name));

		// Construct payer info array similar to PayPal
		$payer = array(
			'name'          => array(
				'given_name' => $first_name,
				'surname'    => $last_name,
			),
			'email_address' => $stripe_email,
			'phone'         => $phone,
		);

		$billing_address  = $this->get_billing_address_str( $customer_details );
		$shipping_address = $this->get_shipping_address_str( $pi_array );

		// $amount_received_in_cents = floatval( $pi_array['amount_received'] );

		// $total_received_amount = Utils::amount_from_cents( $amount_received_in_cents, $currency );
		// $create_time = $pi_array['created'];

		$total_shipping = 0;
		if ( isset( $session_object['total_details']['amount_shipping'] ) ) {
			$total_shipping_in_cents = $session_object['total_details']['amount_shipping'];
			$total_shipping          = Utils::amount_from_cents( $total_shipping_in_cents, $currency );
		}

		$total_tax = 0;
		if ( isset( $session_object['total_details']['amount_tax'] ) ) {
			$total_tax_in_cents = $session_object['total_details']['amount_tax'];
			$total_tax          = Utils::amount_from_cents( $total_tax_in_cents, $currency );
		}

		$line_item = isset( $session_object->line_items->data[0] ) ? $session_object->line_items->data[0] : null;

		$quantity             = $line_item['quantity'];
		$unit_amount_in_cents = $line_item['price']['unit_amount'];
		$unit_amount          = Utils::amount_from_cents( $unit_amount_in_cents, $currency );

		try {
			$order   = Orders::create();
			$product = Products::retrieve( $product_id );
		} catch ( \Exception $e ) {
			wp_die( $e->getMessage() );
		}

		$order_id = $order->get_id();

		$item_name = $product->get_item_name();

		$txn_id      = $charge_id;
		$resource_id = $session_id;

		$price = $this->get_price($product, $this->trans, $this->wpec_data );

		$order->set_payment_gateway( 'stripe' );
		$order->set_description( sprintf( __( '%1$d %2$s - %3$s', 'wp-express-checkout' ), $quantity, $item_name, $transaction_status ) );
		$order->set_currency( $currency );
		$order->set_resource_id( $resource_id );
		$order->set_capture_id( $txn_id );
		$order->set_author_email( $stripe_email );

		$product_item_meta = array(
			'product_type' => $product->get_type(),
		);
		$order->add_item( Products::$products_slug, $item_name, $price, $quantity, $product_id, true, $product_item_meta );
		$order->add_data( 'state', $transaction_status );

		$order->add_data( 'payer', $payer );
		$order->add_data( 'billing_address', $billing_address );
		$order->add_data( 'shipping_address', $shipping_address );

		/**
		 * Runs after draft order created, but before adding items.
		 *
		 * @param Order $order     The order object.
		 * @param array $payment   The raw order data retrieved via API.
		 * @param array $wpec_data The purchase data generated on a client side.
		 */
		do_action( 'wpec_create_order', $order, $session_object, $this->wpec_data );

		if ( ! empty( $total_tax ) ) {
			$order->add_item( 'tax', __( 'Tax', 'wp-express-checkout' ), $total_tax );
		}

		if ( ! empty( $total_shipping ) ) {
			$order->add_item( 'shipping', __( 'Shipping', 'wp-express-checkout' ), $total_shipping );
		}

		$order->set_status( $payment_status );

		$product->update_stock_items( $quantity );

		$order->generate_search_index();

		// Send email to buyer if enabled.
		Emails::send_buyer_email( $order );

		// Send email to seller if needs.
		Emails::send_seller_email( $order );

		/**
		 * This is used for the followings:
		 * redeem_coupon
		 * set_download_limits
		 * handle_signup (wpemember)
		 *
		 * @param array $payment  The raw order data retrieved via API.
		 * @param int $order_id   The order id.
		 * @param int $product_id The purchased product id.
		 */
		do_action( 'wpec_payment_completed', $session_object, $order_id, $product_id );
		Logger::log( 'Payment processing completed' );

		return $order_id;
	}

	public function get_shipping_address_str( $pi_data ) {
		if ( ! isset( $pi_data['shipping']['address'] ) ) {
			return '';
		}

		$shipping_addr = isset( $pi_data['shipping']['address'] ) ? $pi_data['shipping']['address'] : array();

		return Utils::get_stripe_address_str_from_array( $shipping_addr );
	}

	public function get_billing_address_str( $customer_details ) {
		if ( ! isset( $customer_details['address'] ) ) {
			return '';
		}

		$bd_addr = isset( $customer_details['address'] ) ? $customer_details['address'] : array();

		return Utils::get_stripe_address_str_from_array( $bd_addr );
	}

	protected function get_price( $product, $trans, $data = array() ) {
		$price = $trans['price'];
		if ( !empty( $trans['custom_amount'] ) ) {
			// custom amount enabled. let's take amount from JS data, but not less than allowed.
			$price = max( $product->get_meta('wpec_product_min_amount'), $data['orig_price'] );
		}

		return apply_filters('wpec_stripe_create_order_product_price', $price, $product);
	}

	/*	public static function prepare_address_array( $address ) {
		$city         = isset( $address['city'] ) ? $address['city'] : '';
		$state        = isset( $address['state'] ) ? $address['state'] : '';
		$postal_code  = isset( $address['postal_code'] ) ? $address['postal_code'] : '';
		$country_code = isset( $address['country'] ) ? $address['country'] : '';
		// $country       = Utils::get_country_name_by_country_code( $country_code );
		$line1 = isset( $address['line1'] ) ? $address['line1'] : '';
		$line2 = isset( $address['line2'] ) ? $address['line2'] : '';

		return array(
			'address_line_1' => $line1,
			'address_line_2' => $line2,
			'admin_area_1'   => $state,
			'admin_area_2'   => $city,
			'postal_code'    => $postal_code,
			'country_code'   => $country_code,
		);
	}*/

	/*	private function get_payment_data() {
			$payment = array(
				'id'          => $this->transaction_id,
				'intent'      => 'CAPTURE',
				'status'      => $this->transaction_status,
				'create_time' => current_time( 'mysql' ),
				'update_time' => current_time( 'mysql' ),
				'address' => billing_address,
				'shipping_address' => shipping_address,
			);

			return $payment;
		}*/


	public static function check_if_checkout_session_processed( $session_id ) {
		$query = new \WP_Query( array(
			'post_type'      => 'ppdgorder',
			'posts_per_page' => 1, // Only need to know if it exists
			'meta_query'     => [
				[
					'key'   => 'wpec_order_capture_id',
					'value' => sanitize_text_field( $session_id ),
				],
			],
			'fields'         => 'ids',
		) );

		return $query->have_posts();
	}
}
