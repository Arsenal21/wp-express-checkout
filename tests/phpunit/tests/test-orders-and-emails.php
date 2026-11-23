<?php
/**
 * Orders (includes/class-order.php, class-orders.php), the order tags used in the emails and on the Thank You page
 * (public/includes/class-order-tags-*.php) and the emails (includes/class-emails.php).
 */
class Test_Orders_And_Emails extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		WPEC_Test_Factory::update_settings(
			array(
				'currency_code'      => 'USD',
				'currency_symbol'    => '$',
				'price_decimals_num' => '2',
				'send_buyer_email'   => 1,
				'buyer_email_type'   => 'text',
				'buyer_from_email'   => 'Shop <shop@example.com>',
				'buyer_email_subj'   => 'Order {order_id} for {product_name}',
				'buyer_email_body'   => "Dear {first_name} {last_name}\n\n{product_details}",
				'send_seller_email'  => 0,
				'notify_email_address' => 'seller@example.com',
				'seller_email_subj'  => 'Sale of {product_name}',
				'seller_email_body'  => 'Sold to {payer_email} for {purchase_amt}',
				'enable_per_product_email_customization' => 0,
			)
		);
		reset_phpmailer_instance();
	}

	/**
	 * A paid order like the payment processing creates it: 2 x $10 with a $2 coupon, $1.80 tax and $5 shipping.
	 */
	private function paid_order( $product_id ) {
		$order = \WP_Express_Checkout\Orders::create();
		$order->set_payment_gateway( 'paypal' );
		$order->set_currency( 'USD' );
		$order->set_resource_id( 'PAYPAL-ORDER-1' );
		$order->set_capture_id( 'CAPTURE-1' );
		$order->set_author_email( 'buyer@example.com' );
		$order->add_item( 'ppec-products', get_the_title( $product_id ), 10, 2, $product_id, true, array( 'product_type' => 'one_time' ) );
		$order->add_data( 'state', 'COMPLETED' );
		$order->add_data(
			'payer',
			array(
				'name'          => array(
					'given_name' => 'Jane',
					'surname'    => 'Buyer',
				),
				'email_address' => 'buyer@example.com',
			)
		);
		$order->add_data( 'shipping_address', '1 Test Street, Test City, CA, 12345, US' );
		$order->add_item( 'coupon', 'Coupon Code: TENOFF', -2, 1, 0, false, array( 'code' => 'TENOFF' ) );
		$order->add_item( 'tax', 'Tax', 1.8 );
		$order->add_item( 'shipping', 'Shipping', 5 );
		$order->set_status( 'paid' );
		return \WP_Express_Checkout\Orders::retrieve( $order->get_id() );
	}

	// ---- Orders ----

	public function test_a_new_order_is_an_incomplete_transaction_with_the_buyers_ip_address() {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.5';

		$order = \WP_Express_Checkout\Orders::create();

		$this->assertSame( 'incomplete', $order->get_status() );
		$this->assertSame( 'Transaction', $order->get_description() );
		$this->assertSame( '203.0.113.5', $order->get_ip_address() );
		$this->assertSame( (string) $order->get_id(), get_post( $order->get_id() )->post_name );
	}

	public function test_the_order_total_is_the_sum_of_its_items_and_never_negative() {
		$order = \WP_Express_Checkout\Orders::create();
		$order->add_item( 'ppec-products', 'Item', 10, 3, 1, true );
		$order->add_item( 'tax', 'Tax', 3 );
		$order->add_item( 'coupon', 'Coupon', -5 );
		$this->assertSame( '28.00', $order->get_total() );

		$order->add_item( 'coupon', 'Coupon', -50 );
		$this->assertSame( '0.00', $order->get_total() );
	}

	public function test_a_unique_item_replaces_the_previous_one_and_items_can_be_removed() {
		$order = \WP_Express_Checkout\Orders::create();
		$order->add_item( 'ppec-products', 'First', 10, 1, 1, true );
		$order->add_item( 'ppec-products', 'Second', 20, 1, 2, true );
		$order->add_item( 'shipping', 'Shipping', 5 );

		$this->assertCount( 1, $order->get_items( 'ppec-products' ) );
		$this->assertSame( 'Second', $order->get_item( 'ppec-products' )['name'] );

		$order->remove_item( 'shipping' );
		$this->assertFalse( $order->get_item( 'shipping' ) );
		$this->assertSame( '20.00', $order->get_total() );
	}

	public function test_the_order_is_saved_and_read_back() {
		$product_id = WPEC_Test_Factory::create_product( '10' );
		$order      = $this->paid_order( $product_id );

		$this->assertSame( 'paid', $order->get_status() );
		$this->assertSame( 'paypal', $order->get_payment_gateway() );
		$this->assertSame( 'USD', $order->get_currency() );
		$this->assertSame( 'PAYPAL-ORDER-1', $order->get_resource_id() );
		$this->assertSame( 'CAPTURE-1', $order->get_capture_id() );
		$this->assertSame( 'buyer@example.com', $order->get_email_address() );
		$this->assertSame( '24.80', $order->get_total() );
		$this->assertSame( 'COMPLETED', $order->get_data( 'state' ) );
		$this->assertSame( 2, (int) $order->get_quantity_ordered() );
	}

	public function test_a_status_change_runs_its_action_once() {
		$order = \WP_Express_Checkout\Orders::create();
		$runs  = 0;
		add_action(
			'wpec_transaction_paid',
			function () use ( &$runs ) {
				$runs++;
			}
		);

		$order->set_status( 'paid' );
		$order->set_status( 'paid' );

		$this->assertSame( 1, $runs );
	}

	public function test_retrieve_rejects_a_post_that_is_not_an_order() {
		$this->expectException( Exception::class );
		\WP_Express_Checkout\Orders::retrieve( WPEC_Test_Factory::create_product( '10' ) );
	}

	// ---- Order tags ----

	public function test_the_order_tags() {
		$product_id = WPEC_Test_Factory::create_product( '10', array(), 'Tagged Product' );
		$order      = $this->paid_order( $product_id );
		$tags       = new \WP_Express_Checkout\Order_Tags_Plain( $order );

		$this->assertSame( 'Jane', $tags->first_name() );
		$this->assertSame( 'Buyer', $tags->last_name() );
		$this->assertSame( 'buyer@example.com', $tags->payer_email() );
		$this->assertSame( 'CAPTURE-1', $tags->transaction_id() );
		$this->assertSame( '24.80', $tags->purchase_amt(), 'The amount only ({currency_code} is the currency).' );
		$this->assertSame( 'USD', $tags->currency_code() );
		$this->assertSame( 'TENOFF', $tags->coupon_code() );
		$this->assertSame( (string) $order->get_id(), (string) $tags->order_id() );
		$this->assertSame( (string) $product_id, (string) $tags->product_id() );
		$this->assertSame( 'Tagged Product', $tags->product_name() );
		$this->assertSame( 2, (int) $tags->quantity_ordered() );
		$this->assertStringContainsString( '1 Test Street', $tags->address() );
		$this->assertNotEmpty( $tags->purchase_date() );
	}

	public function test_the_product_details_tag_lists_the_item_and_the_totals() {
		$product_id = WPEC_Test_Factory::create_product( '10', array(), 'Tagged Product' );
		$details    = ( new \WP_Express_Checkout\Order_Tags_Plain( $this->paid_order( $product_id ) ) )->product_details();

		$this->assertStringContainsString( 'Tagged Product', $details );
		$this->assertStringContainsString( 'TENOFF', $details );
		$this->assertStringContainsString( '$24.80', $details );
	}

	public function test_the_download_link_tag_for_a_product_with_a_file() {
		$product_id = WPEC_Test_Factory::create_product( '10', array( 'ppec_product_upload' => 'http://example.org/file.zip' ), 'Download Product' );
		$order      = $this->paid_order( $product_id );
		$tags       = new \WP_Express_Checkout\Order_Tags_Plain( $order );

		$this->assertStringContainsString( 'Download Product - download link: ', $tags->download_link() );
		$this->assertStringContainsString( 'wpec_download_file=' . $product_id, $tags->download_link_url_only() );
		$this->assertStringContainsString( 'order_id=' . $order->get_id(), $tags->download_link_url_only() );
		$this->assertStringContainsString( 'wpec_download_file=', $tags->product_details(), 'The product details include the download link.' );

		$this->assertSame( '', ( new \WP_Express_Checkout\Order_Tags_Plain( $this->paid_order( WPEC_Test_Factory::create_product( '10' ) ) ) )->download_link(), 'No file, no link.' );
	}

	public function test_the_selected_variations_tag() {
		$product_id = WPEC_Test_Factory::create_product( '10' );
		$order      = $this->paid_order( $product_id );
		$order->add_item( 'variation', 'Size - Large', 5, 2, $product_id );
		$order->add_item( 'variation', 'Color - Red', 0, 2, $product_id );

		$this->assertSame( 'Size - Large, Color - Red', ( new \WP_Express_Checkout\Order_Tags_Plain( $order ) )->selected_variations() );
	}

	// ---- Emails ----

	public function test_the_buyer_email_goes_to_the_buyer_with_the_tags_replaced() {
		$product_id = WPEC_Test_Factory::create_product( '10', array(), 'Emailed Product' );
		$order      = $this->paid_order( $product_id );

		$this->assertTrue( \WP_Express_Checkout\Emails::send_buyer_email( $order ) );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertSame( 'buyer@example.com', $mailer->get_recipient( 'to' )->address );
		$this->assertSame( 'Order ' . $order->get_id() . ' for Emailed Product', $mailer->get_sent()->subject );
		$this->assertStringContainsString( 'Dear Jane Buyer', $mailer->get_sent()->body );
		$this->assertStringContainsString( 'Emailed Product', $mailer->get_sent()->body );
		$this->assertStringNotContainsString( '{', $mailer->get_sent()->body );
		$this->assertStringContainsString( 'shop@example.com', $mailer->get_sent()->header );
		$this->assertStringContainsString( 'Email sent to: buyer@example.com', get_post_meta( $order->get_id(), 'wpec_buyer_email_sent', true ) );
	}

	public function test_the_buyer_email_can_be_html() {
		WPEC_Test_Factory::update_settings( array( 'buyer_email_type' => 'html' ) );

		\WP_Express_Checkout\Emails::send_buyer_email( $this->paid_order( WPEC_Test_Factory::create_product( '10' ) ) );

		$this->assertStringContainsString( 'text/html', tests_retrieve_phpmailer_instance()->get_sent()->header );
	}

	public function test_a_product_can_have_its_own_buyer_email() {
		WPEC_Test_Factory::update_settings( array( 'enable_per_product_email_customization' => 1 ) );
		$product_id = WPEC_Test_Factory::create_product(
			'10',
			array(
				'custom_buyer_email_enabled' => 1,
				'custom_buyer_email_from'    => 'Product Team <team@example.com>',
				'custom_buyer_email_subj'    => 'Your {product_name}',
				'custom_buyer_email_body'    => 'Special thanks, {first_name}',
			),
			'Special Product'
		);

		\WP_Express_Checkout\Emails::send_buyer_email( $this->paid_order( $product_id ) );

		$sent = tests_retrieve_phpmailer_instance()->get_sent();
		$this->assertSame( 'Your Special Product', $sent->subject );
		$this->assertStringContainsString( 'Special thanks, Jane', $sent->body );
		$this->assertStringContainsString( 'team@example.com', $sent->header );
	}

	public function test_no_buyer_email_when_it_is_turned_off() {
		WPEC_Test_Factory::update_settings( array( 'send_buyer_email' => 0 ) );

		\WP_Express_Checkout\Emails::send_buyer_email( $this->paid_order( WPEC_Test_Factory::create_product( '10' ) ) );

		$this->assertFalse( tests_retrieve_phpmailer_instance()->get_sent() );
	}

	public function test_the_seller_email_goes_to_the_notification_address_when_it_is_turned_on() {
		$product_id = WPEC_Test_Factory::create_product( '10', array(), 'Sold Product' );

		\WP_Express_Checkout\Emails::send_seller_email( $this->paid_order( $product_id ) );
		$this->assertFalse( tests_retrieve_phpmailer_instance()->get_sent(), 'Turned off by default.' );

		WPEC_Test_Factory::update_settings( array( 'send_seller_email' => 1 ) );
		\WP_Express_Checkout\Emails::send_seller_email( $this->paid_order( $product_id ) );

		$sent = tests_retrieve_phpmailer_instance()->get_sent();
		$this->assertSame( 'seller@example.com', tests_retrieve_phpmailer_instance()->get_recipient( 'to' )->address );
		$this->assertSame( 'Sale of Sold Product', $sent->subject );
		$this->assertStringContainsString( 'Sold to buyer@example.com for 24.80', $sent->body );
	}

	// ---- Thank You page shortcodes ----

	public function test_the_thank_you_page_part_shortcodes_show_the_order_with_a_valid_nonce() {
		$product_id = WPEC_Test_Factory::create_product( '10', array(), 'Thanked Product' );
		$order      = $this->paid_order( $product_id );
		$_GET['order_id'] = (string) $order->get_id();
		$_GET['_wpnonce'] = wp_create_nonce( 'thank_you_url' . $order->get_id() );

		$this->assertSame( 'Jane', do_shortcode( '[wpec_ty field="first_name"]' ) );
		$this->assertSame( 'CAPTURE-1', do_shortcode( '[wpec_ty field="transaction_id"]' ) );
		$this->assertStringContainsString( 'Thanked Product', do_shortcode( '[wpec_thank_you]' ) );

		$_GET['_wpnonce'] = 'wrong';
		$this->assertStringContainsString( 'Nonce verification failed', do_shortcode( '[wpec_ty field="first_name"]' ) );

		unset( $_GET['order_id'], $_GET['_wpnonce'] );
		$this->assertStringContainsString( 'Do not access this page directly', do_shortcode( '[wpec_ty field="first_name"]' ) );
	}

	public function test_the_thank_you_page_does_not_show_an_order_that_is_not_completed() {
		$order = \WP_Express_Checkout\Orders::create();
		$order->add_data( 'state', 'PENDING' );
		$_GET['order_id'] = (string) $order->get_id();
		$_GET['_wpnonce'] = wp_create_nonce( 'thank_you_url' . $order->get_id() );

		$this->assertStringContainsString( 'Payment is not approved. Status: PENDING', do_shortcode( '[wpec_thank_you]' ) );

		unset( $_GET['order_id'], $_GET['_wpnonce'] );
	}
}
