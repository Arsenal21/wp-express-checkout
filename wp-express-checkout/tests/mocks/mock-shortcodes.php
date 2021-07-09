<?php

namespace WP_Express_Checkout;

class Mock_Shortcodes extends Shortcodes {

	public function generate_pp_express_checkout_button( $atts ) {
		return serialize( $atts );
	}

	public static function locate_template( $template_name ) {
		$located = __DIR__ . "/mock-$template_name";
		return $located;
	}

}
