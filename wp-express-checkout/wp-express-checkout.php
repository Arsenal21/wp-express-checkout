<?php
/**
 * Plugin Name:       WP Express Checkout
 * Description:       This plugin allows you to generate a customizable PayPal payment button that lets the customers pay quickly in a popup via PayPal.
 * Version:           1.9.3
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
define( 'WPEC_PLUGIN_VER', '1.9.3' );
define( 'WPEC_PLUGIN_URL', plugins_url( '', __FILE__ ) );
define( 'WPEC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPEC_PLUGIN_FILE', __FILE__ );
define( 'WPEC_LOG_FILE', WPEC_PLUGIN_PATH . 'wpec-debug-log.txt' );

/* ----------------------------------------------------------------------------*
 * Includes
 * ---------------------------------------------------------------------------- */
// Generate autoload:
// 1. Add class name/path to composer.json
// 2. Run in console: composer dump-autoload
require WPEC_PLUGIN_PATH . '/vendor/autoload.php';

// Load classes.
function wpec_load_classes() {
	WPEC_Main::get_instance();
	WPECShortcode::get_instance();
	WPEC_View_Download::get_instance();
	WPEC_Post_Type_Content_Handler::get_instance();
	WPEC_Process_IPN::get_instance();
	WPEC_Variations::init();

	new WPEC_Blocks();
	new WPEC_Init_Time_Tasks();
	new WPEC_Integrations();

	// Load admin side class
	if ( is_admin() ) {
		WPEC_Admin::get_instance();
		new WPEC_Coupons_Admin();
	}
}
add_action( 'plugins_loaded', 'wpec_load_classes' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 */
register_activation_hook( __FILE__, array( 'WPEC_Main', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPEC_Main', 'deactivate' ) );

//Add settings link in plugins listing page
function wpec_add_settings_link($links, $file) {
    if ($file == plugin_basename(__FILE__)) {
        $settings_link = '<a href="edit.php?post_type=ppec-products&page=ppec-settings-page">Settings</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}
add_filter('plugin_action_links', 'wpec_add_settings_link', 10, 2);
