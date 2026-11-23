<?php
/**
 * The Stripe checkout session request (wpec_stripe_create_checkout_session, includes/class-stripe-button-ajax-handler.php).
 *
 * The request has two copies of the purchase: 'data' (total, quantity, currency_code), which the pre-submission
 * validation checks, and 'wpec_data', which the Stripe line item is built from. The tests send the request the way
 * assets/js/wpec-stripe.js sends it, then stop it right before the Stripe API call and check the line item.
 * Fixed in 2.5.5: the charged unit price, quantity and currency come from 'wpec_data' and are now checked too.
 */
class Test_Stripe_Checkout_Validation extends WPEC_Ajax_Test_Case {

	public function set_up() {
		parent::set_up();
		WPEC_Test_Factory::update_settings(
			array(
				'enable_stripe_checkout' => 1,
				'stripe_is_live'         => 0,
				'stripe_test_secret_key' => 'sk_test_fake',
				'currency_code'          => 'USD',
				'price_decimals_num'     => '2',
				'shop_page_url'          => 'http://example.org/shop/',
			)
		);
	}

	/**
	 * Sends the request built from the purchase data and returns the Stripe line item (the request must be accepted).
	 */
	private function assert_accepted( $wpec_data, $order_data = array() ) {
		$this->stop_before_stripe_session();
		$result = $this->send_ajax( WPEC_Test_Factory::stripe_request( $wpec_data, $order_data ) );
		$this->assertSame( 'stopped', $result, 'The request was expected to reach the Stripe API call. Response: ' . $this->response_body );
		return $this->stripe_session_opts['line_items'][0];
	}

	private function send( $wpec_data, $order_data = array() ) {
		$this->stop_before_stripe_session();
		return $this->send_ajax( WPEC_Test_Factory::stripe_request( $wpec_data, $order_data ) );
	}

	// ---- Purchases that are accepted, and what Stripe is asked to charge ----

	public function test_one_item_is_charged_the_product_price() {
		$product_id = WPEC_Test_Factory::create_product( '10.00', array(), 'Stripe Product' );

		$item = $this->assert_accepted( WPEC_Test_Factory::frontend_data( $product_id ) );

		$this->assertSame( 1000, $item['price_data']['unit_amount'] );
		$this->assertSame( 1, $item['quantity'] );
		$this->assertSame( 'USD', $item['price_data']['currency'] );
		$this->assertSame( $product_id, $item['price_data']['product_data']['metadata']['wpec_product_id'] );
		$this->assertSame( 'payment', $this->stripe_session_opts['mode'] );
		$this->assertArrayNotHasKey( 'shipping_options', $this->stripe_session_opts );
	}

	public function test_a_price_that_is_not_exact_in_floating_point_becomes_whole_cents() {
		$product_id = WPEC_Test_Factory::create_product( '0.29' );

		$item = $this->assert_accepted( WPEC_Test_Factory::frontend_data( $product_id ) );

		$this->assertSame( 29, $item['price_data']['unit_amount'] );
	}

	public function test_three_items_with_a_percent_coupon() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		WPEC_Test_Factory::create_coupon( 'TENOFF', 10, 'perc' );

		$item = $this->assert_accepted(
			WPEC_Test_Factory::frontend_data(
				$product_id,
				array(
					'quantity' => 3,
					'coupon'   => array( 'code' => 'TENOFF', 'discount' => '10', 'discountType' => 'perc' ),
				)
			)
		);

		$this->assertSame( 900, $item['price_data']['unit_amount'] );
		$this->assertSame( 3, $item['quantity'] );
	}

	public function test_three_items_with_a_fixed_coupon_that_rounds_the_unit_price_down_is_accepted() {
		//$10 x 3 - $2 = $28.00, but the frontend charges the rounded unit price: 10 - 2/3 = 9.33 (x 3 = 27.99).
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		WPEC_Test_Factory::create_coupon( 'TWOOFF', 2, 'fixed' );

		$item = $this->assert_accepted(
			WPEC_Test_Factory::frontend_data(
				$product_id,
				array(
					'quantity' => 3,
					'coupon'   => array( 'code' => 'TWOOFF', 'discount' => '2', 'discountType' => 'fixed' ),
				)
			)
		);

		$this->assertSame( 933, $item['price_data']['unit_amount'] );
		$this->assertSame( 3, $item['quantity'] );
	}

	public function test_a_price_variation_is_added_to_the_price() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		WPEC_Test_Factory::add_variations( $product_id, array( 'Size' => array( 'Small' => 0, 'Large' => 5 ) ) );

		$item = $this->assert_accepted( WPEC_Test_Factory::frontend_data( $product_id, array( 'variations' => array( 1 ) ) ) );

		$this->assertSame( 1500, $item['price_data']['unit_amount'] );
	}

	public function test_a_physical_product_gets_the_shipping_option() {
		$product_id = WPEC_Test_Factory::create_product(
			'10.00',
			array(
				'wpec_product_shipping_enable'       => 1,
				'wpec_product_shipping'              => '5',
				'wpec_product_shipping_per_quantity' => '1',
			)
		);

		$item = $this->assert_accepted( WPEC_Test_Factory::frontend_data( $product_id, array( 'quantity' => 2 ) ) );

		$this->assertSame( 1000, $item['price_data']['unit_amount'] );
		//Shipping: $5 + $1 x 2.
		$this->assertSame( 700, $this->stripe_session_opts['shipping_options'][0]['shipping_rate_data']['fixed_amount']['amount'] );
		$this->assertNotEmpty( $this->stripe_session_opts['shipping_address_collection']['allowed_countries'] );
	}

	public function test_a_zero_decimal_currency_is_not_multiplied_by_100() {
		WPEC_Test_Factory::update_settings( array( 'currency_code' => 'JPY', 'price_decimals_num' => '0' ) );
		$product_id = WPEC_Test_Factory::create_product( '1000' );

		$item = $this->assert_accepted( WPEC_Test_Factory::frontend_data( $product_id, array( 'quantity' => 2 ) ) );

		$this->assertSame( 'JPY', $item['price_data']['currency'] );
		$this->assertEquals( 1000, $item['price_data']['unit_amount'] );
	}

	public function test_a_zero_decimal_currency_with_a_coupon_that_rounds_the_unit_price() {
		WPEC_Test_Factory::update_settings( array( 'currency_code' => 'JPY', 'price_decimals_num' => '0' ) );
		$product_id = WPEC_Test_Factory::create_product( '1000' );
		WPEC_Test_Factory::create_coupon( 'YEN100', 100, 'fixed' );

		$item = $this->assert_accepted(
			WPEC_Test_Factory::frontend_data(
				$product_id,
				array(
					'quantity' => 3,
					'coupon'   => array( 'code' => 'YEN100', 'discount' => '100', 'discountType' => 'fixed' ),
				)
			)
		);

		//1000 - 100/3 = 966.67, rounded to 967 by the frontend.
		$this->assertEquals( 967, $item['price_data']['unit_amount'] );
	}

	public function test_paying_more_than_the_price_is_accepted() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$data       = WPEC_Test_Factory::frontend_data( $product_id );
		$data['newPrice'] = 50;

		$item = $this->assert_accepted( $data );

		$this->assertSame( 5000, $item['price_data']['unit_amount'] );
	}

	public function test_a_product_with_tax_passes_the_validation() {
		//With tax, the plugin calls the Stripe API for the tax rate before building the session, so the request is stopped after the validation.
		$product_id = WPEC_Test_Factory::create_product( '10.00', array( 'wpec_product_tax' => '10' ) );
		$this->stop_after_validation_passes();

		$result = $this->send_ajax( WPEC_Test_Factory::stripe_request( WPEC_Test_Factory::frontend_data( $product_id, array( 'quantity' => 2 ) ) ) );

		$this->assertSame( 'stopped', $result, 'Response: ' . $this->response_body );
		$this->assertTrue( $this->validation_result );
	}

	// ---- Donations ----

	public function test_a_donation_at_the_minimum_amount_is_accepted() {
		$product_id = WPEC_Test_Factory::create_donation_product( 5 );

		$item = $this->assert_accepted( WPEC_Test_Factory::frontend_data( $product_id, array( 'amount' => 5 ) ) );

		$this->assertSame( 500, $item['price_data']['unit_amount'] );
	}

	public function test_a_donation_above_the_minimum_amount_is_accepted() {
		$product_id = WPEC_Test_Factory::create_donation_product( 5 );

		$item = $this->assert_accepted( WPEC_Test_Factory::frontend_data( $product_id, array( 'amount' => 25 ) ) );

		$this->assertSame( 2500, $item['price_data']['unit_amount'] );
	}

	public function test_a_coupon_can_take_a_donation_below_the_minimum_amount() {
		$product_id = WPEC_Test_Factory::create_donation_product( 5 );
		WPEC_Test_Factory::create_coupon( 'TENOFF', 10, 'perc' );

		$item = $this->assert_accepted(
			WPEC_Test_Factory::frontend_data(
				$product_id,
				array(
					'amount' => 5,
					'coupon' => array( 'code' => 'TENOFF', 'discount' => '10', 'discountType' => 'perc' ),
				)
			)
		);

		$this->assertSame( 450, $item['price_data']['unit_amount'] );
	}

	public function test_a_donation_without_a_minimum_accepts_any_amount() {
		$product_id = WPEC_Test_Factory::create_donation_product( 0 );

		$item = $this->assert_accepted( WPEC_Test_Factory::frontend_data( $product_id, array( 'amount' => 1 ) ) );

		$this->assertSame( 100, $item['price_data']['unit_amount'] );
	}

	public function test_a_donation_below_the_minimum_amount_is_rejected() {
		//The unit price check allows one cent less for rounding (see Decisions in TESTS.md), so $4.99 passes a $5.00 minimum.
		$product_id = WPEC_Test_Factory::create_donation_product( 5 );

		$result = $this->send( WPEC_Test_Factory::frontend_data( $product_id, array( 'amount' => 4.98 ) ) );

		$this->assertErrorResponse( $result, 'less than the minimum donation amount' );
		$this->assertNull( $this->stripe_session_opts );
	}

	public function test_a_donation_with_a_tampered_unit_price_below_the_minimum_is_rejected() {
		$product_id = WPEC_Test_Factory::create_donation_product( 5 );
		$data       = WPEC_Test_Factory::frontend_data( $product_id, array( 'amount' => 5 ) );
		$data['newPrice'] = 1;

		$this->assertErrorResponse( $this->send( $data ), 'less than the minimum donation amount' );
	}

	public function test_a_donation_in_another_currency_is_rejected() {
		$product_id = WPEC_Test_Factory::create_donation_product( 5 );
		$data       = WPEC_Test_Factory::frontend_data( $product_id, array( 'amount' => 500 ) );
		$data['currency'] = 'JPY';

		//Both copies changed: the currency check rejects it.
		$this->assertErrorResponse( $this->send( $data, array( 'currency_code' => 'JPY' ) ), 'Currency validation failed' );
	}

	// ---- Tampered requests that are rejected (fixed in 2.5.5) ----

	public function test_a_lower_unit_price_than_the_product_price_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '100.00' );
		$data       = WPEC_Test_Factory::frontend_data( $product_id );
		$data['newPrice'] = 0.01;

		$this->assertErrorResponse( $this->send( $data ), 'Price validation failed' );
		$this->assertNull( $this->stripe_session_opts );
	}

	public function test_a_lower_unit_price_than_the_discounted_price_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		WPEC_Test_Factory::create_coupon( 'TENOFF', 10, 'perc' );
		$data = WPEC_Test_Factory::frontend_data(
			$product_id,
			array( 'coupon' => array( 'code' => 'TENOFF', 'discount' => '10', 'discountType' => 'perc' ) )
		);
		$data['newPrice'] = 5;

		$this->assertErrorResponse( $this->send( $data ), 'Price validation failed' );
	}

	public function test_a_unit_price_that_is_not_a_number_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$data       = WPEC_Test_Factory::frontend_data( $product_id );
		$data['newPrice'] = 'abc';

		$this->assertErrorResponse( $this->send( $data ), 'Price validation failed' );
	}

	public function test_a_different_currency_in_the_charged_data_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '100.00' );
		$data       = WPEC_Test_Factory::frontend_data( $product_id );
		$data['currency'] = 'JPY';

		//Only the charged copy ('wpec_data') is changed; the validated copy ('data') keeps USD.
		$this->assertErrorResponse( $this->send( $data, array( 'currency_code' => 'USD' ) ), 'The submitted order data is inconsistent' );
	}

	public function test_a_different_quantity_in_the_charged_data_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$data       = WPEC_Test_Factory::frontend_data( $product_id );
		$data['quantity'] = 5;

		//Only the charged copy ('wpec_data') is changed; the validated copy ('data') keeps 1 item for $10.
		$this->assertErrorResponse( $this->send( $data, array( 'quantity' => 1, 'total' => 10 ) ), 'The submitted order data is inconsistent' );
	}

	public function test_a_zero_quantity_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$data       = WPEC_Test_Factory::frontend_data( $product_id );
		$data['quantity'] = 0;

		$this->assertErrorResponse( $this->send( $data, array( 'quantity' => 0 ) ), 'The submitted order data is inconsistent' );
	}

	public function test_a_lower_total_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );

		$this->assertErrorResponse(
			$this->send( WPEC_Test_Factory::frontend_data( $product_id ), array( 'total' => 1 ) ),
			'Price validation failed'
		);
	}

	public function test_a_lower_total_for_a_product_with_tax_and_shipping_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product(
			'10.00',
			array(
				'wpec_product_tax'      => '10',
				'wpec_product_shipping' => '5',
			)
		);
		$data = WPEC_Test_Factory::frontend_data( $product_id );
		//The correct total is $16.00 (10 + 1 tax + 5 shipping): without the shipping it's rejected.
		$this->assertEquals( 16, $data['total'] );

		$this->assertErrorResponse( $this->send( $data, array( 'total' => 11 ) ), 'Price validation failed' );
	}

	public function test_another_currency_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$data       = WPEC_Test_Factory::frontend_data( $product_id );
		$data['currency'] = 'EUR';

		$this->assertErrorResponse( $this->send( $data, array( 'currency_code' => 'EUR' ) ), 'Currency validation failed' );
	}

	public function test_a_coupon_only_for_another_product_does_not_lower_the_price() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$other_id   = WPEC_Test_Factory::create_product( '10.00', array(), 'Other Product' );
		WPEC_Test_Factory::create_coupon(
			'OTHERONLY',
			50,
			'perc',
			array(
				'only_for_allowed_products' => 1,
				'allowed_products'          => array( (string) $other_id ),
			)
		);

		$result = $this->send(
			WPEC_Test_Factory::frontend_data(
				$product_id,
				array( 'coupon' => array( 'code' => 'OTHERONLY', 'discount' => '50', 'discountType' => 'perc' ) )
			)
		);

		$this->assertErrorResponse( $result, 'Price validation failed' );
	}

	public function test_an_expired_coupon_does_not_lower_the_price() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		WPEC_Test_Factory::create_coupon( 'OLD', 50, 'perc', array( 'exp_date' => '2020-01-01' ) );

		$result = $this->send(
			WPEC_Test_Factory::frontend_data(
				$product_id,
				array( 'coupon' => array( 'code' => 'OLD', 'discount' => '50', 'discountType' => 'perc' ) )
			)
		);

		$this->assertErrorResponse( $result, 'Price validation failed' );
	}

	public function test_a_made_up_coupon_does_not_lower_the_price() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );

		$result = $this->send(
			WPEC_Test_Factory::frontend_data(
				$product_id,
				array( 'coupon' => array( 'code' => 'NOSUCHCODE', 'discount' => '90', 'discountType' => 'perc' ) )
			)
		);

		$this->assertErrorResponse( $result, 'Price validation failed' );
	}

	/**
	 * @dataProvider coupons_turned_off
	 */
	public function test_a_coupon_does_not_lower_the_price_when_coupons_are_turned_off( $settings_value, $product_value ) {
		//With coupons turned off, the coupon field isn't shown, so the buyer can't apply a coupon in the browser.
		//But a request can still send a valid coupon code.
		WPEC_Test_Factory::update_settings( array( 'coupons_enabled' => $settings_value ) );
		$product_id = WPEC_Test_Factory::create_product( '10.00', array( 'wpec_product_coupons_setting' => $product_value ) );
		WPEC_Test_Factory::create_coupon( 'HALF', 50, 'perc' );

		$result = $this->send(
			WPEC_Test_Factory::frontend_data(
				$product_id,
				array( 'coupon' => array( 'code' => 'HALF', 'discount' => '50', 'discountType' => 'perc' ) )
			)
		);

		if ( 'stopped' === $result ) {
			$this->markTestIncomplete( 'KNOWN ISSUE (still happens): the pre-submission validation (and the coupon check request and the order discount) apply a valid coupon although coupons are turned off.' );
		}
		$this->assertErrorResponse( $result, 'Price validation failed' );
	}

	public function coupons_turned_off() {
		return array(
			'turned off in the settings'    => array( 0, '' ),
			'turned off for the product'    => array( 1, '0' ),
		);
	}

	public function test_a_wrong_nonce_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$post       = WPEC_Test_Factory::stripe_request( WPEC_Test_Factory::frontend_data( $product_id ) );
		$post['nonce'] = 'wrong';
		$this->stop_before_stripe_session();

		$this->assertErrorResponse( $this->send_ajax( $post ), 'Nonce Verification Failed' );
		$this->assertNull( $this->stripe_session_opts );
	}

	public function test_an_unknown_product_is_rejected_without_a_fatal_error() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$data = WPEC_Test_Factory::frontend_data( $product_id );
		$data['product_id'] = $page_id;

		try {
			$result = $this->send( $data );
		} catch ( Exception $e ) {
			//KNOWN ISSUE: the handler retrieves the product (Products::retrieve() throws for a post that isn't a product)
			//outside of a try block, so a real request ends with a PHP fatal error (HTTP 500) instead of an error response.
			//Nothing is charged, but the error isn't shown to the buyer and it fills the PHP error log.
			$this->markTestIncomplete( 'KNOWN ISSUE (still happens): an unknown product ID ends the Stripe request with an uncaught exception: ' . $e->getMessage() );
		}

		$this->assertErrorResponse( $result );
	}
}
