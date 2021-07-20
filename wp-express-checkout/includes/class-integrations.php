<?php

namespace WP_Express_Checkout;

class Integrations {

	public function __construct() {
		// Simple Membership integration
		if ( defined( 'SIMPLE_WP_MEMBERSHIP_VER' ) ) {
			new Integrations\Simple_WP_Membership();
		}
		// WP eMember integration
		if ( function_exists( 'wp_emember_install' ) ) {
			new Integrations\Emember();
		}
	}

}
