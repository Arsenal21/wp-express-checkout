<?php

namespace WP_Express_Checkout\Products;

class Stub_Product extends Product {

	/**
	 * Retrieves the product type.
	 *
	 * @return string
	 */
	public function get_type() {
		$error_msg = sprintf( '<strong>' . __( 'A product type "%s" is not registered. Please activate the appropriate addon to use this product.', 'wp-express-checkout' )  . '</strong>', $this->post->wpec_product_type );
		return $error_msg;
	}

	/**
	 * Retrieves the product price
	 *
	 * @return string
	 */
	public function get_price() {
		return 0;
	}

}

