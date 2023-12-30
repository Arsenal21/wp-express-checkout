<?php
/**
 * Download request handler.
 *
 * Views the requests for the `wpec_download_file` parameter, once one given,
 * triggers the product download process.
 */

namespace WP_Express_Checkout;

use Exception;
use WP_Express_Checkout\Debug\Logger;

/**
 * Download request class.
 */
class View_Downloads {

	/**
	 * The class instance.
	 *
	 * @var View_Downloads
	 */
	protected static $instance = null;

	/**
	 * Construct the instance.
	 */
	public function __construct() {
		if ( isset( $_GET['wpec_download_file'] ) && $this->verify_request() ) {
			$this->process_download();
		}

		add_action( 'wpec_payment_completed', array( $this, 'set_download_limits' ), 10, 3 );
	}

	/**
	 * Retrieves the instance.
	 *
	 * @return View_Downloads
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Retrieves secure download URL for given order
	 *
	 * @param int $order_id The order id.
	 * @param int $grp_id   The variation group ID.
	 * @param int $var_id   The variation ID in the group.
	 */
	public static function get_download_url( $order_id, $grp_id = '', $var_id = '' ) {
		$download_url = '';

		try {
			$order = Orders::retrieve( $order_id );
		} catch ( Exception $exc ) {
			return $download_url;
		}

		$product_item = $order->get_item( Products::$products_slug );
		$product      = get_post( $product_item['post_id'] );

		if ( ! empty( $product ) || ( ! $product->ppec_product_upload && '' === $grp_id ) ) {
			$order_timestamp = get_the_time( 'U', $order_id );
			$product_id      = $product->ID;

			$var_args = array();
			$var_key  = '';

			// Add variation parameters to the hash.
			if ( '' !== $grp_id && '' !== $var_id ) {
				$var_args = array(
					'grp_id' => $grp_id,
					'var_id' => $var_id,
				);
				$var_key  = "|{$grp_id}|{$var_id}";
			}

			$key  = "{$product_id}|{$order_id}|{$order_timestamp}" . $var_key;
			$hash = substr( wp_hash( $key ), 0, 20 );
			$args = array_merge(
				array(
					'wpec_download_file' => $product_id,
					'order_id'           => $order_id,
					'key'                => $hash,
				),
				$var_args
			);

			$download_url = add_query_arg( $args, home_url( '/' ) );
		}

		return $download_url;
	}

	/**
	 * Retrieves downloads list for a given order.
	 *
	 * @param int $order_id The order id.
	 * @return array
	 */
	public static function get_order_downloads_list( $order_id ) {
		$downloads = array();
		try {
			$order        = Orders::retrieve( $order_id );
			$product_item = $order->get_item( Products::$products_slug );
			$product_id   = ! empty( $product_item['post_id'] ) ? $product_item['post_id'] : 0;
			$product      = Products::retrieve( $product_id );
		} catch ( Exception $exc ) {
			return $downloads;
		}

		if ( ! empty( $product->get_download_url() ) ) {
			$downloads[ $product_item['name'] ] = self::get_download_url( $order_id );
		}

		$var_applied = $order->get_items( 'variation' );

		if ( ! empty( $var_applied ) ) {
			foreach ( $var_applied as $var ) {
				if ( ! empty( $var['meta']['url'] ) ) {
					$downloads[ $var['name'] ] = self::get_download_url( $order_id, (int) $var['meta']['grp_id'], (int) $var['meta']['id'] );
				}
			}
		}

		return $downloads;
	}

	/**
	 * Adds download limits to a given order.
	 *
	 * @param array $payment    Payment data.
	 * @param int   $order_id   Order ID.
	 * @param int   $product_id Product ID.
	 */
	public function set_download_limits( $payment, $order_id, $product_id ) {
		try {
			$order   = Orders::retrieve( $order_id );
			$product = Products::retrieve( $product_id );
		} catch ( Exception $exc ) {
			return;
		}

		$duration = $product->get_download_duration();
		$count    = $product->get_download_count();

		if ( $duration ) {
			$order->add_data( 'download_duration', $duration );
			$order->add_data( 'download_start_date', time() );
		}

		if ( $count ) {
			$order->add_data( 'download_count', $count );
		}
	}

	/**
	 * Checks whether a download request is valid.
	 *
	 * @return boolean
	 */
	private function verify_request() {

		if ( empty( $_GET['wpec_download_file'] ) || empty( $_GET['order_id'] ) || empty( $_GET['key'] ) ) {
			wp_die( esc_html__( 'Invalid download URL!', 'wp-express-checkout' ) );
		}

		$order_id = absint( $_GET['order_id'] );
		try {
			$order = Orders::retrieve( $order_id );
		} catch ( Exception $exc ) {
			wp_die( $exc->getMessage() );
		}

		$product = get_post( absint( $_GET['wpec_download_file'] ) );
		$item    = $order->get_item( Products::$products_slug );

		if ( empty( $product ) || (int) $item['post_id'] !== $product->ID ) {
			wp_die( esc_html__( 'Invalid product ID!', 'wp-express-checkout' ) );
		}

		if ( ! $product->ppec_product_upload && ( ! isset( $_GET['var_id'] ) || ! isset( $_GET['grp_id'] ) ) ) {
			wp_die( esc_html__( 'The product has no file for download!', 'wp-express-checkout' ) );
		}

		$order_timestamp = get_the_time( 'U', $order_id );

		$var_args = array();
		$var_key  = '';
		$name     = $item['name'];

		// Add variation parameters to the hash.
		if ( isset( $_GET['var_id'] ) && isset( $_GET['grp_id'] ) ) {
			$var_args = array(
				'grp_id' => (int) $_GET['grp_id'],
				'var_id' => (int) $_GET['var_id'],
			);
			$var_key     = "|{$var_args['grp_id']}|{$var_args['var_id']}";
			$var_applied = $order->get_items( 'variation' );
			$var_applied = wp_list_pluck( $var_applied, 'meta', 'name' );
			$variation   = wp_list_filter( $var_applied, array( 'grp_id' => $_GET['grp_id'], 'id' => $_GET['var_id'] ) );
			if ( ! empty( $variation ) ) {
				$name = key( $variation );
			}
		}

		$key  = "{$product->ID}|{$order_id}|{$order_timestamp}" . $var_key;
		$hash = substr( wp_hash( $key ), 0, 20 );

		if ( $_GET['key'] !== $hash ) {
			wp_die( esc_html__( 'Invalid product key!', 'wp-express-checkout' ) );
		}

		$download_duration = $order->get_data( 'download_duration' );
		if ( ! empty( $download_duration ) ) {
			$download_start_date = $order->get_data( 'download_start_date' );
			if ( $download_start_date < ( time() - ( $download_duration * 60 * 60 ) ) ) {
				wp_die( esc_html__( 'Download limit time is enabled for this product. Time duration limit exceeded. This download link has expired.', 'wp-express-checkout' ) );
			}
		}

		$download_count = (int) $order->get_data( 'download_count' );
		if ( ! empty( $download_count ) ) {
			$counter = (array) $order->get_data( 'downloads_counter' );
			if ( ! empty( $counter[ $name ] ) && $counter[ $name ] >= $download_count ) {
				wp_die( esc_html__( 'Download limit count is enabled for this product. Count limit exceeded. This download link has expired.', 'wp-express-checkout' ) );
			}
		}

		return apply_filters( 'wpec_verify_download_product_request', true, $order, $product );
	}

	/**
	 * Processes the product download. This function is called AFTER the download request has been verified via the verify_request() call.
	 */
	private function process_download() {

		// Get the product custom post type object.
		$product   = get_post( absint( $_GET['wpec_download_file'] ) );
		$file_url  = '';
		$file_name = '';
		$order_id  = absint( $_GET['order_id'] );
		try {
			$order = Orders::retrieve( $order_id );
		} catch ( Exception $exc ) {
			return;
		}

		// Trigger the action hook (product object is also passed).
		do_action( 'wpec_process_download_request', $product, $order_id );

		if ( isset( $_GET['var_id'] ) && isset( $_GET['grp_id'] ) ) {
			$var_applied = $order->get_items( 'variation' );
			$var_applied = wp_list_pluck( $var_applied, 'meta', 'name' );
			$variation   = wp_list_filter( $var_applied, array( 'grp_id' => $_GET['grp_id'], 'id' => $_GET['var_id'] ) );

			if ( ! empty( $variation ) ) {
				$file_name = key( $variation );
				$variation = array_shift( $variation );
				if ( ! empty( $variation['url'] ) ) {
					$file_url = $variation['url'];
				}
			}
		} else {
			$file_url  = $product->ppec_product_upload;
			$item      = $order->get_item( Products::$products_slug );
			$file_name = $item['name'];
		}

		$counter = $order->get_data( 'downloads_counter' );
		if(!is_array($counter)){
			//if it's not an array, reset it to an empty array.
			$counter = array();
		}

		if ( empty( $counter[ $file_name ] ) ) {
			$counter[ $file_name ] = 0;
		}

		$counter[ $file_name ]++;
		$order->add_data( 'downloads_counter', $counter );
		//Logger::log( 'Process download - updating download counter.', true);

		// Clean the file URL.
		$file_url = stripslashes( trim( $file_url ) );

		// Trigger action hook (file_url, product object is also passed).
		// It can be used to override the download handling via an addon.
		do_action( 'wpec_before_file_download', $file_url, $product, $order_id );		

		if($product->wpec_force_download) {
			View_Downloads::handle_force_download_file($file_url);
		}
		else {
			Utils::redirect_to_url( $file_url );					
		}

	}

	public static function handle_force_download_file( $file_url )
	{
		//First, verify if the file URL is accessible. If not, it will use wp_die() to display the error message.
		//It will also return the file size.
		$file_size = View_Downloads::verify_file_url_accessible( $file_url );

		if( $file_size > 0) {
			//If the file size is available, use the first download method.
			View_Downloads::download_method_1_uses_fopen( $file_url, $file_size );
		}
		else {
			//If the file size is not available, use the second download method (uses curl)
			View_Downloads::download_method_2_uses_curl( $file_url );
		}

	}

	/*
	 * Verify if the file URL is accessible. If not, it will use wp_die() to display the error message.
	 * It will also return the file size if available. If not, it will return 0.
	 */
	public static function verify_file_url_accessible( $file_url ){
		//Uses wp_remote_get() to check if the file exists and the response code is 200.
		$remote_get_args = array(
			'method'      => 'HEAD',
			'timeout'     => 30,
			'redirection' => 5,
			'sslverify'   => false,
		);

		$data = wp_remote_get( $file_url, $remote_get_args );

		if ( is_wp_error( $data ) ) {
			$err = $data->get_error_message();
			wp_die( __( 'Error occurred when trying to fetch the file using wp_remote_get().', 'wp-express-checkout' ) . ' ' . $err );
		}

		// Check if the file exists and the response code is 200.
		if ( $data['response']['code'] !== 200 ) {
			if ( $data['response']['code'] === 404 ) {
				status_header( 404 );
				$err_msg = ( __( "Requested file could not be found (error code 404). Verify the file URL specified in the product configuration.", 'wp-express-checkout' ) );
				Logger::log( $err_msg, false );
				wp_die( $err_msg );
			} else {
				status_header( $data['response']['code'] );
				$err_msg = sprintf( __( 'An HTTP error occurred during file retrieval. Error Code: %s', 'wp-express-checkout' ), $data['response']['code'] );
				Logger::log( $err_msg, false );
				wp_die( $err_msg );
			}
		}

		//Check if the file size is available.
		if( isset( $data['headers']['content-length'] ) ) {
			$file_size = intval( $data['headers']['content-length'] );
		} else {
			$file_size = 0;
		}

		return $file_size;
	}

	public static function download_method_1_uses_fopen( $file_url, $file_size ){
		Logger::log( 'Trying to dispatch file using download method 1 (uses fopen).', true);

		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . basename($file_url) . '"');
		
		// Send Content-Length header only if we have a valid size
		if ( $file_size > 0 ) {
			header('Content-Length: ' . $file_size);
		}
		
		// Clear any output that may have already been sent
		ob_end_clean();
		
		// Open the file for reading
		$fp = @fopen($file_url, 'rb');
		if ($fp) {
			// Set the time limit to 0 to prevent the script from timing out
			set_time_limit(0);
			
			// Send the file in 8KB chunks
			$chunk_size = 8192;
			while (!feof($fp) && ($p = ftell($fp)) <= $file_size) {
				if ($file_size > 0 && $p + $chunk_size > $file_size) {
					// Last chunk
					$chunk_size = $file_size - $p;
				}
				echo fread($fp, $chunk_size);
				flush(); // flush the output buffer
			}
			
			// Close the file pointer
			fclose($fp);
		} else {
			// Handle error if file can't be opened
			header('HTTP/1.1 500 Internal Server Error');
			wp_die( "Unable to open file using fopen()." );
		}
	}

	public static function download_method_2_uses_curl( $file_url ){
		Logger::log( 'Trying to dispatch file using download method 2 (uses cURL).', true);

		if ( !function_exists('curl_init') ) {
			$error_msg = __( 'cURL is not installed on this server. Cannot dispatch the download using cURL method.', 'wp-express-checkout' );
			Logger::log( $error_msg, false);
			wp_die( $error_msg );
		}

		$output_headers = array();
		$output_headers[] = 'Content-Type: application/octet-stream';
		$output_headers[] = 'Content-Disposition: attachment; filename="' . basename($file_url) . '"';
		$output_headers[] = 'Content-Encoding: none';

		foreach ( $output_headers as $header ) {
			header( $header );
		}

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_BINARYTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 5 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 0 );
		curl_setopt( $ch, CURLOPT_URL, $file_url );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
		// curl_setopt( $ch, CURLOPT_WRITEFUNCTION, array( $this, 'stream_handler' ) );

		curl_exec( $ch );
		curl_close( $ch );
	}

}
