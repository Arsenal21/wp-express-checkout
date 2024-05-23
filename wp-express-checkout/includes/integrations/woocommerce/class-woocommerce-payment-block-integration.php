<?php

namespace WP_Express_Checkout\Integrations;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use WP_Express_Checkout\Main;

class WooCommerce_Gateway_Block_Support extends AbstractPaymentMethodType {

    private $gateway;

    protected $name = 'wp-express-checkout'; // payment gateway id

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
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'popup_title' => $this->gateway->get_option( 'popup_title' ),
            'renderButtonNonce' => wp_create_nonce( 'wpec-wc-render-button-nonce' ),
            'supports' => $this->gateway->supports,
            'pp_sdk_args' => array(
                'intent' => 'capture',
                'currency' => get_woocommerce_currency(),
                'client-id' => Main::get_instance()->get_setting( 'is_live' ) ? Main::get_instance()->get_setting( 'live_client_id' ) : Main::get_instance()->get_setting( 'sandbox_client_id' ),
            )
        );
    }
}