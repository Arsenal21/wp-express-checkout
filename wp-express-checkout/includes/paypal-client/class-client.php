<?php
/**
 * The PayPal SDK client.
 *
 * @package WPEC
 * @since 1.9.3
 */

namespace WP_Express_Checkout\PayPal;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use WP_Express_Checkout\Main;

/**
 * The plugin's implementation of the PayPal SDK client.
 */
class Client {

	/**
	 * Returns PayPal HTTP client instance with environment which has access
	 * credentials context. This can be used invoke PayPal API's provided the
	 * credentials have the access to do so.
	 *
	 * @param string $mode The PayPal environment mode (`live` or `test`).
	 *
	 * @return PayPalHttpClient
	 */
	public static function client( $mode = '' ) {
		return new PayPalHttpClient( self::environment( $mode ) );
	}

	/**
	 * Setting up and Returns PayPal SDK environment with PayPal Access credentials.
	 *
	 * @param string $mode The PayPal environment mode (`live` or `test`).
	 *
	 * @return SandboxEnvironment|ProductionEnvironment
	 */
	public static function environment( $mode = '' ) {
		$wpec    = Main::get_instance();
		$is_live = $mode ? 'live' === $mode : $wpec->get_setting( 'is_live' );

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
