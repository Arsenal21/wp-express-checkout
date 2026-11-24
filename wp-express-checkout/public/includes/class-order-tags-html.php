<?php

namespace WP_Express_Checkout;

/**
 * Order tags generator with HTML output.
 *
 * The values come from the order (much of it entered by the buyer), so they are escaped for HTML here.
 * The plain text generator (Order_Tags_Plain, used for the emails) returns them unescaped.
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
	 * Prepares a text value for output: escaped for HTML.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string
	 */
	protected function output( $value ) {
		return esc_html( (string) $value );
	}

	/**
	 * Prepares the output of a custom order tag (added with the 'wpec_render_custom_order_tag' filter):
	 * HTML is allowed, but not scripts or event handler attributes.
	 *
	 * @param mixed $value The value.
	 *
	 * @return mixed
	 */
	protected function output_custom_tag( $value ) {
		return is_scalar( $value ) ? wp_kses_post( (string) $value ) : $value;
	}

	/**
	 * Order author first name.
	 *
	 * @return string
	 */
	public function first_name() {
		$payer_details = $this->order->get_data( 'payer' );
		return $this->output( isset( $payer_details['name']['given_name'] ) ? $payer_details['name']['given_name'] : '' );
	}

	/**
	 * Order author last name.
	 *
	 * @return string
	 */
	public function last_name() {
		$payer_details = $this->order->get_data( 'payer' );
		return $this->output( isset( $payer_details['name']['surname'] ) ? $payer_details['name']['surname'] : '' );
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
		return $this->output( $this->order->get_info( 'email' ) );
	}

	/**
	 * Order author email
	 *
	 * @return string
	 */
	public function payer_phone() {
		return $this->output( $this->order->get_phone() );
	}

	/**
	 * Transaction id.
	 *
	 * @return string
	 */
	public function transaction_id() {
		return $this->output( $this->order->get_capture_id() );
	}

	/**
	 * Order total.
	 *
	 * @return float
	 */
	public function purchase_amt() {
		return $this->output( $this->order->get_total() );
	}

	/**
	 * Order date/time.
	 *
	 * @return string
	 */
	public function purchase_date() {
		return $this->output( get_post_time( 'F j, Y, g:i a', false, $this->order->get_id() ) );
	}

	public function quantity_ordered() {
		return $this->output( $this->order->get_quantity_ordered() );
	}

	public function selected_variations() {
		return $this->output( $this->order->get_selected_variations_string() );
	}

	/**
	 * Order currency code.
	 *
	 * @return string
	 */
	public function currency_code() {
		return $this->output( $this->order->get_currency() );
	}

	/**
	 * Coupon code (if used). 0 otherwise.
	 *
	 * @return string
	 */
	public function coupon_code() {
		$coupon_item = $this->order->get_item( 'coupon' );
		return $this->output( ! empty( $coupon_item['meta']['code'] ) ? $coupon_item['meta']['code'] : '0' );
	}

	/**
	 * Shipping address.
	 *
	 * @return string
	 */
	public function address() {
		return $this->output( $this->order->get_shipping_address() );
	}

	/**
	 * Order id
	 *
	 * @return int
	 */
	public function order_id() {
		return $this->output( $this->order->get_id() );
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
			$content .= sprintf( $link_tpl, esc_html( $name ), esc_url( $download_url ), esc_attr( $args['target'] ), esc_html( $text ), (int) $count, $limit ? $limit : '&infin;' );
		}

		return $content;
	}

	/**
	 * Product id
	 *
	 * @return int
	 */
	public function product_id() {
		$product_item = $this->order->get_item( 'ppec-products' );
		return $this->output( ! empty( $product_item['post_id'] ) ? $product_item['post_id'] : 0 );
	}

	/**
	 * Product name
	 *
	 * @return string
	 */
	public function product_name() {
		$product_item = $this->order->get_item( 'ppec-products' );
		$product      = ! empty( $product_item['post_id'] ) ? get_post( $product_item['post_id'] ) : null;
		return $this->output( $product ? $product->post_title : '' );
	}

	/**
	 * A stub for an order tag that is not defined.
	 *
	 * @param string $name      Method to call.
	 * @param array  $arguments Arguments to pass when calling.
	 */
	public function __call( $name, $arguments ) {
		$arguments['renderer'] = $this;
		return $this->output_custom_tag( apply_filters( 'wpec_render_custom_order_tag', null, $name, $arguments ) );
	}

}
