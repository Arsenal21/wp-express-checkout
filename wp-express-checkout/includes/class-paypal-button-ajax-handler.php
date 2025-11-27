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
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Empty data received.', 'wp-express-checkout' ),
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
					'err_msg'  => __( 'Error! Empty WPEC plugin data received from the create-order AJAX call.', 'wp-express-checkout' ),
				)
			);
		}		

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

		// Do the API pre-submission validation.
		$this->do_api_pre_submission_validation($order_data_array, $array_wpec_data);

		// Create the order using the PayPal API call (pass the order data so we can use it in the API call)
		$result = self::create_order_pp_api_call($order_data_array, $array_wpec_data);
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
    public static function create_order_pp_api_call($order_data_array, $array_wpec_data)
    {
		//https://developer.paypal.com/docs/api/orders/v2/#orders_create

		//Create the order-data for the PayPal API call.
		$pp_api_order_data = self::create_order_data_for_pp_api($order_data_array, $array_wpec_data);

		$json_encoded_pp_api_order_data = wp_json_encode($pp_api_order_data);
		Logger::log_array_data($json_encoded_pp_api_order_data, true);
		
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

		$product_id = $array_wpec_data['product_id'];
		Logger::log( 'Creating PayPal order data for product ID: ' . $product_id, true );

		$item_for_pp_order = Products::retrieve( intval( $product_id ) );

		//We need to use the item name from the database to retain the original name (without any special characters creating issues with data coming from ajax request).
		$item_name = $item_for_pp_order->get_item_name();
		$item_name = substr($item_name, 0, 127);//Limit the item name to 127 characters (PayPal limit)

		//$shipping_preference = $item_for_pp_order->is_digital_product() ? 'NO_SHIPPING' : 'SET_PROVIDED_ADDRESS';
		$shipping_preference = $order_data_array['payment_source']['paypal']['experience_context']['shipping_preference'];

		//Get the item quantity and amount from the order data.
		$quantity = $order_data_array['purchase_units'][0]['items'][0]['quantity'];
		$item_amount = $order_data_array['purchase_units'][0]['items'][0]['unit_amount']['value'];

		$currency_code = $order_data_array['purchase_units'][0]['amount']['currency_code'];
		$grand_total = $order_data_array['purchase_units'][0]['amount']['value'];
		$sub_total = $order_data_array['purchase_units'][0]['amount']['breakdown']['item_total']['value']; //This should be equal to (item_amount * quantity)
		//Transaction specific amounts for purchase_units breakdowns (may or may not be available in the order data array depending on the product configuration).
		$shipping_amt = isset($order_data_array['purchase_units'][0]['amount']['breakdown']['shipping']['value'])? $order_data_array['purchase_units'][0]['amount']['breakdown']['shipping']['value'] : 0;
		$tax_amt = isset($order_data_array['purchase_units'][0]['amount']['breakdown']['tax_total']['value']) ? $order_data_array['purchase_units'][0]['amount']['breakdown']['tax_total']['value'] : 0;
		$discount_amt = isset($order_data_array['purchase_units'][0]['amount']['breakdown']['discount']['value']) ? $order_data_array['purchase_units'][0]['amount']['breakdown']['discount']['value'] : 0;		

		//https://developer.paypal.com/docs/api/orders/v2/#orders_create
		$pp_api_order_data = [
			"intent" => "CAPTURE",
			"payment_source" => [
				"paypal" => [
					"experience_context" => [
						"payment_method_preference" => "IMMEDIATE_PAYMENT_REQUIRED",
						"shipping_preference" => $shipping_preference,
						"user_action" => "PAY_NOW",
					]
				]
			], 			
			"purchase_units" => [
				[
					"amount" => [
						"value" => (string) $grand_total, /* The grand total that will be charged for the transaction. Cast to string to make sure there is no precision issue */
						"currency_code" => $currency_code,
						"breakdown" => [
							"item_total" => [
								"currency_code" => $currency_code,
								"value" => (string) $sub_total, /* We can break down the total amount into item_total, tax_total, shipping etc */
							]
						]
					],
					"items" => [
						[
							"name" => $item_name,
							"quantity" => $quantity,
							"unit_amount" => [
								"value" => (string) $item_amount, /* Cast to string to make sure there is no precision issue */
								"currency_code" => $currency_code,
							]
						]
					],
				]
			]
		];

		//Add the shipping amount if available.
		if( isset($shipping_amt) && $shipping_amt > 0){
			$pp_api_order_data['purchase_units'][0]['amount']['breakdown']['shipping'] = [
				"currency_code" => $currency_code,
				"value" => (string) $shipping_amt, /* Cast to string to make sure there is no precision issue */
			];
		}

		//Add the tax amount if available.
		if( isset($tax_amt) && $tax_amt > 0){
			$pp_api_order_data['purchase_units'][0]['amount']['breakdown']['tax_total'] = [
				"currency_code" => $currency_code,
				"value" => (string) $tax_amt, /* Cast to string to make sure there is no precision issue */
			];
		}

		//Add the discount amount if available.
		if( isset($discount_amt) && $discount_amt > 0){
			$pp_api_order_data['purchase_units'][0]['amount']['breakdown']['discount'] = [
				"currency_code" => $currency_code,
				"value" => (string) $discount_amt, /* Cast to string to make sure there is no precision issue */
			];
		}


		//A simple order data for testing            
		// $order_data = [
		//     "intent" => "CAPTURE",
		//     "purchase_units" => [
		//         [
		//             amount => [
		//             currency_code => "USD",
		//             value => "100.00",
		//             ],
		//         ],
		//     ],
		// ];

		//Debug purposes.
		//Logger::log_array_data($pp_api_order_data, true);

		return $pp_api_order_data;
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

	public function get_last_error(){
		return $this->last_error;
	}

	/**
	 * Do the API pre-submission validation. It will send the error message back to the client if there is an error.
	 * return nothing if the validation is successful, or the error message if there is an error.
	 */
	public function do_api_pre_submission_validation($order_data_array, $array_wpec_data){

		$amount = $order_data_array['purchase_units'][0]['amount']['value'];
		$quantity = $order_data_array['purchase_units'][0]['items'][0]['quantity'];
		$submitted_currency = $order_data_array['purchase_units'][0]['amount']['currency_code'];
		
		$product_id = $array_wpec_data['product_id'];

		// Retrieve product item.
		try {
			$this->item_for_validation = Products::retrieve( intval( $product_id ) );
		}catch (\Exception $exception){
			Logger::log( 'API pre-submission validation failed. '. $exception->getMessage(), true );
			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Validation Error: ', 'wp-express-checkout' ) . $exception->getMessage(),
				)
			);
		}

		//Trigger action hook that can be used to do additional API pre-submission validation from an addon.
		do_action( 'wpec_before_api_pre_submission_validation', $this->item_for_validation, $order_data_array, $array_wpec_data );		

		$validated = true; // We will set it to false if the validation fails in the following code.
		$error_msg = '';

		$product_type = $this->item_for_validation->get_type();

		switch ( $product_type ) {
			case 'subscription':
				//It's a subscription payment product. The extension will handle the validation using filter.
				break;
			case 'donation':
				//It's a donation product.
				Logger::log( "This is a donation type product. API pre-submission amount validation is not required.", true );
				break;
			default:
				//It's a one-time product. API pre-submission amount validation is required.

				// Calculate product price amount.
				$product_price = $this->item_for_validation->get_price();

				$variations = (new Variations( $this->item_for_validation->get_id() ))->variations;
				$variation_price_total = 0; 
				$price_variations_applied = isset($array_wpec_data['variations']['applied']) ? $array_wpec_data['variations']['applied'] : array();
				if (is_array($variations) && !empty($price_variations_applied)) {
					foreach ($variations as $index => $variation) {
						$applied_var_index = (int) $price_variations_applied[$index];
						$variation_price = Utils::round_price($variation['prices'][$applied_var_index]);
						$variation_price_total += $variation_price;
					}
					if (!empty($variation_price_total)) {
						$construct_final_price = get_post_meta( $product_id, 'wpec_product_hide_amount_input', true );
						$product_price = !empty($construct_final_price) ? $variation_price_total : $product_price + $variation_price_total;
					}
				}

				// Calculate total product price amount.
				$total_product_price = Utils::round_price($product_price * $quantity);
				// Logger::log( "Total product price: " . $total_product_price, true );

				// Calculate coupon discount amount.
				if (isset($array_wpec_data['couponCode'])) {
					$coupon_code = $array_wpec_data['couponCode'];
					$coupon = Coupons::get_coupon($coupon_code);
					$discount = 0;
					if ($coupon['valid'] && Coupons::is_coupon_allowed_for_product($coupon['id'], $product_id)) {
						// Get the discount amount.
						if ( $coupon['discountType'] === 'perc' ) {
							// Discount type percentage.
							$discount = Utils::round_price( $total_product_price * ( $coupon['discount'] / 100 ) );
						} else {
							// Discount type fixed.
							$discount = $coupon['discount'];
						}
					}

					$total_product_price = $total_product_price - $discount;
				}

				// Calculate tax amount.
				$tax_percentage = $this->item_for_validation->get_tax();
				$tax_amount = Utils::get_tax_amount( $total_product_price , $tax_percentage );
				$tax_amount = Utils::round_price($tax_amount );

				// Logger::log( "Tax percentage: " . $tax_percentage, true );
				// Logger::log( "Tax amount: " . $tax_amount, true );

				// Calculate shipping amount.
				$shipping = $this->item_for_validation->get_shipping();
				$shipping_per_quantity = $this->item_for_validation->get_shipping_per_quantity();
				$total_shipping = Utils::get_total_shipping_cost(
					array(
						'shipping' => $shipping,
						'shipping_per_quantity' => $shipping_per_quantity,
						'quantity' => $quantity,
					)
				);
				// Logger::log( "Base Shipping: " . $shipping, true );
				// Logger::log( "Shipping per quantity: " . $shipping_per_quantity, true );
				// Logger::log( "Total shipping cost: " . $total_shipping, true );

				// Calculate the expected total amount.
				$expected_total_amount = $total_product_price + $tax_amount + $total_shipping ;
				
				// Logger::log("Expected amount: ". $expected_total_amount . ", Submitted amount: " . $amount, false);
				
				// Check if the expected total amount matches the given amount.
				// We mainly check for underpayment (customer paying less than expected).
				// Allow a small tolerance for floating-point rounding differences.
				if ( $amount < $expected_total_amount && !Utils::almost_equal($amount, $expected_total_amount)) {
					Logger::log("API pre-submission validation amount mismatch. Expected amount: ". $expected_total_amount . ", Submitted amount: " . $amount, false);
					
					// Set the last error message that will be displayed to the user.
					$error_msg .= __( "Price validation failed. The submitted amount does not match the product's configured price. ", 'wp-express-checkout' );
					$error_msg .= "Expected: " . $expected_total_amount . ", Submitted: " . $amount;

					//Set the validation failed flag.
					$validated = false;
				}
				
				// Check if the expected currency matches the given currency.
				$configured_currency = Main::get_instance()->get_setting( 'currency_code' );
				if ($submitted_currency != $configured_currency) {
					Logger::log("API pre-submission validation currency mismatch. Expected currency: ". $configured_currency . ", Submitted currency: " . $submitted_currency ."\n", false);
					
					// Set the last error message that will be displayed to the user.
					$error_msg .= __( "Currency validation failed. The submitted currency does not match the configured currency. ", 'wp-express-checkout' );
					$error_msg .= "Expected: " .  $configured_currency . ", Submitted: " . $submitted_currency;

					//Set the validation failed flag.
					$validated = false;
				}

				break;
		}

		//Trigger action hook that can be used to do additional API pre-submission validation from an addon.
		$validated = apply_filters( 'wpec_pre_api_submission_validation', $validated, $this->item_for_validation, $order_data_array, $array_wpec_data );

		//If the validation failed, send the error message back to the client.
		if( ! $validated ){
			//Error condition. The validation function will set the error message which we will use to send back to the client in the next stage of the code.
			Logger::log( "API pre-submission validation failed. Stopping the process.", false );

			wp_send_json(
				array(
					'success' => false,
					'err_msg'  => __( 'Validation Error: ', 'wp-express-checkout' ) . $error_msg,
				)
			);

		}

		//Validation is successful, return nothing.
	}

}
