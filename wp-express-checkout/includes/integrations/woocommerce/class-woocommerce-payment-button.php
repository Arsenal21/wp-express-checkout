<?php

namespace WP_Express_Checkout\Integrations;

use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Shortcodes;

class WooCommerce_Payment_Button {

	public $wpec;

	public $order;

	public $button_id;

	public function __construct( $order, $wpec ) {
		$this->wpec      = $wpec;
		$this->order     = $order;
		$this->button_id = 'paypal_button_0';
	}

	public function wpec_generate_woo_payment_button( $args ) {
		Logger::log( "Code Come here >>>", true );
		extract( $args );

		// TODO: Code Remove
//		if ( $stock_enabled && empty( $stock_items ) ) {
//			return '<div class="wpec-out-of-stock">' . esc_html( 'Out of stock', 'wp-express-checkout' ) . '</div>';
//		}

		// The button ID.
//		$button_id = 'paypal_button_' . count( self::$payment_buttons ); // TODO: Line Replaced
		$button_id = $this->button_id;

//		self::$payment_buttons[] = $button_id; // TODO: Line Removed
		Logger::log( "Code Come here >>> Prep trans name", true );
		$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $name ); // Create key using the item name.
		Logger::log( "Code Come here >>> trans name" . $trans_name, true );

		$trans_data = array(
			'price'           => $price,
			'currency'        => $currency,
			'quantity'        => $quantity,
			'tax'             => $tax,
			'shipping'        => $shipping,
//			'shipping_per_quantity' => $shipping_per_quantity,
			'shipping_enable' => $shipping_enable,
			'url'             => $url,
			'custom_quantity' => $custom_quantity,
			'custom_amount'   => $custom_amount,
			'product_id'      => $product_id,
			'coupons_enabled' => $coupons_enabled,
			'thank_you_url'   => $thank_you_url,
			'wc_id'           => $this->order->get_id(),
		);
		Logger::log_array_data( $trans_data, true );
		Logger::log( "Code Come here >>> Prep trans data", true );

		set_transient( $trans_name, $trans_data, 2 * 3600 );

		$is_live = $this->wpec->get_setting( 'is_live' );
		Logger::log( "Code Come here >>> is live settings", true );
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

//		$output  = '';
//		$located = Shortcodes::locate_template( 'payment-form.php' );
//		Logger::log("Code Come here >>> Locate Template", true);
//		if ( $located ) {
//			Logger::log("Code Come here >>> Template Located", true);
//			ob_start();
//			require $located;
//			$output = ob_get_clean();
//			Logger::log('Code came here', true);
//			Logger::log($output, true);
//		}

//		$modal = Shortcodes::locate_template( 'modal.php' );
//		Logger::log("Code Come here >>> Locate modal", true);
//		if ( $modal && $use_modal ) {
//			Logger::log("Code Come here >>> Modal Located", true);
//			$modal_title = apply_filters( 'wpec_modal_window_title', get_the_title( $product_id ), $args );
//			ob_start();
//			require $modal;
//			$output = ob_get_clean();
//			Logger::log($output, true);
//		}

		$button_html = $this->get_button_tpl_html( $args );

		if ( $use_modal ) {
			$output      = $this->get_modal_html( array(
				'modal_title' => $modal_title,
				'product_id'  => $product_id
			), $button_html );
		}

		Logger::log( "Code Come here >>> Before data process", true );
		$data = array(
			'id'                    => $button_id,
			'nonce'                 => wp_create_nonce( $button_id . $product_id ),
			'env'                   => $env,
			'client_id'             => $client_id,
			'price'                 => $price,
			'quantity'              => $quantity,
			'tax'                   => $tax,
			'shipping'              => $shipping,
//			'shipping_per_quantity' => $shipping_per_quantity,
			'shipping_per_quantity' => 0,
			'shipping_enable'       => $shipping_enable,
			'dec_num'               => intval( $this->wpec->get_setting( 'price_decimals_num' ) ),
			'thousand_sep'          => $this->wpec->get_setting( 'price_thousand_sep' ),
			'dec_sep'               => $this->wpec->get_setting( 'price_decimal_sep' ),
			'curr_pos'              => $this->wpec->get_setting( 'price_currency_pos' ),
			'tos_enabled'           => $this->wpec->get_setting( 'tos_enabled' ),
			'custom_quantity'       => $custom_quantity,
			'custom_amount'         => $custom_amount,
			'currency'              => $currency,
			'currency_symbol'       => ! empty( $this->wpec->get_setting( 'currency_symbol' ) ) ? $this->wpec->get_setting( 'currency_symbol' ) : $currency,
			'coupons_enabled'       => $coupons_enabled,
			'product_id'            => $product_id,
			'name'                  => $name,
			'stock_enabled'         => 0, // TODO: Line remove
			'stock_items'           => 100, // TODO: Line remove
			'variations'            => array(), // TODO: Line remove
			'btnStyle'              => array(
				'height' => $btn_height,
				'shape'  => $btn_shape,
				'label'  => $btn_type,
				'color'  => $btn_color,
				'layout' => $btn_layout,
			),
		);

		Logger::log_array_data( $data, true );
		Logger::log( "Code Come here >>> Before scripting", true );
//		$output .= '<script type="text/javascript">var wpec_' . $button_id . '_data=' . json_encode( $data ) . ';jQuery( function( $ ) {$( document ).on( "wpec_paypal_sdk_loaded", function() { new ppecHandler(wpec_' . $button_id . '_data) } );} );</script>';
        ob_start();
        ?>
        <script type="text/javascript">
            var wpec_<?php echo $this->button_id; ?>_data = <?php echo json_encode( $data )?>;
            jQuery( function( $ ) {
                $( document ).on( "wpec_paypal_sdk_loaded", function() {
                    new ppecHandler( wpec_<?php echo $this->button_id; ?>_data )
                } );
            } );
        </script>
        <?php
		$output .= ob_get_clean();

		add_action( 'wp_footer', array( $this->wpec, 'load_paypal_sdk' ) );
		Logger::log( "Code Come here >>> After add action", true );

		return $output;
	}

	private function show_err_msg( $msg, $code = 0 ) {
		return sprintf( '<div class="wpec-error-message wpec-error-message-' . esc_attr( $code ) . '">%s</div>', $msg );
	}

	public function get_button_tpl_html( $args ) {
		ob_start();
		?>

        <div style="position: relative;"
             class="wp-ppec-shortcode-container wpec-shortcode-container-product-<?php echo esc_attr( $args['product_id'] ); ?>"
             data-ppec-button-id="<?php echo esc_attr( $this->button_id ); ?>"
             data-price-class="<?php echo esc_attr( $args['price_class'] ); ?>">

            <div class="wp-ppec-overlay" data-ppec-button-id="<?php echo esc_attr( $this->button_id ); ?>">
                <div class="wp-ppec-spinner">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </div>

            <div class="wp-ppec-button-container">

				<?php if ( $args['use_modal'] ) { ?>
                    <div class="wpec-price-container <?php echo esc_attr( $args['price_class'] ); ?>">
						<?php echo Shortcodes::get_instance()->generate_price_tag( $args ); ?>
                    </div>
				<?php } ?>

                <div id="place-order-<?php echo esc_attr( $this->button_id );?>" style="display:none;">
                    <button class="wpec-place-order-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
						<?php esc_html_e( 'Place Order', 'wp-express-checkout' ); ?>
                    </button>
                </div>

                <div id="<?php echo esc_attr( $this->button_id ); ?>" style="max-width:<?php echo esc_attr( $args['btn_width'] ); ?>"></div>

                <div class="wpec-button-placeholder" style="display: none; border: 1px solid #E7E9EB; padding:1rem;">
                    <i><?php esc_html_e( 'This is where the Express Checkout Button will show. View it on the front-end to see how it will look to your visitors', 'wp-express-checkout' ); ?></i>
                </div>

            </div>

        </div>

		<?php
		return ob_get_clean();
	}

	public function get_modal_html( $args, $button_html ) {
		ob_start();
		?>

        <!--Modal-->
        <div id="wpec-modal-<?php echo esc_attr( $this->button_id ); ?>"
             class="wpec-modal wpec-opacity-0 wpec-pointer-events-none wpec-modal-product-<?php echo esc_attr( $args['product_id'] ); ?>">

            <div class="wpec-modal-overlay"></div>

            <div class="wpec-modal-container">
                <div class="wpec-modal-content">
                    <!--Title-->
                    <div class="wpec-modal-content-title">
                        <p><?php echo esc_html( $args['modal_title'] ); ?></p>
                        <div class="wpec-modal-close">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                                <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                            </svg>
                        </div>
                    </div>
					<?php echo $button_html; ?>
                </div>
            </div>
        </div>

        <button data-wpec-modal="wpec-modal-<?php echo esc_attr( $this->button_id ); ?>"
                class="wpec-modal-open wpec-modal-open-product-<?php echo esc_attr( $args['product_id'] ); ?>">
			<?php
			//				echo esc_html( $button_text );
			echo "Sample Btn Text (need to fix)";
			?>
        </button>
		<?php
		return ob_get_clean();
	}
}