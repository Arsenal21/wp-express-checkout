<?php
/**
 * Plugin Name:       WP Express Checkout
 * Description:       This plugin allows you to create a customizable PayPal payment button that lets the customers pay quickly in a popup via PayPal.
 * Version:           2.1.6
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
define( 'WPEC_PLUGIN_VER', '2.1.6' );
define( 'WPEC_PLUGIN_URL', plugins_url( '', __FILE__ ) );
define( 'WPEC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPEC_PLUGIN_FILE', __FILE__ );

/* ----------------------------------------------------------------------------*
 * Includes
 * ---------------------------------------------------------------------------- */
// Generate autoload:
// 1. Add class name/path to composer.json
// 2. Run in console: composer dump-autoload
require WPEC_PLUGIN_PATH . '/vendor/autoload.php';

// Create aliases for old class names and autoload them on a request
require WPEC_PLUGIN_PATH . '/vendor/autoload-deprecated.php';

// Load classes.
function wpec_load_classes() {
	WP_Express_Checkout\Main::get_instance();
	WP_Express_Checkout\Shortcodes::get_instance();
	WP_Express_Checkout\Post_Type_Content_Handler::get_instance();
	WP_Express_Checkout\Payment_Processor::get_instance();
	WP_Express_Checkout\Variations::init();

	new WP_Express_Checkout\Payment_Processor_Free();
	new WP_Express_Checkout\Init();
	new WP_Express_Checkout\Integrations();

	// Load admin side class
	if ( is_admin() ) {
		WP_Express_Checkout\Admin\Admin::get_instance();
		WP_Express_Checkout\Admin\Tools::get_instance();
		new WP_Express_Checkout\Coupons();
		new WP_Express_Checkout\Admin\Orders_Meta_Boxes();
	}
}
add_action( 'plugins_loaded', 'wpec_load_classes' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 */
register_activation_hook( __FILE__, array( 'WP_Express_Checkout\Main', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WP_Express_Checkout\Main', 'deactivate' ) );

//Add settings link in plugins listing page
function wpec_add_settings_link($links, $file) {
    if ($file == plugin_basename(__FILE__)) {
        $settings_link = '<a href="edit.php?post_type=ppec-products&page=ppec-settings-page">Settings</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}
add_filter('plugin_action_links', 'wpec_add_settings_link', 10, 2);
