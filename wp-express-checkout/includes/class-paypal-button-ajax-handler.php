<?php

namespace WP_Express_Checkout;

use WP_Express_Checkout\Debug\Logger;

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
		if ( ! check_ajax_referer( 'wpec-create-order-ajax-nonce', '_wpnonce', false ) ) {
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Nonce check failed. The page was most likely cached. Please reload the page and try again.', 'wp-express-checkout' ),
				)
			);
			exit;
		}
		Logger::log('Nonce verification successful!', true);
		
		// Set the additional args for the API call.
		// $additional_args = array();
		// $additional_args['return_response_body'] = true;

		// TODO: Create the order using the PayPal API.
		// $api_injector = new PayPal_Request_API_Injector();
		// $response = $api_injector->create_paypal_order_by_url_and_args( $data, $additional_args, $pu_items );
            
		//We requested the response body to be returned, so we need to JSON decode it.
		/* 
		if( $response === false ){
			//Failed to create the order.
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Failed to create the order using PayPal API. Enable the debug logging feature to get more details.', 'wp-express-checkout' ),
				)
			);
			exit;
		}

		$order_data = json_decode( $response, true );
		$paypal_order_id = isset( $order_data['id'] ) ? $order_data['id'] : '';

        Logger::log( 'PayPal Order ID: ' . $paypal_order_id, true );
		*/

		// If everything is processed successfully, send the success response.
		wp_send_json( 
			array( 
				'success' => true,
				'order_id' => 1234, 	// put $paypal_order_id here
				'order_data' => array() // put $order_data here 
			)
		);
		exit;
    }
}