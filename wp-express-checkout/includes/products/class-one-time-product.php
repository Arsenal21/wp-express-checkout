<?php

namespace WP_Express_Checkout\Products;

class One_Time_Product extends Product {

	/**
	 * Product type (one_time, donation, subscription, etc.)
	 * @var  string
	 */
	protected $type = 'one_time';

	/**
	 * Retrieves the product price
	 *
	 * @return string
	 */
	public function get_price() {
		return $this->post->ppec_product_price;
	}

}

