<?php
/**
 * Plugin Name:       WP Express Checkout
 * Description:       This plugin allows you to create a customizable PayPal payment button that lets the customers pay quickly in a popup via PayPal.
 * Version:           2.4.1
 * Author:            Tips and Tricks HQ, mra13
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
define( 'WPEC_PLUGIN_VER', '2.4.1' );
define( 'WPEC_PLUGIN_URL', plugins_url( '', __FILE__ ) );
define( 'WPEC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPEC_PLUGIN_FILE', __FILE__ );
define( 'WPEC_PRODUCT_POST_TYPE_SLUG', 'ppec-products' );//Slowy use this constant instead of hardcoding the slug in the code.
define( 'WPEC_MENU_PARENT_SLUG', 'edit.php?post_type=' . WPEC_PRODUCT_POST_TYPE_SLUG );
define( 'WPEC_LOAD_NON_MINIFIED', true );//Set to true to load the non-minified version.

/* ----------------------------------------------------------------------------*
 * Includes
 * ---------------------------------------------------------------------------- */
// Generate autoload:
// 1. Add class name/path to composer.json
// 2. Run in console: composer dump-autoload
require WPEC_PLUGIN_PATH . '/vendor/autoload.php';

// Create aliases for old class names and autoload them on a request
require WPEC_PLUGIN_PATH . '/vendor/autoload-deprecated.php';

/*
 * Load the classes.
 */
function wpec_load_classes() {
	WP_Express_Checkout\Main::get_instance();
	WP_Express_Checkout\Shortcodes::get_instance();
	WP_Express_Checkout\Post_Type_Content_Handler::get_instance();
	WP_Express_Checkout\Payment_Processor::get_instance();
	WP_Express_Checkout\Variations::init();

	new WP_Express_Checkout\Payment_Processor_Free();
	new WP_Express_Checkout\Payment_Processor_Manual();
	new WP_Express_Checkout\Init();
	new WP_Express_Checkout\Integrations();
	new WP_Express_Checkout\PayPal_Payment_Button_Ajax_Handler();
	new WP_Express_Checkout\Integrations\WooCommerce_Payment_Button_Ajax_Handler();

	new TTHQ\WPEC\Lib\PayPal\PayPal_Main(
		array(
			'plugin_shortname' => 'wpec',
			'api_connection_settings_page' => WPEC_MENU_PARENT_SLUG . '&page=ppec-settings-page&action=paypal-settings',
			'log_text_method' => '\WP_Express_Checkout\Debug\Logger::log',
			'log_array_method' => '\WP_Express_Checkout\Debug\Logger::log_array_data',
			'ppcp_settings_key' => 'ppdg-settings',
			'enable_sandbox_settings_key' => 'is_live',
		)
	);

	// Load admin side class
	if ( is_admin() ) {
		WP_Express_Checkout\Admin\Admin::get_instance();
		new WP_Express_Checkout\Coupons();
		new WP_Express_Checkout\Admin\Orders_Meta_Boxes();
	}
}
add_action( 'plugins_loaded', 'wpec_load_classes' );

/*
 * Register hooks that are triggered when the plugin is activated or deactivated.
 */
register_activation_hook( __FILE__, array( 'WP_Express_Checkout\Main', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WP_Express_Checkout\Main', 'deactivate' ) );

/*
 * Add settings link in plugins listing page
 */
function wpec_add_settings_link($links, $file) {
    if ($file == plugin_basename(__FILE__)) {
        $settings_link = '<a href="'.WPEC_MENU_PARENT_SLUG.'&page=ppec-settings-page">Settings</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}
add_filter('plugin_action_links', 'wpec_add_settings_link', 10, 2);

/*
 * Manage admin user feedback
 */
function wpec_manage_admin_feedback(){
	if( !class_exists( 'WPEC_Admin_User_Feedback' ) ) {
		include_once WPEC_PLUGIN_PATH . 'admin/includes/class-admin-user-feedback.php';
	}
	$user_feedback = new WPEC_Admin_User_Feedback();
	$user_feedback->init();
}
add_action( 'admin_init', 'wpec_manage_admin_feedback' );

/**
 * Do any WooCommerce-related initialization (if needed for the woo integration).
 * Codes defined in this class needs to run before the plugins_loaded hook.
 */
new WP_Express_Checkout\Integrations\WPEC_WooCommerce_Init_handler();
