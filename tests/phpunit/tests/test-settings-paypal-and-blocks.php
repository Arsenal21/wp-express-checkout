<?php
/**
 * The settings (Main::get_setting()), the PayPal configuration (the PayPal SDK script, the PayPal API client) and the product block.
 */
class Test_Settings_PayPal_And_Blocks extends WP_UnitTestCase {

	public function test_a_setting_that_is_not_saved_uses_its_default() {
		$settings = get_option( 'ppdg-settings' );
		unset( $settings['stripe_btn_text'] );
		update_option( 'ppdg-settings', $settings );

		$this->assertSame( 'Stripe Checkout', \WP_Express_Checkout\Main::get_instance()->get_setting( 'stripe_btn_text' ) );
		$this->assertFalse( \WP_Express_Checkout\Main::get_instance()->get_setting( 'no_such_setting' ) );

		WPEC_Test_Factory::update_settings( array( 'stripe_btn_text' => 'Pay with card' ) );
		$this->assertSame( 'Pay with card', \WP_Express_Checkout\Main::get_instance()->get_setting( 'stripe_btn_text' ) );
	}

	// ---- PayPal ----

	private function paypal_sdk_url() {
		ob_start();
		\WP_Express_Checkout\Main::get_instance()->load_paypal_sdk();
		$script = ob_get_clean();
		preg_match( "/script\.src = '([^']+)'/", $script, $m );
		$this->assertNotEmpty( $m, 'The PayPal SDK script is printed.' );
		$args = array();
		wp_parse_str( wp_parse_url( html_entity_decode( $m[1] ), PHP_URL_QUERY ), $args );
		return $args;
	}

	public function test_the_paypal_sdk_is_loaded_with_the_sandbox_client_id_and_the_currency() {
		WPEC_Test_Factory::update_settings(
			array(
				'is_live'           => 0,
				'sandbox_client_id' => 'sandbox-id',
				'live_client_id'    => 'live-id',
				'currency_code'     => 'EUR',
				'disabled_funding'  => array( 'card', 'credit' ),
			)
		);

		$args = $this->paypal_sdk_url();

		$this->assertSame( 'sandbox-id', $args['client-id'] );
		$this->assertSame( 'EUR', $args['currency'] );
		$this->assertSame( 'capture', $args['intent'] );
		$this->assertSame( 'card,credit', $args['disable-funding'] );
		$this->assertSame( 'US', $args['buyer-country'], 'Sandbox only (needed for Venmo).' );
	}

	public function test_the_paypal_sdk_is_loaded_with_the_live_client_id_and_the_disabled_cards() {
		WPEC_Test_Factory::update_settings(
			array(
				'is_live'          => 1,
				'live_client_id'   => 'live-id',
				'currency_code'    => 'USD',
				'disabled_funding' => array(),
				'disabled_cards'   => array( 'amex' ),
			)
		);

		$args = $this->paypal_sdk_url();

		$this->assertSame( 'live-id', $args['client-id'] );
		$this->assertSame( 'amex', $args['disable-card'] );
		$this->assertArrayNotHasKey( 'buyer-country', $args );
	}

	public function test_the_paypal_api_client_uses_the_live_or_sandbox_credentials() {
		WPEC_Test_Factory::update_settings(
			array(
				'is_live'            => 0,
				'sandbox_client_id'  => 'sandbox-id',
				'sandbox_secret_key' => 'sandbox-secret',
				'live_client_id'     => 'live-id',
				'live_secret_key'    => 'live-secret',
			)
		);

		$this->assertInstanceOf( 'PayPalCheckoutSdk\Core\PayPalHttpClient', \WP_Express_Checkout\PayPal\Client::client() );
		$this->assertInstanceOf( 'PayPalCheckoutSdk\Core\SandboxEnvironment', \WP_Express_Checkout\PayPal\Client::environment() );
		$this->assertSame( base64_encode( 'sandbox-id:sandbox-secret' ), \WP_Express_Checkout\PayPal\Client::environment()->authorizationString() );
		$this->assertInstanceOf( 'PayPalCheckoutSdk\Core\ProductionEnvironment', \WP_Express_Checkout\PayPal\Client::environment( 'live' ) );
		$this->assertSame( base64_encode( 'live-id:live-secret' ), \WP_Express_Checkout\PayPal\Client::environment( 'live' )->authorizationString() );

		WPEC_Test_Factory::update_settings( array( 'is_live' => 1 ) );
		$this->assertInstanceOf( 'PayPalCheckoutSdk\Core\ProductionEnvironment', \WP_Express_Checkout\PayPal\Client::environment() );
		$this->assertInstanceOf( 'PayPalCheckoutSdk\Core\SandboxEnvironment', \WP_Express_Checkout\PayPal\Client::environment( 'test' ) );
	}

	// ---- Product block ----

	public function test_the_product_block_is_registered() {
		$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( 'wp-express-checkout/product-block' ) );
	}

	public function test_the_product_block_shows_the_product() {
		WPEC_Test_Factory::update_settings( array( 'is_live' => 0, 'sandbox_client_id' => 'test-client-id' ) );
		$product_id = WPEC_Test_Factory::create_product( '12.50', array(), 'Block Product' );
		$block      = WP_Block_Type_Registry::get_instance()->get_registered( 'wp-express-checkout/product-block' );

		$html = call_user_func( $block->render_callback, array( 'prod_id' => $product_id ), '' );

		$this->assertStringContainsString( 'Block Product', $html );
		$this->assertStringContainsString( '$12.50', $html );
		$this->assertStringContainsString( 'new ppecHandler(', $html );
	}

	public function test_the_product_block_without_a_product_shows_a_placeholder() {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( 'wp-express-checkout/product-block' );

		$this->assertStringContainsString( 'wpec-no-product-placeholder', call_user_func( $block->render_callback, array(), '' ) );
	}
}
