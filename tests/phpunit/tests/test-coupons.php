<?php
/**
 * Coupons (includes/class-coupons.php): get_coupon(), is_coupon_allowed_for_product() and the coupon check request.
 */
class Test_Coupons extends WPEC_Ajax_Test_Case {

	/**
	 * Sends the coupon check request (wpec_check_coupon) for a product.
	 * The plugin registers it only on admin-ajax.php requests, so the test registers it.
	 */
	private function check_coupon( $code, $product_id ) {
		$coupons = ( new ReflectionClass( '\WP_Express_Checkout\Coupons' ) )->newInstanceWithoutConstructor();
		add_action( 'wp_ajax_nopriv_wpec_check_coupon', array( $coupons, 'frontend_check_coupon' ) );
		$this->send_ajax(
			wp_slash(
				array(
					'action'      => 'wpec_check_coupon',
					'coupon_code' => $code,
					'product_id'  => $product_id,
					'curr'        => 'USD',
				)
			)
		);
		return $this->response();
	}

	public function test_a_valid_coupon_is_found_with_its_discount() {
		$coupon_id = WPEC_Test_Factory::create_coupon( 'SAVE10', 10, 'perc' );

		$coupon = \WP_Express_Checkout\Coupons::get_coupon( 'SAVE10' );

		$this->assertTrue( $coupon['valid'] );
		$this->assertSame( $coupon_id, $coupon['id'] );
		$this->assertSame( '10', $coupon['discount'] );
		$this->assertSame( 'perc', $coupon['discountType'] );
	}

	/**
	 * @dataProvider invalid_coupons
	 */
	public function test_an_invalid_coupon_is_rejected_with_the_reason( $meta, $message ) {
		WPEC_Test_Factory::create_coupon( 'BAD', 10, 'perc', $meta );

		$coupon = \WP_Express_Checkout\Coupons::get_coupon( 'BAD' );

		$this->assertFalse( $coupon['valid'] );
		$this->assertSame( $message, $coupon['err_msg'] );
	}

	public function invalid_coupons() {
		return array(
			'inactive'               => array( array( 'active' => 0 ), 'Coupon is not active.' ),
			'starts tomorrow'        => array( array( 'start_date' => gmdate( 'Y-m-d', strtotime( '+2 days' ) ) ), 'Coupon is not available yet.' ),
			'no start date'          => array( array( 'start_date' => '' ), 'Coupon is not available yet.' ),
			'expired'                => array( array( 'exp_date' => '2020-01-01' ), 'Coupon has expired.' ),
			'redemption limit'       => array( array( 'red_limit' => '3', 'red_count' => '3' ), 'Coupon redemption limit is reached.' ),
		);
	}

	public function test_an_unknown_coupon_is_not_found() {
		$coupon = \WP_Express_Checkout\Coupons::get_coupon( 'NOSUCHCODE' );

		$this->assertFalse( $coupon['valid'] );
		$this->assertSame( 'Coupon not found.', $coupon['err_msg'] );
	}

	public function test_a_coupon_below_its_redemption_limit_is_valid() {
		WPEC_Test_Factory::create_coupon( 'LIMITED', 10, 'perc', array( 'red_limit' => '3', 'red_count' => '2' ) );

		$this->assertTrue( \WP_Express_Checkout\Coupons::get_coupon( 'LIMITED' )['valid'] );
	}

	public function test_a_coupon_for_some_products_is_allowed_only_for_them() {
		$allowed   = WPEC_Test_Factory::create_product( '10' );
		$other     = WPEC_Test_Factory::create_product( '10' );
		$coupon_id = WPEC_Test_Factory::create_coupon(
			'SOME',
			10,
			'perc',
			array(
				'only_for_allowed_products' => 1,
				'allowed_products'          => array( (string) $allowed ),
			)
		);

		$this->assertTrue( \WP_Express_Checkout\Coupons::is_coupon_allowed_for_product( $coupon_id, $allowed ) );
		$this->assertFalse( \WP_Express_Checkout\Coupons::is_coupon_allowed_for_product( $coupon_id, $other ) );
	}

	public function test_a_coupon_for_all_products_is_allowed_for_any_product() {
		$coupon_id = WPEC_Test_Factory::create_coupon( 'ALL', 10, 'perc' );

		$this->assertTrue( \WP_Express_Checkout\Coupons::is_coupon_allowed_for_product( $coupon_id, WPEC_Test_Factory::create_product( '10' ) ) );
	}

	// ---- The coupon check request ----

	public function test_the_coupon_check_request_rejects_an_unknown_coupon_and_an_empty_code() {
		//The other coupon check request cases need the product ID, which the request handler reads with filter_input( INPUT_POST ):
		//it reads the real HTTP request, so they are in the endpoint tests.
		$product_id = WPEC_Test_Factory::create_product( '10' );

		$response = $this->check_coupon( 'NOSUCHCODE', $product_id );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Coupon not found.', $response['msg'] );

		$response = $this->check_coupon( '', $product_id );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Empty coupon code', $response['msg'] );
	}
}
