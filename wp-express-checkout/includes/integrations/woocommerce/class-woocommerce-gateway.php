<?php

namespace WP_Express_Checkout\Integrations;

use WC_Logger;
use WC_Payment_Gateway;
use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Main;

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

class WooCommerce_Gateway extends WC_Payment_Gateway {

	/** @var bool Whether or not logging is enabled */
	public static $log_enabled = false;

	/** @var WC_Logger Logger instance */
	public static $log = false;

	public function __construct() {
		$this->id                 = 'wp-express-checkout';
		$this->method_title       = __( 'WP Express Checkout Gateway', 'wp-express-checkout' );
		$this->method_description = __( 'Use WP Express Checkout plugin to process payments via PayPal Express form', 'wp-express-checkout' );
		$this->notify_url         = WC()->api_request_url( 'wp_express_checkout' );

		$wpec = Main::get_instance();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->has_fields  = true;
		$this->supports    = array( 'products' );

		self::$log_enabled = $wpec->get_setting( 'enable_debug_logging' );

		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
		//add_action( 'woocommerce_api_' . strtolower( __CLASS__ ), array( $this, 'check_response' ) );
		//add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_wc_gateway_class' ) );
	}

	/**
	 * Add the gateway to WooCommerce
	 *
	 * @param array $methods The WC payment methods.
	 *
	 * @return array
	 */
	function add_wc_gateway_class( $methods ) {
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
		);
	}

}
