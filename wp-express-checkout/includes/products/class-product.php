<?php

namespace WP_Express_Checkout\Products;

use WP_Post;

/**
 * Represents an abstract Product.
 *
 * @since 2.0.1
 */
abstract class Product {

	/**
	 * Product ID, defined by WordPress when
	 * creating Product
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Product ID, defined by PayPal when Product has been created
	 * @var string
	 */
	protected $resource_id = '';

	/**
	 * Product type (one-time, subscription, etc.)
	 * @var  string
	 */
	protected $type = '';

	/**
	 * Sets up the product objects
	 *
	 * @param WP_Post $post Post object returned from get_post()
	 */
	public function __construct( $post ) {
		$this->id = $post->ID;

		$meta_fields = get_post_custom( $post->ID );
		$this->resource_id = $this->get_meta_field( 'wpec_product_resource_id', '', $meta_fields );
		$this->type        = $this->get_meta_field( 'wpec_product_type', '', $meta_fields );
	}

	/**
	 * Returns the Product ID
	 * @return int Product ID
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Retrieves the PayPal product resource ID.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_resource_id() {
		return $this->resource_id;
	}

	/**
	 * Sets the PayPal product resource ID.
	 *
	 * @since 2.0.0

	 * @param string $resource_id Resource ID used to identify the currency.
	 * @return boolean True if resource ID was changed
	 */
	public function set_resource_id( $resource_id ) {

		if ( ! is_string( $resource_id ) ) {
			trigger_error( 'Resource ID must be string', E_USER_WARNING );
		}

		$this->resource_id = $resource_id;
		$this->update_meta( 'wpec_product_resource_id', $this->resource_id );
		return true;
	}

	/**
	 * Updates the product's post data
	 * @param $args array Array of values to update. See wp_update_post.
	 */
	protected function update_post( $args ) {

		$defaults = array(
			'ID' => $this->get_id()
		);

		wp_update_post( array_merge( $defaults, $args ) );
	}

	/**
	 * Updates the meta fields for the post
	 * @param $meta_key    string|array Can either be the meta field to be updated, or an associative array
	 * 					of meta keys and values to be updated
	 * @param $meta_value  string	    Value to set the meta value to. Ignored if meta_key is an array
	 * @param $reset_cache boolean      Whether or not to update the cache after updating. Used to limit
	 * 					larges amounts of updates
	 */
	protected function update_meta( $meta_key, $meta_value = '' ) {

		if ( is_array( $meta_key ) ) {
			foreach ( $meta_key as $key => $value ) {
				$this->update_meta( $key, $value, false );
			}
			return;
		}

		update_post_meta( $this->id, $meta_key, $meta_value );
	}

	/**
	 * Returns the URL for an product. Useful for getting the URL
	 * without building the product.
	 * @param int $product_id Product ID
	 * @return string URL for the product
	 */
	static public function get_url( $product_id ) {
		if ( ! is_numeric( $product_id ) ) {
			trigger_error( 'Invalid product id given. Must be an integer', E_USER_WARNING );
		}
		return apply_filters( 'wpec_product_return_url', get_permalink( $product_id ) );
	}

	private function get_meta_field( $field, $default, $fields ) {
		if ( isset( $fields[$field] ) ) {
			return $fields[$field][0];
		} else {
			return $default;
		}
	}

}
