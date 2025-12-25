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
		add_action( 'wpec_sub_stripe_webhook_event', array( $this, 'handle_stripe_subscription_webhook_event_for_emember' ) );

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

		$unique_ref = ''; // This must be maximum of 32 characters.
        $payment_gateway = get_post_meta($order_id, 'wpec_order_payment_gateway', true);
        if (!empty($payment_gateway) && $payment_gateway == 'stripe'){
		    $ipn_data = $this->get_member_info_from_stripe_ipn( $payment );
		    $unique_ref = isset($payment->subscription->id) ? $payment->subscription->id : $ipn_data['txn_id'];
        } else {
		    $ipn_data = $this->get_member_info_from_api( $payment );
		    $unique_ref = $ipn_data['txn_id'];
        }

		Logger::log( 'Calling eMember_handle_subsc_signup_stand_alone' );

		$emember_id = '';
		if ( class_exists( 'Emember_Auth' ) ) {
			// Check if the user is logged in as a member.
			$emember_auth = \Emember_Auth::getInstance();
			$emember_id   = $emember_auth->getUserInfo( 'member_id' );
		}

		if ( defined( 'WP_EMEMBER_PATH' ) ) {
			require_once WP_EMEMBER_PATH . 'ipn/eMember_handle_subsc_ipn_stand_alone.php';
			eMember_handle_subsc_signup_stand_alone( $ipn_data, $level_id, $unique_ref, $emember_id );
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

    public function handle_stripe_subscription_webhook_event_for_emember( $event) {

	    /*
		Note: the Payment_Handler() function has a summary of the type of events we can handle for a subscription webhook. Check function Factory::create for more details.
		*/

	    // Get the subscr_id from the event
	    $sub_id = '';
	    $payer_email = '';

	    if ( $event->data->object->object == 'subscription' ) {
		    $sub_id = $event->data->object->id;
	    }
        else if ( $event->data->object->object == 'invoice' ) {
		    $sub_id = $event->data->object->parent->subscription_details->subscription;
	        $payer_email = $event->data->object->customer_email;
	    }

	    if(empty($sub_id)){
		    Logger::log( __METHOD__ .': no subscription ID found in the event', false );
		    return;
	    }

	    //We can retrieve the Subscription object from the database to get additional info (but it may not be needed here)
	    //$subscription = Subscriptions::retrieve( $sub_id );
	    //if ( ! $subscription , false) {
	    //	return;
	    //}

	    $webhook_event_type = isset($event->type)? $event->type : '';
	    if(empty($webhook_event_type)){
		    Logger::log( __METHOD__ .': no event type found in the webhook.', false );
		    return;
	    }

	    if ( !defined( 'WP_EMEMBER_PATH' ) ) {
		    //This class won't initialize if eMember is not installed. However, we are going to have this check here just in case.
		    Logger::log( __METHOD__ .': WP eMember plugin is not installed.', false );
		    return;
	    }

	    require_once WP_EMEMBER_PATH . 'ipn/eMember_handle_subsc_ipn_stand_alone.php';

	    $ipn_data = array('subscr_id' => $sub_id, 'payer_email' => $payer_email); //The payer_email is not really needed for this function.

	    Logger::log( 'Checking if WP eMember function call is needed to handle the webhook event type: ' .  $webhook_event_type);

	    // Handle the event
	    switch ($event->type) {
		    case 'invoice.paid':
			    // A payment is made on a subscription.
                Logger::log( sprintf('Code came here %s %d', $event->type, __LINE__) );
			    eMember_update_member_subscription_start_date_if_applicable($ipn_data);
			    break;
		    case 'customer.subscription.deleted':
			    // A subscription is canceled.
                Logger::log( sprintf('Code came here %s %d', $event->type, __LINE__) );
			    eMember_handle_subsc_cancel_stand_alone($ipn_data);
			    break;
		    case 'invoice.payment_failed':
		    case 'invoice.payment_action_required':
		    case 'customer.subscription.created':
		    case 'customer.subscription.updated':
		    default:
			    // We don't need to do anything here for WP eMember.
                break;
	    }
    }

	public function get_member_info_from_stripe_ipn( $payment ) {
        $customer_details = isset( $payment->customer_details ) ? $payment->customer_details : array();

        $address = isset($customer_details->address) ? $customer_details->address : array();

		$email = isset($customer_details->email) ? ($customer_details->email) : '';
		$name = isset($customer_details->name) ? sanitize_text_field($customer_details->name) : '';
		$phone = isset($customer_details->phone) ? sanitize_text_field($customer_details->phone) : '';

		$last_name    = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
		$first_name   = trim(preg_replace('#' . $last_name . '#', '', $name));

		$city         = isset( $address->city ) ? sanitize_text_field($address->city) : '';
		$state        = isset( $address->state ) ? sanitize_text_field($address->state) : '';
		$postal_code  = isset( $address->postal_code ) ? sanitize_text_field($address->postal_code) : '';
		$country_code = isset( $address->country ) ? sanitize_text_field($address->country) : '';
        $country       = Utils::get_country_name_by_country_code( $country_code );
		$line1 = isset( $address->line1 ) ? sanitize_text_field($address->line1) : '';
		$line2 = isset( $address->line2 ) ? sanitize_text_field($address->line2) : '';

        $txn_id = isset( $payment->payment_intent->latest_charge->id ) ? $payment->payment_intent->latest_charge->id : ''; ;

		$ipn_data = array(
			'payer_email'     => $email,
			'first_name'      => $first_name,
			'last_name'       => $last_name,
			'txn_id'          => $txn_id,
			'address_street'  => implode( ', ', array($line1, $line2) ),
			'address_city'    => $city,
			'address_state'   => $state,
			'address_zip'     => $postal_code,
			'address_country' => $country,
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
