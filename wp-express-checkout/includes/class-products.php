<?php

namespace WP_Express_Checkout;

class Products {

	static $products_slug = "ppec-products";

	public static function register_post_type() {

		// Products post type.
		$labels = array(
			'name'               => _x( 'Products', 'Post Type General Name', 'wp-express-checkout' ),
			'singular_name'      => _x( 'Product', 'Post Type Singular Name', 'wp-express-checkout' ),
			'menu_name'          => __( 'WP Express Checkout', 'wp-express-checkout' ),
			//'parent_item_colon'	 => __( 'Parent Order:', 'wp-express-checkout' ),
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

		// Using custom dashicon font icon.
		$menu_icon = 'dashicons-wpec-dashicon-1';

		$slug = untrailingslashit( self::$products_slug );
		$args = array(
			'labels'             => $labels,
			'capability_type'    => 'post',
			'public'             => true,
			'publicly_queryable' => true,
			'capability_type'    => 'post',
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

}
