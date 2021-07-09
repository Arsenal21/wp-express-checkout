<?php

namespace WP_Express_Checkout;

use WP_Express_Checkout\Debug\Logger;

class Integrations {

	public function __construct() {
		// Simple Membership integration
		if ( defined( 'SIMPLE_WP_MEMBERSHIP_VER' ) ) {
			add_action( 'wpec_payment_completed', array( $this, 'handle_swpm_signup' ), 10, 3 );
		}
		// WP eMember integration
		if ( function_exists( 'wp_emember_install' ) ) {
			add_action( 'wpec_payment_completed', array( $this, 'handle_emember_signup' ), 10, 3 );
		}
	}

	public function get_member_info_from_api( $payment ) {
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
			$countries = Utils::get_countries_untranslated();
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

		return $ipn_data;
	}

	public function handle_swpm_signup( $payment, $order_id, $product_id ) {

		// let's check if Membership Level is set for this product
		$level_id = get_post_meta( $product_id, 'wpec_product_swpm_level', true );
		if ( empty( $level_id ) ) {
			return;
		}

		$ipn_data = $this->get_member_info_from_api( $payment );

		Logger::log( 'Calling swpm_handle_subsc_signup_stand_alone' );

		$swpm_id = '';
		if ( \SwpmMemberUtils::is_member_logged_in() ) {
			$swpm_id = \SwpmMemberUtils::get_logged_in_members_id();
		}

		if ( defined( 'SIMPLE_WP_MEMBERSHIP_PATH' ) ) {
			require SIMPLE_WP_MEMBERSHIP_PATH . 'ipn/swpm_handle_subsc_ipn.php';
			swpm_handle_subsc_signup_stand_alone( $ipn_data, $level_id, $payment['id'], $swpm_id );
		}

	}

	public function handle_emember_signup( $payment, $order_id, $product_id ) {

		// let's check if Membership Level is set for this product.
		$level_id = get_post_meta( $product_id, 'wpec_product_emember_level', true );
		if ( empty( $level_id ) ) {
			return;
		}

		$ipn_data = $this->get_member_info_from_api( $payment );

		Logger::log( 'Calling eMember_handle_subsc_signup_stand_alone' );

		$emember_id = '';
		if ( class_exists( 'Emember_Auth' ) ) {
			// Check if the user is logged in as a member.
			$emember_auth = \Emember_Auth::getInstance();
			$emember_id   = $emember_auth->getUserInfo( 'member_id' );
		}

		if ( defined( 'WP_EMEMBER_PATH' ) ) {
			require_once WP_EMEMBER_PATH . 'ipn/eMember_handle_subsc_ipn_stand_alone.php';
			eMember_handle_subsc_signup_stand_alone( $ipn_data, $level_id, $payment['id'], $emember_id );
		}

	}

}
