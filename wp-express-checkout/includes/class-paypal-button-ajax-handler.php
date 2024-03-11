<?php

namespace WP_Express_Checkout;

use WP_Express_Checkout\Debug\Logger;

use WP_Express_Checkout\PayPal\Request;
use WP_Express_Checkout\PayPal\Client;


class PayPal_Payment_Button_Ajax_Handler {

	public function __construct()
	{
		//Handle the create-order ajax request for 'Buy Now' type buttons.
		add_action( 'wp_ajax_wpec_pp_create_order', array(&$this, 'pp_create_order' ) );
		add_action( 'wp_ajax_nopriv_wpec_pp_create_order', array(&$this, 'pp_create_order' ) );
		
		add_action( 'wp_ajax_wpec_pp_capture_order', array(&$this, 'pp_capture_order' ) );
		add_action( 'wp_ajax_nopriv_wpec_pp_capture_order', array(&$this, 'pp_capture_order' ) );
	}


	/**
	 * Handle the pp_create_order ajax request.
	 */
	public function pp_create_order(){

		//Get the data from the request
		$data = isset( $_POST['data'] ) ? stripslashes_deep( $_POST['data'] ) : array();
		if ( empty( $data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Empty data received.', 'wp-express-checkout' ),
				)
			);
		}
		
		if( !is_array( $data ) ){
			//Convert the JSON string to an array (Vanilla JS AJAX data will be in JSON format).
			$data = json_decode( $data, true);		
		}

		Logger::log('Received data: ', true);
		Logger::log_array_data($data, true);

		// Verify nonce.
		if ( ! check_ajax_referer( 'wpec-create-order-js-ajax-nonce', '_wpnonce', false ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Nonce check failed. The page was most likely cached. Please reload the page and try again.', 'wp-express-checkout' ),
				)
			);
			exit;
		}

		// TODO - Fix the validation for the amount data received.
		if( ! $this->validate_total_amount( $data) ){
			//Error condition. The validation function will set the error message which we will use to send back to the client in the next stage of the code.
			Logger::log( "API pre-submission amount validation failed. The amount appears to have been altered.", false );

			$out['err'] = __( 'Error occurred:', 'stripe-payments' ) . ' ' . $item_for_validation->get_last_error();
			wp_send_json( $out );
		}else{
			Logger::log( "API pre-submission amount validation successful.", true );
		}


		// Create the order using the PayPal API call
		$response = self::create_order_pp_api_call($data);
		$paypal_order_id = isset($response->result->id) ? $response->result->id : '';
		Logger::log( 'PayPal Order ID: ' . $paypal_order_id, true );
		// $api_injector = new PayPal_Request_API_Injector();
		// $response = $api_injector->create_paypal_order_by_url_and_args( $data, $additional_args, $pu_items );
            
		if( empty( $paypal_order_id ) ){
			//Failed to create the order.
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Failed to create the order using PayPal API. Enable the debug logging feature to get more details.', 'wp-express-checkout' ),
				)
			);
			exit;
		}

		// If everything is processed successfully, send the success response.
		wp_send_json( 
			array( 
				'success' => true,
				'order_id' => $paypal_order_id, 	// put $paypal_order_id here
				'order_data' => array() // put $order_data here 
			)
		);
		exit;
    }

    public static function create_order_pp_api_call($data)
    {
		//https://developer.paypal.com/docs/api/orders/v2/#orders_create

		//FIXME - we will create the full order_data after we get the basics working.
		$currency = 'USD';
		$grand_total = 16.50;
		$description = 'Test order description';
		//Use the simple order_data. It uses purchase unit without the items array.
		//A simple order_data (useful for simple payments)
		$order_data = [
			"intent" => "CAPTURE",
			"purchase_units" => [
				[
					"amount" => [
						"currency_code" => $currency,
						"value" => $grand_total,
					],
					"description" => $description,
				],
			],
		];
		
		//Create the request for the PayPal API call.
		$request = new Request( '/v2/checkout/orders', 'POST' );
		$request->body = $order_data;

		//Execute the request.
		$client = Client::client();
        $response = $client->execute($request);

		// Logger::log_array_data($response->result, true);
		// Logger::log( 'PayPal Order ID: ' . $response->result->id, true );

		// Logger::log( 'PayPal API var exported full response below: ', true );
		// $debug_export = var_export($response, true);
		// Logger::log_array_data($debug_export, true);

        return $response;
    }

	/**
	 * Handles the order capture for standard 'Buy Now' type buttons.
	 */
	public function pp_capture_order(){

		//Get the data from the request
		$data = isset( $_POST['data'] ) ? stripslashes_deep( $_POST['data'] ) : array();
		if ( empty( $data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Empty data received.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
		}
		
		if( !is_array( $data ) ){
			//Convert the JSON string to an array (Vanilla JS AJAX data will be in JSON format).
			$data = json_decode( $data, true);		
		}

		//Get the order_id from data
		$order_id = isset( $data['order_id'] ) ? sanitize_text_field($data['order_id']) : '';
		if ( empty( $order_id ) ) {
			Logger::log( 'pp_capture_order - empty order ID received.', false );
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Empty order ID received.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
		}

		$cart_id = isset( $data['cart_id'] ) ? sanitize_text_field( $data['cart_id'] ) : '';
		$on_page_button_id = isset( $data['on_page_button_id'] ) ? sanitize_text_field( $data['on_page_button_id'] ) : '';
		Logger::log( 'Received request - pp_capture_order. PayPal Order ID: ' . $order_id . ', Cart ID: '.$cart_id.', On Page Button ID: ' . $on_page_button_id, true );

		// Check nonce.
		if ( ! check_ajax_referer( 'wpec-onapprove-js-ajax-nonce', '_wpnonce', false ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Nonce check failed. The page was most likely cached. Please reload the page and try again.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
			exit;
		}

		//Set the additional args for the API call.
		$additional_args = array();
		$additional_args['return_response_body'] = true;

		// Capture the order using the PayPal API.
		// https://developer.paypal.com/docs/api/orders/v2/#orders_capture
		$api_injector = new PayPal_Request_API_Injector();
		$response = $api_injector->capture_paypal_order( $order_id, $additional_args );

		//We requested the response body to be returned, so we need to JSON decode it.
		if($response !== false){
			$txn_data = json_decode( $response, true );//JSON decode the response body that we received.
		} else {
			//Failed to capture the order.
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Failed to capture the order. Enable the debug logging feature to get more details.', 'wordpress-simple-paypal-shopping-cart' ),
				)
			);
			exit;
		}

		//--
		// Logger::log_array_data($data, true); // Debugging purpose.
		// Logger::log_array_data($txn_data, true); // Debugging purpose.
		//--

		//Create the IPN data array from the transaction data.
		//Need to include the following values in the $data array.
		$data['custom_field'] = get_post_meta( $cart_id, 'wpsc_cart_custom_values', true );//We saved the custom field in the cart CPT.
		
		$ipn_data = PayPal_Utility_IPN_Related::create_ipn_data_array_from_capture_order_txn_data( $data, $txn_data );
		$paypal_capture_id = isset( $ipn_data['txn_id'] ) ? $ipn_data['txn_id'] : '';
		Logger::log( 'PayPal Capture ID (Transaction ID): ' . $paypal_capture_id, true );
		Logger::log_array_data( $ipn_data, true ); //Debugging purpose.
		
		/* Since this capture is done from server side, the validation is not required but we are doing it anyway. */
		//Validate the buy now txn data before using it.
		$validation_response = PayPal_Utility_IPN_Related::validate_buy_now_checkout_txn_data( $data, $txn_data );
		if( $validation_response !== true ){
			//Debug logging will reveal more details.
			wp_send_json(
				array(
					'success' => false,
					'error_detail'  => $validation_response,/* it contains the error message */
				)
			);
			exit;
		}
		
		//Process the IPN data array
		Logger::log( 'Validation passed. Going to create/update record and save transaction data.', true );
		
		/**
		 * TODO: This is a plugin specific method.
		 */
		PayPal_Utility_IPN_Related::complete_post_payment_processing( $data, $txn_data, $ipn_data );

		/**
		 * Trigger the IPN processed action hook (so other plugins can can listen for this event).
		 * Remember to use plugin shortname as prefix when searching for this hook.
		 */ 
		do_action( 'wpec_paypal_checkout_ipn_processed', $ipn_data );
		do_action( 'wpec_payment_ipn_processed', $ipn_data );

		//Everything is processed successfully, send the success response.
		wp_send_json( array( 'success' => true, 'order_id' => $order_id, 'capture_id' => $paypal_capture_id, 'txn_data' => $txn_data ) );
		exit;
	}


	public function validate_total_amount($order_data){
		return true;
	}

}