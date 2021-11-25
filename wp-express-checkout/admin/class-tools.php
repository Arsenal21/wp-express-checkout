<?php

namespace WP_Express_Checkout\Admin;

use WP_Express_Checkout\Admin\Admin;
use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Emails;
use WP_Express_Checkout\Main;
use WP_Express_Checkout\Products;

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
	 * The options name associated with current page.
	 *
	 * @since 2.1.2
	 *
	 * @var string
	 */
	protected $option_name = 'wpec-tools';

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

	protected function __construct() {
		parent::__construct();
		add_filter( 'option_page_capability_wpec-tools-group', array( $this, 'settings_permissions' ) );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     2.1.2
	 */
	public function enqueue_admin_styles() {}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     2.1.2
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {}

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
			Main::get_instance()->get_setting( 'access_permission' ),
			'wpec-tools-page',
			array( $this, 'display_plugin_admin_page' )
		);

		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register Admin page settings
	 */
	public function register_settings() {
		//delete_option('wpec-tools');
		/* Register the settings */
		register_setting( 'wpec-tools-group', $this->option_name, array( $this, 'settings_sanitize_field_callback' ) );
		/* Add the sections */
		add_settings_section( 'wpec-email-tools-section', __( 'Send Email to Customers', 'wp-express-checkout' ), array( $this, 'send_email_section_callback' ), 'wpec-tools-page-emails' );
		/* Add the settings fields */

		$defaults = $this->get_defaults();

		add_settings_field(
			'customer_email_from',
			__( 'From Email Address', 'wp-express-checkout' ),
			array( $this, 'settings_field_callback' ),
			'wpec-tools-page-emails',
			'wpec-email-tools-section',
			array(
				'field'    => 'customer_email_from',
				'type'     => 'text',
				'desc'     => __( 'This email will appear in the from field of the email.', 'wp-express-checkout' ),
				'default'  => $defaults['customer_email_from'],
				'required' => true,
			)
		);
		add_settings_field(
			'customer_email_to',
			__( 'To Email Address', 'wp-express-checkout' ),
			array( $this, 'settings_field_callback' ),
			'wpec-tools-page-emails',
			'wpec-email-tools-section',
			array(
				'field'    => 'customer_email_to',
				'type'     => 'email',
				'desc'     => __( 'This is the email address where the email with be sent to.', 'wp-express-checkout' ),
				'required' => true,
			)
		);
		add_settings_field(
			'customer_email_subject',
			__( 'Email Subject', 'wp-express-checkout' ),
			array( $this, 'settings_field_callback' ),
			'wpec-tools-page-emails',
			'wpec-email-tools-section',
			array(
				'field'    => 'customer_email_subject',
				'type'     => 'text',
				'desc'     => __( 'This is the email subject', 'wp-express-checkout' ),
				'required' => true,
			)
		);
		add_settings_field(
			'customer_email_body',
			__( 'Email Body', 'wp-express-checkout' ),
			array( $this, 'settings_field_callback' ),
			'wpec-tools-page-emails',
			'wpec-email-tools-section',
			array(
				'field'    => 'customer_email_body',
				'type'     => 'textarea',
				'desc'     => __( 'Type your email and hit the Send Email button.', 'wp-express-checkout' ),
				'required' => true,
			)
		);
		add_settings_field(
			'send_email',
			'',
			array( $this, 'send_email_button' ),
			'wpec-tools-page-emails',
			'wpec-email-tools-section'
		);

	}

	/**
	 * Validates the admin data
	 *
	 * @param array $input An array of options input.
	 */
	public function settings_sanitize_field_callback( $input ) {
		global $wp_settings_errors;

		$output = array(
			'customer_email_from' => ! empty( $_POST[ $this->option_name ]['customer_email_from'] ) ? stripslashes( $_POST[ $this->option_name ]['customer_email_from'] ) : '',
			'customer_email_body' => ! empty( $_POST[ $this->option_name ]['customer_email_body'] ) ? stripslashes( $_POST[ $this->option_name ]['customer_email_body'] ) : '',
		);

		$input  = parent::settings_sanitize_field_callback( $input );
		$output = array_merge( $input, $output );

		if ( ! empty( $_POST['_wpnonce'] )
			&& wp_verify_nonce( $_POST['_wpnonce'], 'wpec-tools-group-options' )
			&& ! empty( $_POST['ppdg_page_tab'] )
			&& 'wpec-tools-page-emails' === $_POST['ppdg_page_tab']
			&& empty( $wp_settings_errors )
		) {

			$to      = $output['customer_email_to'];
			$from    = $output['customer_email_from'];
			$subject = $output['customer_email_subject'];
			$body    = $output['customer_email_body'];

			$result = Emails::send( $to, $from, $subject, $body );

			if ( $result ) {
				$this->add_admin_notice( __( 'Email successfully sent!' ), 'success' );
				Logger::log( 'Tools menu - Email sent to: ' . $to );
			} else {
				$this->add_admin_notice( __( 'Something went wrong, email is not sent!' ), 'error' );
			}
		}

		return $output;
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
		// Asked to remove "Settings saved" message on the Tools page.
		$settings_errors = get_transient( 'settings_errors' );
		if ( ! empty( $settings_errors[0]['code'] ) && 'settings_updated' === $settings_errors[0]['code'] ) {
			unset( $settings_errors[0] );
			set_transient( 'settings_errors', $settings_errors, 30 );
		}
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

	/**
	 * Retrieves default settings
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array(
			'customer_email_to'      => '',
			'customer_email_from'    => Main::get_instance()->get_setting( 'buyer_from_email' ),
			'customer_email_subject' => '',
			'customer_email_body'    => '',
		);
	}

}
