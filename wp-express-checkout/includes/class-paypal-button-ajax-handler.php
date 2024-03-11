<?php

namespace WP_Express_Checkout;

use WP_Express_Checkout\Debug\Logger;

use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use WP_Express_Checkout\PayPal\Client;


class PayPal_Payment_Button_Ajax_Handler {

	public function __construct()
	{
		//Handle the create-order ajax request for 'Buy Now' type buttons.
		add_action( 'wp_ajax_wpec_pp_create_order', array(&$this, 'pp_create_order' ) );
		add_action( 'wp_ajax_nopriv_wpec_pp_create_order', array(&$this, 'pp_create_order' ) );
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
		if ( ! check_ajax_referer( 'wpec-js-ajax-nonce', '_wpnonce', false ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Nonce check failed. The page was most likely cached. Please reload the page and try again.', 'wp-express-checkout' ),
				)
			);
			exit;
		}

		// FIXME - add validation for the amount data received.
		// TODO...

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
				
				
        $request = new OrdersCreateRequest();
        //$request->headers["prefer"] = "return=representation";
        $request->body = $order_data;

		$client = Client::client();
        $response = $client->execute($request);

		// Logger::log_array_data($response->result, true);
		// Logger::log( 'PayPal Order ID: ' . $response->result->id, true );

		// Logger::log( 'PayPal API var exported full response below: ', true );
		// $debug_export = var_export($response, true);
		// Logger::log_array_data($debug_export, true);

        return $response;
    }

}