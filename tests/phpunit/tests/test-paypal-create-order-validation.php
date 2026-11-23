<?php
/**
 * The PayPal create order request (wpec_pp_create_order, includes/class-paypal-button-ajax-handler.php).
 *
 * The tests send the request the way assets/js/wpec-paypal.js sends it, and stop it right after the pre-submission
 * validation passed (before the PayPal API call). The amount sent to PayPal is the validated total itself; the amount
 * actually paid is checked again after the capture (see test-paypal-capture-processing.php).
 */
class Test_PayPal_Create_Order_Validation extends WPEC_Ajax_Test_Case {

	public function set_up() {
		parent::set_up();
		WPEC_Test_Factory::update_settings(
			array(
				'is_live'            => 0,
				'sandbox_client_id'  => 'test-client-id',
				'sandbox_secret_key' => 'test-secret',
				'currency_code'      => 'USD',
				'price_decimals_num' => '2',
			)
		);
	}

	private function send( $wpec_data, $order_data = null ) {
		$this->stop_after_validation_passes();
		return $this->send_ajax( WPEC_Test_Factory::paypal_create_order_request( $wpec_data, $order_data ) );
	}

	private function assert_passes( $wpec_data, $order_data = null ) {
		$result = $this->send( $wpec_data, $order_data );
		$this->assertSame( 'stopped', $result, 'The request was expected to pass the validation. Response: ' . $this->response_body );
		$this->assertTrue( $this->validation_result );
	}

	// ---- Accepted ----

	public function test_one_item_passes() {
		$this->assert_passes( WPEC_Test_Factory::frontend_data( WPEC_Test_Factory::create_product( '10.00' ) ) );
	}

	public function test_items_with_tax_and_shipping_pass() {
		$product_id = WPEC_Test_Factory::create_product(
			'19.99',
			array(
				'wpec_product_tax'                   => '7.5',
				'wpec_product_shipping'              => '4',
				'wpec_product_shipping_per_quantity' => '0.5',
			)
		);

		$this->assert_passes( WPEC_Test_Factory::frontend_data( $product_id, array( 'quantity' => 3 ) ) );
	}

	public function test_a_percent_coupon_passes() {
		$product_id = WPEC_Test_Factory::create_product( '49.99' );
		WPEC_Test_Factory::create_coupon( 'HALF', 50, 'perc' );

		$this->assert_passes(
			WPEC_Test_Factory::frontend_data(
				$product_id,
				array( 'coupon' => array( 'code' => 'HALF', 'discount' => '50', 'discountType' => 'perc' ) )
			)
		);
	}

	public function test_a_price_variation_passes() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		WPEC_Test_Factory::add_variations(
			$product_id,
			array(
				'Size'  => array( 'Small' => 0, 'Large' => 5 ),
				'Color' => array( 'Red' => 0, 'Gold' => 2.5 ),
			)
		);

		$this->assert_passes( WPEC_Test_Factory::frontend_data( $product_id, array( 'variations' => array( 1, 1 ) ) ) );
	}

	public function test_variations_that_make_up_the_whole_price_pass() {
		//"Hide amount input" products: the variation prices are the product price.
		$product_id = WPEC_Test_Factory::create_product( '0', array( 'wpec_product_hide_amount_input' => 1 ) );
		WPEC_Test_Factory::add_variations( $product_id, array( 'Plan' => array( 'Basic' => 20, 'Pro' => 50 ) ) );
		$data = WPEC_Test_Factory::frontend_data( $product_id, array( 'variations' => array( 1 ) ) );
		$this->assertEquals( 50, $data['total'] );

		$this->assert_passes( $data );
	}

	public function test_a_donation_passes_with_any_amount() {
		//Donations aren't checked when the order is created; the amount paid is checked after the capture
		//(not less than the minimum amount).
		$product_id = WPEC_Test_Factory::create_donation_product( 5 );

		$this->assert_passes( WPEC_Test_Factory::frontend_data( $product_id, array( 'amount' => 1 ) ) );
	}

	// ---- Rejected ----

	public function test_a_lower_total_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$data       = WPEC_Test_Factory::frontend_data( $product_id );
		$order      = WPEC_Test_Factory::paypal_order_data( $data );
		$order['purchase_units'][0]['amount']['value'] = 0.01;

		$this->assertErrorResponse( $this->send( $data, $order ), 'Price validation failed' );
		$this->assertFalse( $this->validation_result );
	}

	public function test_a_total_without_the_tax_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '10.00', array( 'wpec_product_tax' => '10' ) );
		$data       = WPEC_Test_Factory::frontend_data( $product_id, array( 'quantity' => 2 ) );
		$order      = WPEC_Test_Factory::paypal_order_data( $data );
		$this->assertEquals( 22, $order['purchase_units'][0]['amount']['value'] );
		$order['purchase_units'][0]['amount']['value'] = 20;

		$this->assertErrorResponse( $this->send( $data, $order ), 'Price validation failed' );
	}

	public function test_a_quantity_that_does_not_match_the_total_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$data       = WPEC_Test_Factory::frontend_data( $product_id );
		$order      = WPEC_Test_Factory::paypal_order_data( $data );
		$order['purchase_units'][0]['items'][0]['quantity'] = 5;

		$this->assertErrorResponse( $this->send( $data, $order ), 'Price validation failed' );
	}

	public function test_another_currency_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$data       = WPEC_Test_Factory::frontend_data( $product_id );
		$order      = WPEC_Test_Factory::paypal_order_data( $data );
		$order['purchase_units'][0]['amount']['currency_code'] = 'EUR';

		$this->assertErrorResponse( $this->send( $data, $order ), 'Currency validation failed' );
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

	public function test_a_wrong_nonce_is_rejected() {
		$post = WPEC_Test_Factory::paypal_create_order_request( WPEC_Test_Factory::frontend_data( WPEC_Test_Factory::create_product( '10.00' ) ) );
		$post['_wpnonce'] = 'wrong';
		$this->stop_after_validation_passes();

		$this->assertErrorResponse( $this->send_ajax( $post ), 'Nonce check failed' );
		$this->assertNull( $this->validation_result, 'The validation does not run.' );
	}

	public function test_empty_plugin_data_is_rejected() {
		$post = WPEC_Test_Factory::paypal_create_order_request( WPEC_Test_Factory::frontend_data( WPEC_Test_Factory::create_product( '10.00' ) ) );
		$post['wpec_data'] = '';

		$this->assertErrorResponse( $this->send_ajax( $post ), 'Empty WPEC plugin data' );
	}

	public function test_an_unknown_product_is_rejected() {
		$data = WPEC_Test_Factory::frontend_data( WPEC_Test_Factory::create_product( '10.00' ) );
		$data['product_id'] = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertErrorResponse( $this->send( $data ), 'Validation Error' );
	}

	// ---- The order data sent to PayPal ----

	public function test_the_order_sent_to_paypal_uses_the_product_name_from_the_database_and_the_validated_amounts() {
		$product_id = WPEC_Test_Factory::create_product(
			'10.00',
			array(
				'wpec_product_tax'      => '10',
				'wpec_product_shipping' => '5',
			),
			'Real Product Name'
		);
		WPEC_Test_Factory::create_coupon( 'TENOFF', 10, 'perc' );
		$data  = WPEC_Test_Factory::frontend_data(
			$product_id,
			array(
				'quantity' => 2,
				'coupon'   => array( 'code' => 'TENOFF', 'discount' => '10', 'discountType' => 'perc' ),
			)
		);
		$order = WPEC_Test_Factory::paypal_order_data( $data );
		$order['purchase_units'][0]['items'][0]['name'] = 'Name sent by the browser';

		$pp = \WP_Express_Checkout\PayPal_Payment_Button_Ajax_Handler::create_order_data_for_pp_api( $order, $data );

		$unit = $pp['purchase_units'][0];
		$this->assertSame( 'Real Product Name', $unit['items'][0]['name'] );
		$this->assertSame( 2, $unit['items'][0]['quantity'] );
		$this->assertSame( '10', $unit['items'][0]['unit_amount']['value'] );
		//2 x $10 = $20, 10% off = $18, tax $1.80, shipping $5 = $24.80.
		$this->assertSame( '24.8', $unit['amount']['value'] );
		$this->assertSame( '20', $unit['amount']['breakdown']['item_total']['value'] );
		$this->assertSame( '1.8', $unit['amount']['breakdown']['tax_total']['value'] );
		$this->assertSame( '5', $unit['amount']['breakdown']['shipping']['value'] );
		$this->assertSame( '2', $unit['amount']['breakdown']['discount']['value'] );
		$this->assertSame( 'GET_FROM_FILE', $pp['payment_source']['paypal']['experience_context']['shipping_preference'] );
	}

	public function test_the_order_sent_to_paypal_has_no_tax_shipping_or_discount_lines_when_there_are_none() {
		$data  = WPEC_Test_Factory::frontend_data( WPEC_Test_Factory::create_product( '10.00' ) );
		$order = WPEC_Test_Factory::paypal_order_data( $data );

		$pp = \WP_Express_Checkout\PayPal_Payment_Button_Ajax_Handler::create_order_data_for_pp_api( $order, $data );

		$this->assertSame( array( 'item_total' ), array_keys( $pp['purchase_units'][0]['amount']['breakdown'] ) );
		$this->assertSame( 'NO_SHIPPING', $pp['payment_source']['paypal']['experience_context']['shipping_preference'] );
	}

	public function test_a_long_product_name_is_cut_to_127_characters() {
		$data  = WPEC_Test_Factory::frontend_data( WPEC_Test_Factory::create_product( '10.00', array(), str_repeat( 'A', 200 ) ) );
		$order = WPEC_Test_Factory::paypal_order_data( $data );

		$pp = \WP_Express_Checkout\PayPal_Payment_Button_Ajax_Handler::create_order_data_for_pp_api( $order, $data );

		$this->assertSame( 127, strlen( $pp['purchase_units'][0]['items'][0]['name'] ) );
	}
}
