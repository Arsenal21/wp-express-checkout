<?php

namespace WP_Express_Checkout\Integrations;

use WP_Express_Checkout\Shortcodes;

class WooCommerce_Payment_Button {

	public $wpec;

	public $order;

	public function __construct($order, $wpec) {
		$this->wpec = $wpec;
		$this->order = $order;
	}

	public function wpec_generate_woo_payment_button( $args ) {

		extract( $args );

		if ( $stock_enabled && empty( $stock_items ) ) {
			return '<div class="wpec-out-of-stock">' . esc_html( 'Out of stock', 'wp-express-checkout' ) . '</div>';
		}

		// The button ID.
//		$button_id = 'paypal_button_' . count( self::$payment_buttons ); // TODO: Line Replaced
		$button_id = 'paypal_button_0';

//		self::$payment_buttons[] = $button_id; // TODO: Line Removed

		$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $name ); // Create key using the item name.

		$trans_data = array(
			'price'           => $price,
			'currency'        => $currency,
			'quantity'        => $quantity,
			'tax'             => $tax,
			'shipping'        => $shipping,
			'shipping_per_quantity' => $shipping_per_quantity,
			'shipping_enable' => $shipping_enable,
			'url'             => $url,
			'custom_quantity' => $custom_quantity,
			'custom_amount'   => $custom_amount,
			'product_id'      => $product_id,
			'coupons_enabled' => $coupons_enabled,
			'thank_you_url'   => $thank_you_url,
			'wc_id' => $order->get_id(),
		);

		set_transient( $trans_name, $trans_data, 2 * 3600  );

		$is_live = $this->wpec->get_setting( 'is_live' );

		if ( $is_live ) {
			$env       = 'production';
			$client_id = $this->wpec->get_setting( 'live_client_id' );
		} else {
			$env       = 'sandbox';
			$client_id = $this->wpec->get_setting( 'sandbox_client_id' );
		}

		if ( empty( $client_id ) ) {
			$err_msg = sprintf( __( "Please enter %s Client ID in the settings.", 'wp-express-checkout' ), $env );
			$err     = $this->show_err_msg( $err_msg, 'client-id' );
			return $err;
		}

		$output  = '';
		$located = Shortcodes::locate_template( 'payment-form.php' );

		if ( $located ) {
			ob_start();
			require $located;
			$output = ob_get_clean();
		}

		$modal = Shortcodes::locate_template( 'modal.php' );

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
			'shipping_per_quantity' => $shipping_per_quantity,
			'shipping_enable' => $shipping_enable,
			'dec_num'         => intval( $this->wpec->get_setting( 'price_decimals_num' ) ),
			'thousand_sep'    => $this->wpec->get_setting( 'price_thousand_sep' ),
			'dec_sep'         => $this->wpec->get_setting( 'price_decimal_sep' ),
			'curr_pos'        => $this->wpec->get_setting( 'price_currency_pos' ),
			'tos_enabled'     => $this->wpec->get_setting( 'tos_enabled' ),
			'custom_quantity' => $custom_quantity,
			'custom_amount'   => $custom_amount,
			'currency'        => $currency,
			'currency_symbol' => ! empty( $this->wpec->get_setting( 'currency_symbol' ) ) ? $this->wpec->get_setting( 'currency_symbol' ) : $currency,
			'coupons_enabled' => $coupons_enabled,
			'product_id'      => $product_id,
			'name'            => $name,
			'stock_enabled'   => $stock_enabled,
			'stock_items'     => $stock_items,
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

		add_action( 'wp_footer', array( $this->wpec, 'load_paypal_sdk' ) );

		return $output;
	}

	private function show_err_msg( $msg, $code = 0 ) {
		return sprintf( '<div class="wpec-error-message wpec-error-message-' . esc_attr( $code ) . '">%s</div>', $msg );
	}
}