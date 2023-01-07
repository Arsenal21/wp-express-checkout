<?php

namespace WP_Express_Checkout;

use Exception;
use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Products;

class Tags {

	static $POST_SLUG = 'wpec_tags';

	function __construct() {
		
	}

	public static function register_post_type() {
		
        $labels_tags = array(
            'name'              => __( 'Product Tags', 'wp-express-checkout' ),
            'singular_name'     => __( 'Product Tag', 'wp-express-checkout' ),
            'search_items'      => __( 'Search Tags', 'wp-express-checkout' ),
            'all_items'         => __( 'All Tags', 'wp-express-checkout' ),
            'parent_item'       => __( 'Tags Genre', 'wp-express-checkout' ),
            'parent_item_colon' => __( 'Tags Genre:', 'wp-express-checkout' ),
            'edit_item'         => __( 'Edit Tag', 'wp-express-checkout' ),
            'update_item'       => __( 'Update Tag', 'wp-express-checkout' ),
            'add_new_item'      => __( 'Add New Tag', 'wp-express-checkout' ),
            'new_item_name'     => __( 'New Tag', 'wp-express-checkout' ),
            'menu_name'         => __( 'Tags', 'wp-express-checkout' ),
        );
        $args_tags   = array(
            'hierarchical'      => false,
            'labels'            => $labels_tags,
            'show_ui'           => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => self::$POST_SLUG ),
            'show_admin_column' => true,
        );

        register_taxonomy( self::$POST_SLUG, array( Products::$products_slug ), $args_tags );
        

	}


}
