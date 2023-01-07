<?php

namespace WP_Express_Checkout;

use Exception;
use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Products;

class Categories {

	static $POST_SLUG = 'wpec_categories';

	function __construct() {
		
	}

	public static function register_post_type() {
		
        $labels_tags = array(
            'name'              => __( 'Product Categories', 'wp-express-checkout' ),
            'singular_name'     => __( 'Product Category', 'wp-express-checkout' ),
            'search_items'      => __( 'Search Categories', 'wp-express-checkout' ),
            'all_items'         => __( 'All Categories', 'wp-express-checkout' ),
            'parent_item'       => __( 'Categories Genre', 'wp-express-checkout' ),
            'parent_item_colon' => __( 'Categories Genre:', 'wp-express-checkout' ),
            'edit_item'         => __( 'Edit Category', 'wp-express-checkout' ),
            'update_item'       => __( 'Update Category', 'wp-express-checkout' ),
            'add_new_item'      => __( 'Add New Category', 'wp-express-checkout' ),
            'new_item_name'     => __( 'New Category', 'wp-express-checkout' ),
            'menu_name'         => __( 'Categories', 'wp-express-checkout' ),
        );
        $args_tags   = array(
            'hierarchical'      => true,
            'labels'            => $labels_tags,
            'show_ui'           => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => self::$POST_SLUG ),
            'show_admin_column' => true,
        );

        register_taxonomy( self::$POST_SLUG, array( Products::$products_slug ), $args_tags );

	}


}
