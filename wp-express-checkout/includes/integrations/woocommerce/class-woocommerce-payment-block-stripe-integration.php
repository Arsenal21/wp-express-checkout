<?php

namespace WP_Express_Checkout\Integrations;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class WooCommerce_Gateway_Block_Support_Stripe extends AbstractPaymentMethodType {

    private $gateway;

	/**
	 * This property is a string used to reference your payment method. It is important to use the same name as in your
	 * client-side JavaScript payment method registration.
	 *
	 * @var string
	 */
    protected $name = 'wp-express-checkout-stripe'; // payment gateway id

    public function initialize() {
        // get gateway class
        $gateways = WC()->payment_gateways->payment_gateways();
        $this->gateway  = $gateways[ $this->name ];

        // get payment gateway settings
        $this->settings = get_option( "woocommerce_{$this->name}_settings", array() );
    }

    public function is_active() {
        return ! empty( $this->settings[ 'enabled' ] ) && 'yes' === $this->settings[ 'enabled' ];
    }

    public function get_payment_method_script_handles() {
        $asset_path   = WPEC_PLUGIN_PATH . '/includes/integrations/woocommerce/block-integration/index.asset.php';
        $version      = null;
        $dependencies = array();
        if( file_exists( $asset_path ) ) {
            $asset        = require $asset_path;
            $version      = isset( $asset[ 'version' ] ) ? $asset[ 'version' ] : $version;
            $dependencies = isset( $asset[ 'dependencies' ] ) ? $asset[ 'dependencies' ] : $dependencies;
        }

        wp_enqueue_style(
            'wpec-wc-block-support-styles',
            WPEC_PLUGIN_URL . '/includes/integrations/woocommerce/block-integration/index.css',
            array(),
            $version
        );

        wp_register_script(
            'wpec-wc-block-support-script',
            WPEC_PLUGIN_URL . '/includes/integrations/woocommerce/block-integration/index.js',
            $dependencies,
            $version,
            true
        );

        // Return the script handler(s), so woocommerce can handle enqueueing of them.
        return array( 'wpec-wc-block-support-script' );
    }

    public function get_payment_method_data() {
        return array(
            'title'        => $this->get_setting( 'title' ),
            'description'  => $this->get_setting( 'description' ),
            'supports' => $this->gateway->supports,
        );
    }
}