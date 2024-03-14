<?php

namespace WP_Express_Checkout;

use Exception;
use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Products;

class Categories {

	static $CATEGORY_SLUG = 'wpec_categories';

	function __construct() {
		
	}

	public static function register_category_taxonomy() {

		$cap = Main::get_instance()->get_setting( 'access_permission' );
        
        $capabilities = array(
            'manage_terms' 		 => $cap,
            'edit_terms'   		 => $cap,
            'delete_terms'  	 => $cap,
            'assign_terms' 		 => $cap,
        );

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
            'rewrite'           => array( 'slug' => self::$CATEGORY_SLUG ),
            'show_admin_column' => true,
            'capabilities'      => $capabilities,
        );

		//Trigger filter
		$args_tags = apply_filters( 'wpec_product_categories_before_register', $args_tags );
		
        register_taxonomy( self::$CATEGORY_SLUG, array( Products::$products_slug ), $args_tags );

	}

}
