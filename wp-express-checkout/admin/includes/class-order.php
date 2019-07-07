<?php

class OrdersPPDG {

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;

    function __construct() {
	$this->ppdg		 = PPDG::get_instance();
	$this->text_domain	 = $this->ppdg->get_plugin_slug();
    }

    public function register_post_type() {
	$labels	 = array(
	    'name'			 => _x( 'Orders', 'Post Type General Name', 'paypal-express-checkout' ),
	    'singular_name'		 => _x( 'Order', 'Post Type Singular Name', 'paypal-express-checkout' ),
	    'menu_name'		 => __( 'Digital Goods Orders', 'paypal-express-checkout' ),
	    'parent_item_colon'	 => __( 'Parent Order:', 'paypal-express-checkout' ),
	    'all_items'		 => __( 'Orders', 'paypal-express-checkout' ),
	    'view_item'		 => __( 'View Order', 'paypal-express-checkout' ),
	    'add_new_item'		 => __( 'Add New Order', 'paypal-express-checkout' ),
	    'add_new'		 => __( 'Add New', 'paypal-express-checkout' ),
	    'edit_item'		 => __( 'Edit Order', 'paypal-express-checkout' ),
	    'update_item'		 => __( 'Update Order', 'paypal-express-checkout' ),
	    'search_items'		 => __( 'Search Order', 'paypal-express-checkout' ),
	    'not_found'		 => __( 'Not found', 'paypal-express-checkout' ),
	    'not_found_in_trash'	 => __( 'Not found in Trash', 'paypal-express-checkout' ),
	);
	$args	 = array(
	    'label'			 => __( 'orders', 'paypal-express-checkout' ),
	    'description'		 => __( 'PPDG Orders', 'paypal-express-checkout' ),
	    'labels'		 => $labels,
	    'supports'		 => array( 'title', 'editor', 'excerpt', 'revisions', 'custom-fields', ),
	    'hierarchical'		 => false,
	    'public'		 => false,
	    'show_ui'		 => true,
	    'show_in_menu'		 => 'edit.php?post_type=' . PPECProducts::$products_slug,
	    'show_in_nav_menus'	 => true,
	    'show_in_admin_bar'	 => true,
	    'menu_position'		 => 80,
	    'menu_icon'		 => 'dashicons-clipboard',
	    'can_export'		 => true,
	    'has_archive'		 => false,
	    'exclude_from_search'	 => true,
	    'publicly_queryable'	 => false,
	    'capability_type'	 => 'post',
	    'capabilities'		 => array(
		'create_posts' => false, // Removes support for the "Add New" function
	    ),
	    'map_meta_cap'		 => true,
	);

	register_post_type( 'ppdgorder', $args );
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
     * @return    Numeric    Post or Order ID.
     */
    public function insert( $payment, $payer ) {
	$post			 = array();
	$post[ 'post_title' ]	 = $payment[ 'quantity' ] . ' ' . $payment[ 'item_name' ] . ' - ' . $payment[ 'state' ];
	$post[ 'post_status' ]	 = 'publish';

//	$ack	 = strtoupper( $ConfirmPayment_details[ "ACK" ] );
	$output = '';

//	// Add error info in case of failure
//	if ( $ack != "SUCCESS" && $ack != "SUCCESSWITHWARNING" ) {
//
//	    $ErrorCode		 = urldecode( $ConfirmPayment_details[ "L_ERRORCODE0" ] );
//	    $ErrorShortMsg		 = urldecode( $ConfirmPayment_details[ "L_SHORTMESSAGE0" ] );
//	    $ErrorLongMsg		 = urldecode( $ConfirmPayment_details[ "L_LONGMESSAGE0" ] );
//	    $ErrorSeverityCode	 = urldecode( $ConfirmPayment_details[ "L_SEVERITYCODE0" ] );
//
//	    $output	 .= "<h2>Payment Failure Details</h2>" . "\n";
//	    $output	 .= __( "Payment API call failed. " );
//	    $output	 .= __( "Detailed Error Message: " ) . $ErrorLongMsg;
//	    $output	 .= __( "Short Error Message: " ) . $ErrorShortMsg;
//	    $output	 .= __( "Error Code: " ) . $ErrorCode;
//	    $output	 .= __( "Error Severity Code: " ) . $ErrorSeverityCode;
//	    $output	 .= "\n\n";
//	}

	$output	 .= __( "<h2>Order Details</h2>" ) . "\n";
	$output	 .= __( "Order Time: " ) . date( "F j, Y, g:i a", strtotime( $payment[ 'create_time' ] ) ) . "\n";
	$output	 .= __( "Transaction ID: " ) . $payment[ 'id' ] . "\n";
	$output	 .= "--------------------------------" . "\n";
	$output	 .= __( "Product Name: " ) . $payment[ 'item_name' ] . "\n";
	$output	 .= __( "Quantity: " ) . $payment[ 'quantity' ] . "\n";
	$output	 .= __( "Price: " ) . $payment[ 'price' ] . ' ' . $payment[ 'currency' ] . "\n";
	$output	 .= "--------------------------------" . "\n";
	$output	 .= __( "Total Amount: " ) . $payment[ 'amount' ] . ' ' . $payment[ 'currency' ] . "\n";

	$output .= "\n\n";

	$output	 .= __( "<h2>Customer Details</h2>" ) . "\n";
	$output	 .= __( "Name: " ) . $payer[ 'name' ][ 'given_name' ] . ' ' . $payer[ 'name' ][ 'surname' ] . "\n";
	$output	 .= __( "Payer ID: " ) . $payer[ 'payer_id' ] . "\n";
	$output	 .= __( "E-Mail Address: " ) . $payer[ 'email_address' ] . "\n";
	$output	 .= __( "Country Code: " ) . $payer[ 'address' ][ 'country_code' ] . "\n";

	$post[ 'post_content' ]	 = $output; //..var_export($ConfirmPayment_details, true)'<br/><br/>'.var_export($EC_details, true);
	$post[ 'post_type' ]	 = 'ppdgorder';

	$post_id = wp_insert_post( $post );

	//save payment details in post meta for future use
	update_post_meta( $post_id, 'ppec_payment_details', $payment );
	update_post_meta( $post_id, 'ppec_payer_details', $payer );

	return $post_id;
    }

}

?>
