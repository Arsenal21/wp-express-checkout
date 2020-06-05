<?php
/**
 * This class is used to process the payment after successful charge.
 *
 * Inserts the payment date to the orders menu
 * Sends notification emails.
 * Triggers after payment processed hook: wpec_payment_completed
 * Sends to Thank You page.
 */

/**
 * Process IPN class
 */
class WPEC_Process_IPN {

	/**
	 * The class instance.
	 *
	 * @var WPEC_Process_IPN
	 */
	protected static $instance = null;

	/**
	 * Construct the instance.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wpec_process_payment', array( $this, 'wpec_process_payment' ) );
		add_action( 'wp_ajax_nopriv_wpec_process_payment', array( $this, 'wpec_process_payment' ) );
	}

	/**
	 * Retrieves the instance.
	 *
	 * @return WPEC_Process_IPN
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Processes the payment on AJAX call.
	 */
	public function wpec_process_payment() {

		// TODO: AJAX Nonce verification.

		if ( ! isset( $_POST['wp_ppdg_payment'] ) ) {
			// no payment data provided.
			_e( 'No payment data received.', 'wp-express-checkout' );
			exit;
		}

		$payment = $_POST['wp_ppdg_payment'];

		if ( strtoupper( $payment['status'] ) !== 'COMPLETED' ) {
			// payment is unsuccessful.
			WPEC_Debug_Logger::log( 'Payment is not approved. Payment status: ' . $payment['status'], false );
			printf( __( 'Payment is not approved. Status: %s', 'wp-express-checkout' ), $payment['status'] );
			exit;
		}

		// Log debug (if enabled).
		WPEC_Debug_Logger::log( 'Received IPN. Processing payment ...' );

		// get item name.
		$item_name = $payment['purchase_units'][0]['description'];
		// let's check if the payment matches transient data.
		$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $item_name );
		$trans      = get_transient( $trans_name );
		if ( ! $trans ) {
			// no price set.
			WPEC_Debug_Logger::log( 'Error! No transaction info found in transient.', false );

			_e( 'No transaction info found in transient.', 'wp-express-checkout' );
			exit;
		}
		$price    = $trans['price'];
		$quantity = $trans['quantity'];
		$tax      = $trans['tax'];
		$shipping = $trans['shipping'];
		$currency = $trans['currency'];
		$item_id  = $trans['product_id'];

		if ( $trans['custom_quantity'] ) {
			// custom quantity enabled. let's take quantity from PayPal results.
			$quantity = $payment['purchase_units'][0]['items'][0]['quantity'];
		}

		if ( $trans['custom_amount'] ) {
			// custom amount enabled. let's take quantity from PayPal results.
			$price = $payment['purchase_units'][0]['items'][0]['unit_amount']['value'];
		}

		$amount = $payment['purchase_units'][0]['amount']['value'];

		// check if amount paid matches price x quantity.
		$original_price_amt = ( $price + WPEC_Utility_Functions::get_tax_amount( $price, $tax ) ) * $quantity + $shipping;
		if ( $amount != $original_price_amt ) {
			// payment amount mismatch.
			WPEC_Debug_Logger::log('Error! Payment amount mismatch. Original: ' . $original_price_amt . ', Received: ' . $amount, false);
			_e( 'Payment amount mismatch with the original price.', 'wp-express-checkout' );
			exit;
		}

		// check if payment currency matches.
		if ( $payment['purchase_units'][0]['amount']['currency_code'] !== $currency ) {
			// payment currency mismatch.
                        WPEC_Debug_Logger::log('Error! Payment currency mismatch.', false);
			_e( 'Payment currency mismatch.', 'wp-express-checkout' );
			exit;
		}

		// If code execution got this far, it means everything is ok with payment
		// let's insert order.
		$order = OrdersWPEC::get_instance();

		$order_id = $order->insert(
			array(
				'item_id'     => $item_id,
				'item_name'   => $item_name,
				'price'       => $price,
				'quantity'    => $quantity,
				'tax'         => $tax,
				'shipping'    => $shipping,
				'amount'      => $amount,
				'currency'    => $currency,
				'state'       => $payment['status'],
				'id'          => $payment['id'],
				'create_time' => $payment['create_time'],
			),
			$payment['payer']
		);

		$url = WPEC_View_Download::get_download_url( $order_id );

		$wpec_plugin = WPEC_Main::get_instance();

		$product_details = $item_name . ' x ' . $quantity . ' - ' . WPEC_Utility_Functions::price_format( $amount, $currency ) . "\n";
		if ( ! empty( $url ) ) {
			// Include the download link in the product details.
			/* Translators:  %s - download link */
			$product_details .= sprintf( __( 'Download Link: %s', 'wp-express-checkout' ), $url ) . "\n";
		}

		$address = '';
		if ( ! empty( $payment['purchase_units'][0]['shipping']['address'] ) ) {
			$address = implode( ', ', (array) $payment['purchase_units'][0]['shipping']['address'] );
		}

		$args = array(
			'first_name'      => $payment['payer']['name']['given_name'],
			'last_name'       => $payment['payer']['name']['surname'],
			'product_details' => $product_details,
			'payer_email'     => $payment['payer']['email_address'],
			'transaction_id'  => $payment['id'],
			'purchase_amt'    => $amount,
			'purchase_date'   => date( 'Y-m-d' ),
			'coupon_code'     => '', // Seems like not implemented yet.
			'address'         => $address,
			'order_id'        => $order_id,
		);

		// Send email to buyer if enabled.
		if ( $wpec_plugin->get_setting( 'send_buyer_email' ) ) {

			$buyer_email = $payment['payer']['email_address'];
                        WPEC_Debug_Logger::log('Sending buyer notification email.');

			$from_email  = $wpec_plugin->get_setting( 'buyer_from_email' );
			$subject     = $wpec_plugin->get_setting( 'buyer_email_subj' );
			$subject     = $this->apply_dynamic_tags( $subject, $args );
			$body        = $wpec_plugin->get_setting( 'buyer_email_body' );

			$args['email_body'] = $body;

			$body = $this->apply_dynamic_tags( $body, $args );
			$body = apply_filters( 'wpec_buyer_notification_email_body', $body, $payment, $args );

			$headers = array();
			if ( 'html' === $wpec_plugin->get_setting( 'buyer_email_type' ) ) {
				$headers[] = 'Content-Type: text/html; charset=UTF-8';
				$body      = nl2br( $body );
			} else {
				$body = html_entity_decode( $body );
			}

			$headers[] = 'From: ' . $from_email . "\r\n";

			wp_mail( $buyer_email, wp_specialchars_decode( $subject, ENT_QUOTES ), $body, $headers );

			WPEC_Debug_Logger::log( 'Buyer email notification sent to: ' . $buyer_email . '. From email address value used: ' . $from_email );

			update_post_meta( $order_id, 'wpsc_buyer_email_sent', 'Email sent to: ' . $buyer_email );
		}

		// Send email to seller if needs.
		if ( $wpec_plugin->get_setting( 'send_seller_email' ) && ! empty( $wpec_plugin->get_setting( 'notify_email_address' ) ) ) {
                        WPEC_Debug_Logger::log('Sending seller notification email.');

			$notify_email = $wpec_plugin->get_setting( 'notify_email_address' );

			$seller_email_subject = $wpec_plugin->get_setting( 'seller_email_subj' );
			$seller_email_subject = $this->apply_dynamic_tags( $seller_email_subject, $args );

			$seller_email_body = $wpec_plugin->get_setting( 'seller_email_body' );
			$seller_email_body = $this->apply_dynamic_tags( $seller_email_body, $args );
			$seller_email_body = apply_filters( 'wpec_seller_notification_email_body', $seller_email_body, $payment, $args );

			wp_mail( $notify_email, wp_specialchars_decode( $seller_email_subject, ENT_QUOTES ), html_entity_decode( $seller_email_body ), $headers );
                        WPEC_Debug_Logger::log('Seller email notification sent to: ' . $notify_email);
		}

		// Trigger the action hook.
		do_action( 'wpec_payment_completed', $payment );
                WPEC_Debug_Logger::log('Payment processing completed');

		$res = array();

		if ( wp_http_validate_url( $wpec_plugin->get_setting( 'thank_you_url' ) ) ) {
			$redirect_url = add_query_arg(
				array(
					'order_id' => $order_id,
					'_wpnonce' => wp_create_nonce( 'thank_you_url' . $order_id ),
				),
				$wpec_plugin->get_setting( 'thank_you_url' )
			);
			$res['redirect_url'] = esc_url_raw( $redirect_url );
		} else {
			_e( 'Error! Thank you page URL configuration is wrong in the plugin settings.', 'wp-express-checkout' );
			exit;
		}

		echo wp_json_encode( $res );

		exit;
	}

	/**
	 * Replaces tags in the text with appropriate values.
	 *
	 * @param string $text The text with tags to be replaced.
	 * @param array  $args The array of the tags values.
	 *
	 * @return string
	 */
	private function apply_dynamic_tags( $text, $args ) {

		$white_list = array(
			'first_name',
			'last_name',
			'product_details',
			'payer_email',
			'transaction_id',
			'purchase_amt',
			'purchase_date',
			'coupon_code',
			'address',
			'order_id',
		);

		$tags = array();
		$vals = array();

		foreach ( $white_list as $item ) {
			$tags[] = "{{$item}}";
			$vals[] = ( isset( $args[ $item ] ) ) ? $args[ $item ] : '';
		}

		$body = stripslashes( str_replace( $tags, $vals, $text ) );

		return $body;
	}

}
