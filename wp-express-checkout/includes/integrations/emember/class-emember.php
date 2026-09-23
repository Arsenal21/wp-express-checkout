<?php

namespace WP_Express_Checkout\Integrations;

use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Products;
use WP_Express_Checkout\Utils_Kses;

class Emember extends Integration {

    public $plugin_name = 'WP eMember';

	public function handle_signup( $payment, $order_id, $product_id ) {
		// let's check if Membership Level is set for this product.
		$level_id = get_post_meta( $product_id, 'wpec_product_emember_level', true );
		if ( empty( $level_id ) ) {
			return;
		}

        $payment_gateway = get_post_meta($order_id, 'wpec_order_payment_gateway', true);
        if (!empty($payment_gateway) && $payment_gateway == 'stripe'){
		    $ipn_data = $this->get_member_info_from_stripe_ipn( $payment );
        } else {
		    $ipn_data = $this->get_member_info_from_api( $payment );
        }
        $unique_ref = isset($ipn_data['txn_id']) ? sanitize_text_field($ipn_data['txn_id']) : '';

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

	public function handle_paypal_subscription_webhook_event( $event ){

		/*
		Note: the Payment_Handler() function has a summary of the type of events we can handle for a subscription webhook. Check function Factory::create for more details.
		*/

        $webhook_event_type = isset($event['event_type'])? $event['event_type'] : '';
        if(empty($webhook_event_type)){
            Logger::log( sprintf('%s: no event type found in the webhook.', __METHOD__), false );
            return;
        }

		// Get the subscr_id from the event
		$sub_id = '';
        if ( isset($event['resource']['billing_agreement_id']) ){
	        $sub_id = $event['resource']['billing_agreement_id'];
        } else if ( isset($event['resource']['id'])) {
            $sub_id = $event['resource']['id'];
        }

		if(empty($sub_id)){
            Logger::log( sprintf('%s: no subscription ID found in the event: %s', __METHOD__, $webhook_event_type), false );
			return;
		}

		//We can retrieve the Subscription object from the database to get additional info (but it may not be needed here)
		//$subscription = Subscriptions::retrieve( $sub_id );
		//if ( ! $subscription ) {
		//	return;
		//}

		if ( !defined( 'WP_EMEMBER_PATH' ) ) {
			//This class won't initialize if eMember is not installed. However, we are going to have this check here just in case.
			Logger::log( sprintf("%s: %s plugin is not installed.", __METHOD__, $this->get_plugin_name()), false );
			return;
		}
		require_once WP_EMEMBER_PATH . 'ipn/eMember_handle_subsc_ipn_stand_alone.php';

		$ipn_data = array('subscr_id' => $sub_id, 'payer_email' => '');//The payer_email is not really needed for this function.

        Logger::log( sprintf("Checking if %s plugin needs to handle this PayPal webhook event type: %s" , $this->get_plugin_name(), $webhook_event_type));

		switch ( $webhook_event_type ) {
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
			case 'BILLING.SUBSCRIPTION.ACTIVATED':
			default:
                Logger::log( sprintf("This PayPal webhook event '%s' is currently not needed for %s", $webhook_event_type, $this->get_plugin_name()));
				break;
		}

	}

    public function handle_stripe_subscription_webhook_event( $event) {

	    /*
		Note: the Payment_Handler() function has a summary of the type of events we can handle for a subscription webhook. Check function Factory::create for more details.
		*/

        $webhook_event_type = isset($event->type)? $event->type : '';
        if(empty($webhook_event_type)){
            Logger::log( sprintf('%s: no event type found in the webhook.', __METHOD__), false );
            return;
        }

	    // Get the subscr_id from the event
	    $sub_id = '';
	    $payer_email = '';

	    if ( $event->data->object->object == 'subscription' ) {
		    $sub_id = isset($event->data->object->id) ? $event->data->object->id : '';
	    }
        else if ( $event->data->object->object == 'invoice' ) {
		    $sub_id = isset($event->data->object->parent->subscription_details->subscription) ? $event->data->object->parent->subscription_details->subscription : '';
	        $payer_email = isset($event->data->object->customer_email) ? $event->data->object->customer_email : '';
	    }

	    if(empty($sub_id)){
            Logger::log( sprintf('%s: no subscription ID found in the event: %s', __METHOD__, $webhook_event_type), false );
		    return;
	    }

	    //We can retrieve the Subscription object from the database to get additional info (but it may not be needed here)
	    //$subscription = Subscriptions::retrieve( $sub_id );
	    //if ( ! $subscription , false) {
	    //	return;
	    //}

	    if ( !defined( 'WP_EMEMBER_PATH' ) ) {
		    //This class won't initialize if eMember is not installed. However, we are going to have this check here just in case.
            Logger::log( sprintf("%s: %s plugin is not installed.", __METHOD__, $this->get_plugin_name()), false );
		    return;
	    }

	    require_once WP_EMEMBER_PATH . 'ipn/eMember_handle_subsc_ipn_stand_alone.php';

	    $ipn_data = array('subscr_id' => $sub_id, 'payer_email' => $payer_email); //The payer_email is not really needed for this function.

        Logger::log( sprintf("Checking if %s plugin needs to handle this Stripe webhook event type: %s" , $this->get_plugin_name(), $webhook_event_type));

        // Handle the event
	    switch ($event->type) {
		    case 'invoice.paid':
			    // A payment is made on a subscription.
                // Logger::log( sprintf('Code came here %s %d', $event->type, __LINE__) );
			    eMember_update_member_subscription_start_date_if_applicable($ipn_data);
			    break;
		    case 'customer.subscription.deleted':
			    // A subscription is canceled.
                // Logger::log( sprintf('Code came here %s %d', $event->type, __LINE__) );
			    eMember_handle_subsc_cancel_stand_alone($ipn_data);
			    break;
		    case 'invoice.payment_failed':
		    case 'invoice.payment_action_required':
		    case 'customer.subscription.created':
		    case 'customer.subscription.updated':
		    default:
                Logger::log( sprintf("This Stripe webhook event '%s' is currently not needed for %s", $webhook_event_type, $this->get_plugin_name()));
                break;
	    }
    }

	public function add_meta_boxes() {
		add_meta_box( 'wpec_emember_meta_box', __( 'WP eMember Membership Level', 'wp-express-checkout' ), array( $this, 'display_meta_box' ), Products::$products_slug, 'normal', 'high' );
	}

	public function display_meta_box( $post ) {
		$current_val = get_post_meta( $post->ID, 'wpec_product_emember_level', true );

		if ( ! function_exists( 'emember_get_all_membership_levels_list' ) ) {
			esc_html_e( 'Notice: You need to update your copy of the WP eMember plugin before this feature can be used.', 'wp-express-checkout' );
			return;
		}

		$all_levels = emember_get_all_membership_levels_list();
		$levels_str = '<option value="">(' . esc_html__( 'None', 'wp-express-checkout' ) . ')</option>' . "\r\n";

		foreach ( $all_levels as $level ) {
			$levels_str .= '<option value="' . esc_attr( $level->id ) . '"' . ( $level->id === $current_val ? ' selected' : '' ) . '>' . esc_html( stripslashes( $level->alias ) ) . '</option>' . "\r\n";
		}
		?>
        <p><?php esc_html_e( 'If you want this product to be connected to a membership level then select the membership Level here.', 'wp-express-checkout' ); ?></p>
        <select name="wpec_product_emember_level">
            <?php echo wp_kses( $levels_str, Utils_Kses::wp_kses_select_option_tags() ); ?>
        </select>
		<?php
	}

	function save_product_handler( $post_id ) {
		update_post_meta( $post_id, 'wpec_product_emember_level', ! empty( $_POST['wpec_product_emember_level'] ) ? intval( $_POST['wpec_product_emember_level'] ) : '' );
	}

}
