<?php

namespace WP_Express_Checkout\Integrations;

use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Main;
use WP_Express_Checkout\Shortcodes;

class WooCommerce_Payment_Button {

	public $wpec;

	public $order;

	public $button_id;

	public function __construct( $wc_order_id ) {
		$this->order = new \WC_Order( $wc_order_id );
		$this->button_id = 'paypal_button_0';
		$this->wpec      = Main::get_instance();
	}

	public function wpec_generate_woo_payment_button() {
		$modal_title = isset($_POST['modal_title']) ? sanitize_text_field( $_POST['modal_title'] ) : '';
		$btn_sizes = array( 'small' => 25, 'medium' => 35, 'large' => 45, 'xlarge' => 55 );
		$nonce = wp_create_nonce( 'wpec-wc-pp-payment-ajax-nonce' );
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

		$data = array(
			'id'                    => $this->button_id,
			'nonce'                 => $nonce,
			'env'                   => $env,
			'client_id'             => $client_id,
			'price'                 => $this->order->get_total(),
			'quantity'              => 1,
			'tax'                   => 0,
			'shipping'              => 0,
			'shipping_per_quantity' => 0,
			'shipping_enable'       => 0,
			'dec_num'               => intval( $this->wpec->get_setting( 'price_decimals_num' ) ),
			'thousand_sep'          => $this->wpec->get_setting( 'price_thousand_sep' ),
			'dec_sep'               => $this->wpec->get_setting( 'price_decimal_sep' ),
			'curr_pos'              => $this->wpec->get_setting( 'price_currency_pos' ),
			'tos_enabled'           => $this->wpec->get_setting( 'tos_enabled' ),
			'custom_quantity'       => 0,
			'custom_amount'         => 0,
			'currency'              => $this->order->get_currency(),
			'currency_symbol'       => ! empty( $this->wpec->get_setting( 'currency_symbol' ) ) ? $this->wpec->get_setting( 'currency_symbol' ) : $this->order->get_currency(),
			'coupons_enabled'       => false,
			'product_id'            => 0,
			'name'                  => '#' . $this->order->get_id(),
			'stock_enabled'         => 0, // TODO: Maybe unnecessary data.
			'stock_items'           => 0, // TODO: Maybe unnecessary data.
			'variations'            => array(), // TODO: Maybe unnecessary data.
			'btnStyle'              => array(
				'height' => ! empty( $btn_sizes[ $this->wpec->get_setting( 'btn_height' ) ] ) ? $btn_sizes[ $this->wpec->get_setting( 'btn_height' ) ] : 25,
				'shape'  => $this->wpec->get_setting( 'btn_shape' ),
				'label'  => $this->wpec->get_setting( 'btn_type' ),
				'color'  => $this->wpec->get_setting( 'btn_color' ),
				'layout' => $this->wpec->get_setting( 'btn_layout' ),
			),
			'thank_you_url'   => $this->order->get_checkout_order_received_url(),
			'modal_title'     => $modal_title,
			'price_class'     => 'wpec-price-' . substr( sha1( time() . mt_rand( 0, 1000 ) ), 0, 10 ),
		);

		// Logger::log( 'PayPal button generation data: ', true ); // Debug purpose.
		// Logger::log_array_data( $data, true ); // Debug purpose.

		$trans_name = 'wp-ppdg-' . $this->order->get_id(); // Create key using the item name.

		$trans_data = array(
			'price'           => $data['price'],
			'currency'        => $data['currency'],
			'thank_you_url'   => $data['thank_you_url'],
			'wc_id'           => $this->order->get_id(),
		);

		set_transient( $trans_name, $trans_data, 2 * 3600 );

		$output = $this->get_wpec_payment_modal_html( $data );

		ob_start();
		?>
        <script type="text/javascript">
            var wpec_paypal_button_0_data = <?php echo json_encode( $data )?>;
        </script>
		<?php
		$output .= ob_get_clean();

		return $output;
	}

    public function wpec_prepare_woo_payment_button_data() {
        $modal_title = isset($_POST['modal_title']) ? sanitize_text_field( $_POST['modal_title'] ) : '';
        $btn_sizes = array( 'small' => 25, 'medium' => 35, 'large' => 45, 'xlarge' => 55 );
        $nonce = wp_create_nonce( 'wpec-wc-pp-payment-ajax-nonce' );
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

        $data = array(
            'id'                    => $this->button_id,
            'order_id'              => $this->order->get_id(),
            'nonce'                 => $nonce,
            'env'                   => $env,
            'client_id'             => $client_id,
            'price'                 => $this->order->get_total(),
            'price_tag'             => WC()->cart->get_total(),
            'quantity'              => 1,
            'tax'                   => 0,
            'shipping'              => 0,
            'shipping_per_quantity' => 0,
            'shipping_enable'       => 0,
            'dec_num'               => intval( $this->wpec->get_setting( 'price_decimals_num' ) ),
            'thousand_sep'          => $this->wpec->get_setting( 'price_thousand_sep' ),
            'dec_sep'               => $this->wpec->get_setting( 'price_decimal_sep' ),
            'curr_pos'              => $this->wpec->get_setting( 'price_currency_pos' ),
            'tos_enabled'           => $this->wpec->get_setting( 'tos_enabled' ),
            'custom_quantity'       => 0,
            'custom_amount'         => 0,
            'currency'              => $this->order->get_currency(),
            'currency_symbol'       => ! empty( $this->wpec->get_setting( 'currency_symbol' ) ) ? $this->wpec->get_setting( 'currency_symbol' ) : $this->order->get_currency(),
            'coupons_enabled'       => false,
            'product_id'            => 0,
            'name'                  => '#' . $this->order->get_id(),
            'stock_enabled'         => 0, // TODO: Maybe unnecessary data.
            'stock_items'           => 0, // TODO: Maybe unnecessary data.
            'variations'            => array(), // TODO: Maybe unnecessary data.
            'btnStyle'              => array(
                'height' => ! empty( $btn_sizes[ $this->wpec->get_setting( 'btn_height' ) ] ) ? $btn_sizes[ $this->wpec->get_setting( 'btn_height' ) ] : 25,
                'shape'  => $this->wpec->get_setting( 'btn_shape' ),
                'label'  => $this->wpec->get_setting( 'btn_type' ),
                'color'  => $this->wpec->get_setting( 'btn_color' ),
                'layout' => $this->wpec->get_setting( 'btn_layout' ),
            ),
            'thank_you_url'   => $this->order->get_checkout_order_received_url(),
            'modal_title'     => $modal_title,
            'price_class'     => 'wpec-price-' . substr( sha1( time() . mt_rand( 0, 1000 ) ), 0, 10 ),
        );

        return $data;
    }

	private function show_err_msg( $msg, $code = 0 ) {
		return sprintf( '<div class="wpec-error-message wpec-error-message-' . esc_attr( $code ) . '">%s</div>', $msg );
	}

	public function get_wpec_payment_modal_html( $args ) {
		ob_start();
		?>

        <!--Modal-->
        <div id="wpec-modal-<?php echo esc_attr( $args['id'] ); ?>"
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

                    <div style="position: relative;"
                         class="wp-ppec-shortcode-container wpec-shortcode-container-product-<?php echo esc_attr( $args['product_id'] ); ?>"
                         data-ppec-button-id="<?php echo esc_attr( $args['id'] ); ?>"
                         data-price-class="<?php echo esc_attr( $args['price_class'] ); ?>">

                        <div class="wp-ppec-overlay" data-ppec-button-id="<?php echo esc_attr( $args['id'] ); ?>">
                            <div class="wp-ppec-spinner">
                                <div></div>
                                <div></div>
                                <div></div>
                                <div></div>
                            </div>
                        </div>

                        <div class="wp-ppec-button-container">

                            <div class="wpec-price-container <?php echo esc_attr( $args['price_class'] ); ?>">
                                <?php echo Shortcodes::get_instance()->generate_price_tag( $args ); ?>
                            </div>

                            <div id="place-order-<?php echo esc_attr( $args['id'] );?>" style="display:none;">
                                <button class="wpec-place-order-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
					                <?php esc_html_e( 'Place Order', 'wp-express-checkout' ); ?>
                                </button>
                            </div>

                            <div id="<?php echo esc_attr( $args['id'] ); ?>" style="max-width:<?php echo esc_attr( $args['btnStyle']['width'] ); ?>"></div>

                            <div class="wpec-button-placeholder" style="display: none; border: 1px solid #E7E9EB; padding:1rem;">
                                <i><?php esc_html_e( 'This is where the Express Checkout Button will show. View it on the front-end to see how it will look to your visitors', 'wp-express-checkout' ); ?></i>
                            </div>

                        </div>

                    </div>

                </div>
            </div>
        </div>

        <button data-wpec-modal="wpec-modal-<?php echo esc_attr( $args['id'] ); ?>" class="wpec-modal-open wpec-modal-open-product-<?php echo esc_attr( $args['product_id'] ); ?>"></button>

        <?php
		return ob_get_clean();
	}
}