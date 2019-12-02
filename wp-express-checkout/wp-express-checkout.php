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
 * Includes
 * ---------------------------------------------------------------------------- */
require_once( WP_PPEC_PLUGIN_PATH . 'public/class-wpec-main.php' );
require_once( WP_PPEC_PLUGIN_PATH . 'public/includes/class-shortcode-ppec.php' );
require_once( WP_PPEC_PLUGIN_PATH . 'admin/includes/class-products.php' );
require_once( WP_PPEC_PLUGIN_PATH . 'admin/includes/class-order.php' );
include_once( WP_PPEC_PLUGIN_PATH . 'includes/class-wpec-process-ipn.php');

//Load admin side class
if ( is_admin() ) {
    require_once( WP_PPEC_PLUGIN_PATH . 'admin/class-wpec-admin.php' );
    add_action( 'plugins_loaded', array( 'WPEC_Admin', 'get_instance' ) );
}

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 */
register_activation_hook( __FILE__, array( 'WPEC_Main', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPEC_Main', 'deactivate' ) );

/*
 * Register custom post types
 */
$PPECProducts = PPECProducts::get_instance();
add_action( 'init', array( $PPECProducts, 'register_post_type' ), 0 );

$OrdersWPEC = OrdersWPEC::get_instance();
add_action( 'init', array( $OrdersWPEC, 'register_post_type' ), 0 );

add_action( 'plugins_loaded', array( 'WPEC_Main', 'get_instance' ) );
add_action( 'plugins_loaded', array( 'WPECShortcode', 'get_instance' ) );

/*
 * Listen and handle payment processing. IPN handling.
 */
WPEC_Process_IPN::get_instance();
