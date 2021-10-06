<?php

class WC_Order {
	public $status;

	public function update_status( $status ) {
		$this->status = $status;
	}

	public function get_checkout_payment_url() {
		return 'http://example/test_checkout_payment_url';
	}
}
