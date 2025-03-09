<?php

namespace WP_Express_Checkout\Admin;

use WP_Express_Checkout\Main;

class Tools_Admin_Menu {

	public static $instance = null;

	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function display_tools_menu_page() {
		include_once 'views/tools.php';
	}
}