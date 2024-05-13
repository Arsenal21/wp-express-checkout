<?php

namespace WP_Express_Checkout\Integrations;

use WC_Logger;
use WC_Order;
use WC_Payment_Gateway;
use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Main;

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

class WooCommerce_Gateway extends WC_Payment_Gateway {

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
		$this->id                 = 'wp-express-checkout';
		$this->method_title       = __( 'WP Express Checkout Gateway', 'wp-express-checkout' );
		$this->method_description = __( 'Use the WP Express Checkout plugin to process payments via PayPal Express Checkout API.', 'wp-express-checkout' );
		$this->notify_url         = WC()->api_request_url( 'wp_express_checkout' );

		$this->wpec = Main::get_instance();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->has_fields  = true;
		$this->supports    = array( 'products' );

		self::$log_enabled = $this->wpec->get_setting( 'enable_debug_logging' );

		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
		//add_action( 'woocommerce_api_' . strtolower( __CLASS__ ), array( $this, 'check_response' ) );
		//add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );

	}

	/**
	 * Add the gateway to WooCommerce
	 *
	 * @param array $methods The WC payment methods.
	 *
	 * @return array
	 */
	public static function add_wc_gateway_class( $methods ) {
		$methods[] = 'WP_Express_Checkout\Integrations\WooCommerce_Gateway';
		return $methods;
	}

	/**
	 * Logging method
	 *
	 * @param  string $message
	 * @param  string $order_id
	 */
	public static function log( $message, $order_id = '' ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}
			if ( ! empty( $order_id ) ) {
				$message = 'Order: ' . $order_id . '. ' . $message;
			}
			self::$log->add( 'wpec', $message );
			Logger::log( $message );
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
			'enabled'         => array(
				'title'    => __( 'Enable/Disable', 'wp-express-checkout' ),
				'type'     => 'checkbox',
				'label'    => __( 'Enable WP Express Checkout gateway', 'wp-express-checkout' ),
				'default'  => 'false',
				'desc_tip' => true,
			),
			'title'           => array(
				'title'       => __( 'Title', 'wp-express-checkout' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wp-express-checkout' ),
				'default'     => __( 'PayPal', 'wp-express-checkout' ),
				'desc_tip'    => true,
			),
			'description'     => array(
				'title'       => __( 'Description', 'wp-express-checkout' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the description which the user sees during checkout.', 'wp-express-checkout' ),
				'default'     => __( 'Pay by PayPal Express Form.', 'wp-express-checkout' ),
			),
			'popup_title'     => array(
				'title'       => __( 'Checkout Popup Title', 'wp-express-checkout' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the popup window title which the user sees during checkout.', 'wp-express-checkout' ),
				'default'     => __( 'PayPal Express Checkout', 'wp-express-checkout' ),
			),
		);
	}

	public function paypal_sdk_args( $args ) {
		$args['currency'] = get_woocommerce_currency();
		return $args;
	}

	public function payment_fields() {
		if ( ! is_ajax() ) {
			//Logger::log( 'payment_fields() hook triggered via non-ajax');
			return;
		}

        add_filter( 'wpec_paypal_sdk_args', array( $this, 'paypal_sdk_args' ), 10 );
        $this->wpec->load_paypal_sdk();

		$wc_payment_button = new WooCommerce_Payment_Button($this->wpec);
		$wc_payment_button->generate_wpec_payment_button();

		echo wpautop($this->get_option( 'description' ));
		// echo "<pre>" . print_r(get_transient('wpec-pp-create-wc-order'), true) . "</pre>";
	}

	/**
	 * Send payment request to gateway
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		$this->wpec_wc_order = $order;
		$order->update_status( 'pending-payment', __( 'Awaiting payment', 'wp-express-checkout' ) );

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}
}
