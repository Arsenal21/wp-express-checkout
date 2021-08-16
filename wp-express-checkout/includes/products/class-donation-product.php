<?php

namespace WP_Express_Checkout\Products;

class Donation_Product extends Product {

	/**
	 * Product type (one_time, donation, subscription, etc.)
	 * @var  string
	 */
	protected $type = 'donation';

	/**
	 * Retrieves the product price
	 *
	 * @return string
	 */
	public function get_price() {
		return max( $this->post->ppec_product_price, $this->post->wpec_product_min_amount );
	}

}

