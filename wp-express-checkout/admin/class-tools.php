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
		add_settings_section( 'wpec-email-tools-section', __( 'Send Email to Customers', 'wp-express-checkout' ), array( $this, 'send_email_section_callback' ), 'wpec-tools-page-emails' );
		/* Add the settings fields */
		add_settings_field(
			'buyer_from_email',
			__( 'From Email Address', 'wp-express-checkout' ),
			array( $this, 'settings_field_callback' ),
			'wpec-tools-page-emails',
			'wpec-email-tools-section',
			array(
				'field' => 'buyer_from_email',
				'type'  => 'text',
				'desc'  => __( 'This email will appear in the from field of the email.', 'wp-express-checkout' ),
			)
		);
		add_settings_field(
			'to',
			__( 'To Email Address', 'wp-express-checkout' ),
			array( $this, 'settings_field_callback' ),
			'wpec-tools-page-emails',
			'wpec-email-tools-section',
			array(
				'field' => 'to',
				'type'  => 'text',
				'desc'  => __( 'This is the email address where the email with be sent to.', 'wp-express-checkout' ),
			)
		);
		add_settings_field(
			'email_subject',
			__( 'Email Subject', 'wp-express-checkout' ),
			array( $this, 'settings_field_callback' ),
			'wpec-tools-page-emails',
			'wpec-email-tools-section',
			array(
				'field' => 'email_subject',
				'type'  => 'text',
				'desc'  => __( 'This is the email subject', 'wp-express-checkout' ),
			)
		);
		add_settings_field(
			'email_body',
			__( 'Email Body', 'wp-express-checkout' ),
			array( $this, 'settings_field_callback' ),
			'wpec-tools-page-emails',
			'wpec-email-tools-section',
			array(
				'field' => 'email_body',
				'type'  => 'textarea',
				'desc'  => __( 'Type your email and hit the Send Email button.', 'wp-express-checkout' ),
			)
		);
		add_settings_field(
			'send_email',
			'',
			array( $this, 'send_email_button' ),
			'wpec-tools-page-emails',
			'wpec-email-tools-section'
		);

		if ( ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'wpec-tools-page-emails' ) ) {
			$post = stripslashes_deep( $_POST );
			if ( ! empty( $post['ppdg-settings'] ) ) {
				$post = $post['ppdg-settings'];
			}

			if ( empty( $post['to'] ) || ! is_email( $post['to'] ) ) {
				$this->add_admin_notice( __( 'To Email Address is invalid!' ), 'error' );
				return;
			}

			$wpec = Main::get_instance();

			$to   = wp_kses_data( $post['to'] );
			$from = ! empty( $post['buyer_from_email'] ) ? $post['buyer_from_email'] : $wpec->get_setting( 'buyer_from_email' );
			$subject = ! empty( $post['email_subject'] ) ? wp_kses_data( $post['email_subject'] ) : '';
			$body = ! empty( $post['email_body'] ) ? wp_kses_post( $post['email_body'] ) : '';

			if ( 'html' === $wpec->get_setting( 'buyer_email_type' ) ) {
				$headers[] = 'Content-Type: text/html; charset=UTF-8';
				$body = nl2br( $body );
			} else {
				$headers = array();
				$body = html_entity_decode( $body );
			}

			$headers[] = 'From: ' . $from . "\r\n";

			$result = wp_mail( $to, wp_specialchars_decode( $subject, ENT_QUOTES ), $body, $headers );

			if ( $result ) {
				$this->add_admin_notice( __( 'Email successfully sent!' ), 'success' );
			} else {
				$this->add_admin_notice( __( 'Something went wrong, email is not sent!' ), 'error' );
			}
		}

	}

	public function send_email_section_callback() {
		?>
		<p class="description">
			<?php esc_html_e( 'You can use this feature to send a quick email to your customers. If you want to re-send a download link for an order, first get the download link(s) from the Orders menu of the order in question, then email it to them using the following option.', 'wp-express-checkout' ); ?>
		</p>
		<?php
	}

	public function send_email_button() {
		echo '<button class="button">' . __( 'Send Email >>', 'wp-express-checkout' ) . '</button>';
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
