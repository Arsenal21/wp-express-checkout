<?php

class WC_Payment_Gateway {
	public function init_settings() {}

	public function get_option( $key, $empty_value = null ) {
		$value = null;
		switch ( $key ) {
			case 'popup_title':
				$value = 'Test modal window title';

				break;

			default:
				break;
		}
		return $value;
	}
}
