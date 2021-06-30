<?php
/**
 * This test case ensures that core plugin classes mocks created in addons test
 * suites are compatible with original classes.
 *
 * @package Wp_Express_Checkout
 */

/**
 * Sample test case.
 *
 * @group mocks
 */
class AddonsMocksTest extends WP_UnitTestCase {

	/**
	 * Test Main class mock.
	 */
	public function testMain() {
		$this->assertTrue( class_exists( 'WP_Express_Checkout\Main' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Main', 'get_instance' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Main', 'get_defaults' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Main', 'get_setting' ) );
	}

	/**
	 * Test Products class mock.
	 */
	public function testProducts() {
		$this->assertTrue( class_exists( 'WP_Express_Checkout\Products' ) );
		$this->assertClassHasStaticAttribute( 'products_slug', 'WP_Express_Checkout\Products' );
	}

	/**
	 * Test Payment_Processor class mock.
	 */
	public function testPayment_Processor() {
		$this->assertTrue( class_exists( 'WP_Express_Checkout\Payment_Processor' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Payment_Processor', 'get_instance' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Payment_Processor', 'wpec_process_payment' ) );
	}

	/**
	 * Test Order class mock.
	 */
	public function testOrder() {
		$this->assertTrue( class_exists( 'WP_Express_Checkout\Order' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'get_id' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'get_description' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'set_description' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'add_item' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'remove_item' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'get_item' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'get_items' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'get_total' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'set_currency' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'get_currency' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'get_status' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'get_display_status' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'set_status' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'set_author' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'get_author' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'get_ip_address' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'get_return_url' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'get_cancel_url' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'get_parent' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'add_data' ) );
		$this->assertTrue( method_exists( 'WP_Express_Checkout\Order', 'get_data' ) );
	}

}