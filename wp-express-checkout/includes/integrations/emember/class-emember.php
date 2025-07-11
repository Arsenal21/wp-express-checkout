<?php

namespace WP_Express_Checkout\Integrations;

use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Products;
use WP_Express_Checkout\Utils;

class Emember {

	public function __construct() {
		//Standard payment completed hook.
		add_action( 'wpec_payment_completed', array( $this, 'handle_signup' ), 10, 3 );

		//Subscription payment related hooks.
		add_action( 'wpec_sub_webhook_event', array( $this, 'handle_subscription_webhook_event_for_emember' ) );

		if ( is_admin() ) {
			$this->admin();
		}
	}

	public function handle_signup( $payment, $order_id, $product_id ) {

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

	public function handle_subscription_webhook_event_for_emember( $event ){

		/*
		Note: the Payment_Handler() function has a summary of the type of events we can handle for a subscription webhook. Check function Factory::create for more details.
		*/

		// Get the subscr_id from the event
		$sub_id = '';
        if ( isset($event['resource']['billing_agreement_id']) ){
	        $sub_id = $event['resource']['billing_agreement_id'];
        } else if ( isset($event['resource']['id'])) {
            $sub_id = $event['resource']['id'];
        }

		if(empty($sub_id)){
			Logger::log( 'handle_subscription_webhook_event: no subscription ID found in the event', false );
			return;
		}

		//We can retrieve the Subscription object from the database to get additional info (but it may not be needed here)
		//$subscription = Subscriptions::retrieve( $sub_id );
		//if ( ! $subscription ) {
		//	return;
		//}

		$webhook_event_type = isset($event['event_type'])? $event['event_type'] : '';
		if(empty($webhook_event_type)){
			Logger::log( 'handle_subscription_webhook_event: no event type found in the webhook.', false );
			return;
		}

		if ( !defined( 'WP_EMEMBER_PATH' ) ) {
			//This class won't initialize if eMember is not installed. However, we are going to have this check here just in case.
			Logger::log( 'handle_subscription_webhook_event: WP eMember plugin is not installed.', false );
			return;
		}
		require_once WP_EMEMBER_PATH . 'ipn/eMember_handle_subsc_ipn_stand_alone.php';

		$ipn_data = array('subscr_id' => $sub_id, 'payer_email' => '');//The payer_email is not really needed for this function.

		Logger::log( 'Checking if WP eMember function call is needed to handle the webhook event type: ' .  $webhook_event_type);

		switch ( $webhook_event_type ) {
			case 'BILLING.SUBSCRIPTION.ACTIVATED':
				// We don't need to do anything here for WP eMember.
				break;
			case 'BILLING.SUBSCRIPTION.EXPIRED':
				// A subscription expires.
				eMember_handle_subsc_cancel_stand_alone($ipn_data);
				break;
			case 'BILLING.SUBSCRIPTION.CANCELLED':
				// A subscription is canceled.
				eMember_handle_subsc_cancel_stand_alone($ipn_data);
				break;
			case 'BILLING.SUBSCRIPTION.SUSPENDED':
				// A subscription is suspended.
				eMember_handle_subsc_cancel_stand_alone($ipn_data);
				break;
			case 'PAYMENT.SALE.COMPLETED':
				// A payment is made on a subscription.
				eMember_update_member_subscription_start_date_if_applicable($ipn_data);
				break;
			default:
				// NOP
				break;
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

	public function admin() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'wpec_save_product_handler', array( $this, 'save_product_handler' ) );
	}

	public function add_meta_boxes() {
		add_meta_box( 'wpec_emember_meta_box', __( 'WP eMember Membership Level', 'wp-express-checkout' ), array( $this, 'display_meta_box' ), Products::$products_slug, 'normal', 'high' );
	}

	public function display_meta_box( $post ) {
		$current_val = get_post_meta( $post->ID, 'wpec_product_emember_level', true );

		if ( ! function_exists( 'emember_get_all_membership_levels_list' ) ) {
			_e( 'Notice: You need to update your copy of the WP eMember plugin before this feature can be used.', 'wp-express-checkout' );
			return;
		}

		$all_levels = emember_get_all_membership_levels_list();
		$levels_str = '<option value="">(' . __( 'None', 'wp-express-checkout' ) . ')</option>' . "\r\n";

		foreach ( $all_levels as $level ) {
			$levels_str .= '<option value="' . $level->id . '"' . ( $level->id === $current_val ? ' selected' : '' ) . '>' . stripslashes( $level->alias ) . '</option>' . "\r\n";
		}
		?>
<p><?php esc_html_e( 'If you want this product to be connected to a membership level then select the membership Level here.', 'wp-express-checkout' ); ?></p>
<select name="wpec_product_emember_level">
		<?php
		echo wp_kses(
			$levels_str,
			array(
				'option' => array(
					'value'    => array(),
					'selected' => array(),
				),
			)
		);
		?>
</select>
		<?php
	}

	function save_product_handler( $post_id ) {
		update_post_meta( $post_id, 'wpec_product_emember_level', ! empty( $_POST['wpec_product_emember_level'] ) ? intval( $_POST['wpec_product_emember_level'] ) : '' );
	}

}
