<?php

namespace WP_Express_Checkout\Integrations;

class WPEC_WooCommerce_Init_handler
{
    public function __construct()
    {
        add_action('before_woocommerce_init', array($this, 'wpec_handle_before_woocommerce_init'));
        add_action('woocommerce_blocks_payment_method_type_registration', array($this, 'wpec_register_wc_blocks_payment_method_type'));
    }

    public function wpec_handle_before_woocommerce_init()
    {
        // handle woocommerce checkout blocks compatibility
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                WPEC_PLUGIN_FILE,
                true // true (compatible, default) or false (not compatible)
            );
        }
    }

    public function wpec_register_wc_blocks_payment_method_type(\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry)
    {
        $payment_method_registry->register(new WooCommerce_Gateway_Block_Support);
    }
}