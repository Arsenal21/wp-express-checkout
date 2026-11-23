<?php
/**
 * The price helpers in includes/class-utils.php that the payment handlers use for the amount checks.
 */
class Test_Price_Helpers extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		WPEC_Test_Factory::update_settings(
			array(
				'currency_code'      => 'USD',
				'currency_symbol'    => '$',
				'price_decimals_num' => '2',
				'price_decimal_sep'  => '.',
				'price_thousand_sep' => ',',
				'price_currency_pos' => 'left',
				'shipping'           => '',
				'tax'                => '',
			)
		);
	}

	public function test_round_price_uses_the_decimals_setting() {
		$this->assertEquals( 10.13, \WP_Express_Checkout\Utils::round_price( 10.125 ) );
		WPEC_Test_Factory::update_settings( array( 'price_decimals_num' => '0' ) );
		$this->assertEquals( 10, \WP_Express_Checkout\Utils::round_price( 10.4 ) );
	}

	public function test_tax_amount_is_rounded_to_the_cent() {
		$this->assertEquals( 1.5, \WP_Express_Checkout\Utils::get_tax_amount( 19.99, 7.5 ) );
		$this->assertEquals( 2, \WP_Express_Checkout\Utils::get_tax_amount( 20, 10 ) );
		$this->assertEquals( 0, \WP_Express_Checkout\Utils::get_tax_amount( 20, '' ) );
	}

	public function test_shipping_total_is_the_base_shipping_plus_the_shipping_per_item() {
		$this->assertEquals( 5, \WP_Express_Checkout\Utils::get_total_shipping_cost( array( 'shipping' => '5', 'quantity' => 3 ) ) );
		$this->assertEquals(
			6.5,
			\WP_Express_Checkout\Utils::get_total_shipping_cost(
				array(
					'shipping'              => '5',
					'shipping_per_quantity' => '0.5',
					'quantity'              => 3,
				)
			)
		);
		$this->assertEquals( 1.5, \WP_Express_Checkout\Utils::get_total_shipping_cost( array( 'shipping' => '', 'shipping_per_quantity' => '0.5', 'quantity' => 3 ) ) );
		$this->assertEquals( 0, \WP_Express_Checkout\Utils::get_total_shipping_cost( array() ) );
	}

	/**
	 * @dataProvider amounts_in_cents
	 */
	public function test_amount_in_cents( $amount, $cents ) {
		$this->assertSame( $cents, \WP_Express_Checkout\Utils::amount_in_cents( $amount ) );
	}

	public function amounts_in_cents() {
		return array(
			'whole'                       => array( 10, 1000 ),
			'cents'                       => array( 19.99, 1999 ),
			'not exact in floating point' => array( 0.29, 29 ),
			'not exact 2'                 => array( 1.15, 115 ),
			'string'                      => array( '24.80', 2480 ),
			'thousands'                   => array( 1234.5, 123450 ),
			'more decimals'               => array( 9.999, 1000 ),
		);
	}

	public function test_amount_from_cents() {
		$this->assertEquals( 19.99, \WP_Express_Checkout\Utils::amount_from_cents( 1999, 'USD' ) );
		$this->assertEquals( 1000, \WP_Express_Checkout\Utils::amount_from_cents( 1000, 'JPY' ) );
	}

	public function test_zero_decimal_currencies() {
		foreach ( array( 'JPY', 'jpy', 'KRW', 'VND', 'MGA' ) as $currency ) {
			$this->assertTrue( \WP_Express_Checkout\Utils::is_zero_cents_currency( $currency ), $currency );
		}
		$this->assertFalse( \WP_Express_Checkout\Utils::is_zero_cents_currency( 'USD' ) );
	}

	public function test_almost_equal_allows_less_than_a_cent() {
		$this->assertTrue( \WP_Express_Checkout\Utils::almost_equal( 24.995, 25 ) );
		$this->assertTrue( \WP_Express_Checkout\Utils::almost_equal( 0.1 + 0.2, 0.3 ) );
		$this->assertFalse( \WP_Express_Checkout\Utils::almost_equal( 24.99, 25 ) );
	}

	public function test_price_format() {
		$this->assertSame( '$1,234.50', \WP_Express_Checkout\Utils::price_format( 1234.5 ) );
		$this->assertSame( '1,234.50 $', \WP_Express_Checkout\Utils::price_format( 1234.5, '', 'right_space' ) );
		$this->assertSame( 'EUR1,234.50', \WP_Express_Checkout\Utils::price_format( 1234.5, 'EUR' ) );

		WPEC_Test_Factory::update_settings(
			array(
				'price_decimal_sep'  => ',',
				'price_thousand_sep' => '.',
				'price_currency_pos' => 'right_space',
				'currency_symbol'    => '€',
				'currency_code'      => 'EUR',
			)
		);
		$this->assertSame( '1.234,50 €', \WP_Express_Checkout\Utils::price_format( 1234.5 ) );
	}

	public function test_price_modifier() {
		$this->assertSame( '( + $5.00)', \WP_Express_Checkout\Utils::price_modifier( 5, 'USD' ) );
		$this->assertSame( '( - $2.50)', \WP_Express_Checkout\Utils::price_modifier( -2.5, 'USD' ) );
		$this->assertSame( '', \WP_Express_Checkout\Utils::price_modifier( 0, 'USD' ) );
	}

	// ---- Products ----

	public function test_a_product_without_a_saved_type_is_a_one_time_product() {
		$product_id = WPEC_Test_Factory::create_product( '10', array( 'wpec_product_type' => '' ) );

		$product = \WP_Express_Checkout\Products::retrieve( $product_id );

		$this->assertInstanceOf( '\WP_Express_Checkout\Products\One_Time_Product', $product );
		$this->assertSame( 'one_time', $product->get_type() );
		$this->assertSame( '10', $product->get_price() );
	}

	public function test_a_product_saved_by_an_old_version_with_a_custom_amount_is_a_donation() {
		$product_id = WPEC_Test_Factory::create_product( '10', array( 'wpec_product_type' => '', 'wpec_product_custom_amount' => 1 ) );

		$this->assertSame( 'donation', \WP_Express_Checkout\Products::retrieve( $product_id )->get_type() );
	}

	public function test_a_donation_price_is_the_price_or_the_minimum_amount_whichever_is_higher() {
		$product_id = WPEC_Test_Factory::create_donation_product( 5, array( 'ppec_product_price' => '20' ) );
		$this->assertEquals( 20, \WP_Express_Checkout\Products::retrieve( $product_id )->get_price() );

		$product_id = WPEC_Test_Factory::create_donation_product( 5 );
		$this->assertEquals( 5, \WP_Express_Checkout\Products::retrieve( $product_id )->get_price() );
	}

	public function test_retrieve_rejects_a_post_that_is_not_a_product() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->expectException( Exception::class );
		\WP_Express_Checkout\Products::retrieve( $page_id );
	}

	public function test_retrieve_rejects_an_id_that_is_not_a_number() {
		$this->expectException( Exception::class );
		\WP_Express_Checkout\Products::retrieve( 'abc' );
	}

	public function test_an_empty_product_tax_or_shipping_uses_the_settings_and_zero_does_not() {
		WPEC_Test_Factory::update_settings( array( 'tax' => '8', 'shipping' => '3' ) );

		$empty = \WP_Express_Checkout\Products::retrieve( WPEC_Test_Factory::create_product( '10' ) );
		$this->assertSame( '8', $empty->get_tax() );
		$this->assertSame( '3', $empty->get_shipping() );

		$zero = \WP_Express_Checkout\Products::retrieve(
			WPEC_Test_Factory::create_product(
				'10',
				array(
					'wpec_product_tax'      => '0',
					'wpec_product_shipping' => '0',
				)
			)
		);
		$this->assertSame( '0', $zero->get_tax() );
		$this->assertSame( '0', $zero->get_shipping() );
	}

	public function test_the_product_coupons_setting_falls_back_to_the_settings() {
		WPEC_Test_Factory::update_settings( array( 'coupons_enabled' => 1 ) );

		$this->assertEquals( 1, \WP_Express_Checkout\Products::retrieve( WPEC_Test_Factory::create_product( '10', array( 'wpec_product_coupons_setting' => '' ) ) )->get_coupons_setting() );
		$this->assertEquals( 1, \WP_Express_Checkout\Products::retrieve( WPEC_Test_Factory::create_product( '10', array( 'wpec_product_coupons_setting' => '2' ) ) )->get_coupons_setting() );
		$this->assertEquals( 0, \WP_Express_Checkout\Products::retrieve( WPEC_Test_Factory::create_product( '10', array( 'wpec_product_coupons_setting' => '0' ) ) )->get_coupons_setting() );
	}

	public function test_stock_items_go_down_but_not_below_zero() {
		$product = \WP_Express_Checkout\Products::retrieve( WPEC_Test_Factory::create_product( '10', array( 'wpec_product_stock_items' => 3 ) ) );

		$product->update_stock_items( 2 );
		$this->assertSame( '1', get_post_meta( $product->get_id(), 'wpec_product_stock_items', true ) );

		$product->update_stock_items( 5 );
		$this->assertSame( '0', get_post_meta( $product->get_id(), 'wpec_product_stock_items', true ) );
	}

	public function test_a_product_with_shipping_or_the_physical_option_is_not_digital() {
		$digital = \WP_Express_Checkout\Products::retrieve( WPEC_Test_Factory::create_product( '10' ) );
		$this->assertTrue( $digital->is_digital_product() );

		$shipping = \WP_Express_Checkout\Products::retrieve( WPEC_Test_Factory::create_product( '10', array( 'wpec_product_shipping' => '2' ) ) );
		$this->assertFalse( $shipping->is_digital_product() );

		$physical = \WP_Express_Checkout\Products::retrieve( WPEC_Test_Factory::create_product( '10', array( 'wpec_product_shipping_enable' => 1 ) ) );
		$this->assertFalse( $physical->is_digital_product() );
		$this->assertTrue( $physical->is_physical() );
	}
}
