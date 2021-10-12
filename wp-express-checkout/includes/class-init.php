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
	}

	public function do_init_time_tasks() {
		/*
		 * General init time tasks
		 */

		//Register the post types
		Orders::register_post_type();
		Products::register_post_type();

		View_Downloads::get_instance();

		if ( function_exists( 'register_block_type' ) ) {
			// Gutenberg is active.
			new Blocks\Product_Block();
		}

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

	public function wpec_handle_reset_log() {
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
