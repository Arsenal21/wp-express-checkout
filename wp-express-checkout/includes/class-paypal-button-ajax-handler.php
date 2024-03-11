<?php

namespace WP_Express_Checkout;

use WP_Express_Checkout\Debug\Logger;

use WP_Express_Checkout\PayPal\Request;
use WP_Express_Checkout\PayPal\Client;


class PayPal_Payment_Button_Ajax_Handler {

	private $item_for_validation;
	private $last_error = '';

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
		$order_data_array = json_decode( $json_order_data, true );
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

		// >>>> Start of pre API submission validation.
		$amount = $order_data_array['purchase_units'][0]['amount']['value'];
		$quantity = $order_data_array['purchase_units'][0]['items'][0]['quantity'];
		// $product_name = $order_data_array['purchase_units'][0]['item'][0]['name'];
		// $coupon_code = '';
		// $price_variation = '';
		$custom_inputs = array(
			// 'coupon_code' 		=> $coupon_code,
			// 'price_variation' 	=> $price_variation,
			// 'billing_details' 	=> json_decode( html_entity_decode( $post_billing_details ) , true),
			// 'shipping_details' 	=> json_decode( html_entity_decode( $post_shipping_details ) , true),
		);
		$this->item_for_validation = Products::retrieve( intval( $order_data_array['product_id'] ) );
		Logger::log_array_data($this->item_for_validation, true);

		//Do the API pre-submission price/amount validation.
		if ( $this->item_for_validation->get_type() === 'one_time' ) {
			//It's a one-time payment product.
			
			if( ! $this->validate_total_amount( $amount, $quantity, $custom_inputs ) ){
				//Error condition. The validation function will set the error message which we will use to send back to the client in the next stage of the code.
				Logger::log( "API pre-submission amount validation failed. The amount appears to have been altered.", false );

				wp_send_json(
					array(
						'success' => false,
						'err_msg'  => __( 'Error occurred:', 'wp-express-checkout' ) . ' ' . $this->get_last_error(),
					)
				);

			}else{
				Logger::log( "API pre-submission amount validation successful.", true );
			}
		} else if ( $this->item_for_validation->get_type() === 'donation' ) {
			//It's a donation product. Don't need to validate the amount since the user can enter any amount to donate.
			Logger::log( "This is a donation type product. API pre-submission amount validation is not required.", true );
		}
		//Trigger action hook that can be used to do additional API pre-submission validation from an addon.
		do_action( 'wpec_ng_before_api_pre_submission_validation', $this->item_for_validation );
		// <<<< End of pre API submission validation.


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
	 * Handles the order capture for standard 'Buy Now' type buttons.
	 */
	public function pp_capture_order(){
		//Get the data from the request
		$json_pp_bn_data = isset( $_POST['data'] ) ? stripslashes_deep( $_POST['data'] ) : '{}';
		//We need the data in an array format so lets convert it.
		$array_pp_bn_data = json_decode( $json_pp_bn_data, true );
		//Logger::log_array_data($array_pp_bn_data, true);	

		//Get the PayPal order_id from the data
		$order_id = isset( $array_pp_bn_data['order_id'] ) ? sanitize_text_field($array_pp_bn_data['order_id']) : '';
		Logger::log( 'PayPal capture order request received - PayPal order ID: ' . $order_id, true );

		if ( empty( $order_id ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Error! Empty order ID received for PayPal capture order request.', 'wp-express-checkout' ),
				)
			);
		}

		//Get the WPEC plugin specific data from the request
		$json_wpec_data = isset( $_POST['wpec_data'] ) ? stripslashes_deep( $_POST['wpec_data'] ) : '{}';
		//We need the data in an array format so lets convert it.
		$array_wpec_data = json_decode( $json_wpec_data, true );		
		//Logger::log_array_data($array_wpec_data, true);

		if ( empty( $array_wpec_data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Error! Empty WPEC plugin data received.', 'wp-express-checkout' ),
				)
			);
		}

		// Check nonce.
		if ( ! check_ajax_referer( 'wpec-onapprove-js-ajax-nonce', '_wpnonce', false ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Nonce check failed. The page was most likely cached. Please reload the page and try again.', 'wp-express-checkout' ),
				)
			);
			exit;
		}

		// Capture the order using the PayPal API. (https://developer.paypal.com/docs/api/orders/v2/#orders_capture)
		$pp_capture_response_data = $this->capture_order_pp_api_call( $order_id, $array_wpec_data );
		if ( is_wp_error( $pp_capture_response_data ) ) {
			//Failed to capture the order.
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __('Error! PayPal capture-order API call failed.', 'wp-express-checkout'),
				)
			);
			exit;
		}

		
		$array_txn_data = $pp_capture_response_data;

		//Logger::log_array_data($array_txn_data, true); // Debugging purpose.
		//Logger::log_array_data($array_wpec_data, true); // Debugging purpose.
		
		//Process the transaction/payment data.
		Logger::log( 'Going to create/update record and save transaction data.', true );
		$payment_processor = Payment_Processor::get_instance();

		//It will send the appropriate response back to the client (after processing the payment data).
		$payment_processor->wpec_server_side_process_payment( $array_txn_data, $array_wpec_data );

		/* Everything is processed successfully, the previous function call will also send the response back to the client. */
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
	 * Capture the order using the PayPal API call.
	 * return the transaction data if successful, or a WP_Error object if there is an error.
	 */
    public static function capture_order_pp_api_call( $order_id, $array_wpec_data )
    {
		//https://developer.paypal.com/docs/api/orders/v2/#orders_capture

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

	public function get_last_error(){
		return $this->last_error;
	}

	public function validate_total_amount($amount, $quantity, $custom_inputs){
		// Calculate the expected total amount.
		$expected_total_amount = $this->item_for_validation->get_price() * $quantity;
		
		// Check if the expected total amount matches the given amount.
		if ( $expected_total_amount < $amount ) {
			Logger::log("Pre-API Submission validation amount mismatch. Expected amount: ". $expected_total_amount . ", Submitted amount: " . $amount, true);
			// Set the last error message that will be displayed to the user.
			$mismatch_err_msg = __( "Price validation failed. The submitted amount does not match the product's configured price. ", 'wp-express-checkout' );
			$mismatch_err_msg .= "Expected: " . $expected_total_amount . ", Submitted: " . $amount;
			$this->last_error = $mismatch_err_msg;
			return false;
		}

		return true;
	}

}