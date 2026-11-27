<?php
/**
 * The download links and the download request (includes/class-view-downloads.php).
 *
 * Fixed after 2.5.6: a manual checkout order is pending until the admin marks it as paid, but the [wpec_ty_downloads] and
 * [wpec_ty field=download_link] shortcodes showed its download link, and the download request didn't check the order's status
 * (a refunded order's link kept working too). Now only a paid order has download links, and the download request checks it.
 */
class Test_Downloads extends WP_UnitTestCase {

	private function order( $status, $product_id ) {
		$order = \WP_Express_Checkout\Orders::create();
		$order->add_item( 'ppec-products', get_the_title( $product_id ), 10, 1, $product_id, true );
		$order->add_data( 'state', 'COMPLETED' );
		$order->set_status( $status );
		return \WP_Express_Checkout\Orders::retrieve( $order->get_id() );
	}

	private function download_product() {
		return WPEC_Test_Factory::create_product( '10', array( 'ppec_product_upload' => 'http://example.org/file.zip' ), 'Download Product' );
	}

	/**
	 * Runs the download request check (View_Downloads::verify_request()) for a download URL.
	 *
	 * @return bool|string True, or the wp_die() message.
	 */
	private function verify( $download_url ) {
		$_GET = array();
		wp_parse_str( (string) wp_parse_url( $download_url, PHP_URL_QUERY ), $_GET );

		$downloads = ( new ReflectionClass( '\WP_Express_Checkout\View_Downloads' ) )->newInstanceWithoutConstructor();
		$method    = new ReflectionMethod( '\WP_Express_Checkout\View_Downloads', 'verify_request' );
		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}

		try {
			return $method->invoke( $downloads );
		} catch ( WPDieException $e ) {
			return $e->getMessage();
		} finally {
			$_GET = array();
		}
	}

	public function test_a_paid_order_has_a_download_link_that_works() {
		$product_id = $this->download_product();
		$order      = $this->order( 'paid', $product_id );

		$downloads = \WP_Express_Checkout\View_Downloads::get_order_downloads_list( $order->get_id() );

		$this->assertCount( 1, $downloads );
		$this->assertTrue( $this->verify( reset( $downloads ) ) );
	}

	/**
	 * @dataProvider not_paid_statuses
	 */
	public function test_an_order_that_is_not_paid_has_no_download_link_and_its_link_is_rejected( $status ) {
		$product_id = $this->download_product();
		$order      = $this->order( $status, $product_id );

		$this->assertSame( array(), \WP_Express_Checkout\View_Downloads::get_order_downloads_list( $order->get_id() ), 'No download link.' );
		$this->assertSame( '', ( new \WP_Express_Checkout\Order_Tags_Html( $order ) )->download_link(), 'The download_link order tag is empty.' );
		$this->assertSame( '', ( new \WP_Express_Checkout\Order_Tags_Plain( $order ) )->download_link(), 'No download link in the emails.' );

		//A signed link (for example made while the order was paid, or guessed from the order) is rejected.
		$this->assertStringContainsString(
			'payment for this order has not been completed',
			$this->verify( \WP_Express_Checkout\View_Downloads::get_download_url( $order->get_id() ) )
		);
	}

	public function not_paid_statuses() {
		return array(
			'pending (manual checkout)' => array( 'pending' ),
			'incomplete'                => array( 'incomplete' ),
			'refunded'                  => array( 'refunded' ),
		);
	}

	public function test_a_manual_checkout_order_gets_its_download_link_when_the_admin_marks_it_as_paid() {
		$product_id = $this->download_product();
		$order      = $this->order( 'pending', $product_id );
		$this->assertSame( array(), \WP_Express_Checkout\View_Downloads::get_order_downloads_list( $order->get_id() ) );

		$order->set_status( 'paid' );

		$downloads = \WP_Express_Checkout\View_Downloads::get_order_downloads_list( $order->get_id() );
		$this->assertCount( 1, $downloads );
		$this->assertTrue( $this->verify( reset( $downloads ) ) );
	}

	public function test_a_wrong_key_is_rejected() {
		$order = $this->order( 'paid', $this->download_product() );
		$url   = \WP_Express_Checkout\View_Downloads::get_download_url( $order->get_id() );

		$this->assertSame( 'Invalid product key!', $this->verify( preg_replace( '/key=[^&]+/', 'key=wrongkey', $url ) ) );
	}

	public function test_a_link_for_another_orders_product_is_rejected() {
		$order       = $this->order( 'paid', $this->download_product() );
		$other_order = $this->order( 'paid', $this->download_product() );
		$url         = \WP_Express_Checkout\View_Downloads::get_download_url( $other_order->get_id() );

		$this->assertSame( 'Invalid product ID!', $this->verify( add_query_arg( 'order_id', $order->get_id(), $url ) ) );
	}

	public function test_the_downloadable_statuses_can_be_changed_with_a_filter() {
		$order = $this->order( 'pending', $this->download_product() );
		$allow = function () {
			return array( 'paid', 'pending' );
		};
		add_filter( 'wpec_downloadable_order_statuses', $allow );

		$downloads = \WP_Express_Checkout\View_Downloads::get_order_downloads_list( $order->get_id() );

		remove_filter( 'wpec_downloadable_order_statuses', $allow );
		$this->assertCount( 1, $downloads );
	}
}
