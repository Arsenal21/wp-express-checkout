<?php
/**
 * Download request handler.
 *
 * Views the requests for the `wpec_download_file` parameter, once one given,
 * triggers the product download process.
 */

namespace WP_Express_Checkout;

use Exception;

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
		// It can be used to override the download handling via an addon.
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

		if ( empty( $counter[ $file_name ] ) ) {
			$counter[ $file_name ] = 0;
		}

		$counter[ $file_name ]++;
		$order->add_data( 'downloads_counter', $counter );

		// Clean the file URL.
		$file_url = stripslashes( trim( $file_url ) );

		Utils::redirect_to_url( $file_url );

	}

}
