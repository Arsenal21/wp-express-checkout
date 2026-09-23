<?php

namespace WP_Express_Checkout\Integrations;

use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Utils;

abstract class Integration {

	public $plugin_name;

	public function __construct() {
		//Standard payment completed hook.
		add_action( 'wpec_payment_completed', array( $this, 'handle_signup' ), 10, 3 );

		//Subscription payment related hooks.
		add_action( 'wpec_sub_webhook_event', array( $this, 'handle_paypal_subscription_webhook_event' ) );
		add_action( 'wpec_sub_stripe_webhook_event', array( $this, 'handle_stripe_subscription_webhook_event' ) );

		// admin hooks
		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			add_action( 'wpec_save_product_handler', array( $this, 'save_product_handler' ) );
		}
	}

	public function handle_signup( $payment, $order_id, $product_id ) {}

	public function handle_paypal_subscription_webhook_event( $event ) {}

	public function handle_stripe_subscription_webhook_event( $event ) {}

	public function add_meta_boxes() {}

	public function save_product_handler( $post_id ) {}

	public function get_plugin_name() {
		return isset( $this->plugin_name ) ? $this->plugin_name : 'N/A';
	}

	public function get_member_info_from_stripe_ipn( $payment ) {
		$customer_details = isset( $payment->customer_details ) ? $payment->customer_details : array();

		$address = isset( $customer_details->address ) ? $customer_details->address : array();

		$email = isset( $customer_details->email ) ? ( $customer_details->email ) : '';
		$name  = isset( $customer_details->name ) ? sanitize_text_field( $customer_details->name ) : '';
		$phone = isset( $customer_details->phone ) ? sanitize_text_field( $customer_details->phone ) : '';

		$last_name  = ( strpos( $name, ' ' ) === false ) ? '' : preg_replace( '#.*\s([\w-]*)$#', '$1', $name );
		$first_name = trim( preg_replace( '#' . $last_name . '#', '', $name ) );

		$city         = isset( $address->city ) ? sanitize_text_field( $address->city ) : '';
		$state        = isset( $address->state ) ? sanitize_text_field( $address->state ) : '';
		$postal_code  = isset( $address->postal_code ) ? sanitize_text_field( $address->postal_code ) : '';
		$country_code = isset( $address->country ) ? sanitize_text_field( $address->country ) : '';
		$country      = Utils::get_country_name_by_country_code( $country_code );
		$line1        = isset( $address->line1 ) ? sanitize_text_field( $address->line1 ) : '';
		$line2        = isset( $address->line2 ) ? sanitize_text_field( $address->line2 ) : '';

		$txn_id = isset( $payment->payment_intent->latest_charge->id ) ? $payment->payment_intent->latest_charge->id : '';;
		if(isset($payment->subscription->id)){
			$txn_id = sanitize_text_field($payment->subscription->id);
		}

		$ipn_data = array(
			'payer_email'     => $email,
			'first_name'      => $first_name,
			'last_name'       => $last_name,
			'txn_id'          => $txn_id,
			'address_street'  => implode( ', ', array( $line1, $line2 ) ),
			'address_city'    => $city,
			'address_state'   => $state,
			'address_zip'     => $postal_code,
			'address_country' => $country,
		);

		return $ipn_data;
	}

	public function get_member_info_from_api( $payment ) {
		// let's form data required for eMember_handle_subsc_signup_stand_alone function and call it.
		$first_name   = ! empty( $payment['payer']['name']['given_name'] ) ? sanitize_text_field($payment['payer']['name']['given_name']) : '';
		$last_name    = ! empty( $payment['payer']['name']['surname'] ) ? sanitize_text_field($payment['payer']['name']['surname']) : '';
		$addr_street  = ! empty( $payment['payer']['address']['address_line_1'] ) ? sanitize_text_field($payment['payer']['address']['address_line_1']) : '';
		$addr_zip     = ! empty( $payment['payer']['address']['postal_code'] ) ? sanitize_text_field($payment['payer']['address']['postal_code']) : '';
		$addr_city    = ! empty( $payment['payer']['address']['admin_area_2'] ) ? sanitize_text_field($payment['payer']['address']['admin_area_2']) : '';
		$addr_state   = ! empty( $payment['payer']['address']['admin_area_1'] ) ? sanitize_text_field($payment['payer']['address']['admin_area_1']) : '';
		$addr_country = ! empty( $payment['payer']['address']['country_code'] ) ? sanitize_text_field($payment['payer']['address']['country_code']) : '';

		if ( ! empty( $addr_country ) ) {
			// convert country code to country name.
			$countries = Utils::get_countries_untranslated();
			if ( isset( $countries[ $addr_country ] ) ) {
				$addr_country = $countries[ $addr_country ];
			}
		}

		$txn_id = isset($payment['id']) ? sanitize_text_field($payment['id']) : '';
		$payer_email = isset($payment['payer']['email_address']) ? sanitize_email($payment['payer']['email_address']) : '';

		if ( isset($payment['subscription']) ) {
			$txn_id = isset($payment['subscription']['id']) ? sanitize_text_field($payment['subscription']['id']) : '';
		}

		$ipn_data = array(
			'payer_email'     => $payer_email,
			'first_name'      => $first_name,
			'last_name'       => $last_name,
			'txn_id'          => $txn_id,
			'address_street'  => $addr_street,
			'address_city'    => $addr_city,
			'address_state'   => $addr_state,
			'address_zip'     => $addr_zip,
			'address_country' => $addr_country,
		);

		return $ipn_data;
	}
}
