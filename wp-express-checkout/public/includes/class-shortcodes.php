<?php

namespace WP_Express_Checkout;

use Exception;

class Shortcodes {

	public $ppdg     = null;
	public $paypaldg = null;

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance        = null;
	protected static $payment_buttons = array();

	function __construct() {
		$this->ppdg = Main::get_instance();

		// General product/button shortcode
		add_shortcode( 'wp_express_checkout', array( $this, 'shortcode_wp_express_checkout' ) );

		// Default Thank You page shortcode:
		add_shortcode( 'wpec_thank_you', array( $this, 'shortcode_wpec_thank_you' ) );

		// The general thank you part shortcode:
		add_shortcode( 'wpec_ty', array( $this, 'shortcode_wpec_thank_you_parts' ) );

		// Downloads wrapper:
		add_shortcode( 'wpec_ty_downloads', array( $this, 'shortcode_wpec_thank_you_downloads' ) );

		if ( ! is_admin() ) {
			add_filter( 'widget_text', 'do_shortcode' );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function show_err_msg( $msg, $code = 0 ) {
		return sprintf( '<div class="wpec-error-message wpec-error-message-' . esc_attr( $code ) . '">%s</div>', $msg );
	}

	function shortcode_wp_express_checkout( $atts ) {
		global $wp_query;

		$atts = wp_parse_args(
			$atts,
			array(
				'product_id' => 0,
			)
		);

		try {
			$product = Products::retrieve( intval( $atts['product_id'] ) );
		} catch ( Exception $exc ) {
			return $this->show_err_msg( $exc->getMessage() );
		}

		$post_id = intval( $atts['product_id'] );
		$post    = get_post( $post_id );

		$quantity        = $product->get_quantity();
		$url             = $product->get_download_url();
		$button_text     = $product->get_button_text();
		$thank_you_url   = ! empty( $atts['thank_you_url'] ) ? $atts['thank_you_url'] : $product->get_thank_you_url();
		$btn_type        = $product->get_button_type();
		$btn_sizes       = array( 'small' => 25, 'medium' => 35, 'large' => 45, 'xlarge' => 55 );
		$btn_height      = $this->ppdg->get_setting( 'btn_height' );

		$output = '';

		$args = array(
			'name'            => get_the_title( $post_id ),
			'price'           => $product->get_price(),
			'shipping'        => $product->get_shipping(),
			'shipping_enable' => $product->is_physical(),
			'tax'             => $product->get_tax(),
			'custom_amount'   => 'donation' === $product->get_type(), // Temporary, until we remove custom_amount parameter.
			'quantity'        => max( intval( $quantity ), 1 ),
			'custom_quantity' => $product->is_custom_quantity(),
			'url'             => base64_encode( $url ),
			'product_id'      => $post_id,
			'thumbnail_url'   => $product->get_thumbnail_url(),
			'coupons_enabled' => $product->get_coupons_setting(),
			'variations'      => $product->get_variations()
		);

		$args = shortcode_atts(
			array(
				'name'            => 'Item Name',
				'price'           => 0,
				'shipping'        => 0,
				'shipping_enable' => 0,
				'tax'             => 0,
				'quantity'        => 1,
				'url'             => '',
				'product_id'      => '',
				'thumbnail_url'   => '',
				'custom_amount'   => 0,
				'custom_quantity' => 0,
				'currency'        => $this->ppdg->get_setting( 'currency_code' ), // Maybe useless option, the shortcode doesn't send this parameter.
				'btn_shape'       => $this->ppdg->get_setting( 'btn_shape' ),
				'btn_type'        => $btn_type ? $btn_type : $this->ppdg->get_setting( 'btn_type' ),
				'btn_height'      => ! empty( $btn_sizes[ $btn_height ] ) ? $btn_sizes[ $btn_height ] : 25,
				'btn_width'       => $this->ppdg->get_setting( 'btn_width' ) !== false ? $this->ppdg->get_setting( 'btn_width' ) : 0,
				'btn_layout'      => $this->ppdg->get_setting( 'btn_layout' ),
				'btn_color'       => $this->ppdg->get_setting( 'btn_color' ),
				'coupons_enabled' => $this->ppdg->get_setting( 'coupons_enabled' ),
				'button_text'     => $button_text ? $button_text : $this->ppdg->get_setting( 'button_text' ),
				'use_modal'       => ! isset( $atts['modal'] ) ? $this->ppdg->get_setting( 'use_modal' ) : $atts['modal'],
				'thank_you_url'   => $thank_you_url ? $thank_you_url : $this->ppdg->get_setting( 'thank_you_url' ),
				'variations'      => array(),
			),
			$args
		);

		$template = empty( $atts['template'] ) ? 0 : intval( $atts['template'] );
		$located  = self::locate_template( "content-product-{$template}.php" );

		$GLOBALS['post'] = $post;
		setup_postdata( $post );
		$post->post_content = strip_shortcodes( $post->post_content );
		$wp_query->set( 'wpec_button_args', $args );
		if ( $located ) {
			ob_start();
			load_template( $located, false );
			$output .= ob_get_clean();
		} else {
			$output .= $this->generate_pp_express_checkout_button( $args );
		}
		wp_reset_postdata();

		return $output;
	}

	function generate_pp_express_checkout_button( $args ) {

		extract( $args );

		// The button ID.
		$button_id = 'paypal_button_' . count( self::$payment_buttons );

		self::$payment_buttons[] = $button_id;

		$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $name ); // Create key using the item name.

		$trans_data = array(
			'price'           => $price,
			'currency'        => $currency,
			'quantity'        => $quantity,
			'tax'             => $tax,
			'shipping'        => $shipping,
			'shipping_enable' => $shipping_enable,
			'url'             => $url,
			'custom_quantity' => $custom_quantity,
			'custom_amount'   => $custom_amount,
			'product_id'      => $product_id,
			'coupons_enabled' => $coupons_enabled,
			'thank_you_url'   => $thank_you_url,
		);

		set_transient( $trans_name, $trans_data, WEEK_IN_SECONDS );

		$is_live = $this->ppdg->get_setting( 'is_live' );

		if ( $is_live ) {
			$env       = 'production';
			$client_id = $this->ppdg->get_setting( 'live_client_id' );
		} else {
			$env       = 'sandbox';
			$client_id = $this->ppdg->get_setting( 'sandbox_client_id' );
		}

		if ( empty( $client_id ) ) {
			$err_msg = sprintf( __( "Please enter %s Client ID in the settings.", 'wp-express-checkout' ), $env );
			$err     = $this->show_err_msg( $err_msg, 'client-id' );
			return $err;
		}

		$output  = '';
		$located = self::locate_template( 'payment-form.php' );

		if ( $located ) {
			ob_start();
			require $located;
			$output = ob_get_clean();
		}

		$modal = self::locate_template( 'modal.php' );

		if ( $modal && $use_modal ) {
			$modal_title = apply_filters( 'wpec_modal_window_title', get_the_title( $product_id ), $args );
			ob_start();
			require $modal;
			$output = ob_get_clean();
		}

		$data = apply_filters( 'wpec_button_js_data', array(
			'id'              => $button_id,
			'nonce'           => wp_create_nonce( $button_id . $product_id ),
			'env'             => $env,
			'client_id'       => $client_id,
			'price'           => $price,
			'quantity'        => $quantity,
			'tax'             => $tax,
			'shipping'        => $shipping,
			'shipping_enable' => $shipping_enable,
			'dec_num'         => intval( $this->ppdg->get_setting( 'price_decimals_num' ) ),
			'thousand_sep'    => $this->ppdg->get_setting( 'price_thousand_sep' ),
			'dec_sep'         => $this->ppdg->get_setting( 'price_decimal_sep' ),
			'curr_pos'        => $this->ppdg->get_setting( 'price_currency_pos' ),
			'tos_enabled'     => $this->ppdg->get_setting( 'tos_enabled' ),
			'custom_quantity' => $custom_quantity,
			'custom_amount'   => $custom_amount,
			'currency'        => $currency,
			'currency_symbol' => ! empty( $this->ppdg->get_setting( 'currency_symbol' ) ) ? $this->ppdg->get_setting( 'currency_symbol' ) : $currency,
			'coupons_enabled' => $coupons_enabled,
			'product_id'      => $product_id,
			'name'            => $name,
			'variations'      => $variations,
			'btnStyle'        => array(
				'height' => $btn_height,
				'shape'  => $btn_shape,
				'label'  => $btn_type,
				'color'  => $btn_color,
				'layout' => $btn_layout,
			),
		) );


		$output .= '<script type="text/javascript">var wpec_' . $button_id . '_data=' . json_encode( $data ) . ';jQuery( function( $ ) {$( document ).on( "wpec_paypal_sdk_loaded", function() { new ppecHandler(wpec_' . $button_id . '_data) } );} );</script>';

		add_action( 'wp_footer', array( $this->ppdg, 'load_paypal_sdk' ) );

		return $output;
	}

	public function generate_price_tag( $args ) {
		$output = '<span class="wpec-price-amount">' . esc_html( Utils::price_format( $args['price'] ) ) . '</span>';
		$output .= ' <span class="wpec-new-price-amount"></span>';
		$qnt_style = 2 > $args['quantity'] ? ' style="display:none;"' : '';
		/* translators: quantity */
		$output .=  ' <span class="wpec-quantity"' . $qnt_style . '>' . sprintf( __( 'x %s', 'wp-express-checkout' ), '<span class="wpec-quantity-val">' . $args['quantity'] . '</span>' ) . '</span>';

		$under_price_line = '';
		$tax_line         = '';
		$shipping_line    = '';
		$total_line       = '';
		$tot_price        = ! empty( $args['quantity'] ) ? $args['price'] * $args['quantity'] : $args['price'];

		if ( ! empty( $args['tax'] ) ) {
			$tax_amount = Utils::get_tax_amount( $args['price'], $args['tax'] ) * $args['quantity'];
			$tot_price += $tax_amount;
			if ( ! empty( $args['price'] ) ) {
				/* translators: tax amount */
				$tax_tag = sprintf( __( '%s (tax)', 'wp-express-checkout' ), '<span class="wpec-tax-val">' . Utils::price_format( $tax_amount ) . '</span>' );
			} else {
				/* translators: tax percent */
				$tax_tag = sprintf( __( '%s%% tax', 'wp-express-checkout' ), '<span class="wpec-tax-val">' . $args['tax'] . '</span>' );
			}
			$tax_line = '<span class="wpec_price_tax_section">' . $tax_tag . '</span>';
		}
		if ( ! empty( $args['shipping'] ) ) {
			$tot_price += $args['shipping'];
			if ( ! empty( $args['tax'] ) ) {
				/* translators: tax + shipping amount */
				$shipping_tag = sprintf( __( '+ %s (shipping)', 'wp-express-checkout' ), Utils::price_format( $args['shipping'] ) );
			} else {
				/* translators: shipping amount */
				$shipping_tag = sprintf( __( '%s (shipping)', 'wp-express-checkout' ), Utils::price_format( $args['shipping'] ) );
			}
			$shipping_line = '<span class="wpec_price_shipping_section">' . $shipping_tag . '</span>';
		}

		if ( floatval( $tot_price ) !== floatval( $args['price'] ) ) {
			$total_line       = '<div class="wpec_price_full_total">' . esc_html__( 'Total:', 'wp-express-checkout' ) . ' <span class="wpec_tot_current_price">' . esc_html( Utils::price_format( $tot_price ) ) . '</span> <span class="wpec_tot_new_price"></span></div>';
			$under_price_line = '<div class="wpec_under_price_line">' . $tax_line . $shipping_line . $total_line . '</div>';
		}

		$output .= $under_price_line;

		return apply_filters( 'wpec_price_tag', $output, $args );
	}

	/**
	 * Retrieves Order instance for Thank You page.
	 *
	 * On failure retrieves formatted error message.
	 *
	 * @return string
	 */
	public function get_thank_you_page_order() {
		$error_message = '';

		if ( ! isset( $_GET['order_id'] ) ) {
			$error_message .= '<p>' . __( 'This page is used to show the transaction result after a customer makes a payment.', 'wp-express-checkout' ) . '</p>';
			$error_message .= '<p>' . __( 'It will dynamically show the order details to the customers when they are redirected here after a payment. Do not access this page directly.', 'wp-express-checkout' ) . '</p>';
			$error_message .= $this->show_err_msg( __( 'Error! Order ID value is missing in the URL.', 'wp-express-checkout' ), 'missing-order-id' );
			return $error_message;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'thank_you_url' . $_GET['order_id'] ) ) {
			$error_message .= '<p>' . __( 'This page is used to show the transaction result after a customer makes a payment.', 'wp-express-checkout' ) . '</p>';
			$error_message .= '<p>' . __( 'It will dynamically show the order details to the customers when they are redirected here after a payment. Do not access this page directly.', 'wp-express-checkout' ) . '</p>';
			$error_message .= $this->show_err_msg( __( 'Error! Nonce value is missing in the URL or Nonce verification failed.', 'wp-express-checkout' ), 'nonce-verification' );
			return $error_message;
		}

		// Retrieve the order data.
		$order_id = (int) $_GET['order_id'];
		try {
			$order = Orders::retrieve( $order_id );
		} catch ( Exception $exc ) {
			return $this->show_err_msg( $exc->getMessage(), $exc->getCode() );
		}

		if ( 'COMPLETED' !== $order->get_data( 'state' ) ) {
			return $this->show_err_msg( sprintf( __( 'Payment is not approved. Status: %s', 'wp-express-checkout' ), $order->get_data( 'state' ) ), 'order-state' );
		}

		return $order;
	}

	/**
	 * Thank You page shortcode.
	 *
	 * @param array  $atts    An array of attributes. There are no attributes for now.
	 * @param string $content The shortcode content or null if not set.
	 *
	 * @return string
	 */
	public function shortcode_wpec_thank_you( $atts = array(), $content = '' ) {

		$order = $this->get_thank_you_page_order();

		if ( ! $order instanceof Order ) {
			return $order;
		}

		if ( empty( $content ) ) {
			$located = self::locate_template( 'content-thank-you.php' );

			if ( $located ) {
				ob_start();
				require $located;
				$content = ob_get_clean();
			}
		}

		// Do nested shortcodes.
		$content = do_shortcode( $content );

		// Trigger the filter.
		$content = apply_filters( 'wpec_thank_you_message', $content );

		return $content;
	}

	/**
	 * Thank You part shortcode.
	 *
	 * @see Order_Tags_Html for available parts.
	 *
	 * @param array  $atts      An array of attributes.
	 * @param string $content   The shortcode content or null if not set.
	 * @param string $shortcode The shortcode name.
	 */
	public function shortcode_wpec_thank_you_parts( $atts = array(), $content = '', $shortcode = '' ) {
		$args = shortcode_atts(
			array(
				'field' => '',
			),
			$atts
		);

		// Return shorter Thank you page warning.
		if ( ! isset( $_GET['order_id'] ) ) {
			return $this->show_err_msg( __( 'Thank you page shortcodes work after a transaction. Do not access this page directly.', 'wp-express-checkout' ), 'missing-order-id' );
		}

		$order = $this->get_thank_you_page_order();

		// Show other errors.
		if ( ! $order instanceof Order ) {
			return $order;
		}

		$field = $args['field'];
		$renderer = new Order_Tags_Html( $order );
		$content  = $renderer->$field( $atts );

		return $content;
	}

	/**
	 * Thank You Downloads part shortcode.
	 *
	 * @param array  $atts    An array of attributes.
	 * @param string $content The shortcode content or null if not set.
	 */
	public function shortcode_wpec_thank_you_downloads( $atts = array(), $content = '' ) {
		$order = $this->get_thank_you_page_order();

		if ( ! $order instanceof Order ) {
			return $order;
		}

		$order_id  = (int) $order->get_id();
		$downloads = View_Downloads::get_order_downloads_list( $order_id );

		if ( ! $downloads ) {
			return '';
		}

		if ( empty( $content ) ) {
			$located = self::locate_template( 'content-thank-you-downloads.php' );

			if ( $located ) {
				ob_start();
				require $located;
				$content = ob_get_clean();
			}
		}

		$content = do_shortcode( $content );

		return $content;
	}

	/**
	 * Locate template including plugin folder.
	 *
	 * Try to locate template in the theme or child theme:
	 * `yourtheme/wpec/$template_name`,
	 * otherwise try to locate default template in the plugin directory:
	 * `wp-express-checkout/public/views/templates/$template_name`
	 *
	 * @param string $template_name Template file to search for.
	 * @return string
	 */
	public static function locate_template( $template_name ) {
		$default  = WPEC_PLUGIN_PATH . "/public/views/templates/$template_name";
		$located  = locate_template( "wpec/$template_name" );

		if ( ! $located && file_exists( $default ) ) {
			$located = $default;
		}

		return apply_filters( 'wpec_product_template', $located, $template_name );
	}

}
