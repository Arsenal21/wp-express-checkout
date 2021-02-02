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

		if ( ! isset( $_POST['wp_ppdg_payment'] ) ) {
			// no payment data provided.
			_e( 'No payment data received.', 'wp-express-checkout' );
			exit;
		}

		$payment = stripslashes_deep( $_POST['wp_ppdg_payment'] );
		$data    = stripslashes_deep( $_POST['data'] );

		check_ajax_referer( $data['id'] . $data['product_id'], 'nonce' );

		$this->check_status( $payment );

		// Log debug (if enabled).
		WPEC_Debug_Logger::log( 'Received IPN. Processing payment ...' );

		// get item name.
		$item_name  = $this->get_item_name( $payment );
		$trans_name = $this->get_transition_name( $payment );
		$trans      = get_transient( $trans_name );
		// let's check if the payment matches transient data.
		if ( ! $trans ) {
			// no price set.
			WPEC_Debug_Logger::log( 'Error! No transaction info found in transient.', false );

			_e( 'No transaction info found in transient.', 'wp-express-checkout' );
			exit;
		}
		$price    = $this->get_price( $payment, $trans, $data );
		$quantity = $trans['quantity'];
		$tax      = $trans['tax'];
		$shipping = $trans['shipping'];
		$currency = $trans['currency'];
		$item_id  = $trans['product_id'];

		$wpec_plugin = WPEC_Main::get_instance();

		if ( $trans['custom_quantity'] ) {
			// custom quantity enabled. let's take quantity from PayPal results.
			$quantity = $this->get_quantity( $payment );
		}

		$order = OrdersWPEC::create();

		/* translators: Order title: {Quantity} {Item name} - {Status} */
		$order->set_description( sprintf( __( '%1$d %2$s - %3$s', 'wp-express-checkout' ), $quantity, $item_name, $this->get_transaction_status( $payment ) ) );
		$order->set_currency( $currency );
		$order->add_item( PPECProducts::$products_slug, $item_name, $price, $quantity, $item_id, true );
		$order->add_data( 'transaction_id', $this->get_transaction_id( $payment ) );
		$order->add_data( 'state', $this->get_transaction_status( $payment ) );
		$order->add_data( 'payer', $payment['payer'] );

		/**
		 * Runs after draft order created, but before adding items.
		 *
		 * @param WPEC_Order $order   The order object.
		 * @param array      $payment The raw order data retrieved via API.
		 * @param array      $data    The purchase data generated on a client side.
		 */
		do_action( 'wpec_create_order', $order, $payment, $data );

		if ( $tax ) {
			$item_tax_amount = $this->get_item_tax_amount( $order->get_total(), $quantity, $tax );
			$order->add_item( 'tax', __( 'Tax', 'wp-express-checkout' ), $item_tax_amount * $quantity );
		}
		if ( $shipping ) {
			$order->add_item( 'shipping', __( 'Shipping', 'wp-express-checkout' ), $shipping );
		}

		$amount = WPEC_Utility_Functions::round_price( floatval( $this->get_total( $payment ) ) );
		// check if amount paid is less than original price x quantity. This has better fault tolerant than checking for equal (=).
		if ( $amount < $order->get_total() ) {
			// payment amount mismatch. Amount paid is less.
			WPEC_Debug_Logger::log( 'Error! Payment amount mismatch. Original: ' . $order->get_total() . ', Received: ' . $amount, false );
			_e( 'Payment amount mismatch with the original price.', 'wp-express-checkout' );
			exit;
		}

		// check if payment currency matches.
		if ( $this->get_currency( $payment ) !== $currency ) {
			// payment currency mismatch.
			WPEC_Debug_Logger::log( 'Error! Payment currency mismatch.', false );
			_e( 'Payment currency mismatch.', 'wp-express-checkout' );
			exit;
		}

		// If code execution got this far, it means everything is ok with payment
		// let's insert order.
		$order->set_status( 'paid' );

		$order_id  = $order->get_id();
		$downloads = WPEC_View_Download::get_order_downloads_list( $order_id );

		$product_details = WPEC_Utility_Functions::get_product_details( $order );
		if ( ! empty( $downloads ) ) {
			$product_details .= "\n\n";
			// Include the download links in the product details.
			foreach ( $downloads as $name => $download_url ) {
				/* Translators:  %1$s - download item name; %2$s - download URL */
				$product_details .= sprintf( __( '%1$s - download link: %2$s', 'wp-express-checkout' ), $name, $download_url ) . "\n";
			}
		}

		$address = $this->get_address( $payment );

		$coupon_item = $order->get_item( 'coupon' );
		$coupon_code = $coupon_item ? $coupon_item['meta']['code'] :'';

		$args = array(
			'first_name'      => $payment['payer']['name']['given_name'],
			'last_name'       => $payment['payer']['name']['surname'],
			'product_details' => $product_details,
			'payer_email'     => $payment['payer']['email_address'],
			'transaction_id'  => $this->get_transaction_id( $payment ),
			'purchase_amt'    => $amount,
			'purchase_date'   => date( 'Y-m-d' ),
			'coupon_code'     => $coupon_code,
			'address'         => $address,
			'order_id'        => $order_id,
		);

		$headers = array();
		if ( 'html' === $wpec_plugin->get_setting( 'buyer_email_type' ) ) {
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
		}

		// Send email to buyer if enabled.
		if ( $wpec_plugin->get_setting( 'send_buyer_email' ) ) {

			$buyer_email = $payment['payer']['email_address'];
			WPEC_Debug_Logger::log( 'Sending buyer notification email.' );

			$from_email = $wpec_plugin->get_setting( 'buyer_from_email' );
			$subject    = $wpec_plugin->get_setting( 'buyer_email_subj' );
			$subject    = WPEC_Utility_Functions::apply_dynamic_tags( $subject, $args );
			$body       = $wpec_plugin->get_setting( 'buyer_email_body' );

			$args['email_body'] = $body;

			$body = WPEC_Utility_Functions::apply_dynamic_tags( $body, $args );
			$body = apply_filters( 'wpec_buyer_notification_email_body', $body, $payment, $args );

			if ( 'html' === $wpec_plugin->get_setting( 'buyer_email_type' ) ) {
				$body = nl2br( $body );
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
			WPEC_Debug_Logger::log( 'Sending seller notification email.' );

			$notify_email = $wpec_plugin->get_setting( 'notify_email_address' );

			$seller_email_subject = $wpec_plugin->get_setting( 'seller_email_subj' );
			$seller_email_subject = WPEC_Utility_Functions::apply_dynamic_tags( $seller_email_subject, $args );

			$seller_email_body = $wpec_plugin->get_setting( 'seller_email_body' );
			$seller_email_body = WPEC_Utility_Functions::apply_dynamic_tags( $seller_email_body, $args );
			$seller_email_body = apply_filters( 'wpec_seller_notification_email_body', $seller_email_body, $payment, $args );

			if ( 'html' === $wpec_plugin->get_setting( 'buyer_email_type' ) ) {
				$seller_email_body = nl2br( $seller_email_body );
			} else {
				$seller_email_body = html_entity_decode( $seller_email_body );
			}

			wp_mail( $notify_email, wp_specialchars_decode( $seller_email_subject, ENT_QUOTES ), $seller_email_body, $headers );
			WPEC_Debug_Logger::log( 'Seller email notification sent to: ' . $notify_email );
		}

		// Trigger the action hook.
		do_action( 'wpec_payment_completed', $payment, $order_id, $item_id );
		WPEC_Debug_Logger::log( 'Payment processing completed' );

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
	 * Filters the custom amount option.
	 *
	 * @param type $true
	 * @return bool
	 */
	public function is_custom_amount( $true ) {
		return !! $true;
	}

	/**
	 * Checks the payment status before processing.
	 *
	 * @param array $payment
	 */
	protected function check_status( $payment ) {
		$status = $this->get_transaction_status( $payment );
		if ( strtoupper( $status ) !== 'COMPLETED' ) {
			// payment is unsuccessful.
			WPEC_Debug_Logger::log( 'Payment is not approved. Payment status: ' . $status, false );
			printf( __( 'Payment is not approved. Status: %s', 'wp-express-checkout' ), $status );
			exit;
		}
	}

	/**
	 * Retrieves the item name from transaction data.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_item_name( $payment ) {
		return $payment['purchase_units'][0]['description'];
	}

	/**
	 * Retrieves transition name.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_transition_name( $payment ) {
		$item_name  = $this->get_item_name( $payment );
		$trans_name = 'wp-ppdg-' . sanitize_title_with_dashes( $item_name );

		return $trans_name;
	}

	/**
	 * Retrieves peoduct queantity from transaction data.
	 *
	 * @param array $payment
	 * @return int
	 */
	protected function get_quantity( $payment ) {
		return $payment['purchase_units'][0]['items'][0]['quantity'];
	}

	/**
	 * Retrieves item price from transaction data.
	 *
	 * @param array $payment
	 * @param array $trans
	 *
	 * @return string
	 */
	protected function get_price( $payment, $trans, $data = array() ) {
		$price = $trans['price'];
		if ( $this->is_custom_amount( $trans['custom_amount'] ) ) {
			// custom amount enabled. let's take amount from JS data.
			$price = $data['orig_price'];
		}
		return $price;
	}

	/**
	 * Retrieves order total from transaction data.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_total( $payment ) {
		return $payment['purchase_units'][0]['amount']['value'];
	}

	/**
	 * Retrieves currency from transaction data.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_currency( $payment ) {
		return $payment['purchase_units'][0]['amount']['currency_code'];
	}

	/**
	 * Retrieves payer address from transaction data.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_address( $payment ) {
		$address = '';
		if ( ! empty( $payment['purchase_units'][0]['shipping']['address'] ) ) {
			$address = implode( ', ', (array) $payment['purchase_units'][0]['shipping']['address'] );
		}
		return $address;
	}

	/**
	 * Retrieves transaction id.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_transaction_id( $payment ) {
		return $payment['id'];
	}

	/**
	 * Retrieves transaction status.
	 *
	 * @param array $payment
	 * @return string
	 */
	protected function get_transaction_status( $payment ) {
		return $payment['status'];
	}

	/**
	 * Retrieves the tax amount depending on the way the PayPal calcualtes it.
	 *
	 * For regular instant payments PayPal calculates it from the one item and
	 * then rounds, for subscriptions it calculates for a quantity and then rounds.
	 *
	 * This difference in approach creates a lot of difficulties in the total
	 * amount validation. This method allows override it.
	 *
	 * @param type $price
	 * @param type $quantity
	 * @param type $tax
	 * @return type
	 */
	protected function get_item_tax_amount( $price, $quantity, $tax ) {
		return WPEC_Utility_Functions::round_price( WPEC_Utility_Functions::get_tax_amount( $price / $quantity, $tax ) );
	}


}
