<?php
/**
 * The PayPal SDK client.
 *
 * @package WPEC
 * @since 1.9.3
 */

namespace WPEC\PayPal;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;

/**
 * The plugin's implementation of the PayPal SDK client.
 */
class Client {

	/**
	 * Returns PayPal HTTP client instance with environment which has access
	 * credentials context. This can be used invoke PayPal API's provided the
	 * credentials have the access to do so.
	 *
	 * @return PayPalHttpClient
	 */
	public static function client() {
		return new PayPalHttpClient( self::environment() );
	}

	/**
	 * Setting up and Returns PayPal SDK environment with PayPal Access credentials.
	 *
	 * @return SandboxEnvironment|ProductionEnvironment
	 */
	public static function environment() {
		$wpec    = \WPEC_Main::get_instance();
		$is_live = $wpec->get_setting( 'is_live' );

		if ( $is_live ) {
			$client_id     = $wpec->get_setting( 'live_client_id' );
			$client_secret = $wpec->get_setting( 'live_secret_key' );
			$environment   = new ProductionEnvironment( $client_id, $client_secret );
		} else {
			$client_id     = $wpec->get_setting( 'sandbox_client_id' );
			$client_secret = $wpec->get_setting( 'sandbox_secret_key' );
			$environment   = new SandboxEnvironment( $client_id, $client_secret );
		}

		return $environment;
	}

}
