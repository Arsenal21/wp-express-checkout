<?php
/**
 * The PayPal SDK request.
 *
 * @package WPEC
 * @since 1.9.3
 */

namespace WP_Express_Checkout\PayPal;

use PayPalHttp\HttpRequest;

/**
 * Implement HttpRequest class using own attribution ID and other default parameters.
 */
class Request extends HttpRequest {

	/**
	 * Constructor.
	 *
	 * @param string $path The request path (i.e "/v1/catalogs/products").
	 * @param string $verb The request verb (i.e "POST", "GET", "PATCH").
	 */
	public function __construct( $path, $verb ) {
		parent::__construct( $path, $verb );
		$this->headers['Content-Type']                  = 'application/json';
		$this->headers['PayPal-Partner-Attribution-Id'] = 'TipsandTricks_SP';
	}

	/**
	 * Adds optional Prefer header.
	 *
	 * @param string $prefer The preferred server response upon successful
	 *                       completion of the request.
	 */
	public function prefer( $prefer ) {
		$this->headers['Prefer'] = $prefer;
	}

}
