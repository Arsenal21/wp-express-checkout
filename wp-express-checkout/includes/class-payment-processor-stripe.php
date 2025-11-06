<?php

namespace WP_Express_Checkout;

use Stripe\Charge;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use WP_Express_Checkout\Debug\Logger;

/**
 * Process Stripe Checkout class
 */
class Payment_Processor_Stripe {
	/**
	 * @var array The data came from initial ajax request.
	 */
	private $wpec_data = array();

	/**
	 * @var string Redirect URL after order creation.
	 */
	private string $thank_you_url = '';

	public function __construct() {
		add_action( 'init', array( $this, 'process_stripe_payment' ), 999 ); // Lower priority is important
	}

	public function process_stripe_payment() {
		if ( ! isset( $_REQUEST["wpec_process_stripe_ipn"] ) ) {
			return;
		}

		$ref_id = isset( $_GET["ref_id"] ) ? $_GET["ref_id"] : 0;

		$session_id = $ref_id;

		$secret_key = Utils::get_stripe_secret_key();
		
		try {
			Stripe::setApiKey( $secret_key );

			$order_id = $this->process_checkout_session_and_create_order( $session_id );

		} catch ( \Exception $e ) {
			Logger::log( 'Stripe payment data could not be processed.', false );
			wp_die( $e->getMessage(), false );
		}

		//Everything passed. Redirecting user to thank you page.
		$thank_you_url = $this->thank_you_url;

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

		$checkout_session_trans = get_transient( 'wpec_checkout_session_' . $session_id );

		//		Logger::log( 'wpec_checkout_session_' . $session_id . ' transient data' );
		//		Logger::log_array_data( $checkout_session_trans );

		if ( is_array( $checkout_session_trans ) && isset( $checkout_session_trans['wpec_data'] ) ) {
			$this->wpec_data     = $checkout_session_trans['wpec_data'];
			$this->thank_you_url = $checkout_session_trans['thank_you_url'];
		}

		// Make sure fulfillment hasn't already been performed for this Checkout Session
		if ( $this->check_if_checkout_session_processed( $session_id ) ) {
			wp_die( __( 'The order has captured already!', 'wp-express-checkout' ) );
		}

		try {
			// Retrieve the Checkout Session from the API with line_items expanded
			$sess = Session::retrieve( $session_id );

			// Check the Checkout Session's payment_status property
			// to determine if fulfillment should be performed
			if ( $sess->payment_status != 'paid' ) {
				wp_die( __( "The payment for this order hasn't been paid already", 'wp-express-checkout' ) );
			}

			$lineItems = Session::allLineItems( $session_id, array(
				'limit' => 1, // We check out one product only per transactions.
			) );

			$sess->line_items = $lineItems;

			if ( $sess === false ) {
				// Can't find session.
				$error_msg = sprintf( "Error! Payment with ref_id %s (Stripe Session ID) can't be found. This script should be accessed by Stripe's webhook only.", $session_id );
				Logger::log( $error_msg, false );
				wp_die( esc_html( $error_msg ) );
			}

			//ref_id matched
			$pi_id = $sess->payment_intent;

			$pi = PaymentIntent::retrieve( $pi_id );

			$charge = null;

			//Get the charge object based on the Stripe API version used in the payment intents object.
			if ( isset ( $pi->latest_charge ) ) {
				//Using the new Stripe API version 2022-11-15 or later
				Logger::log( 'Using the Stripe API version 2022-11-15 or later for Payment Intents object. Need to retrieve the charge object.', true );
				$charge_id = $pi->latest_charge;
				//For Stripe API version 2022-11-15 or later, the charge object is not included in the payment intents object. It needs to be retrieved using the charge ID.
				//Retrieve the charge object using the charge ID
				$charge = Charge::retrieve( $charge_id );
			} else {
				//Using OLD Stripe API version. Log an error and exit.
				$error_msg = 'Error! You are using the OLD Stripe API version. This version is not supported. Please update the Stripe API version to 2022-11-15 or later from your Stripe account.';
				Logger::log( $error_msg, false );
				wp_die( $error_msg );
			}

		} catch ( ApiErrorException $e ) {
			throw new \Exception( $e->getMessage() );
		}

		//formatting ipn_data
		return $this->create_order( $sess, $pi, $charge );
	}

	public function create_order( $session_object, $pi_object, $charge_object ) {

		//converting the payment intent object to array
		$pi_array = json_decode( json_encode( $pi_object ), true );

		$session_id                = $session_object->id;
		$transaction_status        = $session_object->status;
		$payment_status            = $session_object->payment_status;
		$checkout_session_metadata = $session_object->metadata;

		$product_id = $this->wpec_data['product_id'];


		//Conver the charge object to array
		$charge_array = json_decode( json_encode( $charge_object ), true );

		// TODO: Remove this, for debug purpose only.
		//Logger::log( 'Pi data array', true );
		//Logger::log_array_data( $pi_array, true );
		//Logger::log( 'Charge data array', true );
		//Logger::log_array_data( $charge_array, true );

		$charge_id = $charge_array['id'];

		/**
		 * Retrieve Customer info from the charge object.
		 */
		$stripe_email = isset( $charge_array['billing_details']['email'] ) ? $charge_array['billing_details']['email'] : '';
		$phone        = isset( $charge_array['billing_details']['phone'] ) ? $charge_array['billing_details']['phone'] : '';
		$name         = isset( $charge_array['billing_details']['name'] ) ? trim( $charge_array['billing_details']['name'] ) : '';
		$last_name    = ( strpos( $name, ' ' ) === false ) ? '' : preg_replace( '#.*\s([\w-]*)$#', '$1', $name );
		$first_name   = trim( preg_replace( '#' . $last_name . '#', '', $name ) );

		// Construct payer info array similar to PayPal
		$payer = array(
			'name'          => array(
				'given_name' => $first_name,
				'surname'    => $last_name,
			),
			'email_address' => $stripe_email,
			'phone'         => $phone,
		);

		$billing_address  = $this->get_billing_address_str( $charge_array );
		$shipping_address = $this->get_shipping_address_str( $pi_array );

		// $amount_received_in_cents = floatval( $pi_array['amount_received'] );
		$currency = isset($pi_array['currency']) ? strtoupper( $pi_array['currency'] ) : '';

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

		if ( strtolower( $product->get_type() ) == 'donation' ) {
			$price = $unit_amount;
		} else {
			$price = $product->get_price();
		}

		$order->set_description( sprintf( __( '%1$d %2$s - %3$s', 'wp-express-checkout' ), $quantity, $item_name, $transaction_status ) );
		$order->set_currency( $currency );
		$order->set_resource_id( $resource_id );
		$order->set_capture_id( $txn_id );
		$order->set_author_email( $stripe_email );
		$order->add_item( Products::$products_slug, $item_name, $price, $quantity, $product_id, true );
		$order->add_data( 'state', $transaction_status );

		$order->add_data( 'payer', $payer );
		$order->add_data( 'billing_address', $billing_address );
		$order->add_data( 'shipping_address', $shipping_address );

		/**
		 * Runs after draft order created, but before adding items.
		 *
		 * @param Order $order     The order object.
		 * @param array $payment   The raw order data retrieved via API. TODO: Currently expects the structure like paypal, need to adjusted for stripe.
		 * @param array $wpec_data The purchase data generated on a client side.
		 */
		do_action( 'wpec_create_order', $order, $payment = array(), $this->wpec_data );

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
		 * TODO: Need fix the $payment var
		 *
		 * This is used for the followings:
		 * redeem_coupon
		 * set_download_limits
		 * handle_signup (wpemember)
		 *
		 * @param array $payment  The raw order data retrieved via API. TODO: Currently expects the structure like paypal, need to adjusted for stripe.
		 * @param int $order_id   The order id.
		 * @param int $product_id The purchased product id.
		 */
		do_action( 'wpec_payment_completed', $payment = array(), $order_id, $product_id );
		Logger::log( 'Payment processing completed' );

		return $order_id;
	}

	public function get_shipping_address_str( $pi_data ) {
		if ( ! isset( $pi_data['shipping']['address'] ) ) {
			return '';
		}

		$shipping_addr = isset( $pi_data['shipping']['address'] ) ? $pi_data['shipping']['address'] : array();

		$address_array = self::process_address_array( $shipping_addr );

		return implode( ', ', $address_array );
	}

	public function get_billing_address_str( $charge_array ) {
		if ( ! isset( $charge_array['billing_details']['address'] ) ) {
			return '';
		}

		$bd_addr = isset( $charge_array['billing_details']['address'] ) ? $charge_array['billing_details']['address'] : array();

		$address_array = self::process_address_array( $bd_addr );

		return implode( ', ', $address_array );
	}

	public static function process_address_array( $address ) {
		$city        = isset( $address['city'] ) ? $address['city'] : '';
		$state       = isset( $address['state'] ) ? $address['state'] : '';
		$postal_code = isset( $address['postal_code'] ) ? $address['postal_code'] : '';
		$country     = isset( $address['country'] ) ? Utils::get_country_name_by_country_code( $address['country'] ) : '';
		$line1       = isset( $address['line1'] ) ? $address['line1'] : '';
		$line2       = isset( $address['line2'] ) ? $address['line2'] : '';

		return array_filter( array(
			$line1,
			$line2,
			$city,
			$state,
			$postal_code,
			$country
		) );
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


	public function check_if_checkout_session_processed( $session_id ) {
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
