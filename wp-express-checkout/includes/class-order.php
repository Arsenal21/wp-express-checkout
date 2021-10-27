<?php

namespace WP_Express_Checkout;

/**
 * Represents a purchase order made up of items.
 *
 * @since 1.9.5
 */
class Order {

	/**
	 * Order ID, defined by WordPress when
	 * creating Order
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Order ID, defined by PayPal when Order has been created
	 * @var string
	 */
	protected $resource_id = '0';

	/**
	 * Parent order id for child order
	 * @var int
	 */
	protected $parent = 0;

	/**
	 * Order description
	 * @var  string
	 */
	protected $description = '';

	/**
	 * Information on the creator of the order
	 * @var array
	 */
	protected $creator = array(
		'user_id' => 0,
		'ip_address' => 0,
	);

	/**
	 * Information on the payment amount and method
	 * @var array
	 */
	protected $payment = array(
		'total'    => 0,
		'currency' => 'USD',
	);

	/**
	 * State of the order.
	 * @var string
	 */
	protected $state = 'incomplete';

	/**
	 * List of items in the current order
	 * @var array
	 */
	protected $items = array();

	/**
	 * Additional information for the order
	 * @var array
	 */
	protected $data = array();

	/**
	 * Sets up the order objects
	 *
	 * @param object $post Post object returned from get_post()
	 */
	public function __construct( $post ) {

		$this->id          = $post->ID;
		$this->parent      = $post->post_parent;
		$this->description = $post->post_title;

		$meta_fields = get_post_custom( $post->ID );

		$this->creator['user_id']    = $post->post_author;
		$this->creator['ip_address'] = $this->get_meta_field( 'wpec_ip_address', 0, $meta_fields );
		$this->creator['email']      = $this->get_meta_field( 'wpec_order_customer_email', '', $meta_fields );
		$this->payment['currency']   = $this->get_meta_field( 'wpec_currency', 'USD', $meta_fields );

		$this->state = $this->get_meta_field( 'wpec_order_state', 'incomplete', $meta_fields );

		$this->items = array_filter( (array) get_post_meta( $this->id, 'wpec_order_items', true ) );
		$this->data  = array_filter( (array) get_post_meta( $this->id, 'wpec_order_data', true ) );

		$this->resource_id = $this->get_meta_field( 'wpec_order_resource_id', '', $meta_fields );

		$this->refresh_total();

	}

	public function get_info( $part = '' ){

		$basic = array(
			'id'          => $this->id,
			'parent'      => $this->parent,
			'description' => $this->description,
			'state'       => $this->state
		);

		$fields = array_merge( $basic, $this->creator, $this->payment );

		if ( empty( $part ) ) {
			return $fields;
		} else {
			return $fields[ $part ];
		}

	}

	/**
	 * Returns the Order ID
	 * @return int Order ID
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Returns the Order description
	 * @return string The order description
	 */
	public function get_description() {
		return $this->description;
	}

	public function set_description( $description ){

		if( ! is_string( $description ) ) {
			trigger_error( 'Description must be a string.', E_USER_WARNING );
		}

		$this->description = $description;
		$this->update_post( array(
			'post_title' => $description
		) );

	}

	/**
	 * Adds an item to the order.
	 *
	 * @param string $type     A string representing the type of item being added
	 * @param string $name     A string representing the name of item being added
	 * @param float  $price    The price of the item
	 * @param int    $quantity The quantity of the item
	 * @param int    $post_id  The post that this item affects
	 * @param bool   $unique   (optional) Is the item unique per order
	 * @param array  $meta     (optional) Additional meta fields
	 *
	 * @return bool True if the item has been added, False otherwise
	 */
	public function add_item( $type, $name, $price, $quantity = 1, $post_id = 0, $unique = false, $meta = array() ) {

		if ( empty( $post_id ) ) {
			$post_id = $this->get_id();
		}

		if ( ! is_numeric( $post_id ) ) {
			return ! trigger_error( 'Post ID must be an integer', E_USER_WARNING );
		}

		if ( ! is_numeric( $quantity ) ) {
			return ! trigger_error( 'Quantity must be an integer', E_USER_WARNING );
		}

		if ( ! is_numeric( $price ) ) {
			return ! trigger_error( 'Price must be numeric', E_USER_WARNING );
		}

		if ( ! is_string( $type ) && ! is_int( $type ) ) {
			return ! trigger_error( 'Item Type must be a string or integer', E_USER_WARNING );
		}

		if ( ! is_array( $meta ) ) {
			return ! trigger_error( 'Item Meta must be an array', E_USER_WARNING );
		}

		if ( $unique ) {
			$this->remove_item( $type );
		}

		$this->items[] = array(
			'type'     => $type,
			'name'     => $name,
			'price'    => (float) $price,
			'quantity' => (int) $quantity,
			'post_id'  => (int) $post_id,
			'meta'     => $meta,
		);

		$this->update_meta( 'wpec_order_items', $this->items );
		$this->refresh_total();

		return true;
	}

	/**
	 * Removes an item or items from the order. Removes all items that match the criteria
	 *
	 * @param string $type (optional) A string representing the type of item to remove
	 * @param int $price (optional)   The price of the item being removed
	 * @param int $post_id (optional) The post that this item affects
	 *
	 * @return int|bool Quantity of items removed. Boolean False on failure
	 */
	public function remove_item( $type = '', $price = 0, $post_id = 0 ) {

		if ( ! empty( $post_id ) && ! is_numeric( $post_id ) ) {
			return ! trigger_error( 'Post ID must be an integer', E_USER_WARNING );
		}

		if ( ! empty( $price ) && ! is_numeric( $price ) ) {
			return ! trigger_error( 'Price must be numeric', E_USER_WARNING );
		}

		if ( ! empty( $type ) && ! is_string( $type ) && ! is_int( $type ) ) {
			return ! trigger_error( 'Item Type must be a string or integer', E_USER_WARNING );
		}

		$removed = 0;
		foreach ( $this->items as $key => $item ) {

			if ( ! empty( $type ) && $item['type'] != $type ) {
				continue;
			}

			if ( ! empty( $price ) && $item['price'] != $price ) {
				continue;
			}

			if ( ! empty( $post_id ) && $item['post_id'] != $post_id ) {
				continue;
			}

			unset( $this->items[ $key ] );
			$removed++;
		}

		$this->update_meta( 'wpec_order_items', $this->items );
		$this->refresh_total();

		return $removed;
	}

	/**
	 * Returns the first item in an order, or another as specified
	 * @param  integer $index The index number of the item to return
	 * @return array          An associative array of information about the item
	 */
	public function get_item( $type = '', $index = 0 ) {

		if( is_integer( $type ) ){
			$index = $type;
			$type = '';
		}

		$items = $this->get_items( $type );

		if ( isset( $items[ $index ] ) ) {
			return $items[ $index ];
		} else {
			return false;
		}
	}

	/**
	 * Returns an array of all the items in an order that match a given
	 * type, or all items in the order.
	 *
	 * @param  string $type (optional) Item Type to filter by
	 * @return array        An array of items matching the criteria
	 */
	public function get_items( $type = '' ) {

		if ( empty( $type ) ) {
			return $this->items;
		}

		if( ! is_string( $type ) && ! is_int( $type ) ) {
			trigger_error( 'Item type must be a string or integer.', E_USER_WARNING );
		}

		$results = array();
		foreach ( $this->items as $item ) {
			if ( $item['type'] == $type ) {
				$results[] = $item;
			}
		}

		return $results;
	}

	/**
	 * Recalculates the total of the order.
	 * See get_total() for results
	 * @return void
	 */
	protected function refresh_total() {

		$this->payment['total'] = 0;
		foreach ( $this->items as $item ) {
			$this->payment['total'] += (float) $item['price'] * (int) $item['quantity'];
		}

		if ( $this->payment['total'] < 0 ) {
			$this->payment['total'] = 0;
		}

		$total = get_post_meta( $this->id, 'wpec_total_price', true );

		if ( $total != $this->payment['total'] ) {
			$this->update_meta( 'wpec_total_price', $this->payment['total'] );
		}
	}

	/**
	 * Returns the total price of the order
	 * @return int Total price of the order
	 */
	public function get_total() {
		$ppdg = Main::get_instance();
		$prec = ( ! $ppdg->get_setting( 'price_decimals_num' ) ) ? 0 : $ppdg->get_setting( 'price_decimals_num' );
		return number_format( (float) $this->payment['total'], $prec, '.', '' );
	}

	/**
	 * Sets the currency to be used in this order.
	 * Changing this does not affect any of the prices used in the order
	 * @param string $currency_code Currency code used to identify the currency.
	 * @return boolean True if currency was changed, false on error
	 */
	public function set_currency( $currency_code ) {

		if( ! is_string( $currency_code ) ) {
			trigger_error( 'Currency code must be string', E_USER_WARNING );
		}

		$this->payment['currency'] = $currency_code;
		$this->update_meta( 'wpec_currency', $this->payment['currency'] );
		return true;
	}

	/**
	 * Returns the current currency's code. See WPEC_Currency
	 * @return string Current currency's code
	 */
	public function get_currency() {
		return $this->payment['currency'];
	}

	/**
	 * Returns the current state of the order
	 *
	 * @return string State of the order.
	 */
	public function get_status() {
		return $this->state;
	}

	/**
	 * Returns a version of the current state for display.
	 *
	 * @return string Current state, localized for display
	 */
	public function get_display_status() {
		$statuses = array(
			'incomplete' => __( 'Incomplete', 'wp-express-checkout' ),
			'paid'       => __( 'Paid', 'wp-express-checkout' ),
		);
		$status = $this->get_status();
		return $statuses[ $status ];
	}

	/**
	 * Sets the order statues and sends out correct action hooks.
	 * New order status must be different than old status
	 *
	 * @param string $status Valid status for order.
	 */
	public function set_status( $status ) {

		if ( $this->state === $status ) {
			return;
		}

		$this->state = $status;
		$this->update_meta( 'wpec_order_state', $status );

		do_action( 'wpec_transaction_' . $status, $this );
	}

	/**
	 * Adds an order author by ID.
	 *
	 * @param int $author_id
	 */
	public function set_author( $author_id ){

		$author_id = intval( $author_id );

		$this->creator['user_id'] = $author_id;
		$this->update_post( array(
			'post_author' => $author_id,
		) );

	}

	/**
	 * Adds an order author email address
	 *
	 * @param string $email
	 */
	public function set_author_email( $email ){
		if ( ! is_email( $email ) && ! empty( $email ) ) {
			trigger_error( 'Author Email must be a valid email address', E_USER_WARNING );
		}

		$this->creator['email'] = $email;
		$this->update_meta( 'wpec_order_customer_email', $email );
	}

	/**
	 * Returns the User ID of the creator of the order
	 * @return int User ID
	 */
	public function get_author() {
		return $this->creator['user_id'];
	}

	/**
	 * Returns the IP Address used to create the order
	 * @return string IP Address
	 */
	public function get_ip_address() {
		return $this->creator['ip_address'];
	}

	/**
	 * Returns the URL to redirect to for processing the order
	 * @return string URL
	 */
	public function get_return_url() {
		return self::get_url( $this->id );
	}

	/**
	 * Returns the URL to redirect to for creating a new purchase
	 * @return string URL
	 */
	public function get_cancel_url() {
		return add_query_arg( "cancel", 1, $this->get_return_url() );
	}

	/**
	 * Returns the id of the parent post
	 * @return id the post parent
	 */
	public function get_parent(){
		return $this->parent;
	}

	/**
	 * Retrieves the PayPal order resource ID.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_resource_id() {
		return $this->resource_id;
	}

	/**
	 * Sets the PayPal order resource ID.
	 *
	 * @since 2.0.0

	 * @param string $resource_id Resource ID used to identify the currency.
	 * @return boolean True if resource ID was changed
	 */
	public function set_resource_id( $resource_id ) {

		if( ! is_string( $resource_id ) ) {
			trigger_error( 'Resource ID must be string', E_USER_WARNING );
		}

		$this->resource_id = $resource_id;
		$this->update_meta( 'wpec_order_resource_id', $this->resource_id );
		return true;
	}

	/**
	 * Adds data to the Order.
	 *
	 * @param string $key The key to store the data
	 * @param string $value The value.
	 */
	public function add_data( $key, $value ){
		$this->data[ $key ] = $value;
		$this->update_meta( 'wpec_order_data', $this->data );
	}

	/**
	 * Retrieves a piece of data added to the order via add_data()
	 *
	 * @param string $key The key to retrieve the data from.
	 * @return mixed The value for the given key, or all data present on the order
	 */
	public function get_data( $key = '' ) {

		if ( empty( $key ) ) {
			return $this->data;
		} elseif( ! empty( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		}
		return false;

	}

	/**
	 * Adds all order data tags to the order content field for using in search.
	 */
	public function generate_search_index() {
		$renderer = new Order_Tags_Plain( $this );
		$tags     = array_keys( Utils::get_dynamic_tags_white_list() );

		foreach ( $tags as $tag ) {
			$args[ $tag ] = $renderer->$tag();
		}

		$template = "{first_name} {last_name}\n";

		$post_content = Utils::apply_dynamic_tags( $template, $args );
		foreach ( $tags as $tag ) {
			$post_content .= $tag . ':' . $renderer->$tag() . "\n";
		}

		$this->update_post( array( 'post_content' => $post_content ) );
	}

	/**
	 * Updates the order's post data
	 * @param $args array Array of values to update. See wp_update_post.
	 */
	protected function update_post( $args ){

		$defaults = array(
			'ID' => $this->get_id()
		);

		wp_update_post( array_merge( $defaults, $args ) );
	}

	/**
	 * Updates the meta fields for the post
	 * @param $meta_key    string       Meta field to be updated
	 * @param $meta_value  string	    Value to set the meta value to. Ignored if meta_key is an array
	 * @param $reset_cache boolean      Whether or not to update the cache after updating. Used to limit
	 * 					larges amounts of updates
	 */
	protected function update_meta( $meta_key, $meta_value = ''){
		update_post_meta( $this->id, $meta_key, $meta_value );
	}

	/**
	 * Returns the URL for an order. Useful for getting the URL
	 * without building the order.
	 * @param int $order_id Order ID
	 * @return string URL for the order
	 */
	static public function get_url( $order_id ){
		if( !is_numeric( $order_id ) )
			trigger_error( 'Invalid order id given. Must be an integer', E_USER_WARNING );
		return apply_filters( 'wpec_order_return_url', get_permalink( $order_id ) );
	}

	private function get_meta_field( $field, $default, $fields ){
		if( isset( $fields[ $field ] ) ) {
			return $fields[ $field ][0];
		} else {
			return $default;
		}
	}

}
