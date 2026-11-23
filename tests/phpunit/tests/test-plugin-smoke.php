<?php
/**
 * The plugin loads and activates, registers its post types and shortcodes, and shows a product.
 */
class Test_Plugin_Smoke extends WP_UnitTestCase {

	public function test_plugin_is_loaded() {
		$this->assertTrue( defined( 'WPEC_PLUGIN_VER' ) );
		$this->assertTrue( class_exists( '\WP_Express_Checkout\Main' ) );
		$this->assertNotFalse( has_action( 'plugins_loaded', 'wpec_load_classes' ) );
	}

	public function test_activation_saves_the_default_settings() {
		$settings = get_option( 'ppdg-settings' );
		$this->assertIsArray( $settings );
		$this->assertSame( 'USD', $settings['currency_code'] );
		$this->assertSame( '2', $settings['price_decimals_num'] );
		$this->assertSame( 1, $settings['enable_paypal_checkout'] );
		$this->assertSame( 0, $settings['enable_stripe_checkout'] );
		foreach ( array_keys( \WP_Express_Checkout\Main::get_defaults() ) as $key ) {
			$this->assertArrayHasKey( $key, $settings, "The default setting '$key' is saved." );
		}
	}

	public function test_activation_creates_the_thank_you_and_shop_pages() {
		\WP_Express_Checkout\Main::activate( false );

		$settings = get_option( 'ppdg-settings' );
		$ty_page  = get_post( $settings['thank_you_page_id'] );
		$this->assertSame( 'page', $ty_page->post_type );
		$this->assertSame( 'publish', $ty_page->post_status );
		$this->assertStringContainsString( '[wpec_thank_you]', $ty_page->post_content );
		$this->assertNotEmpty( $settings['thank_you_url'] );

		$shop_page = get_post( $settings['shop_page_id'] );
		$this->assertStringContainsString( '[wpec_show_all_products]', $shop_page->post_content );
		$this->assertNotEmpty( $settings['shop_page_url'] );
	}

	public function test_activating_again_keeps_the_settings_and_does_not_create_the_pages_twice() {
		\WP_Express_Checkout\Main::activate( false );
		WPEC_Test_Factory::update_settings( array( 'currency_code' => 'EUR' ) );

		//An old version's settings without a newer setting: activating again (as a plugin update does) adds it with its default.
		$settings = get_option( 'ppdg-settings' );
		unset( $settings['stripe_btn_text'] );
		update_option( 'ppdg-settings', $settings );

		\WP_Express_Checkout\Main::activate( false );

		$settings = get_option( 'ppdg-settings' );
		$this->assertSame( 'EUR', $settings['currency_code'] );
		$this->assertSame( 'Stripe Checkout', $settings['stripe_btn_text'] );

		$pages = get_pages();
		$ty    = array_filter( $pages, function ( $p ) { return false !== strpos( $p->post_content, '[wpec_thank_you]' ); } );
		$shop  = array_filter( $pages, function ( $p ) { return false !== strpos( $p->post_content, '[wpec_show_all_products]' ); } );
		$this->assertCount( 1, $ty );
		$this->assertCount( 1, $shop );
	}

	public function test_post_types_are_registered() {
		$this->assertTrue( post_type_exists( 'ppec-products' ) );
		$this->assertTrue( post_type_exists( 'ppdgorder' ) );
		$this->assertTrue( post_type_exists( 'wpec_coupons' ) );
	}

	public function test_shortcodes_are_registered() {
		foreach ( array( 'wp_express_checkout', 'wpec_thank_you', 'wpec_ty', 'wpec_ty_downloads', 'wpec_show_all_products', 'wpec_show_products_from_category' ) as $shortcode ) {
			$this->assertTrue( shortcode_exists( $shortcode ), "[$shortcode] is registered." );
		}
	}

	public function test_product_shortcode_shows_the_product_and_the_payment_buttons() {
		WPEC_Test_Factory::update_settings(
			array(
				'is_live'                => 0,
				'sandbox_client_id'      => 'test-client-id',
				'enable_stripe_checkout' => 1,
			)
		);
		$product_id = WPEC_Test_Factory::create_product( '19.95', array(), 'Smoke Test Product' );

		$html = do_shortcode( '[wp_express_checkout product_id="' . $product_id . '"]' );

		$this->assertStringContainsString( 'Smoke Test Product', $html );
		$this->assertStringContainsString( '$19.95', $html );
		$this->assertStringContainsString( 'new ppecHandler(', $html );
		$this->assertStringContainsString( 'new WPECPayPalHandler(', $html );
		$this->assertStringContainsString( 'new WPECStripeHandler(', $html );

		//The shortcode saves the product's price data for the payment processing (keyed by the product name).
		$trans = get_transient( 'wp-ppdg-' . sanitize_title_with_dashes( 'Smoke Test Product' ) );
		$this->assertSame( '19.95', $trans['price'] );
		$this->assertSame( $product_id, $trans['product_id'] );
	}

	public function test_product_shortcode_without_a_paypal_client_id_shows_an_error_instead_of_the_paypal_button() {
		WPEC_Test_Factory::update_settings( array( 'is_live' => 0, 'sandbox_client_id' => '' ) );
		$product_id = WPEC_Test_Factory::create_product( '10' );

		$html = do_shortcode( '[wp_express_checkout product_id="' . $product_id . '"]' );

		$this->assertStringContainsString( 'Please enter sandbox Client ID in the settings.', $html );
		$this->assertStringNotContainsString( 'new WPECPayPalHandler(', $html );
	}

	public function test_product_shortcode_with_a_wrong_product_id_shows_an_error() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertStringContainsString( 'wpec-error-message', do_shortcode( '[wp_express_checkout product_id="abc"]' ) );
		$this->assertStringContainsString( "find product with ID $page_id", do_shortcode( '[wp_express_checkout product_id="' . $page_id . '"]' ) );
	}

	public function test_out_of_stock_product_shows_out_of_stock() {
		WPEC_Test_Factory::update_settings( array( 'is_live' => 0, 'sandbox_client_id' => 'test-client-id' ) );
		$product_id = WPEC_Test_Factory::create_product(
			'10',
			array(
				'wpec_product_enable_stock' => 1,
				'wpec_product_stock_items'  => 0,
			)
		);

		$html = do_shortcode( '[wp_express_checkout product_id="' . $product_id . '"]' );

		$this->assertStringContainsString( 'Out of stock', $html );
		$this->assertStringNotContainsString( 'new ppecHandler(', $html );
	}

	public function test_show_all_products_lists_a_product() {
		WPEC_Test_Factory::create_product( '10', array(), 'Listed Product' );

		$this->assertStringContainsString( 'Listed Product', do_shortcode( '[wpec_show_all_products]' ) );
	}
}
