<?php

namespace WP_Express_Checkout;

use WP_UnitTestCase;

/**
 * @group shortcodes
 *
 * @covers WP_Express_Checkout\Order_Tags_Html
 */
class Order_Tags_HtmlTest extends WP_UnitTestCase {

	/**
	 * @var Order_Tags_Html
	 */
	protected $object;

	/**
	 * @var Order
	 */
	protected $order;

	/**
	 * @var int
	 */
	protected $product_id;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	public function setUp() {
		parent::setUp();

		$product_id = $this->factory->post->create(
			[
				'post_type'  => Products::$products_slug,
				'meta_input' => [
					'ppec_product_upload' => 'dummy'
				]
			]
		);
		$order = Orders::create();
		$order->set_resource_id( "test-resource-id-{$product_id}-{$order->get_id()}" );
		$order->add_data(
			'payer',
			[
				'name' => [
					'given_name' => 'John',
					'surname' => 'Connor',
				],
				'email_address' => 'test@example.com'
			]
		);
		$order->add_data( 'state', 'COMPLETED' );
		$order->add_item( Products::$products_slug, $product_id, $product_id + 42, 1, $product_id );
		$order->add_item( 'coupon', 'Coupon Code: test', -2, 1, 0, false, [ 'code' => "test_$product_id" ] );

		$order->set_currency( 'TEST' );

		$this->product_id = $product_id;
		$this->order      = $order;
		$this->object     = new Order_Tags_Html( $order );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Html::first_name
	 * @todo   Implement testFirst_name().
	 */
	public function testFirst_name() {
		$this->assertEquals( 'John', $this->object->first_name() );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Html::last_name
	 */
	public function testLast_name() {
		$this->assertEquals( 'Connor', $this->object->last_name() );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Html::product_details
	 */
	public function testProduct_details() {
		$this->order->add_item( 'dummy', 'Dummy stuff', 42 );
		$output = $this->object->product_details();
		$this->assertContains( 'Dummy stuff', $output );
		$this->assertContains( '42', $output );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Html::payer_email
	 */
	public function testPayer_email() {
		$this->assertEquals( 'test@example.com', $this->object->payer_email() );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Html::transaction_id
	 */
	public function testTransaction_id() {
		$this->assertEquals( $this->order->get_resource_id(), $this->object->transaction_id() );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Html::purchase_amt
	 */
	public function testPurchase_amt() {
		$this->assertEquals( $this->order->get_total(), $this->object->purchase_amt() );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Html::purchase_date
	 */
	public function testPurchase_date() {
		$this->assertEquals( date_format( get_post_datetime( $this->order->get_id() ), 'F j, Y, g:i a' ), $this->object->purchase_date() );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Html::currency_code
	 */
	public function testCurrency_code() {
		$this->assertEquals( "TEST", $this->object->currency_code() );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Html::coupon_code
	 */
	public function testCoupon_code() {
		$this->assertEquals( "test_{$this->product_id}", $this->object->coupon_code() );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Html::address
	 */
	public function testAddress() {
		$this->assertEmpty( $this->object->address() );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Html::order_id
	 */
	public function testOrder_id() {
		$this->assertEquals( $this->order->get_id(), $this->object->order_id() );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Html::download_link
	 */
	public function testDownload_link__no_links() {
		delete_post_meta( $this->product_id, 'ppec_product_upload' );
		$output = $this->object->download_link();
		$this->assertEmpty( $output );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Html::download_link
	 */
	public function testDownload_link__reflects() {
		$output = $this->object->download_link(
			[
				'anchor_text' => "test download link 2",
				'target'      => "_test_blank",
			]
		);
		$this->assertContains( 'test download link 2', $output );
		$this->assertContains( '_test_blank', $output );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Html::__call
	 */
	public function test__call() {
		$output = $this->object->dummy_test( 42 );
		$this->assertEmpty( $output );
	}

}
