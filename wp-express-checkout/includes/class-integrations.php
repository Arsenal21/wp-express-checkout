<?php

namespace WP_Express_Checkout;

class Integrations {

	public function __construct() {
		// Simple Membership integration
		if ( defined( 'SIMPLE_WP_MEMBERSHIP_VER' ) ) {
			new Integrations\Simple_WP_Membership();
		}
		// WP eMember integration
		if ( function_exists( 'wp_emember_install' ) ) {
			new Integrations\Emember();
		}
		// License Manager integration.
		if ( defined( 'WP_LICENSE_MANAGER_VERSION' ) ) {
			new Integrations\License_Manager();
		}

		// WooCommerce integration.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			new Integrations\WooCommerce_Payment_Processor();
			add_filter( 'woocommerce_payment_gateways', array( 'WP_Express_Checkout\Integrations\WooCommerce_Gateway', 'add_wc_gateway_class' ) );
		}

		// Subscriptions addon integration.
		if ( defined( 'WPEC_SUB_PLUGIN_VER' ) ) {
			add_filter( 'wpec_product_type_subscription', array( $this, 'fallback_subscription_type' ), 999999 );
		}
	}

	/**
	 * Adds a fallback for a subscription product type object when Subscriptions
	 * addon is presented, but not updated to correct version.
	 *
	 * @since 2.0.1
	 *
	 * @param WP_Post|Product $product The product object.
	 *
	 * @return Products\Product
	 */
	public function fallback_subscription_type( $product ) {
		if ( $product instanceof \WP_Post ) {
			$product = new Products\One_Time_Product( $product );
		}

		return $product;
	}

}
