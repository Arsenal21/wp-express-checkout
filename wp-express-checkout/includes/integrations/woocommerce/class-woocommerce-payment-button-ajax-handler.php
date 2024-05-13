<?php

namespace WP_Express_Checkout\Integrations;

use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\PayPal\Request;
use WP_Express_Checkout\PayPal\Client;
use WP_Express_Checkout\Main;
use WP_Express_Checkout\Utils;


class WooCommerce_Payment_Button_Ajax_Handler {

	public function __construct()
	{
		//Handle the create-order ajax request for woocommerce checkout.
		add_action( 'wp_ajax_wpec_woocommerce_pp_create_order', array(&$this, 'pp_create_order' ) );
		add_action( 'wp_ajax_nopriv_wpec_woocommerce_pp_create_order', array(&$this, 'pp_create_order' ) );
		
		//Handle the capture-order ajax request for woocommerce checkout.
		add_action( 'wp_ajax_wpec_woocommerce_pp_capture_order', array(&$this, 'pp_capture_order' ) );
		add_action( 'wp_ajax_nopriv_wpec_woocommerce_pp_capture_order', array(&$this, 'pp_capture_order' ) );
	}

	/**
	 * Handle the pp_create_order ajax request.
	 */
	public function pp_create_order(){

		//Get the order data from the request. 
		//The data will be in JSON format string (not actual JSON object). We can json_decode it to get it in json object or array format.
		$json_order_data = isset( $_POST['data'] ) ? stripslashes_deep( $_POST['data'] ) : '{}';
		//Lets have the data in array format (easier to work with in PHP).
		$order_data_array = json_decode( $json_order_data, true );
		$encoded_item_name = isset($order_data_array['purchase_units'][0]['items'][0]['name']) ? $order_data_array['purchase_units'][0]['items'][0]['name'] : '';
		$decoded_item_name = html_entity_decode($encoded_item_name);
		Logger::log( 'PayPal create-order request received for item name: ' . $decoded_item_name, true );

		//Set this decoded item name back to the order data.
		$order_data_array['purchase_units'][0]['items'][0]['name'] = $decoded_item_name;
		//Logger::log_array_data($order_data_array, true);

		//If the data is empty, send the error response.
		if ( empty( $json_order_data ) ) {
			self::send_response(__( 'Empty data received.', 'wp-express-checkout' ), false);
		}

		//Get the WPEC plugin specific data from the request
		$json_wpec_data = isset( $_POST['wpec_data'] ) ? stripslashes_deep( $_POST['wpec_data'] ) : '{}';
		//We need the data in an array format so lets convert it.
		$array_wpec_data = json_decode( $json_wpec_data, true );		
		//Logger::log_array_data($array_wpec_data, true);

		if ( empty( $array_wpec_data ) ) {
			self::send_response(__( 'Error! Empty WPEC plugin data received from the create-order AJAX call.', 'wp-express-checkout' ), false);
		}		

		// Verify nonce.
		if ( ! check_ajax_referer( 'wpec-woocommerce-payment-ajax-nonce', '_wpnonce', false ) ) {
			self::send_response(__( 'Nonce check failed. The page was most likely cached. Please reload the page and try again.', 'wp-express-checkout' ), false);
		}

		// Create the order using the PayPal API call (pass the order data so we can use it in the API call)
		$result = self::create_order_pp_api_call($order_data_array, $array_wpec_data);

		if(is_wp_error($result) ){
			//Failed to create the order.
			self::send_response(__( 'Failed to create the order using PayPal API. Enable the debug logging feature to get more details.', 'wp-express-checkout' ), false);
		}

		//The PayPal order ID returned by the API call.
		$paypal_order_id = isset($result) ? $result : '';
		Logger::log( 'PayPal Order ID: ' . $paypal_order_id, true );

		// PayPal Create order complete. Now create a woocommerce order as well.
		try {
			$wc_order_id = $this->create_woocommerce_order();

			Logger::log( 'WooCommerce Order ID: ' . $wc_order_id, true );

			// WooCommerce Order created. Now save the order id in transient.
			$this->handle_order_transients($paypal_order_id, $wc_order_id);
		} catch (\Exception $exception){
			Logger::log( $exception->getMessage(), false );
			self::send_response(__( 'Error occurred on create_woocommerce_order(). ', 'wp-express-checkout' ) . $exception->getMessage(), false);
		}

		//If everything is processed successfully, send the success response.
		self::send_response(
			__('Create-order API call to PayPal completed successfully.', 'wp-express-checkout'),
			true,
			array(
				'order_id' => $paypal_order_id,
			)
		);
		exit;
    }


	/**
	 * Create the order using the PayPal API call.
	 * return the PayPal order ID if successful, or a WP_Error object if there is an error.
	 */
	public static function create_order_pp_api_call($order_data_array, $array_wpec_data)
	{
		//https://developer.paypal.com/docs/api/orders/v2/#orders_create

		//Create the order-data for the PayPal API call.
		$pp_api_order_data = self::create_order_data_for_pp_api($order_data_array, $array_wpec_data);

		$json_encoded_pp_api_order_data = wp_json_encode($pp_api_order_data);
		// Logger::log_array_data($json_encoded_pp_api_order_data, true); // Debug purpose.

		//Create the request for the PayPal API call.
		$request = new Request( '/v2/checkout/orders', 'POST' );
		//The order data already in JSON format so we don't need to json_encode it again.
		$request->body = $json_encoded_pp_api_order_data;

		//Execute the request.
		$client = Client::client();

		try{
			$response = $client->execute($request);
		}
		catch( \Exception $e ) {
			//If there is an error, log the error and return the error message.
			$exception_msg = json_decode($e->getMessage());
			if(is_array($exception_msg->details) && sizeof($exception_msg->details)>0){
				$error_string = $exception_msg->details[0]->issue.". ".$exception_msg->details[0]->description;
				Logger::log( 'Error creating PayPal order via API call: ' . $error_string, true );
				return new \WP_Error(2002,$error_string);
			}
			Logger::log( 'Error creating PayPal order: ' . $e->getMessage(), true );
			return new \WP_Error(2002,__( 'Something went wrong, the PayPal create-order API call failed!', 'wp-express-checkout' ));
		}

		//The paypal debug ID if available (useful for debugging purposes)
		//$paypal_debug_Id = isset($response->headers['Paypal-Debug-Id']) ? $response->headers['Paypal-Debug-Id'] : '';
		//Logger::log( 'PayPal Debug ID: ' . $paypal_debug_Id, true );

		$paypal_order_id = isset($response->result->id) ? $response->result->id : '';
		// Logger::log( 'PayPal Order ID: ' . $paypal_order_id, true );

		return $paypal_order_id;
	}


	public static function create_order_data_for_pp_api($order_data_array, $array_wpec_data){
		Logger::log( 'Creating PayPal order data for Woocommerce Checkout', true );

		//We need to use the item name from the database to retain the original name (without any special characters creating issues with data coming from ajax request).
		// $item_name = $item_for_pp_order->get_item_name();
		// $item_name = substr($item_name, 0, 127);//Limit the item name to 127 characters (PayPal limit)

		// //$shipping_preference = $item_for_pp_order->is_digital_product() ? 'NO_SHIPPING' : 'SET_PROVIDED_ADDRESS';
		// $shipping_preference = $order_data_array['payment_source']['paypal']['experience_context']['shipping_preference'];

		// //Get the item quantity and amount from the order data.
		// $quantity = $order_data_array['purchase_units'][0]['items'][0]['quantity'];
		// $item_amount = $order_data_array['purchase_units'][0]['items'][0]['unit_amount']['value'];

		// $currency_code = $order_data_array['purchase_units'][0]['amount']['currency_code'];
		// $grand_total = $order_data_array['purchase_units'][0]['amount']['value'];
		// $sub_total = $order_data_array['purchase_units'][0]['amount']['breakdown']['item_total']['value']; //This should be equal to (item_amount * quantity)
		// //Transaction specific amounts for purchase_units breakdowns (may or may not be available in the order data array depending on the product configuration).
		// $shipping_amt = isset($order_data_array['purchase_units'][0]['amount']['breakdown']['shipping']['value'])? $order_data_array['purchase_units'][0]['amount']['breakdown']['shipping']['value'] : 0;
		// $tax_amt = isset($order_data_array['purchase_units'][0]['amount']['breakdown']['tax_total']['value']) ? $order_data_array['purchase_units'][0]['amount']['breakdown']['tax_total']['value'] : 0;
		// $discount_amt = isset($order_data_array['purchase_units'][0]['amount']['breakdown']['discount']['value']) ? $order_data_array['purchase_units'][0]['amount']['breakdown']['discount']['value'] : 0;

		//https://developer.paypal.com/docs/api/orders/v2/#orders_create
		// $pp_api_order_data = [
		// 	"intent" => "CAPTURE",
		// 	"payment_source" => [
		// 		"paypal" => [
		// 			"experience_context" => [
		// 				"payment_method_preference" => "IMMEDIATE_PAYMENT_REQUIRED",
		// 				"shipping_preference" => $shipping_preference,
		// 				"user_action" => "PAY_NOW",
		// 			]
		// 		]
		// 	],
		// 	"purchase_units" => [
		// 		[
		// 			"amount" => [
		// 				"value" => (string) $grand_total, /* The grand total that will be charged for the transaction. Cast to string to make sure there is no precision issue */
		// 				"currency_code" => $currency_code,
		// 				"breakdown" => [
		// 					"item_total" => [
		// 						"currency_code" => $currency_code,
		// 						"value" => (string) $sub_total, /* We can break down the total amount into item_total, tax_total, shipping etc */
		// 					]
		// 				]
		// 			],
		// 			"items" => [
		// 				[
		// 					"name" => $item_name,
		// 					"quantity" => $quantity,
		// 					"unit_amount" => [
		// 						"value" => (string) $item_amount, /* Cast to string to make sure there is no precision issue */
		// 						"currency_code" => $currency_code,
		// 					]
		// 				]
		// 			],
		// 		]
		// 	]
		// ];

		// //Add the shipping amount if available.
		// if( isset($shipping_amt) && $shipping_amt > 0){
		// 	$pp_api_order_data['purchase_units'][0]['amount']['breakdown']['shipping'] = [
		// 		"currency_code" => $currency_code,
		// 		"value" => (string) $shipping_amt, /* Cast to string to make sure there is no precision issue */
		// 	];
		// }

		// //Add the tax amount if available.
		// if( isset($tax_amt) && $tax_amt > 0){
		// 	$pp_api_order_data['purchase_units'][0]['amount']['breakdown']['tax_total'] = [
		// 		"currency_code" => $currency_code,
		// 		"value" => (string) $tax_amt, /* Cast to string to make sure there is no precision issue */
		// 	];
		// }

		// //Add the discount amount if available.
		// if( isset($discount_amt) && $discount_amt > 0){
		// 	$pp_api_order_data['purchase_units'][0]['amount']['breakdown']['discount'] = [
		// 		"currency_code" => $currency_code,
		// 		"value" => (string) $discount_amt, /* Cast to string to make sure there is no precision issue */
		// 	];
		// }


		//A simple order data for testing
		$currency_code = isset($order_data_array['purchase_units'][0]['amount']['currency_code']) ? $order_data_array['purchase_units'][0]['amount']['currency_code'] : 'USD';
		$woo_order_grand_total = $order_data_array['purchase_units'][0]['amount']['breakdown']['item_total']['value'];
		$pp_api_order_data = [
			"intent" => "CAPTURE",
			"purchase_units" => [
				[
					"amount" => [
						"currency_code" => $currency_code,
						"value" => (string)$woo_order_grand_total,
					],
				],
			],
		];

		//Debug purposes.
		Logger::log_array_data($pp_api_order_data, true);

		return $pp_api_order_data;
	}


	/**
	 * Creates a WooCommerce order using cart data.
	 *
	 * @return int|WP_ERROR
	 */
	public function create_woocommerce_order() {
		logger::log('Retrieving cart contents to create woocommerce order...', true);

		global $woocommerce;

		// Get cart contents to create order with.
		$cart_contents = $woocommerce->cart->get_cart();

		// logger::log_array_data($cart_contents); // Debug purpose.

		$wc_order_id = $woocommerce->checkout->create_order($cart_contents);

		return $wc_order_id;
	}


	/**
	 * Handles the order capture for standard 'Buy Now' type buttons.
	 */
	public function pp_capture_order(){
		//Get the data from the request
		$wpec_pp_data = isset( $_POST['data'] ) ? stripslashes_deep( $_POST['data'] ) : '{}';
		//We need the data in an array format so lets convert it.
		$wpec_pp_data_array = json_decode( $wpec_pp_data, true );
		//Logger::log_array_data($wpec_pp_data_array, true);

		//Get the PayPal order_id from the data
		$order_id = isset( $wpec_pp_data_array['order_id'] ) ? sanitize_text_field($wpec_pp_data_array['order_id']) : '';
		Logger::log( 'PayPal capture order request received - PayPal order ID: ' . $order_id, true );

		if ( empty( $order_id ) ) {
			self::send_response(__( 'Error! Empty order ID received for PayPal capture order request.', 'wp-express-checkout' ), false);
		}

		//Get the WPEC plugin specific data from the request
		$json_wpec_data = isset( $_POST['wpec_data'] ) ? stripslashes_deep( $_POST['wpec_data'] ) : '{}';
		//We need the data in an array format so lets convert it.
		$array_wpec_data = json_decode( $json_wpec_data, true );		
		//Logger::log_array_data($array_wpec_data, true); // Debugging purpose.

		if ( empty( $array_wpec_data ) ) {
			self::send_response(__( 'Error! Empty WPEC plugin data received.', 'wp-express-checkout' ), false);
		}

		// Check nonce.
		if ( ! check_ajax_referer( 'wpec-woocommerce-payment-ajax-nonce', '_wpnonce', false ) ) {
			self::send_response(__( 'Nonce check failed. The page was most likely cached. Please reload the page and try again.', 'wp-express-checkout' ), false);
		}

		// Capture the order using the PayPal API. (https://developer.paypal.com/docs/api/orders/v2/#orders_capture)
		$pp_capture_response_data = $this->capture_order_pp_api_call( $order_id, $array_wpec_data );
		if ( is_wp_error( $pp_capture_response_data ) ) {
			//Failed to capture the order.
			self::send_response(__('Error! PayPal capture-order API call failed.', 'wp-express-checkout'), false);
		}

		Logger::log( 'PayPal capture order API call success! ' . $order_id, true );

		// logger::log('PayPal capture order data for Woocommerce Checkout: '); // Debugging purpose.
		// logger::log_array_data($pp_capture_response_data); // Debugging purpose.

		$wpec_txn_data_array = $pp_capture_response_data;

		//Logger::log_array_data($wpec_txn_data_array, true); // Debugging purpose.
		//Logger::log_array_data($array_wpec_data, true); // Debugging purpose.
		
		// The transaction by wpec has been finalized. Now process the order with this transaction data for woocommerce.

		$this->wpec_woocommerce_process_payment($wpec_txn_data_array);

		// Everything is processed successfully, the previous function call will also send the response back to the client.
	}


	/**
	 * Capture the order using the PayPal API call.
	 * https://developer.paypal.com/docs/api/orders/v2/#orders_capture
	 *
	 * Return the transaction data if successful, or a WP_Error object if there is an error.
	 */
    public static function capture_order_pp_api_call( $order_id, $array_wpec_data )
    {
		Logger::log( 'Capturing the PayPal order using the PayPal API call...', true);

		$api_params = array( 'order_id' => $order_id );
		$json_api_params = json_encode($api_params);
		
		//Create the request for the PayPal API call.
		$endpoint = '/v2/checkout/orders/' . $order_id . '/capture';
		$request = new Request( $endpoint, 'POST' );
		//The order data alreaady in JSON format so we don't need to json_encode it again.
		$request->body = $json_api_params;

		//Execute the request.
		$client = Client::client();

		try{
        	$response = $client->execute($request);
		}
		catch( \Exception $e ) {
			//If there is an error, log the error and return the error message.
			$exception_msg = json_decode($e->getMessage());
			if(is_array($exception_msg->details) && sizeof($exception_msg->details)>0){
				$error_string = $exception_msg->details[0]->issue.". ".$exception_msg->details[0]->description;							
				Logger::log( 'Error capturing the PayPal order via API call: ' . $error_string, true );
				return new \WP_Error(2002,$error_string);
			}
			Logger::log( 'Error creating PayPal order: ' . $e->getMessage(), true );
			return new \WP_Error(2002,__( 'Something went wrong, the PayPal capture-order API call failed!', 'wp-express-checkout' ));
		}

		//The paypal debug ID if available (useful for debugging purposes)
		//$paypal_debug_Id = isset($response->headers['Paypal-Debug-Id']) ? $response->headers['Paypal-Debug-Id'] : '';
		//Logger::log( 'PayPal Debug ID: ' . $paypal_debug_Id, true );
		
		//Get the transaction data from the response.
		$txn_data = $response->result;

		//We need to convert the object to an array so we can use it in our process payment function easily. 
		//json_encode then json_decode will do this.
		$txn_data_array = json_decode(json_encode($txn_data), true);
		//Logger::log_array_data($txn_data_array, true); // Debugging purpose.

		$paypal_capture_id = isset($txn_data_array['purchase_units'][0]['payments']['captures'][0]['id']) ? $txn_data_array['purchase_units'][0]['payments']['captures'][0]['id'] : '';
		Logger::log( 'PayPal Capture ID: ' . $paypal_capture_id, true );

        return $txn_data_array;
    }


	/**
	 * Handles necessary transient data for order processing.
	 * First it check if any transient related to order processing exists or not.
	 * Old transient may exist if payment is canceled before completing.
	 * If so, remove that transient as well as the wc order before creating a new one.
	 *
	 * TODO: This method could be improved.
	 *
	 * @param $paypal_order_id string Order ID generated by PayPal.
	 * @param $wc_order_id string Order ID generated by WooCommerce.
	 *
	 * @return void
	 */
	public function handle_order_transients($paypal_order_id, $wc_order_id) {
		// Create transient using the item name.
		$trans_name = 'wpec-pp-create-wc-order';

		$old_transient = get_transient($trans_name);
		if (!empty($old_transient)){// TODO: Need to delete old wc order.
			Logger::log('Old transient exists!', true);
			Logger::log_array_data( $old_transient, true ); // Debug purpose.
			if (!empty($old_transient['wc_order_id'])){
				wp_delete_post(intval($old_transient['wc_order_id']), true);
			}
			delete_transient($trans_name);
		}

		$trans_data = array(
			'wc_order_id'     => $wc_order_id,
			'paypal_order_id' => $paypal_order_id,
		);

		// Logger::log('Transient name: ' . $trans_name, true); // Debug purpose.
		// Logger::log_array_data( $trans_data, true ); // Debug purpose.

		set_transient( $trans_name, $trans_data, 2 * 3600 );
	}

	/**
	 * Processes payment information for WooCommerce.
	 *
	 * @param $txn_data
	 * @param $order_data
	 *
	 * @return void
	 */
	public function wpec_woocommerce_process_payment($wpec_txn_data) {
		// Logger::log('On wpec_woocommerce_process_payment: $txn_data', true);
		// Logger::log_array_data( $txn_data, true ); // Debug purpose.
		// Logger::log('On wpec_woocommerce_process_payment: $order_data', true);
		// Logger::log_array_data( $order_data, true ); // Debug purpose.

		if ( empty( $wpec_txn_data ) ) {
			// no payment data provided.
			$msg = __( 'No payment data received.', 'wp-express-checkout' );
			Logger::log( $msg, false );
			self::send_response($msg, false);
		}

		$status =  $wpec_txn_data['status'];
		if ( strtoupper( $status ) !== 'COMPLETED' ) {
			// payment is not successful.
			$msg =  sprintf( __( 'Payment status is not completed. Status: %s', 'wp-express-checkout' ), $status );
			Logger::log( $msg, false );
			self::send_response($msg, false);
		}

		// Log debug (if enabled).
		Logger::log( 'Payment Captured. Doing post payment processing tasks ...' );

		// Retrieve transient data.
		$trans_name  = 'wpec-pp-create-wc-order';
		$trans = get_transient($trans_name);

		// Let's check if the payment matches transient data.
		if ( ! $trans ) {
			// no price set.
			$msg =  __( 'No transaction info found in transient.', 'wp-express-checkout' );
			Logger::log( $msg, false );
			self::send_response($msg, false);
		}

		// Retrieve woocommerce order object form order id.
		$order = new \WC_Order( $trans['wc_order_id'] );

		// Get received amount.
		$received_amount = Utils::round_price( floatval( $wpec_txn_data['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ) );
		$wc_expected_amt = $order->get_total();
		Logger::log( 'We will check the received amount: '. $received_amount, true );
		// check if amount paid is less than original price x quantity. This has better fault tolerant than checking for equal (=).
		if ( $received_amount < $wc_expected_amt ) {
			// payment amount mismatch. Amount paid is less.
			Logger::log( 'Error! Woocommerce checkout amount mismatch. Expected: ' . $wc_expected_amt . ', Received: ' . $received_amount, false );
			self::send_response(__( 'Payment amount mismatch with the expected amount.', 'wp-express-checkout' ), false );
		}

		// Check if payment currency matches.
		$received_currency = $wpec_txn_data['purchase_units'][0]['payments']['captures'][0]['amount']['currency_code'];
		$expected_currency = $order->get_currency();
		Logger::log( 'We will check the received currency: '. $received_currency, true );
		if ( $received_currency !== $expected_currency ) {
			// payment currency mismatch.
			Logger::log( 'Error! Payment currency mismatch. Exepcted: ' . $expected_currency . ', Received: ' . $received_currency, false );
			self::send_response(__( 'Payment currency mismatch.', 'wp-express-checkout' ), false);
		}

		// If code execution got this far, it means everything is ok with payment

		// Now prepare to hand over order data to WooCommerce.
		$paypal_capture_id = isset($txn_data['purchase_units'][0]['payments']['captures'][0]['id']) ? $txn_data['purchase_units'][0]['payments']['captures'][0]['id'] : '';
		Logger::log( 'PayPal transaction id is: '. $paypal_capture_id, true );

		// Handle updating order status after payment is complete.
		add_action('woocommerce_payment_complete', array($this, 'wpec_wc_complete_order'));

		// Hand over to woocommerce for processing the payment and complete the checkout process after wards.
		$order->payment_complete( $paypal_capture_id );

		// Clear woocommerce cart.
		WC()->cart->empty_cart();

		$res = array(
			'redirect_url' => $order->get_checkout_order_received_url()
		);

		Logger::log( "Process order completed successfully", true );

		delete_transient($trans_name);

		self::send_response(__('Process order completed successfully', 'wp-express-checkout'), true, $res);
	}

	/**
	 * Callback for 'woocommerce_payment_complete' action hook. Triggers when $order->payment_complete() method is called.
	 *
	 * @param $order_id string The ID of WC_Order.
	 *
	 * @return void
	 */
	public function wpec_wc_complete_order($order_id) {
		$order = wc_get_order($order_id);

		// Check if the order is not already completed
		if ($order && $order->get_status() !== 'completed') {
			// Mark the order as completed
			$order->update_status('completed');

			Logger::log( "Updating order status from '" . $order->get_status() . "' to 'completed'.", true );
			// Optionally, send order completion email to the customer
			// WC()->mailer()->emails['WC_Email_Customer_Completed_Order']->trigger($order_id); // TODO: Need to use or remove.
			return;
		}

		Logger::log( "Order status could not be updated!", false );
	}


	/**
	 * Send JSON response back to ajax api call.
	 *
	 * @param $message string The message to send.
	 * @param $success bool Is it a success response or not.
	 * @param $data null|array Additional data to send back.
	 * @param $status_code int HTTP status code.
	 *
	 * @return void
	 */
	public static function send_response($message, $success = true, $data = null, $status_code = 200) {
		$payload = 	array(
			'success' => $success,
			'message' => $message,
		);

		if (!empty($data)){
			$payload['data'] = $data;
		}

		wp_send_json($payload, $status_code);
	}}