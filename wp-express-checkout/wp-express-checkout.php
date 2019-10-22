<?php
/**
 * Plugin Name:       WP Express Checkout
 * Description:       This plugin allows you to generate a customizable PayPal payment button that lets the customers pay quickly in a popup via PayPal.
 * Version:           1.1
 * Author:            Tips and Tricks HQ
 * Author URI:        https://www.tipsandtricks-hq.com/
 * Plugin URI:        https://wp-express-checkout.com/
 * License:           GPL2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

//slug wpec_

if ( ! defined( 'ABSPATH' ) ) {
    exit; //Exit if accessed directly
}

//Define constants
define( 'WP_PPEC_PLUGIN_URL', plugins_url( '', __FILE__ ) );
define( 'WP_PPEC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_PPEC_PLUGIN_FILE', __FILE__ );

/* ----------------------------------------------------------------------------*
 * Public-Facing Functionality
 * ---------------------------------------------------------------------------- */

require_once( plugin_dir_path( __FILE__ ) . 'public/class-ppdg.php' );
require_once( plugin_dir_path( __FILE__ ) . 'public/includes/class-shortcode-ppdg.php' );
require_once( plugin_dir_path( __FILE__ ) . 'admin/includes/class-products.php' );
require_once( plugin_dir_path( __FILE__ ) . 'admin/includes/class-order.php' );


/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 */

register_activation_hook( __FILE__, array( 'PPDG', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PPDG', 'deactivate' ) );

/*
 * Load admin side class
 */

if ( is_admin() ) {
    require_once( plugin_dir_path( __FILE__ ) . 'admin/class-wpec-admin.php' );
    add_action( 'plugins_loaded', array( 'WPEC_Admin', 'get_instance' ) );
}

//Register post types
$PPECProducts = PPECProducts::get_instance();
add_action( 'init', array( $PPECProducts, 'register_post_type' ), 0 );

$OrdersWPEC = OrdersWPEC::get_instance();
add_action( 'init', array( $OrdersWPEC, 'register_post_type' ), 0 );

add_action( 'plugins_loaded', array( 'PPDG', 'get_instance' ) );
add_action( 'plugins_loaded', array( 'PPDGShortcode', 'get_instance' ) );
add_action( 'wp_ajax_wp_ppdg_process_payment', 'wp_ppdg_process_payment' );
add_action( 'wp_ajax_nopriv_wp_ppdg_process_payment', 'wp_ppdg_process_payment' );

function wp_ppdg_process_payment() {
    if ( ! isset( $_POST[ 'wp_ppdg_payment' ] ) ) {
	//no payment data provided
	_e( 'No payment data received.', 'paypal-express-checkout' );
	exit;
    }
    $payment = $_POST[ 'wp_ppdg_payment' ];

    if ( strtoupper( $payment[ 'status' ] ) !== 'COMPLETED' ) {
	//payment is unsuccessful
	printf( __( 'Payment is not approved. Status: %s', 'paypal-express-checkout' ), $payment[ 'status' ] );
	exit;
    }

    // get item name
    $item_name	 = $payment[ 'purchase_units' ][ 0 ][ 'description' ];
    // let's check if the payment matches transient data
    $trans_name	 = 'wp-ppdg-' . sanitize_title_with_dashes( $item_name );
    $trans		 = get_transient( $trans_name );
    if ( ! $trans ) {
	//no price set
	_e( 'No transaction info found in transient.', 'paypal-express-checkout' );
	exit;
    }
    $price		 = $trans[ 'price' ];
    $quantity	 = $trans[ 'quantity' ];
    $currency	 = $trans[ 'currency' ];
    $url		 = $trans[ 'url' ];

    if ( $trans[ 'custom_quantity' ] ) {
	//custom quantity enabled. let's take quantity from PayPal results
	$quantity = $payment[ 'purchase_units' ][ 0 ][ 'items' ][ 0 ][ 'quantity' ];
    }

    $amount = $payment[ 'purchase_units' ][ 0 ][ 'amount' ][ 'value' ];

    //check if amount paid matches price x quantity
    if ( $amount != $price * $quantity ) {
	//payment amount mismatch
	_e( 'Payment amount mismatch original price.', 'paypal-express-checkout' );
	exit;
    }

    //check if payment currency matches
    if ( $payment[ 'purchase_units' ][ 0 ][ 'amount' ][ 'currency_code' ] !== $currency ) {
	//payment currency mismatch
	_e( 'Payment currency mismatch.', 'paypal-express-checkout' );
	exit;
    }

    //if code execution got this far, it means everything is ok with payment
    //let's insert order
    $order = OrdersWPEC::get_instance();

    $order->insert( array(
	'item_name'	 => $item_name,
	'price'		 => $price,
	'quantity'	 => $quantity,
	'amount'	 => $amount,
	'currency'	 => $currency,
	'state'		 => $payment[ 'status' ],
	'id'		 => $payment[ 'id' ],
	'create_time'	 => $payment[ 'create_time' ],
    ), $payment[ 'payer' ] );

    do_action( 'ppdg_payment_completed', $payment );

    $res		 = array();
    $res[ 'title' ]	 = __( 'Payment Completed', 'paypal-express-checkout' );

    $thank_you_msg	 = '<div class="wp_ppdg_thank_you_message"><p>' . __( 'Thank you for your purchase.', 'paypal-express-checkout' ) . '</p>';
    $click_here_str	 = sprintf( __( 'Please <a href="%s">click here</a> to download the file.', 'paypal-express-checkout' ), base64_decode( $url ) );
    $thank_you_msg	 .= '<br /><p>' . $click_here_str . '</p></div>';
    $thank_you_msg	 = apply_filters( 'wp_ppdg_thank_you_message', $thank_you_msg );
    $res[ 'msg' ]	 = $thank_you_msg;

    echo json_encode( $res );

    exit;
}
