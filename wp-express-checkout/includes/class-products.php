<?php

namespace WP_Express_Checkout;

use Exception;
use WP_Express_Checkout\Products\Donation_Product;
use WP_Express_Checkout\Products\One_Time_Product;
use WP_Express_Checkout\Products\Product;
use WP_Post;

class Products {

	static $products_slug = "ppec-products";

	public static function register_post_type() {

		// Products post type.
		$labels = array(
			'name'               => _x( 'Products', 'Post Type General Name', 'wp-express-checkout' ),
			'singular_name'      => _x( 'Product', 'Post Type Singular Name', 'wp-express-checkout' ),
			'menu_name'          => __( 'WP Express Checkout', 'wp-express-checkout' ),
			//'parent_item_colon'	 => __( 'Parent Product:', 'wp-express-checkout' ),
			'all_items'          => __( 'Products', 'wp-express-checkout' ),
			'view_item'          => __( 'View Product', 'wp-express-checkout' ),
			'add_new_item'       => __( 'Add New Product', 'wp-express-checkout' ),
			'add_new'            => __( 'Add New Product', 'wp-express-checkout' ),
			'edit_item'          => __( 'Edit Product', 'wp-express-checkout' ),
			'update_item'        => __( 'Update Products', 'wp-express-checkout' ),
			'search_items'       => __( 'Search Product', 'wp-express-checkout' ),
			'not_found'          => __( 'Not found', 'wp-express-checkout' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'wp-express-checkout' ),
		);

		$cap = Main::get_instance()->get_setting( 'access_permission' );

		$capabilities = array(
			'edit_post'          => $cap,
			'delete_post'        => $cap,
			'read_post'          => $cap,
			'edit_posts'         => $cap,
			'edit_others_posts'  => $cap,
			'delete_posts'       => $cap,
			'publish_posts'      => $cap,
			'read_private_posts' => $cap
		);

		// Using custom dashicon font icon.
		$menu_icon = 'dashicons-wpec-dashicon-1';

		$slug = untrailingslashit( self::$products_slug );
		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'capability_type'    => 'post',
			'capabilities'       => $capabilities,
			'query_var'          => true,
			'has_archive'        => false,
			'hierarchical'       => false,
			'rewrite'            => array( 'slug' => $slug ),
			'supports'           => array( 'title' ),
			'show_ui'            => true,
			'show_in_nav_menus'  => true,
			'show_in_admin_bar'  => true,
			'menu_position'      => 80,
			'menu_icon'          => $menu_icon,
		);

		register_post_type( self::$products_slug, $args );
	}

	/**
	 * Retrieves an existing produt by ID.
	 *
	 * @param int $product_id Product ID
	 *
	 * @return Product Product Object representing the product.
	 *
	 * @throws Exception
	 */
	static public function retrieve( $product_id ) {

		if ( ! is_numeric( $product_id ) ) {
			throw new Exception( __( 'Invalid product id given. Must be an integer', 'wp-express-checkout' ), 1001 );
		}

		$product_data = get_post( $product_id );
		if ( ! $product_data || $product_data->post_type !== self::$products_slug ) {
			throw new Exception( sprintf( __( "Can't find product with ID %s", 'wp-express-checkout' ), $product_id ), 1002 );
		}

		if ( ! empty( $product_data->wpec_product_type ) ) {
			$product_type = $product_data->wpec_product_type;
		} elseif ( ! empty( $product_data->wpec_product_custom_amount ) ) {
			// Check Custom amount for backward compatibility.
			$product_type = 'donation';
		} else {
			$product_type = 'one_time';
		}

		/**
		 * Filter for setting an extended Product type object .
		 *
		 * Dynamic portion of the name refers to a product type.
		 *
		 * @param WP_Post|Product $object The product object. Default WP object.
		 */
		$product = apply_filters( "wpec_product_type_{$product_type}", $product_data );

		if ( $product instanceof Product ) {
			return $product;
		}

		switch ( $product_type ) {
			case 'one_time':
				$product = new One_Time_Product( $product_data );
				break;
			case 'donation':
				$product = new Donation_Product( $product_data );
				break;
			default:
				throw new Exception( sprintf( __( "Unknown product type '%s'. Activate the required addon to use this product type.", 'wp-express-checkout' ), $product_data->wpec_product_type ), 1003 );
		}

		return $product;
	}

}
