<?php
class WooCommerce {
	public function api_request_url( $param ) {}
}

function WC() {
	return new WooCommerce();
}
