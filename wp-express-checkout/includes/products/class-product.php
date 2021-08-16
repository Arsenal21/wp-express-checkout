<?php

namespace WP_Express_Checkout\Products;

use WP_Express_Checkout\Variations;
use WP_Post;

/**
 * Represents an abstract Product.
 *
 * @since 2.1.1
 */
abstract class Product {

	/**
	 * Product type (one_time, donation, subscription, etc.)
	 * @var  string
	 */
	protected $type = '';

	/**
	 * WordPress post object representation.
	 *
	 * @var WP_Post
	 */
	protected $post;

	/**
	 * Sets up the product objects
	 *
	 * @param WP_Post $post Post object returned from get_post()
	 */
	public function __construct( $post ) {
		$this->post = $post;

		if ( empty( $this->type ) ) {
			$this->type = $this->post->wpec_product_type;
		}
	}

	/**
	 * Retrieves the product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Returns the Product ID
	 * @return int Product ID
	 */
	public function get_id() {
		return (int) $this->post->ID;
	}

	/**
	 * Retrieves the PayPal product resource ID.
	 *
	 * @return string
	 */
	public function get_resource_id() {
		return $this->post->wpec_product_resource_id;
	}

	/**
	 * Sets the PayPal product resource ID.
	 *
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
	 * Retrieves the product price
	 *
	 * @return string
	 */
	abstract public function get_price();

	/**
	 * Retrieves the default product quantity.
	 *
	 * @return int
	 */
	public function get_quantity() {
		return (int) $this->post->ppec_product_quantity;
	}

	/**
	 * Whether customers allowed to specify quantity.
	 *
	 * @return bool
	 */
	public function is_custom_quantity() {
		return (bool) $this->post->ppec_product_custom_quantity;
	}

	/**
	 * Retrieves the product download URL
	 *
	 * @return string
	 */
	public function get_download_url() {
		return $this->post->ppec_product_upload;
	}

	/**
	 * Retrieves the product thumbnal URL
	 *
	 * @return string
	 */
	public function get_thumbnail_url() {
		return $this->post->wpec_product_thumbnail;
	}

	/**
	 * Reteives the shipping cost.
	 *
	 * @return string
	 */
	public function get_shipping() {
		return $this->post->wpec_product_shipping;
	}

	/**
	 * Whether product type is physical.
	 *
	 * @return bool
	 */
	public function is_physical() {
		return (bool) $this->post->wpec_product_shipping_enable;
	}

	/**
	 * Retrieves the tax percentage.
	 *
	 * @return string
	 */
	public function get_tax() {
		return $this->post->wpec_product_tax;
	}

	/**
	 * Retrieves the Thank You page URL.
	 *
	 * @return string
	 */
	public function get_thank_you_url() {
		return $this->post->wpec_product_thankyou_page;
	}

	/**
	 * Retrieves the button text.
	 *
	 * @return string
	 */
	public function get_button_text() {
		return $this->post->wpec_product_button_text;
	}

	/**
	 * Retrieves the button type.
	 *
	 * @return string
	 */
	public function get_button_type() {
		return $this->post->wpec_product_button_type;
	}

	/**
	 * Retrieves the coupons apply option.
	 *
	 * @return string
	 */
	public function get_coupons_setting() {
		return $this->post->wpec_product_coupons_setting;
	}

	/**
	 * Retrieves Product Variations
	 *
	 * @return array
	 */
	public function get_variations() {
		$v          = new Variations( $this->get_id() );
		$variations = $v->variations;
		$variations['groups'] = $v->groups;

		return $variations;
	}

}
