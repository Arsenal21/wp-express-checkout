<?php

namespace WP_Express_Checkout\Debug;

use WP_Express_Checkout\Main;

class Logger {

	static function log( $msg, $success = true ) {

		$wpec_plugin = Main::get_instance();

		$enable_debug_logging = $wpec_plugin->get_setting( 'enable_debug_logging' );
		if ( $enable_debug_logging ) {
			file_put_contents( WPEC_LOG_FILE, date( 'Y-m-d H:i:s', time() ) . ': [' . ($success === true ? 'SUCCESS' : 'FAIL') . '] ' . $msg . "\r\n", FILE_APPEND );
		}
	}

	static function reset_log() {
		file_put_contents( WPEC_LOG_FILE, date( 'Y-m-d H:i:s', time() ) . ': Log has been reset.' . "\r\n" );
		file_put_contents( WPEC_LOG_FILE, '-------------------------------------------------------' . "\r\n", FILE_APPEND );
	}

}
