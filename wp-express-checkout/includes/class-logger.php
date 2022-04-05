<?php

namespace WP_Express_Checkout\Debug;

use WP_Express_Checkout\Main;

class Logger {

	static function log( $msg, $success = true ) {

		$wpec_plugin = Main::get_instance();

		$enable_debug_logging = $wpec_plugin->get_setting( 'enable_debug_logging' );
		if ( $enable_debug_logging ) {
			file_put_contents( self::get_file_name(), date( 'Y-m-d H:i:s', time() ) . ': [' . ( $success === true ? 'SUCCESS' : 'FAIL' ) . '] ' . $msg . "\r\n", FILE_APPEND );
		}
	}

	static function log_array_data( $array_to_write, $success = true, $overwrite = false) {
		$wpec_plugin = Main::get_instance();
		$enable_debug_logging = $wpec_plugin->get_setting( 'enable_debug_logging' );
		if ( ! $enable_debug_logging ){
			//Debug logging is disabled.
			return true;
		}

		$log_file = self::get_file_name();

		$output = '';
		//Timestamp it
		$output .= date( 'Y-m-d H:i:s', time() ) . ': [';

		//Flag success or failure
		$output .= ($success === true) ? 'SUCCESS' : 'FAIL';

		$output .= '] ';

		//Put the array content into a string
		ob_start();
		print_r( $array_to_write );
		$var = ob_get_contents();
		ob_end_clean();
		$output .= $var;

		//Write to the log file
		if ( ! file_put_contents( $log_file, $output . "\r\n", ( ! $overwrite ? FILE_APPEND : 0 ) ) ) {
			return false;
		}
		return true;
	}

	static function reset_log() {
		file_put_contents( self::get_file_name(), date( 'Y-m-d H:i:s', time() ) . ': Log has been reset.' . "\r\n" );
		file_put_contents( self::get_file_name(), '-------------------------------------------------------' . "\r\n", FILE_APPEND );
	}

	static function get_file_name() {
		$log_file = get_option( 'wpec_log_file_name' );
		if ( ! $log_file ) {
			// Let's generate new log file name.
			$log_file = uniqid() . '_debug_log.txt';
			update_option( 'wpec_log_file_name', $log_file );
		}

		return WPEC_PLUGIN_PATH . $log_file;
	}

}
