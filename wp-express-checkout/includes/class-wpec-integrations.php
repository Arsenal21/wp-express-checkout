<?php

class WPEC_Integrations {

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 0 );
	}

	public function plugins_loaded() {
		// WP eMember integration.
		if ( function_exists( 'wp_emember_install' ) ) {
			add_action( 'wpec_payment_completed', array( $this, 'handle_emember_signup' ), 10, 3 );
		}
	}

	public function handle_emember_signup( $payment, $order_id = null, $product_id = null ) {

		if ( empty( $order_id ) || empty( $product_id ) ) {
			return;
		}

		// let's check if Membership Level is set for this product.
		$level_id = get_post_meta( $product_id, 'wpec_product_emember_level', true );
		if ( empty( $level_id ) ) {
			return;
		}

		// let's form data required for eMember_handle_subsc_signup_stand_alone function and call it.
		$first_name   = ! empty( $payment['payer']['name']['given_name'] ) ? $payment['payer']['name']['given_name'] : '';
		$last_name    = ! empty( $payment['payer']['name']['surname'] ) ? $payment['payer']['name']['surname'] : '';
		$addr_street  = ! empty( $payment['payer']['address']['address_line_1'] ) ? $payment['payer']['address']['address_line_1'] : '';
		$addr_zip     = ! empty( $payment['payer']['address']['postal_code'] ) ? $payment['payer']['address']['postal_code'] : '';
		$addr_city    = ! empty( $payment['payer']['address']['admin_area_2'] ) ? $payment['payer']['address']['admin_area_2'] : '';
		$addr_state   = ! empty( $payment['payer']['address']['admin_area_1'] ) ? $payment['payer']['address']['admin_area_1'] : '';
		$addr_country = ! empty( $payment['payer']['address']['country_code'] ) ? $payment['payer']['address']['country_code'] : '';

		if ( ! empty( $addr_country ) ) {
			// convert country code to country name.
			$countries = WPEC_Utility_Functions::get_countries_untranslated();
			if ( isset( $countries[ $addr_country ] ) ) {
				$addr_country = $countries[ $addr_country ];
			}
		}

		$ipn_data = array(
			'payer_email'     => $payment['payer']['email_address'],
			'first_name'      => $first_name,
			'last_name'       => $last_name,
			'txn_id'          => $payment['id'],
			'address_street'  => $addr_street,
			'address_city'    => $addr_city,
			'address_state'   => $addr_state,
			'address_zip'     => $addr_zip,
			'address_country' => $addr_country,
		);

		WPEC_Debug_Logger::log( 'Calling eMember_handle_subsc_signup_stand_alone' );

		$emember_id = '';
		if ( class_exists( 'Emember_Auth' ) ) {
			// Check if the user is logged in as a member.
			$emember_auth = Emember_Auth::getInstance();
			$emember_id   = $emember_auth->getUserInfo( 'member_id' );
		}

		if ( defined( 'WP_EMEMBER_PATH' ) ) {
			require_once WP_EMEMBER_PATH . 'ipn/eMember_handle_subsc_ipn_stand_alone.php';
			eMember_handle_subsc_signup_stand_alone( $ipn_data, $level_id, $payment['id'], $emember_id );
		}

	}

}

new WPEC_Integrations();
