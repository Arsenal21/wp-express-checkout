<?php

namespace WP_Express_Checkout;

/**
 * Order tags generator with HTML output.
 *
 * @since 2.1.0
 */
class Order_Tags_Html {

	/**
	 * @var Order Order object.
	 */
	protected $order;

	/**
	 * Construnct renderer.
	 *
	 * @param Order $order
	 */
	public function __construct( Order $order ) {
		$this->order = $order;
	}

	/**
	 * Order author first name.
	 *
	 * @return string
	 */
	public function first_name() {
		$payer_details = $this->order->get_data( 'payer' );
		return $payer_details['name']['given_name'];
	}

	/**
	 * Order author last name.
	 *
	 * @return string
	 */
	public function last_name() {
		$payer_details = $this->order->get_data( 'payer' );
		return $payer_details['name']['surname'];
	}

	/**
	 * Generates Order Summary Table.
	 *
	 * @return string
	 */
	public function product_details() {
		$table = new Order_Summary_Table( $this->order );
		ob_start();
		$table->show();
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Order author email
	 *
	 * @return string
	 */
	public function payer_email() {
		return $this->order->get_info( 'email' );
	}

	/**
	 * Transaction id.
	 *
	 * @return string
	 */
	public function transaction_id() {
		return $this->order->get_resource_id();
	}

	/**
	 * Order total.
	 *
	 * @return float
	 */
	public function purchase_amt() {
		return $this->order->get_total();
	}

	/**
	 * Order date/time.
	 *
	 * @return string
	 */
	public function purchase_date() {
		return get_post_time( 'F j, Y, g:i a', false, $this->order->get_id() );
	}

	/**
	 * Order currency code.
	 *
	 * @return string
	 */
	public function currency_code() {
		return $this->order->get_currency();
	}

	/**
	 * Coupon code (if used). 0 otherwise.
	 *
	 * @return string
	 */
	public function coupon_code() {
		$coupon_item = $this->order->get_item( 'coupon' );
		return ! empty( $coupon_item['meta']['code'] ) ? $coupon_item['meta']['code'] : '0';
	}

	/**
	 * Shipping address.
	 *
	 * @return string
	 */
	public function address() {
		return $this->order->get_data( 'shipping_address' );
	}

	/**
	 * Order id
	 *
	 * @return int
	 */
	public function order_id() {
		return $this->order->get_id();
	}

	/**
	 * Get Order object
	 *
	 * @return Order
	 */
	public function get_order() {
		return $this->order;
	}

	/**
	 * Generates download links.
	 *
	 * @param array $args {
	 *     Optional. An array of additional parameters.
	 *
	 *     @type string $anchor_text Default 'Click here to download'.
	 *     @type string $target      Default '_blank'.
	 * }
	 *
	 * @return string
	 */
	public function download_link( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'anchor_text' => __( 'Click here to download', 'wp-express-checkout' ),
			'target'      => '_blank',
		) );

		$downloads = View_Downloads::get_order_downloads_list( $this->order->get_id() );
		$content   = '';

		if ( ! $downloads ) {
			return $content;
		}

		$limit    = (int) $this->order->get_data( 'download_count' );
		$counter  = $this->order->get_data( 'downloads_counter' );
		$link_tpl = apply_filters( 'wpec_downloads_list_item_template', '%1$s (%5$s/%6$s) - <a href="%2$s" target="%3$s">%4$s</a><br/>' );
		foreach ( $downloads as $name => $download_url ) {
			$count = ! empty( $counter[ $name ] ) ? $counter[ $name ] : 0;
			$text = ! empty( $args['anchor_text'] ) ? $args['anchor_text'] : $download_url;
			$content .= sprintf( $link_tpl, $name, $download_url, $args['target'], $text, $count, $limit ? $limit : '&infin;' );
		}

		return $content;
	}

	/**
	 * A stub for an order tag that is not defined.
	 *
	 * @param string $name      Method to call.
	 * @param array  $arguments Arguments to pass when calling.
	 */
	public function __call( $name, $arguments ) {
		$arguments['renderer'] = $this;
		return apply_filters( 'wpec_render_custom_order_tag', null, $name, $arguments );
	}

}
