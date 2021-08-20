<?php

namespace WP_Express_Checkout\Admin;

use WP_Express_Checkout\Main;
use WP_Express_Checkout\Products;
use WP_Express_Checkout\Admin\Admin;

class Tools extends Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    2.1.2
	 *
	 * @var      object
	 */
	private static $instance = null;

	/**
	 * Return an instance of this class.
	 *
	 * @since     2.1.2
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     2.1.2
	 */
	public function enqueue_admin_styles() {
		//$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		//wp_enqueue_style( $this->plugin_slug . '-admin-styles', WPEC_PLUGIN_URL . "/assets/css/admin{$min}.css", array(), WPEC_PLUGIN_VER );
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     2.1.2
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		/*if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix === $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', WPEC_PLUGIN_URL . '/assets/js/admin.js', array( 'jquery' ), WPEC_PLUGIN_VER );
		}*/
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    2.1.2
	 */
	public function add_plugin_admin_menu() {

		/**
		 * Add a tools page for this plugin to the Settings menu.
		 */
		$this->plugin_screen_hook_suffix = add_submenu_page(
			'edit.php?post_type=' . Products::$products_slug,
			__( 'WP Express Checkout Tools', 'wp-express-checkout' ),
			__( 'Tools', 'wp-express-checkout' ),
			'manage_options',
			'wpec-tools-page',
			array( $this, 'display_plugin_admin_page' )
		);

		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register Admin page settings
	 */
	public function register_settings() {
		/* Register the settings */
		/* Add the sections */
		/* Add the settings fields */
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    2.1.2
	 */
	public function display_plugin_admin_page() {
		include_once 'views/tools.php';
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 *
	 * @param string[] $links An array of plugin action links.
	 */
	public function add_action_links( $links ) {}

}
