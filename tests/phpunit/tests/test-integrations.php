<?php
/**
 * The integrations with the other plugins (includes/integrations/): Simple Membership, WP eMember, Software License Manager.
 * They run on 'wpec_payment_completed' (and the License Manager on the buyer email). The other plugins are faked
 * (phpunit/includes/integration-fakes.php): the tests check what the integration sends them.
 */
class Test_Integrations extends WP_UnitTestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once dirname( __DIR__ ) . '/includes/integration-fakes.php';
	}

	public function set_up() {
		parent::set_up();
		$GLOBALS['wpec_test_integration_calls'] = array();
		SwpmMemberUtils::$logged_in_member_id   = 0;
		Emember_Auth::$member_id                = '';
	}

	private function calls( $name ) {
		return array_values(
			array_filter(
				$GLOBALS['wpec_test_integration_calls'],
				function ( $call ) use ( $name ) {
					return $name === $call['name'];
				}
			)
		);
	}

	/**
	 * A PayPal capture response (what wpec_payment_completed gets for a PayPal payment).
	 */
	private function paypal_payment() {
		return array(
			'id'    => 'PAYPAL-ORDER-1',
			'payer' => array(
				'name'          => array(
					'given_name' => 'Jane',
					'surname'    => 'Buyer',
				),
				'email_address' => 'buyer@example.com',
				'payer_id'      => 'PAYERID1',
				'address'       => array(
					'address_line_1' => '1 Test Street',
					'admin_area_2'   => 'Test City',
					'admin_area_1'   => 'CA',
					'postal_code'    => '12345',
					'country_code'   => 'US',
				),
			),
		);
	}

	/**
	 * A Stripe checkout session (what wpec_payment_completed gets for a Stripe payment), as objects like the Stripe SDK returns.
	 */
	private function stripe_session() {
		return json_decode(
			wp_json_encode(
				array(
					'id'               => 'cs_test_1',
					'customer_details' => array(
						'email'   => 'stripe-buyer@example.com',
						'name'    => 'Jane Stripe',
						'phone'   => null,
						'address' => array(
							'line1'       => '1 Test Street',
							'line2'       => 'Apt 2',
							'city'        => 'Test City',
							'state'       => 'CA',
							'postal_code' => '12345',
							'country'     => 'US',
						),
					),
					'payment_intent'   => array(
						'id'            => 'pi_test_1',
						'latest_charge' => array( 'id' => 'ch_test_1' ),
					),
				)
			)
		);
	}

	private function order( $gateway, $product_id = 0, $quantity = 1 ) {
		$order = \WP_Express_Checkout\Orders::create();
		$order->set_payment_gateway( $gateway );
		$order->set_resource_id( 'PAYPAL-ORDER-1' );
		$order->add_data( 'payer', $this->paypal_payment()['payer'] );
		if ( $product_id ) {
			$order->add_item( 'ppec-products', get_the_title( $product_id ), 10, $quantity, $product_id, true );
		}
		return $order;
	}

	// ---- Simple Membership ----

	public function test_simple_membership_signs_up_the_paypal_buyer_to_the_products_level() {
		$product_id = WPEC_Test_Factory::create_product( '10', array( 'wpec_product_swpm_level' => 3 ) );
		$order      = $this->order( 'paypal' );

		( new \WP_Express_Checkout\Integrations\Simple_WP_Membership() )->handle_signup( $this->paypal_payment(), $order->get_id(), $product_id );

		$calls = $this->calls( 'swpm_handle_subsc_signup_stand_alone' );
		$this->assertCount( 1, $calls );
		list( $ipn_data, $level_id, $unique_ref, $member_id ) = $calls[0]['args'];
		$this->assertEquals( 3, $level_id );
		$this->assertSame( 'PAYPAL-ORDER-1', $unique_ref );
		$this->assertSame( '', $member_id, 'Not logged in: a new member.' );
		$this->assertSame( 'buyer@example.com', $ipn_data['payer_email'] );
		$this->assertSame( 'Jane', $ipn_data['first_name'] );
		$this->assertSame( 'Buyer', $ipn_data['last_name'] );
		$this->assertSame( 'Test City', $ipn_data['address_city'] );
		$this->assertSame( 'CA', $ipn_data['address_state'] );
		$this->assertSame( 'United States', $ipn_data['address_country'] );
	}

	public function test_simple_membership_upgrades_a_logged_in_member() {
		SwpmMemberUtils::$logged_in_member_id = 42;
		$product_id = WPEC_Test_Factory::create_product( '10', array( 'wpec_product_swpm_level' => 3 ) );

		( new \WP_Express_Checkout\Integrations\Simple_WP_Membership() )->handle_signup( $this->paypal_payment(), $this->order( 'paypal' )->get_id(), $product_id );

		$this->assertSame( 42, $this->calls( 'swpm_handle_subsc_signup_stand_alone' )[0]['args'][3] );
	}

	public function test_simple_membership_signs_up_the_stripe_buyer_with_the_stripe_customer_details() {
		$product_id = WPEC_Test_Factory::create_product( '10', array( 'wpec_product_swpm_level' => 3 ) );

		( new \WP_Express_Checkout\Integrations\Simple_WP_Membership() )->handle_signup( $this->stripe_session(), $this->order( 'stripe' )->get_id(), $product_id );

		$calls = $this->calls( 'swpm_handle_subsc_signup_stand_alone' );
		$this->assertCount( 1, $calls );
		list( $ipn_data, , $unique_ref ) = $calls[0]['args'];
		$this->assertSame( 'ch_test_1', $unique_ref, 'The charge ID.' );
		$this->assertSame( 'stripe-buyer@example.com', $ipn_data['payer_email'] );
		$this->assertSame( 'Jane', $ipn_data['first_name'] );
		$this->assertSame( 'Stripe', $ipn_data['last_name'] );
		$this->assertSame( '1 Test Street, Apt 2', $ipn_data['address_street'] );
		$this->assertSame( 'United States', $ipn_data['address_country'] );
	}

	public function test_simple_membership_does_nothing_for_a_product_without_a_level() {
		$product_id = WPEC_Test_Factory::create_product( '10' );

		( new \WP_Express_Checkout\Integrations\Simple_WP_Membership() )->handle_signup( $this->paypal_payment(), $this->order( 'paypal' )->get_id(), $product_id );

		$this->assertCount( 0, $GLOBALS['wpec_test_integration_calls'] );
	}

	public function test_simple_membership_runs_on_payment_completed() {
		$integration = new \WP_Express_Checkout\Integrations\Simple_WP_Membership();
		$product_id  = WPEC_Test_Factory::create_product( '10', array( 'wpec_product_swpm_level' => 3 ) );

		do_action( 'wpec_payment_completed', $this->paypal_payment(), $this->order( 'paypal' )->get_id(), $product_id );

		$this->assertCount( 1, $this->calls( 'swpm_handle_subsc_signup_stand_alone' ) );
		remove_action( 'wpec_payment_completed', array( $integration, 'handle_signup' ) );
	}

	/**
	 * @dataProvider paypal_subscription_events
	 */
	public function test_simple_membership_handles_the_paypal_subscription_events( $event_type, $function ) {
		( new \WP_Express_Checkout\Integrations\Simple_WP_Membership() )->handle_paypal_subscription_webhook_event(
			array(
				'event_type' => $event_type,
				'resource'   => array( 'billing_agreement_id' => 'I-SUB123' ),
			)
		);

		$calls = $this->calls( $function );
		$this->assertCount( 1, $calls );
		$this->assertSame( 'I-SUB123', $calls[0]['args'][0]['subscr_id'] );
	}

	public function paypal_subscription_events() {
		return array(
			'cancelled' => array( 'BILLING.SUBSCRIPTION.CANCELLED', 'swpm_handle_subsc_cancel_stand_alone' ),
			'expired'   => array( 'BILLING.SUBSCRIPTION.EXPIRED', 'swpm_handle_subsc_cancel_stand_alone' ),
			'suspended' => array( 'BILLING.SUBSCRIPTION.SUSPENDED', 'swpm_handle_subsc_cancel_stand_alone' ),
			'payment'   => array( 'PAYMENT.SALE.COMPLETED', 'swpm_update_member_subscription_start_date_if_applicable' ),
		);
	}

	public function test_simple_membership_ignores_other_paypal_events() {
		( new \WP_Express_Checkout\Integrations\Simple_WP_Membership() )->handle_paypal_subscription_webhook_event(
			array(
				'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED',
				'resource'   => array( 'id' => 'I-SUB123' ),
			)
		);
		( new \WP_Express_Checkout\Integrations\Simple_WP_Membership() )->handle_paypal_subscription_webhook_event( array( 'event_type' => 'BILLING.SUBSCRIPTION.CANCELLED' ) );

		$this->assertCount( 0, $GLOBALS['wpec_test_integration_calls'], 'An activation, and an event without a subscription ID, do nothing.' );
	}

	// ---- WP eMember ----

	public function test_emember_signs_up_the_buyer_to_the_products_level() {
		$product_id = WPEC_Test_Factory::create_product( '10', array( 'wpec_product_emember_level' => 5 ) );

		( new \WP_Express_Checkout\Integrations\Emember() )->handle_signup( $this->paypal_payment(), $this->order( 'paypal' )->get_id(), $product_id );

		$calls = $this->calls( 'eMember_handle_subsc_signup_stand_alone' );
		$this->assertCount( 1, $calls );
		list( $ipn_data, $level_id, $unique_ref, $member_id ) = $calls[0]['args'];
		$this->assertEquals( 5, $level_id );
		$this->assertSame( 'PAYPAL-ORDER-1', $unique_ref );
		$this->assertSame( '', $member_id );
		$this->assertSame( 'buyer@example.com', $ipn_data['payer_email'] );
	}

	public function test_emember_upgrades_a_logged_in_member_and_reads_stripe_payments() {
		Emember_Auth::$member_id = '17';
		$product_id = WPEC_Test_Factory::create_product( '10', array( 'wpec_product_emember_level' => 5 ) );

		( new \WP_Express_Checkout\Integrations\Emember() )->handle_signup( $this->stripe_session(), $this->order( 'stripe' )->get_id(), $product_id );

		list( $ipn_data, , $unique_ref, $member_id ) = $this->calls( 'eMember_handle_subsc_signup_stand_alone' )[0]['args'];
		$this->assertSame( '17', $member_id );
		$this->assertSame( 'ch_test_1', $unique_ref );
		$this->assertSame( 'stripe-buyer@example.com', $ipn_data['payer_email'] );
	}

	public function test_emember_does_nothing_for_a_product_without_a_level() {
		( new \WP_Express_Checkout\Integrations\Emember() )->handle_signup( $this->paypal_payment(), $this->order( 'paypal' )->get_id(), WPEC_Test_Factory::create_product( '10' ) );

		$this->assertCount( 0, $GLOBALS['wpec_test_integration_calls'] );
	}

	// ---- Software License Manager ----

	public function test_license_manager_creates_a_license_per_item_and_adds_them_to_the_buyer_email() {
		update_option( 'slm_plugin_options', array( 'lic_prefix' => 'WPEC-', 'default_max_domains' => 1 ) );
		$product_id = WPEC_Test_Factory::create_product(
			'10',
			array(
				'wpec_slm_license_enabled'     => 1,
				'wpec_slm_max_allowed_domains' => 3,
				'wpec_slm_date_of_expiry'      => 30,
			),
			'Licensed Product'
		);
		$order = $this->order( 'paypal', $product_id, 2 );

		$body = ( new \WP_Express_Checkout\Integrations\License_Manager() )->email_body_filter( "Your keys:{wpec_slm_data}", array(), array( 'order_id' => $order->get_id() ) );

		$calls = $this->calls( 'insert_license_data_internal' );
		$this->assertCount( 2, $calls, 'One license per item.' );
		$fields = $calls[0]['args'][0];
		$this->assertStringStartsWith( 'WPEC-', $fields['license_key'] );
		$this->assertSame( 'pending', $fields['lic_status'] );
		$this->assertSame( 'buyer@example.com', $fields['email'] );
		$this->assertSame( 'Jane', $fields['first_name'] );
		$this->assertSame( 'Buyer', $fields['last_name'] );
		$this->assertSame( 'PAYPAL-ORDER-1', $fields['txn_id'] );
		$this->assertEquals( 3, $fields['max_allowed_domains'], 'The product setting overrides the default.' );
		$this->assertSame( wp_date( 'Y-m-d', strtotime( '+30 days' ) ), $fields['date_expiry'] );
		$this->assertSame( $product_id, $fields['product_ref'] );

		$this->assertStringNotContainsString( '{wpec_slm_data}', $body );
		$this->assertStringContainsString( 'Item Name: Licensed Product - License Key: ' . $calls[0]['args'][0]['license_key'], $body );
		$this->assertStringContainsString( $calls[1]['args'][0]['license_key'], $body );
	}

	public function test_license_manager_uses_the_default_domains_and_a_one_year_expiry() {
		update_option( 'slm_plugin_options', array( 'lic_prefix' => 'WPEC-', 'default_max_domains' => 2 ) );
		$product_id = WPEC_Test_Factory::create_product( '10', array( 'wpec_slm_license_enabled' => 1 ) );

		( new \WP_Express_Checkout\Integrations\License_Manager() )->email_body_filter( '{wpec_slm_data}', array(), array( 'order_id' => $this->order( 'paypal', $product_id )->get_id() ) );

		$fields = $this->calls( 'insert_license_data_internal' )[0]['args'][0];
		$this->assertEquals( 2, $fields['max_allowed_domains'] );
		$this->assertSame( wp_date( 'Y-m-d', strtotime( '+1 year' ) ), $fields['date_expiry'] );
	}

	public function test_license_manager_does_nothing_for_a_product_without_licenses_or_without_an_order() {
		update_option( 'slm_plugin_options', array( 'lic_prefix' => 'WPEC-', 'default_max_domains' => 1 ) );
		$integration = new \WP_Express_Checkout\Integrations\License_Manager();
		$product_id  = WPEC_Test_Factory::create_product( '10' );

		$this->assertSame( 'Body {wpec_slm_data}', $integration->email_body_filter( 'Body {wpec_slm_data}', array(), array( 'order_id' => $this->order( 'paypal', $product_id )->get_id() ) ) );
		$this->assertSame( 'Body {wpec_slm_data}', $integration->email_body_filter( 'Body {wpec_slm_data}', array(), array( 'order_id' => 0 ) ) );
		$this->assertCount( 0, $this->calls( 'insert_license_data_internal' ) );
	}

	// ---- Subscriptions add-on fallback ----

	public function test_a_subscription_product_without_the_updated_add_on_is_treated_as_a_one_time_product() {
		$post = get_post( WPEC_Test_Factory::create_product( '10', array( 'wpec_product_type' => 'subscription' ) ) );

		$product = ( new \WP_Express_Checkout\Integrations() )->fallback_subscription_type( $post );

		$this->assertInstanceOf( '\WP_Express_Checkout\Products\One_Time_Product', $product );
	}
}
