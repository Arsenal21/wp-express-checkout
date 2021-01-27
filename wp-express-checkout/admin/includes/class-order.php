<?php

/**
 * Orders post type register and factory.
 */
class OrdersWPEC {

	/**
	 * Order post type
	 *
	 * @since 2.0.0
	 */
	const PTYPE = 'ppdgorder';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Registers order post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name' => _x( 'Orders', 'Post Type General Name', 'wp-express-checkout' ),
			'singular_name' => _x( 'Order', 'Post Type Singular Name', 'wp-express-checkout' ),
			'menu_name' => __( 'Digital Goods Orders', 'wp-express-checkout' ),
			'parent_item_colon' => __( 'Parent Order:', 'wp-express-checkout' ),
			'all_items' => __( 'Orders', 'wp-express-checkout' ),
			'view_item' => __( 'View Order', 'wp-express-checkout' ),
			'add_new_item' => __( 'Add New Order', 'wp-express-checkout' ),
			'add_new' => __( 'Add New', 'wp-express-checkout' ),
			'edit_item' => __( 'Edit Order', 'wp-express-checkout' ),
			'update_item' => __( 'Update Order', 'wp-express-checkout' ),
			'search_items' => __( 'Search Order', 'wp-express-checkout' ),
			'not_found' => __( 'Not found', 'wp-express-checkout' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'wp-express-checkout' ),
		);
		$args = array(
			'label' => __( 'orders', 'wp-express-checkout' ),
			'description' => __( 'WPEC Orders', 'wp-express-checkout' ),
			'labels' => $labels,
			'supports' => array( 'title', 'editor', 'excerpt', 'revisions', 'custom-fields', ),
			'hierarchical' => false,
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => 'edit.php?post_type=' . PPECProducts::$products_slug,
			'show_in_nav_menus' => true,
			'show_in_admin_bar' => true,
			'menu_position' => 80,
			'menu_icon' => 'dashicons-clipboard',
			'can_export' => true,
			'has_archive' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => false,
			'capability_type' => 'post',
			'capabilities' => array(
				'create_posts' => false, // Removes support for the "Add New" function
			),
			'map_meta_cap' => true,
		);

		register_post_type( self::PTYPE, $args );
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
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Receive Response of GetExpressCheckout and ConfirmPayment function returned data.
	 * Returns the order ID.
	 *
	 * @since     1.0.0
	 *
	 * @param WPEC_Order $order The order object.
	 *
	 * @return    Numeric    Post or Order ID.
	 */
	public function insert( $order ) {
		$post  = array();
		$payer = $order->get_data( 'payer' );

		/* translators: Order Summary Item Name: Value */
		$template = __( '%1$s: %2$s', 'wp-express-checkout' );

		$output = '';

		$output .= __( '<h2>Order Details</h2>' ) . "\n";
		$output .= sprintf( $template, __( 'Order Time' ), date( 'F j, Y, g:i a', get_post_datetime() ) ) . "\n";
		$output .= sprintf( $template, __( 'Transaction ID' ), $order->get_data( 'transaction_id' ) ) . "\n";
		$output .= '--------------------------------' . "\n";

		$output .= WPEC_Utility_Functions::get_product_details( $order );
		$output .= "\n\n";

		$output .= __( '<h2>Customer Details</h2>' ) . "\n";
		$output .= sprintf( $template, __( 'Name' ), $payer['name']['given_name'] . ' ' . $payer['name']['surname'] ) . "\n";
		$output .= sprintf( $template, __( 'Payer ID' ), $payer['payer_id'] ) . "\n";
		$output .= sprintf( $template, __( 'E-Mail Address' ), $payer['email_address'] ) . "\n";
		$output .= sprintf( $template, __( 'Country Code' ), $payer['address']['country_code'] ) . "\n";

		$post['ID'] = $order->get_id();
		$post['post_content'] = $output;

		wp_update_post( $post );
	}

	/**
	 * Creates and returns a new Order.
	 *
	 * @param string $description (optional)
	 *
	 * @return bool|WPEC_Order New Order object. Boolean False on failure.
	 */
	static public function create( $description = '' ) {
		if ( empty( $description ) ) {
			$description = __( 'Transaction', 'wp-express-checkout' );
		}

		$id = wp_insert_post(
			array(
				'post_title' => $description,
				'post_content' => __( 'Transaction Data', 'wp-express-checkout' ),
				'post_type' => self::PTYPE,
				'post_status' => 'publish',
			)
		);

		if ( ! $id ) {
			return false;
		}

		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			add_post_meta( $id, 'ip_address', $_SERVER['REMOTE_ADDR'], true );
		}

		wp_update_post( array(
			'ID' => $id,
			'post_name' => $id
		) );

		$order = self::retrieve( $id );

		do_action( 'wpec_create_order_draft', $order );

		return $order;
	}

	/**
	 * Retrieves an existing order by ID.
	 *
	 * @param int $order_id Order ID
	 *
	 * @return object|bool WPEC_Order Object representing the order. Boolean False on failure.
	 */
	static public function retrieve( $order_id ) {

		if ( ! is_numeric( $order_id ) ) {
			trigger_error( 'Invalid order id given. Must be an integer', E_USER_WARNING );
			return false;
		}

		$order_data = get_post( $order_id );
		if ( ! $order_data || $order_data->post_type !== self::PTYPE ) {
			return false;
		}

		$order = new WPEC_Order( $order_data );

		// Maybe upgrade the order to version 2.0.0
		if ( ! is_numeric( $order_data->post_name ) ) {
			self::upgrade_legacy( $order );
		}

		return $order;
	}

	/**
	 * Upgrades legacy order.
	 *
	 * @since 2.0.0
	 *
	 * @param WPEC_Order $order
	 * @return type
	 */
	private static function upgrade_legacy( $order ) {

		$data = get_post_meta( $order->get_id(), 'ppec_payment_details', true );
		$user = get_post_meta( $order->get_id(), 'ppec_payer_details', true );

		if ( empty( $data ) ) {
			return $order;
		}

		$defaults = array(
			'item_id'     => 0,
			'item_name'   => '',
			'price'       => 0,
			'quantity'    => 1,
			'tax'         => 0,
			'tax_total'   => 0,
			'shipping'    => 0,
			'amount'      => 0,
			'discount'    => 0,
			'coupon_code' => '',
			'currency'    => 'USD',
			'state'       => '',
			'id'          => '',
			'variations'  => array(),
			'var_applied' => array(),
			'var_amount'  => 0,
		);

		$data = array_merge( $defaults, $data );

		$order->set_currency( $data['currency'] );
		$order->add_item( PPECProducts::$products_slug, $data['item_name'], $data['price'], $data['quantity'], $data['item_id'], true );
		$order->add_data( 'transaction_id', $data['id'] );
		$order->add_data( 'state', $data['state'] );
		$order->add_data( 'payer', $user );

		foreach ( $data['var_applied'] as $var ) {
			if ( ! empty( $var ) ) {
				$order->add_item(
					'variation',
					$var['group_name'] . ' - ' . $var['name'],
					$var['price'],
					$data['quantity'],
					$data['item_id'],
					false,
					array(
						'id'     => $var['id'],
						'grp_id' => $var['grp_id'],
						'url'    => $var['url'],
					)
				);
			}
		}
		if ( $data['tax_total'] ) {
			$order->add_item( 'tax', __( 'Tax', 'wp-express-checkout' ), $data['tax_total'] );
		}
		if ( $data['shipping'] ) {
			$order->add_item( 'shipping', __( 'Shipping', 'wp-express-checkout' ), $data['shipping'] );
		}
		if ( $data['coupon_code'] ) {
			$order->add_item( 'coupon', sprintf( __( 'Coupon Code: %s', 'wp-express-checkout' ), $data['coupon_code'] ), abs( $data['discount'] ) * -1, 1, false, array( 'code' => $data['coupon_code'] ) );
		}

		wp_update_post( array(
			'ID' => $order->get_id(),
			'post_name' => $order->get_id(),
		) );

		return $order;
	}

}
