<?php
/**
 * The return from Stripe (Payment_Processor_Stripe, /?wpec_process_stripe_ipn=1&ref_id=<checkout session ID>).
 * The whole return is in the endpoint tests; this checks the duplicate check on its own.
 */
class Test_Stripe_Return_Processing extends WP_UnitTestCase {

	/**
	 * An order like create_order() saves it: the session ID as the resource ID, the charge ID as the capture ID.
	 */
	private function create_stripe_order( $session_id, $charge_id ) {
		$order = \WP_Express_Checkout\Orders::create();
		$order->set_payment_gateway( 'stripe' );
		$order->set_resource_id( $session_id );
		$order->set_capture_id( $charge_id );
		$order->set_status( 'paid' );
		return $order;
	}

	public function test_a_session_with_an_order_is_processed() {
		//Fixed after 2.5.5: the check looked for the session ID in the capture ID, so opening the return URL again created another order.
		$this->create_stripe_order( 'cs_test_paid_1', 'ch_test_1' );

		$this->assertTrue( \WP_Express_Checkout\Payment_Processor_Stripe::check_if_checkout_session_processed( 'cs_test_paid_1' ) );
	}

	public function test_a_session_without_an_order_is_not_processed() {
		$this->create_stripe_order( 'cs_test_paid_1', 'ch_test_1' );

		$this->assertFalse( \WP_Express_Checkout\Payment_Processor_Stripe::check_if_checkout_session_processed( 'cs_test_other' ) );
		$this->assertFalse( \WP_Express_Checkout\Payment_Processor_Stripe::check_if_checkout_session_processed( 'ch_test_other' ) );
	}

	public function test_an_order_with_the_session_id_as_the_capture_id_is_also_found() {
		$this->create_stripe_order( 'something-else', 'cs_test_paid_2' );

		$this->assertTrue( \WP_Express_Checkout\Payment_Processor_Stripe::check_if_checkout_session_processed( 'cs_test_paid_2' ) );
	}
}
