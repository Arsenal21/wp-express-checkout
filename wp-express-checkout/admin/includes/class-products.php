<?php

class PPECProducts {

    static $products_slug		 = "ppec-products";
    protected static $instance	 = null;

    function __construct() {
	if ( is_admin() ) {
	    //products meta boxes handler
	    require_once(WPEC_PLUGIN_PATH . 'admin/includes/class-products-meta-boxes.php');
	}
    }

    public static function get_instance() {

	// If the single instance hasn't been set, set it now.
	if ( null == self::$instance ) {
	    self::$instance = new self;
	}

	return self::$instance;
    }

    function register_post_type() {

	// Products post type
	$labels		 = array(
	    'name'			 => _x( 'Products', 'Post Type General Name', 'wp-express-checkout' ),
	    'singular_name'		 => _x( 'Product', 'Post Type Singular Name', 'wp-express-checkout' ),
	    'menu_name'		 => __( 'WP Express Checkout', 'wp-express-checkout' ),
//	    'parent_item_colon'	 => __( 'Parent Order:', 'wp-express-checkout' ),
	    'all_items'		 => __( 'Products', 'wp-express-checkout' ),
	    'view_item'		 => __( 'View Product', 'wp-express-checkout' ),
	    'add_new_item'		 => __( 'Add New Product', 'wp-express-checkout' ),
	    'add_new'		 => __( 'Add New Product', 'wp-express-checkout' ),
	    'edit_item'		 => __( 'Edit Product', 'wp-express-checkout' ),
	    'update_item'		 => __( 'Update Products', 'wp-express-checkout' ),
	    'search_items'		 => __( 'Search Product', 'wp-express-checkout' ),
	    'not_found'		 => __( 'Not found', 'wp-express-checkout' ),
	    'not_found_in_trash'	 => __( 'Not found in Trash', 'wp-express-checkout' ),
	);
        
        //Using custom dashicon font icon.
	$menu_icon	 = 'dashicons-wpec-dashicon-1'; //WPEC_PLUGIN_URL . '/admin/assets/img/wpec-dashicon.png';
        
	$slug		 = untrailingslashit( self::$products_slug );
	$args		 = array(
	    'labels'		 => $labels,
	    'capability_type'	 => 'post',
	    'public'		 => true,
	    'publicly_queryable'	 => true,
	    'capability_type'	 => 'post',
	    'query_var'		 => true,
	    'has_archive'		 => false,
	    'hierarchical'		 => false,
	    'rewrite'		 => array( 'slug' => $slug ),
	    'supports'		 => array( 'title' ),
	    'show_ui'		 => true,
	    'show_in_nav_menus'	 => true,
	    'show_in_admin_bar'	 => true,
	    'menu_position'		 => 80,
	    'menu_icon'		 => $menu_icon
	);

	register_post_type( self::$products_slug, $args );

	//add custom columns for list view
	add_filter( 'manage_' . self::$products_slug . '_posts_columns', array( $this, 'manage_columns' ) );
	add_action( 'manage_' . self::$products_slug . '_posts_custom_column', array( $this, 'manage_custom_columns' ), 10, 2 );
	//set custom columns sortable
	add_filter( 'manage_edit-' . self::$products_slug . '_sortable_columns', array( $this, 'manage_sortable_columns' ) );
	//set custom messages on post save\update etc.
	add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
    }

    function post_updated_messages( $messages ) {
	$post		 = get_post();
	$post_type	 = get_post_type( $post );
	$slug		 = self::$products_slug;
	if ( $post_type === self::$products_slug ) {
	    $permalink		 = get_permalink( $post->ID );
	    $view_link		 = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View product', 'wp-express-checkout' ) );
	    $preview_permalink	 = add_query_arg( 'preview', 'true', $permalink );
	    $preview_link		 = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview product', 'wp-express-checkout' ) );
	    $messages[ $slug ]	 = $messages[ 'post' ];
	    $messages[ $slug ][ 1 ]	 = __( "Product updated.", 'wp-express-checkout' ) . $view_link;
	    $messages[ $slug ][ 4 ]	 = __( "Product updated.", 'wp-express-checkout' );
	    $messages[ $slug ][ 6 ]	 = __( "Product published.", 'wp-express-checkout' ) . $view_link;
	    $messages[ $slug ][ 7 ]	 = __( "Product saved.", 'wp-express-checkout' );
	    $messages[ $slug ][ 8 ]	 = __( "Product submitted.", 'wp-express-checkout' ) . $preview_link;
	    $messages[ $slug ][ 10 ] = __( "Product draft updated.", 'wp-express-checkout' ) . $preview_link;
	}
	return $messages;
    }

    function manage_columns( $columns ) {
	unset( $columns );
	$columns = array(
	    "title"		 => __( 'Product Name', 'wp-express-checkout' ),
	    "id"		 => __( "ID", 'wp-express-checkout' ),
	    "price"		 => __( "Price", 'wp-express-checkout' ),
	    "shortcode"	 => __( "Shortcode", 'wp-express-checkout' ),
	    "date"		 => __( "Date", 'wp-express-checkout' ),
	);
	return $columns;
    }

    function manage_custom_columns( $column, $post_id ) {
	switch ( $column ) {
	    case 'id':
		echo $post_id;
		break;
	    case 'price':
		$price = get_post_meta( $post_id, 'ppec_product_price', true );
		if ( $price ) {
		    $output = $price;
		} else {
		    $output = "Invalid";
		}
		$output = apply_filters( 'ppec_products_table_price_column', $output, $price, $post_id );
		echo $output;
		break;
	    case 'shortcode':
		?>
		<input type="text" name="ppec_product_shortcode" class="ppec-select-on-click" readonly value="[wp_express_checkout product_id=&quot;<?php echo $post_id; ?>&quot;]">
		<?php
		break;
	}
    }

    function manage_sortable_columns( $columns ) {
	$columns[ 'id' ]	 = 'id';
	$columns[ 'price' ]	 = 'price';
	return $columns;
    }

}
