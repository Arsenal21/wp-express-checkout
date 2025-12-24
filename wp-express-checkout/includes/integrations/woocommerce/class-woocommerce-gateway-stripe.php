<?php

namespace WP_Express_Checkout\Integrations;

use Stripe\Exception\ApiErrorException;
use WC_Log_Levels;
use WC_Logger;
use WC_Order;
use WC_Payment_Gateway;
use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Main;
use WP_Express_Checkout\Payment_Processor_Stripe;
use WP_Express_Checkout\Utils;

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

class WPEC_WC_Payment_Gateway_Stripe extends WC_Payment_Gateway {

	/** @var bool Whether or not logging is enabled */
	public static $log_enabled = false;

	/** @var WC_Logger Logger instance */
	public static $log = false;

	/** @var WC_Order */
	public $wpec_wc_order = false;

	/** @var Main */
	public $wpec;

	public $notify_url;

	public function __construct() {
		$this->id                 = 'wp-express-checkout-stripe';
		$this->method_title       = __( 'WP Express Checkout - Stripe', 'wp-express-checkout' );
		$this->method_description = __( 'Use the WP Express Checkout plugin to process payments via Stripe Checkout API.', 'wp-express-checkout' );
		$this->notify_url         = WC()->api_request_url( 'wp_express_checkout' );

		$this->wpec = Main::get_instance();

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->has_fields  = false;
		$this->supports    = array( 'products' );

		self::$log_enabled = $this->wpec->get_setting( 'enable_debug_logging' );

		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );
		}
	}

	/**
	 * Add the gateway to WooCommerce
	 *
	 * @param array $methods The WC payment methods.
	 *
	 * @return array
	 */
	public static function add_wc_gateway_class( $methods ) {
		$methods[] = 'WP_Express_Checkout\Integrations\WPEC_WC_Payment_Gateway_Stripe';

		return $methods;
	}

	/**
	 * Logging method
	 *
	 * @param string $message
	 * @param string $order_id
	 */
	public static function log( $message, $level = WC_Log_Levels::NOTICE, $order_id = '' ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}
			if ( ! empty( $order_id ) ) {
				$message = 'Order: ' . $order_id . '. ' . $message;
			}

			$is_success = $level != WC_Log_Levels::ERROR;

			self::$log->add( 'wpec', $message, $level );
			Logger::log( $message, $is_success );
		}
	}

	/**
	 * Initialize gateway settings form fields
	 *
	 * @access public
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array(
				'title'    => __( 'Enable/Disable', 'wp-express-checkout' ),
				'type'     => 'checkbox',
				'label'    => __( 'Enable WP Express Checkout gateway', 'wp-express-checkout' ),
				'default'  => 'false',
				'desc_tip' => true,
			),
			'title'       => array(
				'title'       => __( 'Title', 'wp-express-checkout' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wp-express-checkout' ),
				'default'     => __( 'Stripe', 'wp-express-checkout' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'wp-express-checkout' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the description which the user sees during checkout.', 'wp-express-checkout' ),
				'default'     => __( 'Pay by Stripe Checkout Session.', 'wp-express-checkout' ),
			),
			'popup_title' => array(
				'title'       => __( 'Checkout Popup Title', 'wp-express-checkout' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the popup window title which the user sees during checkout.', 'wp-express-checkout' ),
				'default'     => __( 'Stripe Express Checkout', 'wp-express-checkout' ),
			),
		);
	}

	/**
	 * Send payment request to gateway
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order               = new WC_Order( $order_id );
		$this->wpec_wc_order = $order;

		try {
			$session = $this->create_stripe_checkout_session( $order );

			return [
				'result'   => 'success',
				'redirect' => $session->url,
			];

		} catch ( \Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );

			return [
				'result' => 'failure',
			];
		}
	}

	public function create_stripe_checkout_session( \WC_Order $order ) {
		$stripe = Utils::get_stripe_client();

		try {
			$line_items  = $this->get_stripe_line_items( $order );
			$success_url = $order->get_checkout_order_received_url();
			$success_url = add_query_arg( array(
				'wc_wpec_stripe_ipn' => 1,
				'csid'               => '{CHECKOUT_SESSION_ID}'
			), $success_url );

			$opts = array(
				'mode'                => 'payment',
				'customer_email'      => $order->get_billing_email(),
				'line_items'          => $line_items,
				'client_reference_id' => $order->get_id(),
				'success_url'         => $success_url,
				'cancel_url'          => wc_get_checkout_url(),
				'metadata'            => [
					'order_id' => $order->get_id(),
				],
			);

			// Add shipping options
			if ( $order->get_shipping_total() > 0 ) {
				$shipping_amount          = wc_format_decimal( $order->get_shipping_total(), '' );
				$shipping_tax             = wc_format_decimal( $order->get_shipping_tax(), '' );
				$opts["shipping_options"] = array(
					array(
						'shipping_rate_data' => array(
							'type'         => 'fixed_amount',
							'display_name' => $order->get_shipping_method(),
							'fixed_amount' => array(
								'amount'   => Utils::amount_in_cents( $shipping_amount + $shipping_tax ),
								'currency' => strtolower( $order->get_currency() ),
							),
						),
					),
				);
			}

			return $stripe->checkout->sessions->create( $opts );

		} catch ( ApiErrorException $e ) {
			self::log( 'Stripe API error: ' . $e->getMessage(), WC_Log_Levels::ERROR );

			throw new \Exception( __( 'Payment provider error. Please try again.', 'wp-express-checkout' ) );

		} catch ( \Exception $e ) {
			self::log( 'General error: ' . $e->getMessage(), WC_Log_Levels::ERROR );

			throw $e;
		}
	}

	private function get_stripe_line_items( WC_Order $order ) {
		$line_items = [];

		foreach ( $order->get_items() as $item_id => $item ) {
			// Calculate total for this line (Price + Tax)
			$total_amount   = wc_format_decimal( $item->get_total(), '' );
			$total_tax      = wc_format_decimal( $item->get_total_tax(), '' );
			$total_incl_tax = $total_amount + $total_tax;
			$unit_price     = $total_incl_tax / $item->get_quantity();

			$line_items[] = array(
				'price_data' => array(
					'currency'     => strtolower( $order->get_currency() ),
					'unit_amount'  => Utils::amount_in_cents( $unit_price ),
					'product_data' => array(
						'name' => $item->get_name(),
					),
				),
				'quantity'   => $item->get_quantity(),
			);
		}

		return $line_items;
	}

	public static function check_stripe_ipn() {
		$checkout_session_id = isset( $_GET['csid'] ) ? $_GET['csid'] : '';

		if ( empty( $checkout_session_id ) ) {
			return;
		}

		self::log( 'Checking if Stripe Checkout session is valid & completed by matching client_reference_id' );

		if(Payment_Processor_Stripe::check_if_checkout_session_processed( $checkout_session_id )){
			// This has processed already, nothing to do here.
			self::log(sprintf("The checkout session with ID: %s has already processed!", $checkout_session_id), WC_Log_Levels::ERROR, "Stripe Checkout session already processed");

			return;
		}

		try {
			$sess = self::retrieve_checkout_session_object( $checkout_session_id );

			if ( $sess === false ) {
				// Can't find session.
				$error_msg = sprintf( "Error! Payment with csid %s (Checkout Session ID) can't be found. This script should be accessed by Stripe's webhook only.", $checkout_session_id );
				self::log( $error_msg, false );

				return;
			}

			// Logger::log_array_data( $sess ); // TODO: For Debug Purpose Only

			// Retrieve the order using the metadata we sent during session creation
			$client_reference_id = isset( $sess->client_reference_id ) ? $sess->client_reference_id : '';
			$order_id = $client_reference_id;

			$order = wc_get_order( $order_id );

			if ( empty($order) ) {
				self::log('Error! Order data could not be retrieved!', WC_Log_Levels::ERROR );
				return;
			}

			$total_expected = wc_format_decimal( $order->get_total(), '' );
			$total_received = wc_format_decimal( Utils::amount_from_cents($sess->amount_total, $sess->currency),'');

			$diff = abs( $total_expected - $total_received );
			if ( $diff > 1 ) {
				self::log('Error! Price Amount Mismatch!', WC_Log_Levels::ERROR );
				self::log( sprintf('Expected amount %f, received %f', $total_expected, $total_received), WC_Log_Levels::ERROR );
				return;
			}

			self::complete_order( $order, $sess );
		} catch ( \Exception $e ) {
			$error_msg = 'Error! ' . $e->getMessage();
			self::log( $error_msg, WC_Log_Levels::ERROR );
		}
	}

	public static function retrieve_checkout_session_object( $checkout_session_id ) {
		$stripe = Utils::get_stripe_client();
		$sess   = $stripe->checkout->sessions->retrieve( $checkout_session_id );

		if ( ! empty( $sess ) ) {
			return $sess;
		}

		self::log( 'The checkout session could not be retrieved. Retrying to retrieve checkout session...', false );
		sleep( 2 );

		$sess = $stripe->checkout->sessions->retrieve( $checkout_session_id );

		return $sess;
	}

	private static function complete_order( \WC_Order $order, $session ) {
		if ( $order->has_status( array( 'processing', 'completed' ) ) ) {
			self::log('Error! Order has processed already.', WC_Log_Levels::ERROR );
			return; // Already processed
		}

		// Mark as paid
		$order->payment_complete( $session->payment_intent );
	}
}