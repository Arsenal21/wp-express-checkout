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

		$from_email = $wpec_plugin->get_setting( 'buyer_from_email' );
		$subject    = $wpec_plugin->get_setting( 'buyer_email_subj' );
		$subject    = Utils::apply_dynamic_tags( $subject, $args );
		$body       = $wpec_plugin->get_setting( 'buyer_email_body' );

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

		$notify_email = $wpec_plugin->get_setting( 'notify_email_address' );

		$seller_email_subject = $wpec_plugin->get_setting( 'seller_email_subj' );
		$seller_email_subject = Utils::apply_dynamic_tags( $seller_email_subject, $args );

		$seller_email_body = $wpec_plugin->get_setting( 'seller_email_body' );
		$seller_email_body = Utils::apply_dynamic_tags( $seller_email_body, $args );
		$seller_email_body = apply_filters( 'wpec_seller_notification_email_body', $seller_email_body, $order, $args );

		$result = self::send( $notify_email, null, $seller_email_subject, $seller_email_body );

		if ( $result ) {
			Logger::log( 'Seller email notification sent to: ' . $notify_email );
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

}
