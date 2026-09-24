<?php

namespace WP_Express_Checkout\Integrations;

use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Products;
use WP_Express_Checkout\Utils_Kses;

class Simple_WP_Membership extends Integration {

    public $plugin_name = 'Simple WP Membership';

	public function handle_signup( $payment, $order_id, $product_id ) {

		// let's check if Membership Level is set for this product
		$level_id = get_post_meta( $product_id, 'wpec_product_swpm_level', true );
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

		Logger::log( 'Calling swpm_handle_subsc_signup_stand_alone' );

		$swpm_id = '';
		if ( \SwpmMemberUtils::is_member_logged_in() ) {
			$swpm_id = \SwpmMemberUtils::get_logged_in_members_id();
		}

		if ( defined( 'SIMPLE_WP_MEMBERSHIP_PATH' ) ) {
			if ( !function_exists('swpm_handle_subsc_signup_stand_alone') ){
				require_once SIMPLE_WP_MEMBERSHIP_PATH . 'ipn/swpm_handle_subsc_ipn.php';
			}
			swpm_handle_subsc_signup_stand_alone( $ipn_data, $level_id, $unique_ref, $swpm_id );
		}

	}

	public function add_meta_boxes() {
		add_meta_box( 'wpec_swpm_meta_box', __( 'Simple Membership Level', 'wp-express-checkout' ), array( $this, 'display_meta_box' ), Products::$products_slug, 'normal', 'high' );
	}

	public function display_meta_box( $post ) {
		$current_val = get_post_meta( $post->ID, 'wpec_product_swpm_level', true );
		?>
		<p>
			<?php esc_html_e( 'If you want this product to be connected to a membership level then select the membership Level here.', 'wp-express-checkout' ); ?>
		</p>
		<select name="wpec_product_swpm_level">
			<option value=""><?php esc_html_e( 'None', 'wp-express-checkout' ); ?></option>
			<?php echo wp_kses( \SwpmUtils::membership_level_dropdown( $current_val ), Utils_Kses::wp_kses_select_option_tags() ); ?>
		</select>
		<?php
	}

	function save_product_handler( $post_id ) {
		update_post_meta( $post_id, 'wpec_product_swpm_level', ! empty( $_POST['wpec_product_swpm_level'] ) ? intval( $_POST['wpec_product_swpm_level'] ) : '' );
	}

    public function handle_paypal_subscription_webhook_event( $event ) {
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

        $ipn_data = array(
            'subscr_id' => $sub_id,
            'payer_email' => ''
        );//The payer_email is not really needed for this function.

        Logger::log( sprintf("Checking if %s plugin needs to handle this PayPal webhook event type: %s" ,  $this->get_plugin_name(), $webhook_event_type));

        switch ( $webhook_event_type ) {
            case 'BILLING.SUBSCRIPTION.EXPIRED':
            case 'BILLING.SUBSCRIPTION.CANCELLED':
            case 'BILLING.SUBSCRIPTION.SUSPENDED':
                // A subscription is suspended.
                if (function_exists('swpm_handle_subsc_cancel_stand_alone')){
                    swpm_handle_subsc_cancel_stand_alone( $ipn_data , false);
                } else {
                    Logger::log(sprintf("The function/method '%s' isn't available, the event couldn't be handled for %s integration", "swpm_handle_subsc_cancel_stand_alone", $this->get_plugin_name()), false);
                }
                break;
            case 'PAYMENT.SALE.COMPLETED':
                // A payment is made on a subscription.
                if (function_exists('swpm_update_member_subscription_start_date_if_applicable')){
                    swpm_update_member_subscription_start_date_if_applicable( $ipn_data );
                } else {
                    Logger::log(sprintf("The function/method '%s' isn't available, the event couldn't be handled for %s integration", "swpm_update_member_subscription_start_date_if_applicable", $this->get_plugin_name()), false);
                }
                break;
            case 'BILLING.SUBSCRIPTION.ACTIVATED':
            default:
                Logger::log( sprintf("This PayPal webhook event '%s' is currently not needed for %s", $webhook_event_type, $this->get_plugin_name()));
                break;
        }
    }

    public function handle_stripe_subscription_webhook_event( $event ) {
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
        } else if ( $event->data->object->object == 'invoice' ) {
            $sub_id = isset($event->data->object->parent->subscription_details->subscription) ? $event->data->object->parent->subscription_details->subscription : '';
            $payer_email = isset($event->data->object->customer_email) ? $event->data->object->customer_email : '';
        }

        if(empty($sub_id)){
            Logger::log( sprintf('%s: no subscription ID found in the event: %s', __METHOD__, $webhook_event_type), false );
            return;
        }

        $ipn_data = array(
            'subscr_id' => $sub_id,
            'payer_email' => $payer_email
        ); //The payer_email is not really needed for this function.

        Logger::log( sprintf("Checking if %s plugin needs to handle this Stripe webhook event type: %s" ,  $this->get_plugin_name(), $webhook_event_type));

        // Handle the event
        switch ($event->type) {
            case 'invoice.paid':
                // A payment is made on a subscription.
                if (function_exists('swpm_update_member_subscription_start_date_if_applicable')){
                    swpm_update_member_subscription_start_date_if_applicable( $ipn_data );
                } else {
                    Logger::log(sprintf("The function/method '%s' isn't available, the event couldn't be handled for %s integration", "swpm_update_member_subscription_start_date_if_applicable", $this->get_plugin_name()), false);
                }
                break;
            case 'customer.subscription.deleted':
                // A subscription is canceled.
                if (function_exists('swpm_handle_subsc_cancel_stand_alone')){
                    swpm_handle_subsc_cancel_stand_alone( $ipn_data , false);
                } else {
                    Logger::log(sprintf("The function/method '%s' isn't available, the event couldn't be handled for %s integration", "swpm_handle_subsc_cancel_stand_alone", $this->get_plugin_name()), false);
                }

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
}
