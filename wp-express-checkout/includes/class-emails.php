<?php

namespace WP_Express_Checkout;

use WP_Express_Checkout\Debug\Logger;

class Emails {

	/**
	 * Send email notification to buyer if enabled.
	 *
	 * @param Order $order Buyer's order object.
	 *
	 * @return bool Whether the email was sent successfully.
	 */
	public static function send_buyer_email( $order ) {
		$wpec_plugin = Main::get_instance();
		// Send email to buyer if enabled.
		if ( ! $wpec_plugin->get_setting( 'send_buyer_email' ) ) {
			return;
		}

		$renderer = new Order_Tags_Plain( $order );
		$tags     = array_keys( Utils::get_dynamic_tags_white_list() );

		foreach ( $tags as $tag ) {
			$args[ $tag ] = $renderer->$tag();
		}

		$buyer_email = $renderer->payer_email();
		Logger::log( 'Sending buyer notification email.' );

		// Check if custom email is configured or not.
		$is_product_custom_email_enabled = $wpec_plugin->get_setting( 'enable_per_product_email_customization' ) == 1;
		$ordered_item = $order->get_item('ppec-products');
		$ordered_item_post_id = isset($ordered_item['post_id']) ? $ordered_item['post_id'] : 0;
		$is_product_custom_buyer_email_enabled = !empty($ordered_item_post_id) && get_post_meta( $ordered_item_post_id, 'custom_buyer_email_enabled', true ) == 1;
		if ($is_product_custom_email_enabled && $is_product_custom_buyer_email_enabled) {
			Logger::log( 'Per-product customized buyer email notification is enabled for this product.' );
			$from_email = html_entity_decode(get_post_meta( $ordered_item_post_id, 'custom_buyer_email_from', true ));
			$subject    = get_post_meta( $ordered_item_post_id, 'custom_buyer_email_subj', true );
			$body       = get_post_meta( $ordered_item_post_id, 'custom_buyer_email_body', true );
		}else{
			$from_email = $wpec_plugin->get_setting( 'buyer_from_email' );
			$subject    = $wpec_plugin->get_setting( 'buyer_email_subj' );
			$body       = $wpec_plugin->get_setting( 'buyer_email_body' );
		}
		$subject    = Utils::apply_dynamic_tags( $subject, $args );

		$args['email_body'] = $body;

		$body = Utils::apply_dynamic_tags( $body, $args );
		$body = apply_filters( 'wpec_buyer_notification_email_body', $body, $order, $args );

		$result = self::send( $buyer_email, $from_email, $subject, $body );

		if ( $result ) {
			Logger::log( 'Buyer email notification sent to: ' . $buyer_email . '. From email address value used: ' . $from_email );

			update_post_meta( $order->get_id(), 'wpec_buyer_email_sent', 'Email sent to: ' . $buyer_email );
		} else {
			Logger::log( 'Buyer email notification sending failed to: ' . $buyer_email . '. From email address value used: ' . $from_email );
		}

		return $result;
	}

	/**
	 * Send email notification to seller if enabled.
	 *
	 * @param Order $order Buyer's order object.
	 *
	 * @return bool Whether the email was sent successfully.
	 */
	public static function send_seller_email( $order ) {
		$wpec_plugin = Main::get_instance();
		// Send email to seller if needs.
		if ( ! $wpec_plugin->get_setting( 'send_seller_email' ) || empty( $wpec_plugin->get_setting( 'notify_email_address' ) ) ) {
			return;
		}

		$renderer = new Order_Tags_Plain( $order );
		$tags     = array_keys( Utils::get_dynamic_tags_white_list() );

		foreach ( $tags as $tag ) {
			$args[ $tag ] = $renderer->$tag();
		}

		Logger::log( 'Sending seller notification email.' );

		// Check if custom email is configured or not.
		$is_product_custom_email_enabled = $wpec_plugin->get_setting( 'enable_per_product_email_customization' ) == 1;
		$ordered_item = $order->get_item('ppec-products');
		$ordered_item_post_id = isset($ordered_item['post_id']) ? $ordered_item['post_id'] : 0;
		$is_product_custom_seller_email_enabled = !empty($ordered_item_post_id) && get_post_meta( $ordered_item_post_id, 'custom_seller_email_enabled', true ) == 1;
		if ($is_product_custom_email_enabled && $is_product_custom_seller_email_enabled) {
			Logger::log( 'Per-product customized seller email notification is enabled for this product.' );
			//From email address value is common for both buyer and seller emails. Need to html_entity_decode to get the correct email address format when custom email is used.
			$from_email = html_entity_decode(get_post_meta( $ordered_item_post_id, 'custom_buyer_email_from', true ));

			$notify_email = get_post_meta( $ordered_item_post_id, 'custom_seller_notification_email', true );
			$seller_email_subject = get_post_meta( $ordered_item_post_id, 'custom_seller_email_subj', true );
			$seller_email_body = get_post_meta( $ordered_item_post_id, 'custom_seller_email_body', true );
		}else{
			$from_email = $wpec_plugin->get_setting( 'buyer_from_email' );
			$notify_email = $wpec_plugin->get_setting( 'notify_email_address' );
			$seller_email_subject = $wpec_plugin->get_setting( 'seller_email_subj' );
			$seller_email_body = $wpec_plugin->get_setting( 'seller_email_body' );
		}

		$seller_email_subject = Utils::apply_dynamic_tags( $seller_email_subject, $args );

		$seller_email_body = Utils::apply_dynamic_tags( $seller_email_body, $args );
		$seller_email_body = apply_filters( 'wpec_seller_notification_email_body', $seller_email_body, $order, $args );

		$result = self::send( $notify_email, $from_email, $seller_email_subject, $seller_email_body );

		if ( $result ) {
			Logger::log( 'Seller email notification sent to: ' . $notify_email . '. From email address value used: ' . $from_email);
		} else {
			Logger::log( 'Seller email notification sending failed to: ' . $notify_email );
		}

		return $result;
	}

	/**
	 * Sends email with standardized headers.
	 *
	 * @param string $to      Email To address.
	 * @param string $from    Email To address.
	 * @param string $subject Email subject.
	 * @param string $body    Email body.
	 *
	 * @return bool Whether the email was sent successfully.
	 */
	public static function send( $to, $from = null, $subject = '', $body = '' ) {
		$wpec_plugin = Main::get_instance();

		$headers = array();

		if ( 'html' === $wpec_plugin->get_setting( 'buyer_email_type' ) ) {
			$headers[] = 'Content-Type: text/html; charset="' . get_bloginfo( 'charset' ) . '"';
		}

		if ( $from ) {
			$headers[] = 'From: ' . $from . "\r\n";
		}

		if ( 'html' === $wpec_plugin->get_setting( 'buyer_email_type' ) ) {
			$body = nl2br( $body );
		} else {
			$body = html_entity_decode( $body );
		}

		return wp_mail( $to, wp_specialchars_decode( $subject, ENT_QUOTES ), $body, $headers );
	}

	/**
	 * Send manual payment instruction email to buyer if enabled.
	 */
	public static function send_manual_checkout_buyer_instruction_email( $order ) {
		$wpec_plugin = Main::get_instance();

		// Send email to buyer if enabled.
		if ( empty($wpec_plugin->get_setting( 'enable_manual_checkout_buyer_instruction_email' )) ) {
			return;
		}

		$renderer = new Order_Tags_Plain( $order );
		$tags     = array_keys( Utils::get_dynamic_tags_white_list_for_manual_checkout() );

		foreach ( $tags as $tag ) {
			$args[ $tag ] = $renderer->$tag();
		}

		$buyer_email = $renderer->payer_email();
		Logger::log( 'Sending manual checkout buyer instruction email.' );

		$from_email = $wpec_plugin->get_setting( 'buyer_from_email' );
		if (empty($from_email)){
			$from_email = get_bloginfo( 'name' ) . ' <sales@your-domain.com>';
		}

		$subject    = $wpec_plugin->get_setting( 'manual_checkout_buyer_instruction_email_subject' );
		$body       = $wpec_plugin->get_setting( 'manual_checkout_buyer_instruction_email_body' );

		$subject    = Utils::apply_dynamic_tags( $subject, $args );

		$args['email_body'] = $body;

		$body = Utils::apply_dynamic_tags( $body, $args );
		$body = apply_filters( 'wpec_manual_payment_instruction_email_body', $body, $order, $args );

		$result = self::send( $buyer_email, $from_email, $subject, $body );

		if ( $result ) {
			Logger::log( 'Manual payment buyer instruction email sent to: ' . $buyer_email . '. From email address value used: ' . $from_email );
			update_post_meta( $order->get_id(), 'wpec_manual_payment_instruction_email_sent', 'Email sent to: ' . $buyer_email );
		} else {
			Logger::log( 'Manual payment buyer instruction email sending failed to: ' . $buyer_email . '. From email address value used: ' . $from_email );
		}

		return $result;
	}

	/**
	 * Send manual checkout notification email to seller if enabled.
	 */
	public static function send_manual_checkout_seller_notification_email($order) {
		$wpec_plugin = Main::get_instance();

		// Send email to seller if enabled.
		if ( ! $wpec_plugin->get_setting( 'enable_manual_checkout_seller_notification_email' ) ) {
			return;
		}

		$renderer = new Order_Tags_Plain( $order );
		$tags     = array_keys( Utils::get_dynamic_tags_white_list_for_manual_checkout() );

		foreach ( $tags as $tag ) {
			$args[ $tag ] = $renderer->$tag();
		}

		Logger::log( 'Sending manual checkout seller notification email.' );

		$seller_email    = $wpec_plugin->get_setting( 'manual_checkout_seller_notification_email_address' );
		if (empty($seller_email) || !is_email($seller_email)){
			$seller_email = $wpec_plugin->get_setting( 'notify_email_address' );
		}

		$from_email = $wpec_plugin->get_setting( 'buyer_from_email' );
		if (empty($from_email)){
			$from_email = get_bloginfo( 'name' ) . ' <sales@your-domain.com>';
		}

		$subject    = $wpec_plugin->get_setting( 'manual_checkout_seller_notification_email_subject' );
		$body       = $wpec_plugin->get_setting( 'manual_checkout_seller_notification_email_body' );

		$subject = Utils::apply_dynamic_tags( $subject, $args );

		$args['email_body'] = $body;

		$body = Utils::apply_dynamic_tags( $body, $args );
		$body = apply_filters( 'wpec_manual_checkout_notification_email_body', $body, $order, $args );

		$result = self::send( $seller_email, $from_email, $subject, $body );

		if ( $result ) {
			Logger::log( 'Manual payment seller notification email sent to: ' . $seller_email . '. From email address value used: ' . $from_email );
		} else {
			Logger::log( 'Manual payment seller notification email sending failed to: ' . $seller_email . '. From email address value used: ' . $from_email );
		}

		return $result;
	}
}