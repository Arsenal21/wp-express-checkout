<?php
/**
 * Download request handler.
 *
 * Views the requests for the `wpec_download_file` parameter, once one given,
 * triggers the product download process.
 */

/**
 * Download request class.
 */
class WPEC_View_Download {

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
		if ( isset( $_GET['wpec_download_file'] ) && $this->verify_request() ) {
			$this->process_download();
		}
	}

	/**
	 * Retrieves the instance.
	 *
	 * @return WPEC_View_Download
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
	 */
	public static function get_download_url( $order_id ) {
		$download_url = '';

		$order = get_post_meta( $order_id, 'ppec_payment_details', true );

		if ( $order && ! empty( $order['item_id'] ) ) {
			$order_timestamp = get_the_time( 'U', $order_id );

			$product_id = (int) $order['item_id'];
			$product    = get_post( $product_id );

			if ( empty( $product ) || PPECProducts::$products_slug !== $product->post_type || ! $product->ppec_product_upload ) {
				return $download_url;
			}

			$key  = "{$product_id}|{$order_id}|{$order_timestamp}";
			$hash = substr( wp_hash( $key ), 0, 20 );

			$download_url = add_query_arg(
				array(
					'wpec_download_file' => $product_id,
					'order_id'           => $order_id,
					'key'                => $hash,
				),
				home_url( '/' )
			);

		}

		return $download_url;
	}

	/**
	 * Retrieves secure downloads page URL for given order
	 *
	 * @param int $order_id The order id.
	 */
	public static function get_downloads_page_url( $order_id ) {
		$wpec_plugin = WPEC_Main::get_instance();

		$download_url       = '';
		$downloads_page_url = $wpec_plugin->get_setting( 'downloads_url' );

		if ( ! wp_http_validate_url( $downloads_page_url ) ) {
			return $download_url;
		}

		$order = get_post_meta( $order_id, 'ppec_payment_details', true );

		if ( ! $order || 'COMPLETED' !== $order['state'] || empty( $order['item_id'] ) ) {
			return $download_url;
		}

		$order_timestamp = get_the_time( 'U', $order_id );

		$product_id = (int) $order['item_id'];
		$product    = get_post( $product_id );

		if ( empty( $product ) || PPECProducts::$products_slug !== $product->post_type ) {
			return $download_url;
		}

		$downloads = self::get_order_downloads( $order_id );

		if ( empty( $downloads ) ) {
			return $download_url;
		}

		$key  = "{$order_id}|{$order_timestamp}";
		$hash = substr( wp_hash( $key ), 0, 20 );

		$download_url = add_query_arg(
			array(
				'wpec_downloads_key' => $hash,
				'order_id'           => $order_id,
			),
			$downloads_page_url
		);

		return $download_url;
	}

	/**
	 * Retrieves downloads list for a given order.
	 *
	 * @param int $order_id The order id.
	 * @return array
	 */
	public static function get_order_downloads( $order_id ) {
		$downloads = array();
		$order     = get_post_meta( $order_id, 'ppec_payment_details', true );

		if ( ! $order || empty( $order['item_id'] ) ) {
			return $downloads;
		}

		$product_id = (int) $order['item_id'];
		$product    = get_post( $product_id );

		if ( empty( $product ) ) {
			return $downloads;
		}

		if ( ! empty( $product->ppec_product_upload ) ) {
			$downloads[ $order['item_name'] ] = $product->ppec_product_upload;
		}

		$var_applied = $order['var_applied'];

		if ( ! empty( $var_applied ) ) {
			foreach ( $var_applied as $var ) {
				if ( ! empty( $var['url'] ) ) {
					$downloads[ $var['group_name'] . ' - ' . $var['name'] ] = $var['url'];
				}
			}
		}

		return $downloads;
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
		$order    = get_post_meta( $order_id, 'ppec_payment_details', true );

		if ( empty( $order ) ) {
			wp_die( esc_html__( 'Invalid Order ID!', 'wp-express-checkout' ) );
		}

		$product = get_post( absint( $_GET['wpec_download_file'] ) );

		if ( empty( $product ) || PPECProducts::$products_slug !== $product->post_type || $order['item_id'] !== $product->ID ) {
			wp_die( esc_html__( 'Invalid product ID!', 'wp-express-checkout' ) );
		}

		if ( ! $product->ppec_product_upload ) {
			wp_die( esc_html__( 'The product has no file for download!', 'wp-express-checkout' ) );
		}

		$order_timestamp = get_the_time( 'U', $order_id );

		$key  = "{$product->ID}|{$order_id}|{$order_timestamp}";
		$hash = substr( wp_hash( $key ), 0, 20 );

		if ( $_GET['key'] !== $hash ) {
			wp_die( esc_html__( 'Invalid product key!', 'wp-express-checkout' ) );
		}

		return apply_filters( 'wpec_verify_download_product_request', true, $order, $product );
	}

	/**
	 * Processes the product download. This function is called AFTER the download request has been verified via the verify_request() call.
	 */
	private function process_download() {

		// Get the product custom post type object.
		$product = get_post( absint( $_GET['wpec_download_file'] ) );

		// Trigger the action hook (product object is also passed). It can be usewd to override the download handling via an addon.
		do_action( 'wpec_process_download_request', $product );

		// Clean the file URL.
		$file_url = stripslashes( trim( $product->ppec_product_upload ) );

		WPEC_Utility_Functions::redirect_to_url( $file_url );

	}

}
