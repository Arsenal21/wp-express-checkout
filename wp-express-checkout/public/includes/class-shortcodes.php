<?php

namespace WP_Express_Checkout;

use Exception;
use WP_Express_Checkout\Debug\Logger;

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

		//show all product
		add_shortcode( 'wpec_show_all_products', array( $this, 'shortcode_wpec_show_all_products' ) );

		//show all product based on tags / categories
		add_shortcode( 'wpec_show_products_from_category', array( $this, 'shortcode_wpec_show_products_from_category' ) );

		if ( ! is_admin() ) {
			add_filter( 'widget_text', 'do_shortcode' );
		}

		//register scripts for shortcodes
		add_action( 'wp_enqueue_scripts', array( $this, 'register_wpec_script' ) );
	}

	public function register_wpec_script()
	{
		wp_register_style( 'wpec-all-products-css', WPEC_PLUGIN_URL . '/public/views/templates/all-products/style.css', array(), WPEC_PLUGIN_VER );
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
		$button_text     = isset( $atts['button_text'] ) ? $atts['button_text'] : $product->get_button_text();
		$thank_you_url   = ! empty( $atts['thank_you_url'] ) ? $atts['thank_you_url'] : $product->get_thank_you_url();
		$btn_type        = $product->get_button_type();
		$btn_sizes       = array( 'small' => 25, 'medium' => 35, 'large' => 45, 'xlarge' => 55 );
		$btn_height      = $this->ppdg->get_setting( 'btn_height' );

		$output = '';

		$args = array(
			'name'            => get_the_title( $post_id ),
			'price'           => $product->get_price(),
			'shipping'        => $product->get_shipping(),
			'shipping_per_quantity' => $product->get_shipping_per_quantity(),
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
				'shipping_per_quantity' => 0,
				'shipping_enable' => 0,
				'is_digital_product' => $product->is_digital_product(),
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
				'stock_enabled'   => $product->is_stock_control_enabled(),
				'stock_items'     => $product->get_stock_items(),
				'price_class'     => isset( $atts['price_class'] ) ? $atts['price_class'] : 'wpec-price-' . substr( sha1( time() . mt_rand( 0, 1000 ) ), 0, 10 ),
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
			//Load the defalt template
			$located  = self::locate_template( "content-product-default.php" );
			ob_start();
			load_template( $located, false );
			$output .= ob_get_clean();			
		}
		wp_reset_postdata();

		return $output;
	}

	function generate_pp_express_checkout_button( $args ) {

		extract( $args );

		if ( $stock_enabled && empty( $stock_items ) ) {
			return '<div class="wpec-out-of-stock">' . esc_html( 'Out of stock', 'wp-express-checkout' ) . '</div>';
		}

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
			'shipping_per_quantity' => $shipping_per_quantity,
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
			'shipping_per_quantity' => $shipping_per_quantity,
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
			'is_manual_checkout_enabled' => !empty($this->ppdg->get_setting('enable_manual_checkout')),
		) );

		$output .= '<script type="text/javascript">var wpec_' . esc_attr( $button_id ) . '_data=' . json_encode( $data ) . ';document.addEventListener( "wpec_paypal_sdk_loaded", function() { new ppecHandler(wpec_' . esc_attr( $button_id ) . '_data); } );</script>';

		add_action( 'wp_footer', array( $this->ppdg, 'load_paypal_sdk' ) );

		return $output;
	}

	public function generate_price_tag( $args ) {		
		$args['price']	= floatval($args['price']);
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

		$shipping_cost = Utils::get_total_shipping_cost( $args );
		if ( ! empty( $shipping_cost ) ) {
			$tot_price += $shipping_cost;
			$formatted_shipping_cost =  Utils::price_format( $shipping_cost );
			if ( ! empty( $args['tax'] ) ) {
				/* translators: tax + shipping amount */
				$shipping_tag = sprintf( __( '+ <span class="wpec_price_shipping_amount">%s</span> (shipping)', 'wp-express-checkout' ), $formatted_shipping_cost );
			} else {
				/* translators: shipping amount */
				$shipping_tag = sprintf( __( '<span class="wpec_price_shipping_amount">%s</span> (shipping)', 'wp-express-checkout' ), $formatted_shipping_cost );
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

		// Trigger the action.
		do_action( 'before_wpec_thank_you_page_shortcode_execution' );

		// Get the order.
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

	public function shortcode_wpec_show_all_products($params=array())
	{
		
		$params = shortcode_atts(
			array(
				'items_per_page' => '30',
				'sort_by'        => 'ID',
				'sort_order'     => 'DESC',
				'template'       => '',
				'search_box'     => '1',
			),
			$params,
			'wpec_show_all_products'
		);

		//if user has changed sort by from UI
		$sort_by = isset( $_GET['wpec-sortby'] ) ? sanitize_text_field( stripslashes ( $_GET['wpec-sortby'] ) ) : '';

		include_once WPEC_PLUGIN_PATH . 'public/views/templates/all-products/all-products.php';

		$page = filter_input( INPUT_GET, 'wpec_page', FILTER_SANITIZE_NUMBER_INT );

		$page = empty( $page ) ? 1 : $page;		

		$order_by = isset( $params['sort_by'] ) ? ( $params['sort_by'] ) : 'none';

		
		$sort_direction = isset( $params['sort_order'] ) ? strtoupper( $params['sort_order'] ) : 'DESC';

		if($sort_by)
		{
			$order_by=explode("-",$sort_by)[0];
			$sort_direction=isset(explode("-",$sort_by)[1])?explode("-",$sort_by)[1]:"asc";
		}
		

		$q = array(
			'post_type'      => Products::$products_slug,
			'post_status'    => 'publish',
			'posts_per_page' => isset( $params['items_per_page'] ) ? $params['items_per_page'] : 30,
			'paged'          => $page,
			'orderby'        => $order_by,
			'order'          => $sort_direction,
		);
		
		//handle search

		$search = isset( $_GET['wpec_search'] ) ? sanitize_text_field( stripslashes ( $_GET['wpec_search'] ) ) : '';

		$search = empty( $search ) ? false : $search;

		if ( $search !== false ) {
			$q['s'] = $search;
		}

		$products = Products::retrieve_all_active_products($q,$search);


		$search_box = ! empty( $params['search_box'] ) ? $params['search_box'] : false;

		if ( $search_box ) {
			if ( $search !== false ) {
				$tpl['clear_search_url']   = esc_url( remove_query_arg( array( 'wpec_search', 'wpec_page' ) ) );
				$tpl['search_result_text'] = $products->found_posts === 0 ? __( 'Nothing found for', 'wp-express-checkout' ) . ' "%s".' : __( 'Search results for', 'wp-express-checkout' ) . ' "%s".';
				$tpl['search_result_text'] = sprintf( $tpl['search_result_text'], htmlentities( $search ) );
				$tpl['search_term']        = htmlentities( $search );
			} else {
				$tpl['search_result_text']  = '';
				$tpl['clear_search_button'] = '';
				$tpl['search_term']         = '';
			}
		} else {
			$tpl['search_box'] = '';
		}

		

		$tpl['products_list'] .= $tpl['products_row_start'];
		$i                     = $tpl['products_per_row']; //items per row

		while ( $products->have_posts() ) {
			$products->the_post();	
			$product;		
			
			$i --;
			if ( $i < 0 ) { //new row
				$tpl['products_list'] .= $tpl['products_row_end'];
				$tpl['products_list'] .= $tpl['products_row_start'];
				$i                     = $tpl['products_per_row'] - 1;
			}

			$id = get_the_ID();
			

			try {
				$product = Products::retrieve( $id );				
			} catch ( Exception $exc ) {
				return $this->show_err_msg( $exc->getMessage() );								
			}

			$thumb_url = $product->get_thumbnail_url();


			if ( ! $thumb_url ) {
				$thumb_url = WPEC_PLUGIN_URL . '/assets/img/product-thumb-placeholder.png';
			}

			$view_btn = str_replace( '%[product_url]%', get_permalink(), $tpl['view_product_btn'] );

			$price = $product->get_price();			
			
			$price_args = array_merge(
				array(
					'price'           => 0,
					'shipping'        => 0,
					'tax'             => 0,
					'quantity'        => 1,
				),
				array(
					'name'            => get_the_title( $id ),
					'price'           => (float) $product->get_price(),
					'shipping'        => $product->get_shipping(),
					'shipping_per_quantity' => $product->get_shipping_per_quantity(),
					'tax'             => $product->get_tax(),
					'quantity'        => $product->get_quantity(),
					'product_id'      => $id,
				)
			);

			
			$price = $this->generate_price_tag( $price_args );

			$item = str_replace(
				array(
					'%[product_id]%',
					'%[product_name]%',
					'%[product_thumb]%',
					'%[view_product_btn]%',
					'%[product_price]%',
				),
				array(
					$id,
					get_the_title(),
					$thumb_url,
					$view_btn,
					$price,
				),
				$tpl['products_item']
			);

			$tpl['products_list'] .= $item;
		}

		$tpl['products_list'] .= $tpl['products_row_end'];

		//pagination

		$tpl['pagination_items'] = '';

		$pages = $products->max_num_pages;

		if ( $pages > 1 ) {
			$i = 1;

			while ( $i <= $pages ) {
				if ( $i != $page ) {
					$url = esc_url( add_query_arg( 'wpec_page', $i ) );
					$str = str_replace( array( '%[url]%', '%[page_num]%' ), array( $url, $i ), $tpl['pagination_item'] );
				} else {
					$str = str_replace( '%[page_num]%', $i, $tpl['pagination_item_current'] );
				}
				$tpl['pagination_items'] .= $str;
				$i ++;
			}
		}

		if ( empty( $tpl['pagination_items'] ) ) {
			$tpl['pagination'] = '';
		}

		wp_reset_postdata();

		//Build template
		foreach ( $tpl as $key => $value ) {
			$tpl['page'] = str_replace( '_%' . $key . '%_', $value, $tpl['page'] );
		}

		$output = '<div class="wpec_shop_products">'.$tpl['page'].'</div>';
		return $output;
	}

	public function shortcode_wpec_show_products_from_category($params = array())
	{
		$params=shortcode_atts(
			array(
				'items_per_page' => '30',
				'sort_by'        => 'ID',
				'sort_order'     => 'DESC',
				'template'       => '1',
				'search_box'     => '1',
				'category_slug' => '',
				'tag_slug'	=> ''
			),
			$params,
			'wpec_show_products_from_category'
		);

		include_once WPEC_PLUGIN_PATH . 'public/views/templates/all-products/all-products-from-category.php';
		
		$page = filter_input(INPUT_GET, 'wpec_page', FILTER_SANITIZE_NUMBER_INT);

		$page = empty($page) ? 1 : $page;

		$wp_tax_query = array("relation" => "or");

		$category_slugs = isset($params['category_slug']) ? $params['category_slug'] : false;

		if ($category_slugs) {
			$category_slugs = explode(",", $category_slugs);

			foreach ($category_slugs as $cat_slug) {
				array_push(
					$wp_tax_query,
					array(
						'taxonomy' => Categories::$CATEGORY_SLUG,
						'field' => 'slug',
						'terms' => $cat_slug
					)
				);
			}
		}

		$tag_slugs = isset($params['tag_slug']) ? $params['tag_slug'] : false;

		if ($tag_slugs) {
			$tag_slugs = explode(",", $tag_slugs);

			foreach ($tag_slugs as $tag_slug) {
				array_push(
					$wp_tax_query,
					array(
						'taxonomy' => Tags::$TAGS_SLUG,
						'field' => 'slug',
						'terms' => $tag_slug
					)
				);
			}
		}

		
		$q = array(
			'post_type'      => Products::$products_slug,			
			'post_status'    => 'publish',
			'posts_per_page' => isset($params['items_per_page']) ? $params['items_per_page'] : 30,
			'paged'          => $page,
			'orderby'        => isset($params['sort_by']) ? ($params['sort_by']) : 'none',
			'order'          => isset($params['sort_order']) ? strtoupper($params['sort_order']) : 'DESC',
			'tax_query' => $wp_tax_query
		);


		//handle search
		$search = isset( $_GET['wpec_search'] ) ? sanitize_text_field( stripslashes ( $_GET['wpec_search'] ) ) : '';

		$search = empty($search) ? false : $search;

		if ($search !== false) {
			$q['s'] = $search;
		}

		$products = Products::retrieve_all_active_products($q, $search);

		$search_box = !empty($params['search_box']) ? $params['search_box'] : false;

		if ($search_box) {
			if ($search !== false) {
				$tpl['clear_search_url']   = esc_url(remove_query_arg(array('wpec_search', 'wpec_page')));
				$tpl['search_result_text'] = $products->found_posts === 0 ? __('Nothing found for', 'wp-express-checkout') . ' "%s".' : __('Search results for', 'wp-express-checkout') . ' "%s".';
				$tpl['search_result_text'] = sprintf($tpl['search_result_text'], htmlentities($search));
				$tpl['search_term']        = htmlentities($search);
			} else {
				$tpl['search_result_text']  = '';
				$tpl['clear_search_button'] = '';
				$tpl['search_term']         = '';
			}
		} else {
			$tpl['search_box'] = '';
		}

		$tpl['products_list'] .= $tpl['products_row_start'];
		$i                     = $tpl['products_per_row']; //items per row

		while ($products->have_posts()) {
			$products->the_post();
			$product;

			$i--;
			if ($i < 0) { //new row
				$tpl['products_list'] .= $tpl['products_row_end'];
				$tpl['products_list'] .= $tpl['products_row_start'];
				$i                     = $tpl['products_per_row'] - 1;
			}

			$id = get_the_ID();


			try {
				$product = Products::retrieve($id);
			} catch (Exception $exc) {
				return $this->show_err_msg($exc->getMessage());
			}

			$thumb_url = $product->get_thumbnail_url();


			if (!$thumb_url) {
				$thumb_url = WPEC_PLUGIN_URL . '/assets/img/product-thumb-placeholder.png';
			}

			$view_btn = str_replace('%[product_url]%', get_permalink(), $tpl['view_product_btn']);

			$price = $product->get_price();

			$price_args = array_merge(
				array(
					'price'           => 0,
					'shipping'        => 0,
					'tax'             => 0,
					'quantity'        => 1,
				),
				array(
					'name'            => get_the_title( $id ),
					'price'           => (float) $product->get_price(),
					'shipping'        => $product->get_shipping(),
					'shipping_per_quantity' => $product->get_shipping_per_quantity(),
					'tax'             => $product->get_tax(),
					'quantity'        => $product->get_quantity(),
					'product_id'      => $id,
				)
			);
			
			$price = $this->generate_price_tag( $price_args );
			
			$item = str_replace(
				array(
					'%[product_id]%',
					'%[product_name]%',
					'%[product_thumb]%',
					'%[view_product_btn]%',
					'%[product_price]%',
				),
				array(
					$id,
					get_the_title(),
					$thumb_url,
					$view_btn,
					$price,
				),
				$tpl['products_item']
			);

			$tpl['products_list'] .= $item;
		}

		$tpl['products_list'] .= $tpl['products_row_end'];

		//pagination

		$tpl['pagination_items'] = '';

		$pages = $products->max_num_pages;

		if ($pages > 1) {
			$i = 1;

			while ($i <= $pages) {
				if ($i != $page) {
					$url = esc_url(add_query_arg('wpec_page', $i));
					$str = str_replace(array('%[url]%', '%[page_num]%'), array($url, $i), $tpl['pagination_item']);
				} else {
					$str = str_replace('%[page_num]%', $i, $tpl['pagination_item_current']);
				}
				$tpl['pagination_items'] .= $str;
				$i++;
			}
		}

		if (empty($tpl['pagination_items'])) {
			$tpl['pagination'] = '';
		}

		wp_reset_postdata();

		//Build template
		foreach ($tpl as $key => $value) {
			$tpl['page'] = str_replace('_%' . $key . '%_', $value, $tpl['page']);
		}

		$output = '<div class="wpec_shop_products">'.$tpl['page'].'</div>';
		return $output;
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