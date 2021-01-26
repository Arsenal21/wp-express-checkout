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
	 * @var WPEC_View_Download
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
	 * @param int $grp_id   The variation group ID.
	 * @param int $var_id   The variation ID in the group.
	 */
	public static function get_download_url( $order_id, $grp_id = '', $var_id = '' ) {
		$download_url = '';

		$order = OrdersWPEC::retrieve( $order_id );

		if ( ! $order ) {
			return $download_url;
		}

		$product_item = $order->get_item( PPECProducts::$products_slug );
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
		$order     = OrdersWPEC::retrieve( $order_id );

		if ( ! $order ) {
			return $downloads;
		}

		$product_item = $order->get_item( PPECProducts::$products_slug );
		$product      = get_post( $product_item['post_id'] );

		if ( empty( $product ) ) {
			return $downloads;
		}

		if ( ! empty( $product->ppec_product_upload ) ) {
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
	 * Checks whether a download request is valid.
	 *
	 * @return boolean
	 */
	private function verify_request() {

		if ( empty( $_GET['wpec_download_file'] ) || empty( $_GET['order_id'] ) || empty( $_GET['key'] ) ) {
			wp_die( esc_html__( 'Invalid download URL!', 'wp-express-checkout' ) );
		}

		$order_id = absint( $_GET['order_id'] );
		$order    = OrdersWPEC::retrieve( $order_id );

		if ( empty( $order ) ) {
			wp_die( esc_html__( 'Invalid Order ID!', 'wp-express-checkout' ) );
		}

		$product = get_post( absint( $_GET['wpec_download_file'] ) );
		$item    = $order->get_item( PPECProducts::$products_slug );

		if ( empty( $product ) || (int) $item['post_id'] !== $product->ID ) {
			wp_die( esc_html__( 'Invalid product ID!', 'wp-express-checkout' ) );
		}

		if ( ! $product->ppec_product_upload && ( ! isset( $_GET['var_id'] ) || ! isset( $_GET['grp_id'] ) ) ) {
			wp_die( esc_html__( 'The product has no file for download!', 'wp-express-checkout' ) );
		}

		$order_timestamp = get_the_time( 'U', $order_id );

		$var_args = array();
		$var_key  = '';

		// Add variation parameters to the hash.
		if ( isset( $_GET['var_id'] ) && isset( $_GET['grp_id'] ) ) {
			$var_args = array(
				'grp_id' => (int) $_GET['grp_id'],
				'var_id' => (int) $_GET['var_id'],
			);
			$var_key  = "|{$var_args['grp_id']}|{$var_args['var_id']}";
		}

		$key  = "{$product->ID}|{$order_id}|{$order_timestamp}" . $var_key;
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
		$product  = get_post( absint( $_GET['wpec_download_file'] ) );
		$order_id = absint( $_GET['order_id'] );
		$order    = OrdersWPEC::retrieve( $order_id );
		$file_url = '';

		// Trigger the action hook (product object is also passed). It can be usewd to override the download handling via an addon.
		do_action( 'wpec_process_download_request', $product, $order_id );

		if ( isset( $_GET['var_id'] ) && isset( $_GET['grp_id'] ) ) {
			$var_applied = $order->get_items( 'variation' );
			$var_applied = wp_list_pluck( $var_applied, 'meta' );
			$variation   = wp_list_filter( $var_applied, array( 'grp_id' => $_GET['grp_id'], 'id' => $_GET['var_id'] ) );

			if ( ! empty( $variation ) ) {
				$variation = array_shift( $variation );
				if ( ! empty( $variation['url'] ) ) {
					$file_url = $variation['url'];
				}
			}
		} else {
			$file_url = $product->ppec_product_upload;
		}

		// Clean the file URL.
		$file_url = stripslashes( trim( $file_url ) );

		WPEC_Utility_Functions::redirect_to_url( $file_url );

	}

}
