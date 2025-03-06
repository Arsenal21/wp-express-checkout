<?php

namespace WP_Express_Checkout;

use WP_Express_Checkout\Admin\Orders_List;
use WP_Express_Checkout\Admin\Products_List;
use WP_Express_Checkout\Admin\Products_Meta_Boxes;
use WP_Express_Checkout\Debug\Logger;

/*
 * This class handles various init time (init hook) tasks.
 */

class Init {

	public function __construct() {
		add_action( 'init', array( $this, 'do_init_time_tasks' ) );
		add_action( 'admin_init', array( $this, 'do_admin_init_time_tasks' ) );
	}

	public function do_init_time_tasks() {
		/*
		 * General init time tasks
		 */

		//Register the post types
		Orders::register_post_type();
		Products::register_post_type();
		
		//Register taxonomy
		Categories::register_category_taxonomy();
		Tags::register_tags_taxonomy();
		
		//Download request handler
		View_Downloads::get_instance();

		if ( function_exists( 'register_block_type' ) ) {
			// Gutenberg is active.
			new Blocks\Product_Block();
		}

		//Debug log reset ajax action handler
		add_action( 'wp_ajax_wpec_reset_log', array( $this, 'wpec_handle_reset_log' ) );

		if ( is_admin() ) {
			/*
			 * Do admin side only tasks
			 */

			$this->handle_view_log_action();
			Orders_List::init();
			Products_List::init();
			new Products_Meta_Boxes();
		} else {
			/*
			 * Front-end only tasks
			 */

			//NOP
		}
	}

	public function do_admin_init_time_tasks() {
		\WP_Express_Checkout\Admin\Admin::paypal_onboard_actions_handler();
	}

	public function wpec_handle_reset_log() {
		if ( ! current_user_can( 'manage_options' ) ) {
				Logger::log("Error! No permission to reset log file.");
				//No permission for the current user to do this operation.
				wp_die( 0 );
		}

		if ( ! check_ajax_referer( 'wpec_settings_ajax_nonce', 'nonce', false ) ) {
				//The nonce check failed
				echo 'Error! Nonce security check failed. Could not reset the log file.';
				wp_die( 0 );
		}

		Logger::reset_log();
		echo '1';
		wp_die();
	}

	public function handle_view_log_action() {

		if ( user_can( wp_get_current_user(), 'administrator' ) ) {
			// user is an admin
			if ( isset( $_GET['wpec-debug-action'] ) ) {
				if ( $_GET['wpec-debug-action'] === 'view_log' ) {
					$filename = Logger::get_file_name();
					if ( file_exists( $filename ) ) {
						$logfile = fopen( Logger::get_file_name(), 'rb' );
						header( 'Content-Type: text/plain' );
						fpassthru( $logfile );
					}
					die;
				}
			}
		}
	}

}
