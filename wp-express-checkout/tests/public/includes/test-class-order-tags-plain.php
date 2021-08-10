<?php

namespace WP_Express_Checkout;

use WP_UnitTestCase;

/**
 * @group shortcodes
 *
 * @covers WP_Express_Checkout\Order_Tags_Plain
 */
class Order_Tags_PlainTest extends WP_UnitTestCase {

	/**
	 * @var Order_Tags_Plain
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
		$order->add_item( Products::$products_slug, $product_id, $product_id + 42, 1, $product_id );

		$this->product_id = $product_id;
		$this->order      = $order;
		$this->object     = new Order_Tags_Plain( $order );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Plain::product_details
	 */
	public function testProduct_details() {
		$this->order->add_item( 'dummy', 'Dummy stuff', 42 );
		$output = $this->object->product_details();
		$this->assertContains( 'Dummy stuff', $output );
		$this->assertContains( '42', $output );
		$this->assertContains( "\n", $output );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Plain::download_link
	 */
	public function testDownload_link__no_links() {
		delete_post_meta( $this->product_id, 'ppec_product_upload' );
		$output = $this->object->download_link();
		$this->assertEmpty( $output );
	}

	/**
	 * @covers WP_Express_Checkout\Order_Tags_Plain::download_link
	 */
	public function testDownload_link__reflects() {
		$output = $this->object->download_link();
		$this->assertContains( "\n" . $this->product_id . ' - download link:', $output );
	}

}
