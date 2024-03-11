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

		//Get the order data from the request (it will be in JSON format). 
		//Keep the order data in JSON format so we can use it directly in the API call later.
		$json_order_data = isset( $_POST['data'] ) ? stripslashes_deep( $_POST['data'] ) : '{}';

		//If the data is empty, send the error response.
		if ( empty( $json_order_data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Empty data received.', 'wp-express-checkout' ),
				)
			);
		}
		
		//If we need the data as an array, we can use the following code.
		// $array_data = json_decode( $json_order_data, true );
		// Logger::log('Received data in array format: ', true);
		// Logger::log_array_data($array_data, true);

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
		/* 		
		if( ! $this->validate_total_amount( $json_order_data) ){
			//Error condition. The validation function will set the error message which we will use to send back to the client in the next stage of the code.
			Logger::log( "API pre-submission amount validation failed. The amount appears to have been altered.", false );

			$out['err'] = __( 'Error occurred:', 'stripe-payments' ) . ' ' . $item_for_validation->get_last_error();
			wp_send_json( $out );
		}else{
			Logger::log( "API pre-submission amount validation successful.", true );
		} 
		*/


		// Create the order using the PayPal API call (pass the order data in JSON format so we can use it directly in the API call)
		$result = self::create_order_pp_api_call($json_order_data);
		if(is_wp_error($result) ){
			//Failed to create the order.
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Failed to create the order using PayPal API. Enable the debug logging feature to get more details.', 'wp-express-checkout' ),
				)
			);
			exit;
		}

		//The PayPal order ID returned by the API call.
		$paypal_order_id = isset($result) ? $result : '';
		Logger::log( 'PayPal Order ID: ' . $paypal_order_id, true );

		//If everything is processed successfully, send the success response.
		wp_send_json( 
			array( 
				'success' => true,
				'order_id' => $paypal_order_id,
				'additional_data' => array(),
			)
		);
		exit;
    }

	/**
	 * Create the order using the PayPal API call.
	 * return the PayPal order ID if successful, or a WP_Error object if there is an error.
	 */
    public static function create_order_pp_api_call($json_order_data)
    {
		//https://developer.paypal.com/docs/api/orders/v2/#orders_create

		Logger::log( 'Creating PayPal order using the PayPal API call...', true);
		Logger::log_array_data($json_order_data, true);
		
		//Create the request for the PayPal API call.
		$request = new Request( '/v2/checkout/orders', 'POST' );
		//The order data alreaady in JSON format so we don't need to json_encode it again.
		$request->body = $json_order_data;

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

		Logger::log( 'Received request - pp_capture_order. PayPal Order ID: ' . $order_id, true );

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

		// Set the additional args for the API call.
		// $additional_args = array();
		// $additional_args['return_response_body'] = true;

		// Capture the order using the PayPal API. (https://developer.paypal.com/docs/api/orders/v2/#orders_capture)
		// $api_injector = new PayPal_Request_API_Injector(); // TODO: Fix this.
		// $response = $api_injector->capture_paypal_order( $order_id, $additional_args );

		// We requested the response body to be returned, so we need to JSON decode it.
		/*
		if($response !== false){
			$txn_data = json_decode( $response, true );
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
		*/

		// Logger::log_array_data($data, true); // Debugging purpose.
		// Logger::log_array_data($txn_data, true); // Debugging purpose.

		// Create the IPN data array from the transaction data.
		// Need to include the following values in the $data array.
		// $data['custom_field'] = get_post_meta( $cart_id, 'wpec_cart_custom_values', true ); // TODO: Take care of the custom fields data.
		
		// $ipn_data = PayPal_Utility_IPN_Related::create_ipn_data_array_from_capture_order_txn_data( $data, $txn_data ); // TODO: Fix this.
		// $paypal_capture_id = isset( $ipn_data['txn_id'] ) ? $ipn_data['txn_id'] : '';
		// Logger::log( 'PayPal Capture ID (Transaction ID): ' . $paypal_capture_id, true );
		// Logger::log_array_data( $ipn_data, true ); //Debugging purpose.
		
		/* Since this capture is done from server side, the validation is not required but we are doing it anyway. */
		// Validate the buy now txn data before using it.
		// $validation_response = PayPal_Utility_IPN_Related::validate_buy_now_checkout_txn_data( $data, $txn_data ); // TODO: Fix this
		
		/*
		if( $validation_response !== true ){
			//Debug logging will reveal more details.
			wp_send_json(
				array(
					'success' => false,
					'error_detail'  => $validation_response, // It contains the error message.
				)
			);
			exit;
		}
		 */

		//Process the IPN data array
		Logger::log( 'Validation passed. Going to create/update record and save transaction data.', true );
		
		/**
		 * TODO: Fix this
		 */
		// PayPal_Utility_IPN_Related::complete_post_payment_processing( $data, $txn_data, $ipn_data );

		/**
		 * Trigger the IPN processed action hook (so other plugins can can listen for this event).
		 */ 
		// do_action( 'wpec_paypal_checkout_ipn_processed', $ipn_data );
		// do_action( 'wpec_payment_ipn_processed', $ipn_data );

		//Everything is processed successfully, send the success response.
		/* 
		wp_send_json( array( 
			'success' => true, 
			'order_id' => $order_id, 
			'capture_id' => $paypal_capture_id, 
			'txn_data' => $txn_data 
			)
		); 
		*/

		exit;
	}


	public function validate_total_amount($order_data){
		return true;
	}

}