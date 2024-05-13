<?php
/**
 * This class is used to process the payment after successful wpec payment.
 *
 * Completes WC_Order.
 * Sends to Thank You page.
 */

namespace WP_Express_Checkout\Integrations;

use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Utils;

/**
 * Process IPN class
 */
class WooCommerce_Payment_Processor {

	/**
	 * Construct the instance.
	 */
	public function __construct() {

	}
}
