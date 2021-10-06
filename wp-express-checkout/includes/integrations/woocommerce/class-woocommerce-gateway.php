<?php

namespace WP_Express_Checkout\Integrations;

use WC_Logger;
use WC_Order;
use WC_Payment_Gateway;
use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Main;
use WP_Express_Checkout\Shortcodes;

defined( 'ABSPATH' ) || die( 'No script kiddies please!' ); // @codeCoverageIgnore

class WooCommerce_Gateway extends WC_Payment_Gateway {

	/** @var bool Whether or not logging is enabled */
	public static $log_enabled = false;

	/** @var WC_Logger Logger instance */
	public static $log = false;

	/** @var WC_Order */
	public $wpec_wc_order = false;

	/** @var Main */
	public $wpec;

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
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_filter( 'wpec_modal_window_title', array( $this, 'modal_window_title' ), 10, 2 );
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

    public function receipt_page( $order_id ) {

		$order = new WC_Order( $order_id );

		$btn_sizes = array( 'small' => 25, 'medium' => 35, 'large' => 45, 'xlarge' => 55 );

		$form_args = array(
			'btn_color'  => $this->wpec->get_setting( 'btn_color' ),
			'btn_height' => ! empty( $btn_sizes[ $this->wpec->get_setting( 'btn_height' ) ] ) ? $btn_sizes[ $this->wpec->get_setting( 'btn_height' ) ] : 25,
			'btn_layout' => $this->wpec->get_setting( 'btn_layout' ),
			'btn_shape'  => $this->wpec->get_setting( 'btn_shape' ),
			'btn_type'   => $this->wpec->get_setting( 'btn_type' ),
			'coupons_enabled' => false,
			'curr_pos'        => $this->wpec->get_setting( 'price_currency_pos' ),
			'currency'        => $order->get_currency(),
			'custom_amount'   => 0,
			'custom_quantity' => 0,
			'dec_num'         => intval( $this->wpec->get_setting( 'price_decimals_num' ) ),
			'dec_sep'         => $this->wpec->get_setting( 'price_decimal_sep' ),
			'name'            => '#' . $order->get_id(),
			'price'           => $order->get_total(),
			'product_id'      => 0,
			'quantity'        => 1,
			'shipping'        => 0,
			'shipping_enable' => 0,
			'tax'             => 0,
			'thank_you_url'   => $order->get_checkout_order_received_url(),
			'thousand_sep'    => $this->wpec->get_setting( 'price_thousand_sep' ),
			'tos_enabled'     => $this->wpec->get_setting( 'tos_enabled' ),
			'url'             => '',
			'use_modal'       => true,
			'variations'      => array(),
		);

		$this->wpec_wc_order = $order;

		add_filter( 'wpec_paypal_sdk_args', array( $this, 'paypal_sdk_args' ), 10 );

		$button_sc = Shortcodes::get_instance();

		echo $button_sc->generate_pp_express_checkout_button( $form_args );

		$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $form_args['name'] );
		$trans_data = get_transient( $trans_name );
		$trans_data['wc_id'] = $order->get_id();
		set_transient( $trans_name, $trans_data, 2 * 3600 );

		?>
		<script>
		jQuery( document ).on( 'wpec_before_render_button', function( e, handler ) {

			handler.buttonArgs.onApprove = function( data, actions ) {
				jQuery( 'div.wp-ppec-overlay[data-ppec-button-id="' + handler.data.id + '"]' ).css( 'display', 'flex' );
				return actions.order.capture().then( function( details ) {
					handler.processPayment( details, "wpec_process_wc_payment" );
				} );
			};

		} );
		</script>
	<?php
	}

	public function paypal_sdk_args( $args ) {
		$args['currency'] = $this->wpec_wc_order ? $this->wpec_wc_order->get_currency() : get_woocommerce_currency();
		return $args;
	}

	public function payment_fields() {
		echo $this->get_option( 'description' );

		if ( ! is_ajax() ) {
			add_filter( 'wpec_paypal_sdk_args', array( $this, 'paypal_sdk_args' ), 10 );
			$this->wpec->load_paypal_sdk();
			return;
		}

		?>
		<style>
			.wpec-modal-open {
				display: none;
			}
		</style>
		<div class="wpec-wc-button-container"></div>
		<script>
		jQuery( function( $ ) {

			$( '.checkout.woocommerce-checkout' ).on( 'checkout_place_order_success', function( e, result ) {
				var get_payment_method = function() {
					return $( '#payment input[name="payment_method"]:checked' ).val();
				};
				if ( 'success' !== result.result || 'wp-express-checkout' !== get_payment_method() ) {
					return result;
				}

				var order_id = result.order_id;

				$.ajax( {
					type: 'POST',
					url: ppecFrontVars.ajaxUrl,
					async: false,
					data: {
						action: 'wpec_wc_render_button',
						order_id: order_id,
						nonce: "<?php echo esc_js( wp_create_nonce( 'wpec-wc-render-button-nonce' ) ); ?>"
					},
					dataType: 'json',
					success: function( data ) {
						if ( true !== data.success ) {
							result.result = 'failure';
							result.messages = '<div class="woocommerce-error">' + data.data + '</div>';
							return false;
						}
						//$( '#place_order' ).before( data.data );
						$( '.wpec-wc-button-container' ).html( data.data );
						//$( 'form.processing' ).unblock();
						new ppecHandler( wpec_paypal_button_0_data );
						new wpecModal( $ );
						$( '.wpec-modal-open' ).trigger( 'click' );
						$( 'form.processing' ).removeClass( 'processing' ).unblock();
					}
				} );

				// Do not redirect, just focus on popup window.
				result.redirect = '#';

				if ( 'failure' === result.result ) {
					throw 'Result failure';
				}
			} );
		} );
		</script>
		<?php
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
		$order->update_status( 'pending-payment', __( 'Awaiting payment', 'wp-express-checkout' ) );

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * Changes the WPEC popup window title.
	 *
	 * @param string $title
	 * @param array $args
	 * @return string
	 */
	public function modal_window_title( $title, $args ) {
		if ( empty( $args['product_id'] ) ) {
			$title = $this->get_option( 'popup_title' );
		}
		return $title;
	}

}
