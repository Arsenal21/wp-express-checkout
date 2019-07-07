<?php

class PPECProducts {

    static $products_slug		 = "ppec-products";
    protected static $instance	 = null;

    function __construct() {
	if ( is_admin() ) {
	    //products meta boxes handler
	    require_once(WP_PPEC_PLUGIN_PATH . 'admin/includes/class-products-meta-boxes.php');
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
	    'name'			 => _x( 'Products', 'Post Type General Name', 'paypal-express-checkout' ),
	    'singular_name'		 => _x( 'Product', 'Post Type Singular Name', 'paypal-express-checkout' ),
	    'menu_name'		 => __( 'PayPal Express Checkout', 'paypal-express-checkout' ),
//	    'parent_item_colon'	 => __( 'Parent Order:', 'paypal-express-checkout' ),
	    'all_items'		 => __( 'Products', 'paypal-express-checkout' ),
	    'view_item'		 => __( 'View Product', 'paypal-express-checkout' ),
	    'add_new_item'		 => __( 'Add New Product', 'paypal-express-checkout' ),
	    'add_new'		 => __( 'Add New Product', 'paypal-express-checkout' ),
	    'edit_item'		 => __( 'Edit Product', 'paypal-express-checkout' ),
	    'update_item'		 => __( 'Update Products', 'paypal-express-checkout' ),
	    'search_items'		 => __( 'Search Product', 'paypal-express-checkout' ),
	    'not_found'		 => __( 'Not found', 'paypal-express-checkout' ),
	    'not_found_in_trash'	 => __( 'Not found in Trash', 'paypal-express-checkout' ),
	);
	$menu_icon	 = WP_PPEC_PLUGIN_URL . '/admin/assets/img/ppec-dashicon.png';
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
	    $view_link		 = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View product', 'paypal-express-checkout' ) );
	    $preview_permalink	 = add_query_arg( 'preview', 'true', $permalink );
	    $preview_link		 = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview product', 'paypal-express-checkout' ) );
	    $messages[ $slug ]	 = $messages[ 'post' ];
	    $messages[ $slug ][ 1 ]	 = __( "Product updated.", 'paypal-express-checkout' ) . $view_link;
	    $messages[ $slug ][ 4 ]	 = __( "Product updated.", 'paypal-express-checkout' );
	    $messages[ $slug ][ 6 ]	 = __( "Product published.", 'paypal-express-checkout' ) . $view_link;
	    $messages[ $slug ][ 7 ]	 = __( "Product saved.", 'paypal-express-checkout' );
	    $messages[ $slug ][ 8 ]	 = __( "Product submitted.", 'paypal-express-checkout' ) . $preview_link;
	    $messages[ $slug ][ 10 ] = __( "Product draft updated.", 'paypal-express-checkout' ) . $preview_link;
	}
	return $messages;
    }

    function manage_columns( $columns ) {
	unset( $columns );
	$columns = array(
	    "title"		 => __( 'Product Name', 'paypal-express-checkout' ),
	    "id"		 => __( "ID", 'paypal-express-checkout' ),
	    "price"		 => __( "Price", 'paypal-express-checkout' ),
	    "shortcode"	 => __( "Shortcode", 'paypal-express-checkout' ),
	    "date"		 => __( "Date", 'paypal-express-checkout' ),
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
		<input type="text" name="ppec_product_shortcode" class="ppec-select-on-click" readonly value="[paypal_express_checkout product_id=&quot;<?php echo $post_id; ?>&quot;]">
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
