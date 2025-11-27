<?php

namespace WP_Express_Checkout\Integrations;

use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\PayPal\Request;
use WP_Express_Checkout\PayPal\Client;
use WP_Express_Checkout\Main;


class WooCommerce_Payment_Button_Ajax_Handler {

	public function __construct()
	{
		//Handle the generate-button ajax request.
		add_action( 'wp_ajax_wpec_wc_generate_button', array( $this, 'handle_wpec_wc_generate_button' ) );
		add_action( 'wp_ajax_nopriv_wpec_wc_generate_button', array( $this, 'handle_wpec_wc_generate_button' ) );

        // For WooCommerce checkout block.
        add_action( 'wp_ajax_wpec_wc_block_payment_button_data', array( $this, 'handle_wpec_wc_block_payment_button_data' ) );
		add_action( 'wp_ajax_nopriv_wpec_wc_block_payment_button_data', array( $this, 'handle_wpec_wc_block_payment_button_data' ) );

		//Handle the create-order ajax request for woocommerce checkout.
		add_action( 'wp_ajax_wpec_woocommerce_pp_create_order', array(&$this, 'pp_create_order' ) );
		add_action( 'wp_ajax_nopriv_wpec_woocommerce_pp_create_order', array(&$this, 'pp_create_order' ) );

		//Handle the capture-order ajax request for woocommerce checkout.
		add_action( 'wp_ajax_wpec_woocommerce_pp_capture_order', array(&$this, 'pp_capture_order' ) );
		add_action( 'wp_ajax_nopriv_wpec_woocommerce_pp_capture_order', array(&$this, 'pp_capture_order' ) );
	}


	/*
	 * Generates the PPCP button that will be used for the checkout.
	 * This is called via AJAX from the WooCommerce checkout page (when the user clicks the 'Place Order' button).
	 */
	public function handle_wpec_wc_generate_button() {
		if ( ! check_ajax_referer( 'wpec-wc-render-button-nonce', 'nonce', false ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'Error! Nonce value is missing in the URL or Nonce verification failed.', 'wp-express-checkout' )
				)
			);
		}

		if ( empty( $_POST['order_id'] ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'No order data received.', 'wp-express-checkout' )
				)
			);
		}

		$wc_order_id = intval( $_POST['order_id'] );

		$woo_button_sc = new WooCommerce_Payment_Button($wc_order_id);
		$output = $woo_button_sc->wpec_generate_woo_payment_button();

		wp_send_json_success( $output );
	}

    public function handle_wpec_wc_block_payment_button_data() {
        if ( ! check_ajax_referer( 'wpec-wc-render-button-nonce', 'nonce', false ) ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'Error! Nonce value is missing in the URL or Nonce verification failed.', 'wp-express-checkout' )
                )
            );
        }

        if ( empty( $_POST['order_id'] ) ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'No order data received.', 'wp-express-checkout' )
                )
            );
        }

        $wc_order_id = intval( $_POST['order_id'] );

        $woo_button_sc = new WooCommerce_Payment_Button($wc_order_id);

        $button_data = $woo_button_sc->wpec_prepare_woo_payment_button_data();

        $trans_name = 'wp-ppdg-' . $button_data['order_id']; // Create key using the item name.
        $trans_data = array(
            'price'           => $button_data['price'],
            'currency'        => $button_data['currency'],
            'thank_you_url'   => $button_data['thank_you_url'],
            'wc_id'           => $button_data['order_id'],
        );

        set_transient( $trans_name, $trans_data, 2 * 3600 );

        wp_send_json(
            array(
                'success' => true,
                'message' => __("Button Generated", 'wp-express-checkout'),
                'data' => $button_data
            )
        );
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
		Logger::log( 'PayPal create-order request received for order item: ' . $decoded_item_name, true );

		//Set this decoded item name back to the order data.
		$order_data_array['purchase_units'][0]['items'][0]['name'] = $decoded_item_name;

		//If the data is empty, send the error response.
		if ( empty( $json_order_data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message'  => __( 'Empty data received.', 'wp-express-checkout' ),
				)
			);
		}

		//Get the WPEC plugin specific data from the request
		$json_wpec_data = isset( $_POST['wpec_data'] ) ? stripslashes_deep( $_POST['wpec_data'] ) : '{}';
		//We need the data in an array format so lets convert it.
		$array_wpec_data = json_decode( $json_wpec_data, true );

		if ( empty( $array_wpec_data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message'  => __( 'Error! Empty WPEC plugin data received from the create-order AJAX call.', 'wp-express-checkout' ),
				)
			);
		}

		// Verify nonce.
		if ( ! check_ajax_referer( 'wpec-wc-pp-payment-ajax-nonce', '_wpnonce', false ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message'  => __( 'Nonce check failed. The page was most likely cached. Please reload the page and try again.', 'wp-express-checkout' ),
				)
			);
			exit;
		}

		// Create the order using the PayPal API call (pass the order data so we can use it in the API call)
		$result = self::create_order_pp_api_call($order_data_array);
		if(is_wp_error($result) ){
			//Failed to create the order.
			wp_send_json(
				array(
					'success' => false,
					'message'  => __( 'Failed to create the order using PayPal API. Enable the debug logging feature to get more details.', 'wp-express-checkout' ),
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
	public static function create_order_pp_api_call($order_data_array)
	{
		//https://developer.paypal.com/docs/api/orders/v2/#orders_create

		//Create the order-data for the PayPal API call.
		$pp_api_order_data = self::create_order_data_for_pp_api($order_data_array);

		$json_encoded_pp_api_order_data = wp_json_encode($pp_api_order_data);
		// Logger::log_array_data($json_encoded_pp_api_order_data, true); // Debug purpose

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

	public static function create_order_data_for_pp_api($order_data_array){
		Logger::log( 'Creating PayPal order data for Woocommerce Checkout', true );

		//A simple order data for testing
		$currency_code = isset($order_data_array['purchase_units'][0]['amount']['currency_code']) ? $order_data_array['purchase_units'][0]['amount']['currency_code'] : 'USD';
		$woo_order_grand_total = $order_data_array['purchase_units'][0]['amount']['breakdown']['item_total']['value'];
		$pp_api_order_data = [
			"intent" => "CAPTURE",
			"purchase_units" => [
				[
					"amount" => [
						"currency_code" => $currency_code,
						"value" => (string) $woo_order_grand_total,
					],
				],
			],
		];

		// Logger::log_array_data($pp_api_order_data, true); //Debug purposes.

		return $pp_api_order_data;
	}

	/**
	 * Capture the order using the PayPal API call.
	 * https://developer.paypal.com/docs/api/orders/v2/#orders_capture
	 *
	 * return the transaction data if successful, or a WP_Error object if there is an error.
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
				Logger::log( 'Error capturing the PayPal order via API call: ' . $error_string, false );
				return new \WP_Error(2002,$error_string);
			}
			Logger::log( 'Error creating PayPal order: ' . $e->getMessage(), false );
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
	 * Handles the order capture for standard 'Buy Now' type buttons.
	 */
	public function pp_capture_order(){
		//Get the data from the request
		$json_pp_bn_data = isset( $_POST['data'] ) ? stripslashes_deep( $_POST['data'] ) : '{}';
		//We need the data in an array format so lets convert it.
		$array_pp_bn_data = json_decode( $json_pp_bn_data, true );

		//Logger::log_array_data($array_pp_bn_data, true); //Debug purposes.

		//Get the PayPal order_id from the data
		$order_id = isset( $array_pp_bn_data['order_id'] ) ? sanitize_text_field($array_pp_bn_data['order_id']) : '';
		Logger::log( 'PayPal capture order request received - PayPal order ID: ' . $order_id, true );

		if ( empty( $order_id ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message'  => __( 'Error! Empty order ID received for PayPal capture order request.', 'wp-express-checkout' ),
				)
			);
		}

		//Get the WPEC plugin specific data from the request
		$json_wpec_data = isset( $_POST['wpec_data'] ) ? stripslashes_deep( $_POST['wpec_data'] ) : '{}';
		//We need the data in an array format so lets convert it.
		$array_wpec_data = json_decode( $json_wpec_data, true );
		//Logger::log_array_data($array_wpec_data, true); //Debug purposes.

		if ( empty( $array_wpec_data ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message'  => __( 'Error! Empty WPEC plugin data received.', 'wp-express-checkout' ),
				)
			);
		}

		// Check nonce.
		if ( ! check_ajax_referer( 'wpec-wc-pp-payment-ajax-nonce', '_wpnonce', false ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message'  => __( 'Nonce check failed. The page was most likely cached. Please reload the page and try again.', 'wp-express-checkout' ),
				)
			);
			exit;
		}

		$pp_capture_response_data = $this->capture_order_pp_api_call( $order_id, $array_wpec_data );

		if ( is_wp_error( $pp_capture_response_data ) ) {
			//Failed to capture the order.
			wp_send_json(
				array(
					'success' => false,
					'message'  => __('Error! PayPal capture-order API call failed.', 'wp-express-checkout'),
				)
			);
			exit;
		}

		Logger::log( 'PayPal capture order API call success for order id: ' . $order_id, true );
		// logger::log('PayPal capture order data for Woocommerce Checkout: ');
		// logger::log_array_data($pp_capture_response_data);

		$payment_data_array = $pp_capture_response_data;

		//Logger::log_array_data($payment_data_array, true); // Debugging purpose.
		//Logger::log_array_data($array_wpec_data, true); // Debugging purpose.

		//The transaction has been finalized. Now we can tell woocomemrce to process the order.

		$wc_payment_processor = new WooCommerce_Payment_Processor();
		$wc_payment_processor->wpec_woocommerce_process_payment($payment_data_array, $array_wpec_data);

		/* Everything is processed successfully, the previous function call will also send the response back to the client. */
	}

}
