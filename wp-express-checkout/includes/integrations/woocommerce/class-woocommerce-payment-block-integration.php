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
            'pp_sdk_args' => $this->get_wpec_paypal_sdk_args(),
        );
    }

    /**
     * Get required arguments load paypal SDK.
     *
     * @return array SDK args.
     */
    public function get_wpec_paypal_sdk_args(){
        $pp_sdk_args = array(
            'intent' => 'capture',
            'currency' => get_woocommerce_currency(),
            'client-id' => Main::get_instance()->get_setting( 'is_live' ) ? Main::get_instance()->get_setting( 'live_client_id' ) : Main::get_instance()->get_setting( 'sandbox_client_id' ),
        );

        // Enable Venmo by default (could be disabled by 'disable-funding' option).
		$pp_sdk_args['enable-funding']  = 'venmo';
		// Required for Venmo in sandbox.
		if ( ! Main::get_instance()->get_setting( 'is_live' ) ) {
			$pp_sdk_args['buyer-country']  = 'US';
		}

        $disabled_funding = Main::get_instance()->get_setting( 'disabled_funding' );
        if (!empty($disabled_funding)) {
            $pp_sdk_args['disable-funding'] = implode(',', $disabled_funding);
        }

        // Check if cards aren't disabled globally first.
		if ( ! in_array( 'card', $disabled_funding, true ) ) {
			$disabled_cards = Main::get_instance()->get_setting( 'disabled_cards' );
			if ( ! empty( $disabled_cards ) ) {
				$pp_sdk_args['disable-card'] = implode(',', $disabled_cards);
			}
		}

        return $pp_sdk_args;
    }
}