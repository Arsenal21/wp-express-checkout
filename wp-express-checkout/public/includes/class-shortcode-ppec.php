<?php

class WPECShortcode {

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
		$this->ppdg = WPEC_Main::get_instance();

		add_shortcode( 'wp_express_checkout', array( $this, 'shortcode_wp_express_checkout' ) );
		add_shortcode( 'wpec_thank_you', array( $this, 'shortcode_wpec_thank_you' ) );

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

	private function show_err_msg( $msg ) {
		return sprintf( '<div class="wp-ppec-error-msg" style="color: red;">%s</div>', $msg );
	}

	function shortcode_wp_express_checkout( $atts ) {
		global $wp_query;

		if ( empty( $atts['product_id'] ) ) {
			$error_msg = __( 'Error: product ID is invalid.', 'wp-express-checkout' );
			$err       = $this->show_err_msg( $error_msg );
			return $err;
		}
		$post_id = intval( $atts['product_id'] );
		$post    = get_post( $post_id );
		if ( ! $post || get_post_type( $post_id ) !== PPECProducts::$products_slug ) {
			$error_msg = sprintf( __( "Can't find product with ID %s", 'wp-express-checkout' ), $post_id );
			$err       = $this->show_err_msg( $error_msg );
			return $err;
		}

		$title           = get_the_title( $post_id );
		$price           = get_post_meta( $post_id, 'ppec_product_price', true );
		$custom_amount   = get_post_meta( $post_id, 'wpec_product_custom_amount', true );
		$quantity        = get_post_meta( $post_id, 'ppec_product_quantity', true );
		$custom_quantity = get_post_meta( $post_id, 'ppec_product_custom_quantity', true );
		$url             = get_post_meta( $post_id, 'ppec_product_upload', true );
		$thumb_url       = get_post_meta( $post_id, 'wpec_product_thumbnail', true );
		$shipping        = get_post_meta( $post_id, 'wpec_product_shipping', true );
		$tax             = get_post_meta( $post_id, 'wpec_product_tax', true );

		$coupons_enabled = get_post_meta( $post_id, 'wpec_product_coupons_setting', true );

		if ( ( '' === $coupons_enabled ) || '2' === $coupons_enabled ) {
			$coupons_enabled = $this->ppdg->get_setting( 'coupons_enabled' );
		}

		// Use global options only if the product value is explicitly set to ''.
		// So user can set product value '0' and override non-empty global option.
		$shipping = ( '' === $shipping ) ? $this->ppdg->get_setting( 'shipping' ) : $shipping;
		$tax      = ( '' === $tax ) ? $this->ppdg->get_setting( 'tax' ) : $tax;

		$output = '';

		$args = array(
			'name'            => $title,
			'price'           => $price,
			'shipping'        => $shipping,
			'tax'             => $tax,
			'custom_amount'   => $custom_amount,
			'quantity'        => $quantity,
			'custom_quantity' => $custom_quantity,
			'url'             => $url,
			'product_id'      => $post_id,
			'thumbnail_url'   => $thumb_url,
			'coupons_enabled' => $coupons_enabled,
		);

		$template = empty( $atts['template'] ) ? 0 : intval( $atts['template'] );
		$name     = "content-product-{$template}.php";
		$default  = WPEC_PLUGIN_PATH . '/public/views/templates/' . $name;
		$located  = locate_template( 'wpec/' . $name );

		// Try to locate template in the theme or child theme:
		// yourtheme/wpec/content-product-{$template}.php,
		// otherwise try to locate default template in the plugin directory:
		// wp-express-checkout/public/views/templates/content-product-{$template}.php
		// If no template found - render only the button.

		if ( ! $located && file_exists( $default ) ) {
			$located = $default;
		}

		if ( $located ) {
			ob_start();
			$post->post_content = strip_shortcodes( $post->post_content );
			$GLOBALS['post'] = $post;
			setup_postdata( $post );
			$wp_query->set( 'wpec_button_args', $args );
			load_template( $located, false );
			wp_reset_postdata();
			$output .= ob_get_clean();
		} else {
			$output .= $this->generate_pp_express_checkout_button( $args );
		}

		return $output;
	}

	function generate_pp_express_checkout_button( $args ) {

		extract(
			shortcode_atts(
				array(
					'name'            => 'Item Name',
					'price'           => 0,
					'shipping'        => 0,
					'tax'             => 0,
					'quantity'        => 1,
					'url'             => '',
					'product_id'      => '',
					'custom_amount'   => 0,
					'custom_quantity' => 0,
					'currency'        => $this->ppdg->get_setting( 'currency_code' ), // Maybe useless option, the shortcode doesn't send this parameter.
					'btn_shape'       => $this->ppdg->get_setting( 'btn_shape' ) !== false ? $this->ppdg->get_setting( 'btn_shape' ) : 'pill',
					'btn_type'        => $this->ppdg->get_setting( 'btn_type' ) !== false ? $this->ppdg->get_setting( 'btn_type' ) : 'checkout',
					'btn_height'      => $this->ppdg->get_setting( 'btn_height' ) !== false ? $this->ppdg->get_setting( 'btn_height' ) : 'small',
					'btn_width'       => $this->ppdg->get_setting( 'btn_width' ) !== false ? $this->ppdg->get_setting( 'btn_width' ) : 0,
					'btn_layout'      => $this->ppdg->get_setting( 'btn_layout' ) !== false ? $this->ppdg->get_setting( 'btn_layout' ) : 'horizontal',
					'btn_color'       => $this->ppdg->get_setting( 'btn_color' ) !== false ? $this->ppdg->get_setting( 'btn_color' ) : 'gold',
					'coupons_enabled' => $this->ppdg->get_setting( 'coupons_enabled' ),
				),
				$args
			)
		);

		$product_btn_type = get_post_meta( $product_id, 'wpec_product_button_type', true );

		if ( ! empty( $product_btn_type ) ) {
			$btn_type = $product_btn_type;
		}

		// Lets check the digital item URL.
		if ( ! empty( $url ) ) {
			$url = base64_encode( $url );
		}

		// The button ID.
		$button_id = 'paypal_button_' . count( self::$payment_buttons );

		self::$payment_buttons[] = $button_id;

		$quantity = empty( $quantity ) ? 1 : $quantity;

		// Variations.
		$variations = array();
		$v          = new WPEC_Variations( $product_id );
		if ( ! empty( $v->groups ) ) {
			$variations['groups']   = $v->groups;
			$variations_names       = get_post_meta( $product_id, 'wpec_variations_names', true );
			$variations_prices_orig = get_post_meta( $product_id, 'wpec_variations_prices', true );
			$variations_prices      = apply_filters( 'wpec_variations_prices_filter', $variations_prices_orig, $product_id );
			$variations_urls        = get_post_meta( $product_id, 'wpec_variations_urls', true );
			$variations_opts        = get_post_meta( $product_id, 'wpec_variations_opts', true );
			$variations['names']    = $variations_names;
			$variations['prices']   = $variations_prices;
			$variations['urls']     = $variations_urls;
			$variations['opts']     = $variations_opts;
		}

		$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $name ); // Create key using the item name.

		$trans_data = array(
			'price'           => $price,
			'currency'        => $currency,
			'quantity'        => $quantity,
			'tax'             => $tax,
			'shipping'        => $shipping,
			'url'             => $url,
			'custom_quantity' => $custom_quantity,
			'custom_amount'   => $custom_amount,
			'product_id'      => $product_id,
			'coupons_enabled' => $coupons_enabled,
		);

		set_transient( $trans_name, $trans_data, 2 * 3600 );

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
			$err     = $this->show_err_msg( $err_msg );
			return $err;
		}

		$btn_sizes = array( 'small' => 25, 'medium' => 35, 'large' => 45, 'xlarge' => 55 );

		if ( isset( $btn_sizes[ $btn_height ] ) ) {
			$btn_height = $btn_sizes[ $btn_height ];
		} else {
			$btn_height = 25;
		}

		$output = '';

		$output .= '<div style="position: relative;" class="wp-ppec-shortcode-container" data-ppec-button-id="' . $button_id . '">'
				. '<div class="wp-ppec-overlay" data-ppec-button-id="' . $button_id . '">'
				. '<div class="wp-ppec-spinner">'
				. '<div></div>'
				. '<div></div>'
				. '<div></div>'
				. '<div></div>'
				. '</div>'
				. '</div>';

		// custom quantity.
		if ( $custom_quantity ) {
			$output .= '<div>';
			$output .= '<label>' . esc_html__( 'Quantity:', 'wp-express-checkout' ) . '</label>';
			$output .= '<input id="wp-ppec-custom-quantity" data-ppec-button-id="' . $button_id . '" type="number" name="custom-quantity" class="wp-ppec-input wp-ppec-custom-quantity" min="1" value="' . $quantity . '">';
			$output .= '<div class="wp-ppec-form-error-msg"></div>';
			$output .= '</div>';
		}

		if ( $custom_amount ) {
			$step    = pow( 10, -intval( $this->ppdg->get_setting( 'price_decimals_num' ) ) );
			$output .= '<div class="wpec-custom-amount-section">';
			$output .= '<span class="wpec-custom-amount-label-field"><label>' . sprintf( __( 'Enter Amount (%s): ', 'wp-express-checkout' ), $currency ) . '</label></span>';
			$output .= '<span class="wpec-custom-amount-input-field">';
			$output .= '<input id="wp-ppec-custom-amount" data-ppec-button-id="' . $button_id . '" type="number" step="' . $step . '" name="custom-quantity" class="wp-ppec-input wp-ppec-custom-amount" min="0" value="' . $price . '">';
			$output .= '</span>';
			$output .= '<div class="wp-ppec-form-error-msg"></div>';
			$output .= '</div>';
		}

		// Variations.
		if ( ! empty( $variations ) ) {
			// we got variations for this product.
			$variations_str = '';
			foreach ( $variations['groups'] as $grp_id => $group ) {
				if ( ! empty( $variations['names'] ) ) {
					$variations_str .= '<div class="wpec-product-variations-cont">';
					$variations_str .= '<label class="wpec-product-variations-label">' . $group . '</label>';
					if ( isset( $variations['opts'][ $grp_id ] ) && $variations['opts'][ $grp_id ] === '1' ) {
						// radio buttons output.
					} else {
						$variations_str .= sprintf( '<select class="wpec-product-variations-select" data-wpec-variations-group-id="%1$d" name="wpecVariations[%1$d][]">', $grp_id );
					}
					foreach ( $variations['names'][ $grp_id ] as $var_id => $var_name ) {
						if ( isset( $variations['opts'][ $grp_id ] ) && $variations['opts'][ $grp_id ] === '1' ) {
							$tpl = '<label class="wpec-product-variations-select-radio-label"><input class="wpec-product-variations-select-radio" data-wpec-variations-group-id="' . $grp_id . '" name="wpecVariations[' . $grp_id . '][]" type="radio" name="123" value="%d"' . ( $var_id === 0 ? 'checked' : '' ) . '>%s %s</label>';
						} else {
							$tpl = '<option value="%d">%s %s</option>';
						}
						$price_mod = $variations['prices'][ $grp_id ][ $var_id ];
						if ( ! empty( $price_mod ) ) {
							$fmt_price = WPEC_Utility_Functions::price_format( abs( $price_mod ), $currency );
							$price_mod = $price_mod < 0 ? ' - ' . $fmt_price : ' + ' . $fmt_price;
							$price_mod = '(' . $price_mod . ')';
						} else {
							$price_mod = '';
						}
						$variations_str .= sprintf( $tpl, $var_id, $var_name, $price_mod );
					}
					if ( isset( $variations['opts'][ $grp_id ] ) && $variations['opts'][ $grp_id ] === '1' ) {
						// radio buttons output.
					} else {
						$variations_str .= '</select>';
					}
					$variations_str .= '</div>';
				}
			}
			$output .= $variations_str;
		}

		// Coupons
		if ( $coupons_enabled ) {
			$str_coupon_label = __( 'Coupon Code:', 'wp-express-checkout' );
			$output          .= '<div class="wpec_product_coupon_input_container"><label class="wpec_product_coupon_field_label">' . $str_coupon_label . ' ' . '</label><input id="wpec-coupon-field-' . $button_id . '" class="wpec_product_coupon_field_input" type="text" name="wpec_coupon">'
			. '<input type="button" id="wpec-redeem-coupon-btn-' . $button_id . '" type="button" class="wpec_coupon_apply_btn" value="' . __( 'Apply', 'wp-express-checkout' ) . '">'
			. '<div id="wpec-coupon-info-' . $button_id . '" class="wpec_product_coupon_info"></div>'
			. '</div>';
		}

		$output .= '<div id="wp-ppdg-dialog-message" title="">';
		$output .= '<p id="wp-ppdg-dialog-msg"></p>';
		$output .= '</div>';

		$output .= '<div class = "wp-ppec-button-container">';

		$output .= sprintf( '<div id="%s" style="max-width:%s"></div>', $button_id, $btn_width ? $btn_width . 'px;' : '' );
		$output .= '<div class="wpec-button-placeholder" style="border: 1px solid #E7E9EB; padding:1rem;"><i>' . __( 'This is where the Express Checkout Button will show. View it on the front-end to see how it will look to your visitors', 'wp-express-checkout' ) . '</i></div>';

		$output .= '</div>';

		$data = apply_filters( 'wpec_button_js_data', array(
			'id'              => $button_id,
			'nonce'           => wp_create_nonce( $button_id . $product_id ),
			'env'             => $env,
			'client_id'       => $client_id,
			'price'           => $price,
			'quantity'        => $quantity,
			'tax'             => $tax,
			'shipping'        => $shipping,
			'dec_num'         => intval( $this->ppdg->get_setting( 'price_decimals_num' ) ),
			'thousand_sep'    => $this->ppdg->get_setting( 'price_thousand_sep' ),
			'dec_sep'         => $this->ppdg->get_setting( 'price_decimal_sep' ),
			'curr_pos'        => $this->ppdg->get_setting( 'price_currency_pos' ),
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

		$output .= '<script>jQuery(document).ready(function() {new ppecHandler(' . json_encode( $data ) . ')});</script>';

		$output .= '</div>';

		add_action( 'wp_footer', array( $this->ppdg, 'load_paypal_sdk' ) );

		return $output;
	}

	public function generate_price_tag( $args ) {
		$output = '<span class="wpec-price-amount">' . esc_html( WPEC_Utility_Functions::price_format( $args['price'] ) ) . '</span>';
		$output .= ' <span class="wpec-new-price-amount"></span>';
		/* translators: quantity */
		$output .= 1 < $args['quantity'] ? ' <span class="wpec-quantity">' . sprintf( __( 'x %s', 'wp-express-checkout' ), '<span class="wpec-quantity-val">' . $args['quantity'] . '</span>' ) . '</span>' : '';

		$under_price_line = '';
		$tax_line         = '';
		$shipping_line    = '';
		$total_line       = '';
		$tot_price        = ! empty( $args['quantity'] ) ? $args['price'] * $args['quantity'] : $args['price'];

		if ( ! empty( $args['tax'] ) ) {
			$tax_amount = WPEC_Utility_Functions::get_tax_amount( $args['price'], $args['tax'] ) * $args['quantity'];
			$tot_price += $tax_amount;
			if ( ! empty( $args['price'] ) ) {
				/* translators: tax amount */
				$tax_tag = sprintf( __( '%s (tax)', 'wp-express-checkout' ), '<span class="wpec-tax-val">' . WPEC_Utility_Functions::price_format( $tax_amount ) . '</span>' );
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
				$shipping_tag = sprintf( __( '+ %s (shipping)', 'wp-express-checkout' ), WPEC_Utility_Functions::price_format( $args['shipping'] ) );
			} else {
				/* translators: shipping amount */
				$shipping_tag = sprintf( __( '%s (shipping)', 'wp-express-checkout' ), WPEC_Utility_Functions::price_format( $args['shipping'] ) );
			}
			$shipping_line = '<span class="wpec_price_shipping_section">' . $shipping_tag . '</span>';
		}

		if ( floatval( $tot_price ) !== floatval( $args['price'] ) ) {
			$total_line       = '<div class="wpec_price_full_total">' . esc_html__( 'Total:', 'wp-express-checkout' ) . ' <span class="wpec_tot_current_price">' . esc_html( WPEC_Utility_Functions::price_format( $tot_price ) ) . '</span> <span class="wpec_tot_new_price"></span></div>';
			$under_price_line = '<div class="wpec_under_price_line">' . $tax_line . $shipping_line . $total_line . '</div>';
		}

		$output .= $under_price_line;

		return apply_filters( 'wpec_price_tag', $output, $args );
	}

	/**
	 * Thank You page shortcode.
	 *
	 * @return string
	 */
	public function shortcode_wpec_thank_you() {

		$error_message = '';

		if ( ! isset( $_GET['order_id'] ) ) {
			$error_message .= '<p>' . __( 'This page is used to show the transaction result after a customer makes a payment.', 'wp-express-checkout' ) . '</p>';
			$error_message .= '<p>' . __( 'It will dynamically show the order details to the customers when they are redirected here after a payment. Do not access this page directly.', 'wp-express-checkout' ) . '</p>';
			$error_message .= '<p class="wpec-error-message">' . __( 'Error! Order ID value is missing in the URL.', 'wp-express-checkout' ) . '</p>';
			return $error_message;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'thank_you_url' . $_GET['order_id'] ) ) {
			$error_message .= '<p>' . __( 'This page is used to show the transaction result after a customer makes a payment.', 'wp-express-checkout' ) . '</p>';
			$error_message .= '<p>' . __( 'It will dynamically show the order details to the customers when they are redirected here after a payment. Do not access this page directly.', 'wp-express-checkout' ) . '</p>';
			$error_message .= '<p class="wpec-error-message">' . __( 'Error! Nonce value is missing in the URL or Nonce verification failed.', 'wp-express-checkout' ) . '</p>';
			return $error_message;
		}

		// Retrieve the order data.
		$order_id = (int) $_GET['order_id'];
		$order    = get_post_meta( $order_id, 'ppec_payment_details', true );

		if ( empty( $order ) ) {
			return __( 'Error! Incorrect order ID. Could not find that order in the orders table.', 'wp-express-checkout' );
		}

		if ( 'COMPLETED' !== $order['state'] ) {
			return printf( __( 'Payment is not approved. Status: %s', 'wp-express-checkout' ), $order['state'] );
		}

		$thank_you_msg  = '';
		$thank_you_msg .= '<div class="wpec_thank_you_message">';
		$thank_you_msg .= '<p>' . __( 'Thank you for your purchase.', 'wp-express-checkout' ) . '</p>';

		$thank_you_msg .= '<p>' . __( 'Your purchase details are below:', 'wp-express-checkout' ) . '</p>';
		$thank_you_msg .= '<p>{product_details}</p>';
		$thank_you_msg .= '<p>' . __( 'Transaction ID: ', 'wp-express-checkout' ) . '{transaction_id}</p>';

		$downloads = WPEC_View_Download::get_order_downloads_list( $order_id );

		if ( ! empty( $downloads ) ) {
			$download_var_str  = '';
			$download_var_str .= "<br /><div class='wpec-thank-you-page-download-link'>";
			$download_var_str .= '<span>' . _n( 'Download link', 'Download links', count( $downloads ), 'wp-express-checkout' ) . ':</span><br/>';
			$download_txt      = __( 'Click here to download', 'wp-express-checkout' );
			$link_tpl          = apply_filters( 'wpec_downloads_list_item_template', '%1$s - <a href="%2$s">%3$s</a><br/>' );
			foreach ( $downloads as $name => $download_url ) {
				$download_var_str .= sprintf( $link_tpl, $name, $download_url, $download_txt );
			}
			$download_var_str .= '</div>';

			$thank_you_msg .= $download_var_str;
		}

		$thank_you_msg .= '</div>'; // end .wpec_thank_you_message.

		$args = array(
			'product_details' => self::generate_product_details_tag( $order ),
		);

		// Apply the dynamic tags.
		$thank_you_msg = nl2br( WPEC_Utility_Functions::replace_dynamic_order_tags( $thank_you_msg, $order_id, $args ) );

		// Trigger the filter.
		$thank_you_msg = apply_filters( 'wpec_thank_you_message', $thank_you_msg );

		return $thank_you_msg;
	}

	/**
	 * Generates product details HTML for Thank You page by given order details.
	 *
	 * @since 2.0
	 *
	 * @param array $payment The order details stored in the
	 *                       `ppec_payment_details` meta field.
	 * @return string
	 */
	public static function generate_product_details_tag( $payment ) {
		$output   = '';

		/* translators: {Order Summary Item Name}: {Value} */
		$template = '<div class="wpec-thank-you-page-product-details">' .  __( '%1$s: %2$s', 'wp-express-checkout' ) . '</div>';

		$output .= sprintf( $template, __( 'Product Name', 'wp-express-checkout' ), $payment['item_name'] );
		$output .= sprintf( $template, __( 'Quantity', 'wp-express-checkout' ), $payment['quantity'] );
		$output .= sprintf( $template, __( 'Price', 'wp-express-checkout' ), WPEC_Utility_Functions::price_format( $payment['price'] ) );

		foreach ( $payment['variations'] as $var ) {
			if ( $var[1] < 0 ) {
				$amnt_str = '-' . WPEC_Utility_Functions::price_format( abs( $var[1] ) );
			} else {
				$amnt_str = WPEC_Utility_Functions::price_format( $var[1] );
			}
			$output .= sprintf( $template, $var[0], $amnt_str );
		}

		if ( $payment['discount'] || $payment['tax_total'] || $payment['shipping'] ) {
			$output .= '<hr />';
			$output .= sprintf( $template, __( 'Subtotal', 'wp-express-checkout' ), WPEC_Utility_Functions::price_format( ( $payment['price'] + $payment['var_amount'] ) * $payment['quantity'] ) );
			$output .= '<hr />';
		}

		if ( $payment['discount'] ) {
			$output .= ( $payment['coupon_code'] ) ? sprintf( $template, __( 'Coupon Code', 'wp-express-checkout' ), $payment['coupon_code'] ) : '';
			$output .= ( $payment['discount'] ) ? sprintf( $template, __( 'Discount', 'wp-express-checkout' ), WPEC_Utility_Functions::price_format( $payment['discount'] ) ) : '';
		}

		$output .= ( $payment['tax_total'] ) ? sprintf( $template, __( 'Tax', 'wp-express-checkout' ), WPEC_Utility_Functions::price_format( $payment['tax_total'] ) ) : '';
		$output .= ( $payment['shipping'] ) ? sprintf( $template, __( 'Shipping', 'wp-express-checkout' ), WPEC_Utility_Functions::price_format( $payment['shipping'] ) ) : '';
		$output .= '<hr />';
		$output .= sprintf( $template, __( 'Total Amount', 'wp-express-checkout' ), WPEC_Utility_Functions::price_format( $payment['amount'] ) );

		return $output;
	}

}
