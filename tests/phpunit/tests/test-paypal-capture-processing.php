<?php
/**
 * The payment processing after a PayPal capture (Payment_Processor::wpec_server_side_process_payment(), called by the
 * wpec_pp_capture_order request with PayPal's capture response).
 *
 * It checks the captured amount and currency against the price data the product shortcode saved (a transient keyed by
 * the product name), creates the order, lowers the stock, redeems the coupon and sends the emails.
 * The tests pass a capture response like PayPal's, so no PayPal API call is made.
 */
class Test_PayPal_Capture_Processing extends WPEC_Ajax_Test_Case {

	private $completed_actions = array();

	public function set_up() {
		parent::set_up();
		WPEC_Test_Factory::update_settings(
			array(
				'is_live'            => 0,
				'sandbox_client_id'  => 'test-client-id',
				'currency_code'      => 'USD',
				'price_decimals_num' => '2',
				'thank_you_url'      => 'http://example.org/thank-you/',
				'send_buyer_email'   => 1,
				'send_seller_email'  => 0,
			)
		);
		reset_phpmailer_instance();
		$this->completed_actions = array();
		add_action(
			'wpec_payment_completed',
			function ( $payment, $order_id, $item_id ) {
				$this->completed_actions[] = array( $order_id, $item_id );
			},
			10,
			3
		);
	}

	/**
	 * Shows the product (the shortcode saves the price data the payment processing checks against).
	 */
	private function show_product( $product_id ) {
		do_shortcode( '[wp_express_checkout product_id="' . $product_id . '"]' );
	}

	/**
	 * A capture response like PayPal's (the parts the plugin reads).
	 */
	private function capture_response( $amount, $overrides = array() ) {
		return array_replace_recursive(
			array(
				'id'             => 'PAYPAL-ORDER-1',
				'status'         => 'COMPLETED',
				'payer'          => array(
					'name'          => array( 'given_name' => 'Jane', 'surname' => 'Buyer' ),
					'email_address' => 'buyer@example.com',
					'payer_id'      => 'PAYERID1',
				),
				'purchase_units' => array(
					array(
						'shipping' => array(
							'address' => array(
								'address_line_1' => '1 Test Street',
								'admin_area_2'   => 'Test City',
								'postal_code'    => '12345',
								'country_code'   => 'US',
							),
						),
						'payments' => array(
							'captures' => array(
								array(
									'id'     => 'CAPTURE-1',
									'status' => 'COMPLETED',
									'amount' => array(
										'value'         => (string) $amount,
										'currency_code' => 'USD',
									),
								),
							),
						),
					),
				),
			),
			$overrides
		);
	}

	private function process( $payment, $data ) {
		return $this->run_request(
			function () use ( $payment, $data ) {
				\WP_Express_Checkout\Payment_Processor::get_instance()->wpec_server_side_process_payment( $payment, $data );
			}
		);
	}

	/**
	 * The orders, newest first.
	 *
	 * @return \WP_Express_Checkout\Order[]
	 */
	private function orders() {
		$ids = get_posts(
			array(
				'post_type'   => 'ppdgorder',
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
				'orderby'     => 'ID',
			)
		);
		return array_map( array( '\WP_Express_Checkout\Orders', 'retrieve' ), $ids );
	}

	private function paid_orders() {
		return array_values(
			array_filter(
				$this->orders(),
				function ( $order ) {
					return 'paid' === $order->get_status();
				}
			)
		);
	}

	private function assert_rejected( $result, $message ) {
		$this->assertSame( 'die', $result );
		$this->assertSame( $message, json_decode( $this->response_body, true ), 'Response: ' . $this->response_body );
		$this->assertCount( 0, $this->paid_orders(), 'No paid order is created.' );
		$this->assertCount( 0, $this->completed_actions, 'wpec_payment_completed does not run.' );
		$this->assertFalse( tests_retrieve_phpmailer_instance()->get_sent(), 'No email is sent.' );
	}

	// ---- Paid ----

	public function test_a_paid_capture_creates_a_paid_order_and_redirects_to_the_thank_you_page() {
		$product_id = WPEC_Test_Factory::create_product( '10.00', array(), 'Capture Product' );
		$this->show_product( $product_id );

		$result = $this->process( $this->capture_response( '10.00' ), WPEC_Test_Factory::frontend_data( $product_id ) );

		$this->assertSame( 'die', $result );
		$orders = $this->paid_orders();
		$this->assertCount( 1, $orders );
		$order = $orders[0];
		$this->assertSame( '10.00', $order->get_total() );
		$this->assertSame( 'USD', $order->get_currency() );
		$this->assertSame( 'paypal', $order->get_payment_gateway() );
		$this->assertSame( 'PAYPAL-ORDER-1', $order->get_resource_id() );
		$this->assertSame( 'CAPTURE-1', $order->get_capture_id() );
		$this->assertSame( 'buyer@example.com', $order->get_email_address() );
		$this->assertSame( '1 Capture Product - COMPLETED', $order->get_description() );
		$item = $order->get_item( 'ppec-products' );
		$this->assertSame( $product_id, $item['post_id'] );
		$this->assertEquals( 1, $item['quantity'] );

		$response = json_decode( $this->response_body, true );
		$this->assertStringStartsWith( 'http://example.org/thank-you/?order_id=' . $order->get_id(), $response['redirect_url'] );
		$this->assertStringContainsString( '_wpnonce=', $response['redirect_url'] );

		$this->assertSame( array( array( $order->get_id(), $product_id ) ), $this->completed_actions );
		$this->assertSame( 'buyer@example.com', tests_retrieve_phpmailer_instance()->get_recipient( 'to' )->address );
	}

	public function test_the_seller_email_is_sent_when_it_is_turned_on() {
		WPEC_Test_Factory::update_settings(
			array(
				'send_buyer_email'     => 0,
				'send_seller_email'    => 1,
				'notify_email_address' => 'seller@example.com',
			)
		);
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$this->show_product( $product_id );

		$this->process( $this->capture_response( '10.00' ), WPEC_Test_Factory::frontend_data( $product_id ) );

		$this->assertCount( 1, $this->paid_orders() );
		$this->assertSame( 'seller@example.com', tests_retrieve_phpmailer_instance()->get_recipient( 'to' )->address );
	}

	public function test_tax_and_shipping_are_added_to_the_order_for_the_buyers_quantity() {
		$product_id = WPEC_Test_Factory::create_product(
			'10.00',
			array(
				'wpec_product_tax'             => '10',
				'wpec_product_shipping'        => '5',
				'ppec_product_custom_quantity' => 1,
			)
		);
		$this->show_product( $product_id );

		//2 x $10 + $2 tax + $5 shipping.
		$this->process( $this->capture_response( '27.00' ), WPEC_Test_Factory::frontend_data( $product_id, array( 'quantity' => 2 ) ) );

		$orders = $this->paid_orders();
		$this->assertCount( 1, $orders );
		$this->assertSame( '27.00', $orders[0]->get_total() );
		$this->assertEquals( 2, $orders[0]->get_item( 'tax' )['price'] );
		$this->assertEquals( 5, $orders[0]->get_item( 'shipping' )['price'] );
		$this->assertNotEmpty( $orders[0]->get_data( 'shipping_address' ) );
	}

	public function test_a_coupon_is_added_to_the_order_and_redeemed() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$coupon_id  = WPEC_Test_Factory::create_coupon( 'TENOFF', 10, 'perc' );
		$this->show_product( $product_id );

		$this->process(
			$this->capture_response( '9.00' ),
			WPEC_Test_Factory::frontend_data( $product_id, array( 'coupon' => array( 'code' => 'TENOFF', 'discount' => '10', 'discountType' => 'perc' ) ) )
		);

		$orders = $this->paid_orders();
		$this->assertCount( 1, $orders );
		$this->assertSame( '9.00', $orders[0]->get_total() );
		$this->assertEquals( -1, $orders[0]->get_item( 'coupon' )['price'] );
		$this->assertEquals( 1, get_post_meta( $coupon_id, 'wpec_coupon_red_count', true ) );
	}

	public function test_a_price_variation_is_added_to_the_order() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		WPEC_Test_Factory::add_variations( $product_id, array( 'Size' => array( 'Small' => 0, 'Large' => 5 ) ) );
		$this->show_product( $product_id );

		$this->process( $this->capture_response( '15.00' ), WPEC_Test_Factory::frontend_data( $product_id, array( 'variations' => array( 1 ) ) ) );

		$orders = $this->paid_orders();
		$this->assertCount( 1, $orders );
		$this->assertSame( '15.00', $orders[0]->get_total() );
		$this->assertSame( 'Size - Large', $orders[0]->get_item( 'variation' )['name'] );
	}

	public function test_the_stock_goes_down() {
		$product_id = WPEC_Test_Factory::create_product(
			'10.00',
			array(
				'wpec_product_enable_stock'    => 1,
				'wpec_product_stock_items'     => 5,
				'ppec_product_custom_quantity' => 1,
			)
		);
		$this->show_product( $product_id );

		$this->process( $this->capture_response( '20.00' ), WPEC_Test_Factory::frontend_data( $product_id, array( 'quantity' => 2 ) ) );

		$this->assertCount( 1, $this->paid_orders() );
		$this->assertEquals( 3, get_post_meta( $product_id, 'wpec_product_stock_items', true ) );
	}

	public function test_a_donation_creates_an_order_with_the_buyers_amount() {
		$product_id = WPEC_Test_Factory::create_donation_product( 5 );
		$this->show_product( $product_id );

		$this->process( $this->capture_response( '20.00' ), WPEC_Test_Factory::frontend_data( $product_id, array( 'amount' => 20 ) ) );

		$orders = $this->paid_orders();
		$this->assertCount( 1, $orders );
		$this->assertSame( '20.00', $orders[0]->get_total() );
	}

	public function test_the_quantity_is_the_products_quantity_when_the_buyer_cannot_change_it() {
		$product_id = WPEC_Test_Factory::create_product( '10.00', array( 'ppec_product_quantity' => 2 ) );
		$this->show_product( $product_id );
		$data = WPEC_Test_Factory::frontend_data( $product_id, array( 'quantity' => 2 ) );
		$data['quantity'] = 1;

		//The product is sold in 2s: paying for 1 is rejected.
		$this->assertSame( 'die', $this->process( $this->capture_response( '10.00' ), $data ) );
		$this->assertCount( 0, $this->paid_orders() );

		$this->process( $this->capture_response( '20.00' ), $data );
		$this->assertCount( 1, $this->paid_orders() );
	}

	// ---- Rejected ----

	public function test_a_capture_lower_than_the_price_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$this->show_product( $product_id );

		$this->assert_rejected(
			$this->process( $this->capture_response( '0.01' ), WPEC_Test_Factory::frontend_data( $product_id ) ),
			'Payment amount mismatch with the original price.'
		);
	}

	public function test_a_capture_without_the_tax_and_shipping_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product(
			'10.00',
			array(
				'wpec_product_tax'      => '10',
				'wpec_product_shipping' => '5',
			)
		);
		$this->show_product( $product_id );

		$this->assert_rejected(
			$this->process( $this->capture_response( '10.00' ), WPEC_Test_Factory::frontend_data( $product_id ) ),
			'Payment amount mismatch with the original price.'
		);
	}

	public function test_a_more_expensive_quantity_paid_for_one_item_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '10.00', array( 'ppec_product_custom_quantity' => 1 ) );
		$this->show_product( $product_id );

		$this->assert_rejected(
			$this->process( $this->capture_response( '10.00' ), WPEC_Test_Factory::frontend_data( $product_id, array( 'quantity' => 3 ) ) ),
			'Payment amount mismatch with the original price.'
		);
	}

	public function test_a_made_up_coupon_does_not_lower_the_price() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$this->show_product( $product_id );

		$this->assert_rejected(
			$this->process(
				$this->capture_response( '1.00' ),
				WPEC_Test_Factory::frontend_data( $product_id, array( 'coupon' => array( 'code' => 'NOSUCHCODE', 'discount' => '90', 'discountType' => 'perc' ) ) )
			),
			'Payment amount mismatch with the original price.'
		);
	}

	public function test_a_donation_below_the_minimum_amount_is_rejected() {
		$product_id = WPEC_Test_Factory::create_donation_product( 5 );
		$this->show_product( $product_id );

		$this->assert_rejected(
			$this->process( $this->capture_response( '1.00' ), WPEC_Test_Factory::frontend_data( $product_id, array( 'amount' => 1 ) ) ),
			'Payment amount mismatch with the original price.'
		);
	}

	public function test_a_capture_in_another_currency_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$this->show_product( $product_id );
		$payment = $this->capture_response( '10.00' );
		$payment['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'] = 'EUR';

		$this->assert_rejected(
			$this->process( $payment, WPEC_Test_Factory::frontend_data( $product_id ) ),
			'Payment currency mismatch.'
		);
	}

	public function test_a_capture_that_is_not_completed_is_rejected() {
		$product_id = WPEC_Test_Factory::create_product( '10.00' );
		$this->show_product( $product_id );

		$this->assert_rejected(
			$this->process( $this->capture_response( '10.00', array( 'status' => 'PAYER_ACTION_REQUIRED' ) ), WPEC_Test_Factory::frontend_data( $product_id ) ),
			'Payment is not approved. Status: PAYER_ACTION_REQUIRED'
		);
		$this->assertCount( 0, $this->orders(), 'No order is created.' );
	}

	public function test_more_items_than_in_stock_are_rejected() {
		$product_id = WPEC_Test_Factory::create_product(
			'10.00',
			array(
				'wpec_product_enable_stock'    => 1,
				'wpec_product_stock_items'     => 1,
				'ppec_product_custom_quantity' => 1,
			)
		);
		$this->show_product( $product_id );

		$this->assert_rejected(
			$this->process( $this->capture_response( '20.00' ), WPEC_Test_Factory::frontend_data( $product_id, array( 'quantity' => 2 ) ) ),
			'There are not enough product items in stock.'
		);
		$this->assertEquals( 1, get_post_meta( $product_id, 'wpec_product_stock_items', true ) );
	}

	public function test_a_product_that_was_not_shown_is_rejected() {
		//The price data is saved when the product is shown (the transient); without it there is nothing to check the payment against.
		$product_id = WPEC_Test_Factory::create_product( '10.00', array(), 'Never Shown Product' );

		$this->assert_rejected(
			$this->process( $this->capture_response( '10.00' ), WPEC_Test_Factory::frontend_data( $product_id ) ),
			'No transaction info found in transient.'
		);
		$this->assertCount( 0, $this->orders(), 'No order is created.' );
	}
}
