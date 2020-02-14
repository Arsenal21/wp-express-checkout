<?php

class WPEC_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/**
		 * Call $plugin_slug from public plugin class.
		 */
		$plugin = WPEC_Main::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		add_action( 'admin_notices', array( $this, 'show_admin_notices' ), 1 );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
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

	public function add_admin_notice( $text, $type = "notice", $dism = true ) {
		$msg_arr   = get_transient( 'ppec_admin_msg_arr' );
		$msg_arr   = empty( $msg_arr ) ? array() : $msg_arr;
		$msg_arr[] = array(
			'type' => $type,
			'text' => $text,
			'dism' => $dism,
		);
		set_transient( 'ppec_admin_msg_arr', $msg_arr );
	}

	public function show_admin_notices() {
		$msg_arr = get_transient( 'ppec_admin_msg_arr' );

		if ( ! empty( $msg_arr ) ) {
			delete_transient( 'ppec_admin_msg_arr' );
			$tpl = '<div class="notice notice-%1$s%3$s"><p>%2$s</p></div>';
			foreach ( $msg_arr as $msg ) {
				echo sprintf( $tpl, $msg['type'], $msg['text'], $msg['dism'] === true ? ' is-dismissible' : '' );
			}
		}
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 */
	public function enqueue_admin_styles() {
		wp_enqueue_style( $this->plugin_slug . '-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), WPEC_Main::VERSION );
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix === $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), WPEC_Main::VERSION );
		}
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/**
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 *
		 * @TODO:
		 *
		 * - Change 'Page Title' to the title of your plugin admin page
		 * - Change 'Menu Text' to the text for menu item for the plugin settings page
		 * - Change 'manage_options' to the capability you see fit
		 *   For reference: http://codex.wordpress.org/Roles_and_Capabilities
		 */
		$this->plugin_screen_hook_suffix = add_submenu_page(
			'edit.php?post_type=' . PPECProducts::$products_slug,
			__( 'WP Express Checkout Settings', 'wp-express-checkout' ),
			__( 'Settings', 'wp-express-checkout' ),
			'manage_options',
			'ppec-settings-page',
			array( $this, 'display_plugin_admin_page' )
		);
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register Admin page settings
	 */
	public function register_settings() {

		$wpec = WPEC_Main::get_instance();

		/* Register the settings */
		register_setting( 'ppdg-settings-group', 'ppdg-settings', array( $this, 'settings_sanitize_field_callback' ) );

		/* Add the sections */
		add_settings_section( 'ppdg-global-section', __( 'Global Settings', 'wp-express-checkout' ), null, $this->plugin_slug );
		add_settings_section( 'ppdg-credentials-section', __( 'PayPal Credentials', 'wp-express-checkout' ), null, $this->plugin_slug );
		add_settings_section( 'ppdg-button-style-section', __( 'Button Style', 'wp-express-checkout' ), null, $this->plugin_slug );
		add_settings_section( 'ppdg-disable-funding-section', __( 'Disable Funding', 'wp-express-checkout' ), array( $this, 'disable_funding_note' ), $this->plugin_slug );
		add_settings_section( 'ppdg-debug-logging-section', __( 'Debug Logging', 'wp-express-checkout' ), array( $this, 'debug_logging_note' ), $this->plugin_slug );

		add_settings_section( 'ppdg-emails-section', __( 'Purchase Confirmation Email Settings', 'wp-express-checkout' ), array( $this, 'emails_note' ), $this->plugin_slug . '-emails' );

		add_settings_section( 'ppdg-price-display-section', __( 'Price Display Settings', 'wp-express-checkout' ), null, $this->plugin_slug . '-advanced' );

		/* Add the settings fields */

		// Global settings fields.
		add_settings_field( 'currency_code', __( 'Currency Code', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-global-section', array( 'field' => 'currency_code', 'type' => 'select', 'desc' => __( 'Example: USD, CAD, GBP etc', 'wp-express-checkout' ), 'size' => 10, 'required' => true,
			'vals'  => array( 'USD', 'EUR', 'GBP', 'AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'HKD', 'HUF', 'INR', 'IDR', 'ILS', 'JPY', 'MYR', 'MXN', 'NZD', 'NOK', 'PHP', 'PLN', 'SGD', 'ZAR', 'KRW', 'SEK', 'CHF', 'TWD', 'THB', 'TRY', 'VND', 'RUB' ),
			'texts' => array( __( 'US Dollars (USD)', 'wp-express-checkout' ), __( 'Euros (EUR)', 'wp-express-checkout' ), __( 'Pounds Sterling (GBP)', 'wp-express-checkout' ), __( 'Australian Dollars (AUD)', 'wp-express-checkout' ), __( 'Brazilian Real (BRL)', 'wp-express-checkout' ), __( 'Canadian Dollars (CAD)', 'wp-express-checkout' ), __( 'Chinese Yuan (CNY)', 'wp-express-checkout' ), __( 'Czech Koruna (CZK)', 'wp-express-checkout' ), __( 'Danish Krone (DKK)', 'wp-express-checkout' ), __( 'Hong Kong Dollar (HKD)', 'wp-express-checkout' ), __( 'Hungarian Forint (HUF)', 'wp-express-checkout' ), __( 'Indian Rupee (INR)', 'wp-express-checkout' ), __( 'Indonesia Rupiah (IDR)', 'wp-express-checkout' ),
				__( 'Israeli Shekel (ILS)', 'wp-express-checkout' ), __( 'Japanese Yen (JPY)', 'wp-express-checkout' ), __( 'Malaysian Ringgits (MYR)', 'wp-express-checkout' ), __( 'Mexican Peso (MXN)', 'wp-express-checkout' ), __( 'New Zealand Dollar (NZD)', 'wp-express-checkout' ), __( 'Norwegian Krone (NOK)', 'wp-express-checkout' ), __( 'Philippine Pesos (PHP)', 'wp-express-checkout' ), __( 'Polish Zloty (PLN)', 'wp-express-checkout' ), __( 'Singapore Dollar (SGD)', 'wp-express-checkout' ), __( 'South African Rand (ZAR)', 'wp-express-checkout' ), __( 'South Korean Won (KRW)', 'wp-express-checkout' ), __( 'Swedish Krona (SEK)', 'wp-express-checkout' ), __( 'Swiss Franc (CHF)', 'wp-express-checkout' ),
				__( 'Taiwan New Dollars (TWD)', 'wp-express-checkout' ), __( 'Thai Baht (THB)', 'wp-express-checkout' ), __( 'Turkish Lira (TRY)', 'wp-express-checkout' ), __( 'Vietnamese Dong (VND)', 'wp-express-checkout' ), __( 'Russian Ruble (RUB)', 'wp-express-checkout' ),
			),
		) );
		add_settings_field(
			'currency_symbol',
			__( 'Currency Symbol', 'wp-express-checkout' ),
			array( &$this, 'settings_field_callback' ),
			$this->plugin_slug,
			'ppdg-global-section',
			array(
				'field' => 'currency_symbol',
				'type'  => 'text',
				'desc'  => '',
				'size'  => 10,
			)
		);
		add_settings_field( 'thank_you_url', __( 'Thank You Page URL', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-global-section',
			array(
				'field' => 'thank_you_url',
				'type'  => 'text',
				'desc'  => sprintf( __( 'This is the thank you page. This page is automatically created for you when you install the plugin. Do not delete this page from the pages menu of your site. The plugin will send the customers to this page after the payment.', 'wp-express-checkout' ) ),
				'size'  => 100,
			)
		);

		// API details.
		add_settings_field( 'is_live', __( 'Live Mode', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-credentials-section', array( 'field' => 'is_live', 'type' => 'checkbox', 'desc' => __( 'Check this to run the transaction in live mode. When unchecked it will run in sandbox mode.', 'wp-express-checkout' ) ) );
		add_settings_field( 'live_client_id', __( 'Live Client ID', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-credentials-section',
			array(
				'field' => 'live_client_id',
				'type'  => 'text',
				'desc'  => sprintf( __( 'Enter your PayPal Client ID for live mode. <a href="%s" target="_blank">Read this documentation</a> to learn how to locate your Client ID.', 'wp-express-checkout' ), 'https://wp-express-checkout.com/getting-live-and-sandbox-client-ids/' ),
				'size'  => 100,
			)
		);
		add_settings_field( 'sandbox_client_id', __( 'Sandbox Client ID', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-credentials-section',
			array(
				'field' => 'sandbox_client_id',
				'type'  => 'text',
				'desc'  => __( 'Enter your PayPal Client ID for sandbox mode.', 'wp-express-checkout' ),
				'size'  => 100,
			)
		);

		// button style section.
		add_settings_field( 'btn_type', __( 'Button Type', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-button-style-section', array( 'field' => 'btn_type', 'type' => 'select', 'class' => 'wp-ppdg-button-style', 'desc' => '', 'vals' => array( 'checkout', 'pay', 'paypal', 'buynow' ), 'texts' => array( __( 'Checkout', 'wp-express-checkout' ), __( 'Pay', 'wp-express-checkout' ), __( 'PayPal', 'wp-express-checkout' ), __( 'Buy Now', 'wp-express-checkout' ) ) ) );
		add_settings_field( 'btn_shape', __( 'Button Shape', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-button-style-section', array( 'field' => 'btn_shape', 'type' => 'select', 'class' => 'wp-ppdg-button-style', 'desc' => '', 'vals' => array( 'pill', 'rect' ), 'texts' => array( __( 'Pill', 'wp-express-checkout' ), __( 'Rectangle', 'wp-express-checkout' ) ) ) );
		add_settings_field( 'btn_layout', __( 'Button Layout', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-button-style-section', array( 'field' => 'btn_layout', 'type' => 'radio', 'class' => 'wp-ppdg-button-style', 'desc' => __( '', 'wp-express-checkout' ), 'vals' => array( 'vertical', 'horizontal' ), 'texts' => array( __( 'Vertical', 'wp-express-checkout' ), __( 'Horizontal', 'wp-express-checkout' ) ) ) );
		add_settings_field( 'btn_height', __( 'Button Height', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-button-style-section', array( 'field' => 'btn_height', 'type' => 'select', 'class' => 'wp-ppdg-button-style', 'desc' => '', 'vals' => array( 'small', 'medium', 'large', 'xlarge' ), 'texts' => array( __( 'Small', 'wp-express-checkout' ), __( 'Medium', 'wp-express-checkout' ), __( 'Large', 'wp-express-checkout' ), __( 'Extra Large', 'wp-express-checkout' ) ) ) );
		add_settings_field( 'btn_width', __( 'Button Width', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-button-style-section', array( 'field' => 'btn_width', 'type' => 'number', 'class' => 'wp-ppdg-button-style', 'placeholder' => __( 'Auto', 'wp-express-checkout' ), 'desc' => __( 'Button width in pixels. Minimum width is 150px. Leave it blank for auto width.', 'wp-express-checkout' ), 'size' => 10 ) );
		add_settings_field( 'btn_color', __( 'Button Color', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-button-style-section', array( 'field' => 'btn_color', 'type' => 'select', 'class' => 'wp-ppdg-button-style', 'desc' => '<div id="wp-ppdg-preview-container"><p>' . __( 'Button preview:', 'wp-express-checkout' ) . '</p><br /><div id="paypal-button-container"></div><div id="wp-ppdg-preview-protect"></div></div>', 'vals' => array( 'gold', 'blue', 'silver', 'white', 'black' ), 'texts' => array( __( 'Gold', 'wp-express-checkout' ), __( 'Blue', 'wp-express-checkout' ), __( 'Silver', 'wp-express-checkout' ), __( 'White', 'wp-express-checkout' ), __( 'Black', 'wp-express-checkout' ) ) ) );

		// disable funding section.
		add_settings_field( 'disabled_funding', __( 'Disabled Funding Options', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-disable-funding-section', array( 'field' => 'disabled_funding', 'type' => 'checkboxes', 'desc' => '', 'vals' => array( 'card', 'credit'/*, 'sepa'*/ ), 'texts' => array( __( 'Credit or debit cards', 'wp-express-checkout' ), __( 'PayPal Credit', 'wp-express-checkout' )/*, __( 'SEPA-Lastschrift', 'wp-express-checkout' )*/ ) ) );
		//add_settings_field( 'disabled_cards', __( 'Disabled Cards', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-disable-funding-section', array( 'field' => 'disabled_cards', 'type' => 'checkboxes', 'desc' => '', 'vals' => array( 'visa', 'mastercard', 'amex', 'discover', 'jcb', 'elo', 'hiper' ), 'texts' => array( __( 'Visa', 'wp-express-checkout' ), __( 'Mastercard', 'wp-express-checkout' ), __( 'American Express', 'wp-express-checkout' ), __( 'Discover', 'wp-express-checkout' ), __( 'JCB', 'wp-express-checkout' ), __( 'Elo', 'wp-express-checkout' ), __( 'Hiper', 'wp-express-checkout' ) ) ) );

		// debug logging section.
		add_settings_field( 'enable_debug_logging', __( 'Enable Debug Logging', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-debug-logging-section',
			array(
				'field' => 'enable_debug_logging',
				'type'  => 'checkbox',
				'desc'  => __( 'Check this option to enable debug logging.', 'wp-express-checkout' ) .
							'<p class="description"><a href="' . get_admin_url() . '?wpec-debug-action=view_log" target="_blank">' .
							__( 'Click here', 'wp-express-checkout' ) . '</a>' .
							__( ' to view log file.', 'wp-express-checkout' ) . '<br>' .
							'<a id="wpec-reset-log" href="#0" style="color: red">' . __( 'Click here', 'wp-express-checkout' ) . '</a>' .
							__( ' to reset log file.', 'wp-express-checkout' ) . '</p>',
			)
		);

		/***********************/
		/* Email Settings Menu Tab */
		/***********************/

		// emails section.
		add_settings_field( 'send_buyer_email', __( 'Send Emails to Buyer After Purchase', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug . '-emails', 'ppdg-emails-section', array( 'field' => 'send_buyer_email', 'type' => 'checkbox', 'desc' => __( 'If checked the plugin will send an email to the buyer with the sale details. If digital goods are purchased then the email will contain the download links for the purchased products.', 'wp-express-checkout' ) ) );
		add_settings_field( 'buyer_from_email', __( 'From Email Address', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug . '-emails', 'ppdg-emails-section', array( 'field' => 'buyer_from_email', 'type' => 'text', 'desc' => __( 'Example: Your Name &lt;sales@your-domain.com&gt; This is the email address that will be used to send the email to the buyer. This name and email address will appear in the from field of the email.', 'wp-express-checkout' ) ) );
		add_settings_field( 'buyer_email_subj', __( 'Buyer Email Subject', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug . '-emails', 'ppdg-emails-section', array( 'field' => 'buyer_email_subj', 'type' => 'text', 'desc' => __( 'This is the subject of the email that will be sent to the buyer.', 'wp-express-checkout' ) ) );
		add_settings_field( 'buyer_email_body', __( 'Buyer Email Body', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug . '-emails', 'ppdg-emails-section', array( 'field' => 'buyer_email_body', 'type' => 'textarea', 'desc' => ''
			. 'This is the body of the email that will be sent to the buyer. Do not change the text within the braces {}. You can use the following email tags in this email body field:'
			. '<br />{first_name} – ' . __( 'First name of the buyer', 'wp-express-checkout' )
			. '<br />{last_name} – ' . __( 'Last name of the buyer', 'wp-express-checkout' )
			. '<br />{payer_email} – ' . __( 'Email Address of the buyer', 'wp-express-checkout' )
			. '<br />{address} – ' . __( 'Address of the buyer', 'wp-express-checkout' )
			. '<br />{product_details} – ' . __( 'The item details of the purchased product (this will include the download link for digital items).', 'wp-express-checkout' )
			. '<br />{transaction_id} – ' . __( 'The unique transaction ID of the purchase', 'wp-express-checkout' )
			. '<br />{order_id} – ' . __( 'The order ID reference of this transaction in the cart orders menu', 'wp-express-checkout' )
			. '<br />{purchase_amt} – ' . __( 'The amount paid for the current transaction', 'wp-express-checkout' )
			. '<br />{purchase_date} – ' . __( 'The date of the purchase', 'wp-express-checkout' )
			. '<br />{coupon_code} – ' . __( 'Coupon code applied to the purchase', 'wp-express-checkout' ),
		) );
		add_settings_field( 'send_seller_email', __( 'Send Emails to Seller After Purchase', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug . '-emails', 'ppdg-emails-section', array( 'field' => 'send_seller_email', 'type' => 'checkbox', 'desc' => __( 'If checked the plugin will send an email to the seller with the sale details', 'wp-express-checkout' ) ) );
		add_settings_field( 'notify_email_address', __( 'Notification Email Address*', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug . '-emails', 'ppdg-emails-section', array( 'field' => 'notify_email_address', 'type' => 'text', 'desc' => __( 'This is the email address where the seller will be notified of product sales. You can put multiple email addresses separated by comma (,) in the above field to send the notification to multiple email addresses.', 'wp-express-checkout' ), 'required' => true, ) );
		add_settings_field( 'seller_email_subj', __( 'Seller Email Subject*', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug . '-emails', 'ppdg-emails-section', array( 'field' => 'seller_email_subj', 'type' => 'text', 'desc' => __( 'This is the subject of the email that will be sent to the seller for record.', 'wp-express-checkout' ), 'required' => true, ) );
		add_settings_field( 'seller_email_body', __( 'Seller Email Body*', 'wp-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug . '-emails', 'ppdg-emails-section', array( 'field' => 'seller_email_body', 'type' => 'textarea', 'required' => true, 'desc' => ''
			. 'This is the body of the email that will be sent to the seller for record. Do not change the text within the braces {}. You can use the following email tags in this email body field:'
			. '<br />{first_name} – ' . __( 'First name of the buyer', 'wp-express-checkout' )
			. '<br />{last_name} – ' . __( 'Last name of the buyer', 'wp-express-checkout' )
			. '<br />{payer_email} – ' . __( 'Email Address of the buyer', 'wp-express-checkout' )
			. '<br />{address} – ' . __( 'Address of the buyer', 'wp-express-checkout' )
			. '<br />{product_details} – ' . __( 'The item details of the purchased product (this will include the download link for digital items).', 'wp-express-checkout' )
			. '<br />{transaction_id} – ' . __( 'The unique transaction ID of the purchase', 'wp-express-checkout' )
			. '<br />{order_id} – ' . __( 'The order ID reference of this transaction in the cart orders menu', 'wp-express-checkout' )
			. '<br />{purchase_amt} – ' . __( 'The amount paid for the current transaction', 'wp-express-checkout' )
			. '<br />{purchase_date} – ' . __( 'The date of the purchase', 'wp-express-checkout' )
			. '<br />{coupon_code} – ' . __( 'Coupon code applied to the purchase', 'wp-express-checkout' ),
		) );

		/******************************/
		/* Advanced Settings Menu Tab */
		/******************************/

		// Price Display section
		add_settings_field(
			'price_currency_pos',
			__( 'Currency Position', 'wp-express-checkout' ),
			array( &$this, 'settings_field_callback' ),
			$this->plugin_slug . '-advanced',
			'ppdg-price-display-section',
			array(
				'field' => 'price_currency_pos',
				'type'  => 'select',
				'desc'  => __( 'This controls the position of the currency symbol.', 'wp-express-checkout' ),
				'vals'  => array( 'left', 'left_space', 'right', 'right_space' ),
				'texts' => array(
					sprintf( __( 'Left (%s1.00)', 'wp-express-checkout' ), $wpec->get_setting( 'currency_symbol' ) ),
					sprintf( __( 'Left with space (%s 1.00)', 'wp-express-checkout' ), $wpec->get_setting( 'currency_symbol' ) ),
					sprintf( __( 'Right (1.00%s)', 'wp-express-checkout' ), $wpec->get_setting( 'currency_symbol' ) ),
					sprintf( __( 'Right with space (1.00 %s)', 'wp-express-checkout' ), $wpec->get_setting( 'currency_symbol' ) ), )
			)
		);
		add_settings_field(
			'price_decimal_sep',
			__( 'Decimal Separator', 'wp-express-checkout' ),
			array( &$this, 'settings_field_callback' ),
			$this->plugin_slug . '-advanced',
			'ppdg-price-display-section',
			array(
				'field' => 'price_decimal_sep',
				'type'  => 'text',
				'desc'  => __( 'This sets the decimal separator of the displayed price.', 'wp-express-checkout' ),
			)
		);
		add_settings_field(
			'price_thousand_sep',
			__( 'Thousand Separator', 'wp-express-checkout' ),
			array( &$this, 'settings_field_callback' ),
			$this->plugin_slug . '-advanced',
			'ppdg-price-display-section',
			array(
				'field' => 'price_thousand_sep',
				'type'  => 'text',
				'desc'  => __( 'This sets the thousand separator of the displayed price.', 'wp-express-checkout' ),
			)
		);
		add_settings_field(
			'price_decimals_num',
			__( 'Number of Decimals', 'wp-express-checkout' ),
			array( &$this, 'settings_field_callback' ),
			$this->plugin_slug . '-advanced',
			'ppdg-price-display-section',
			array(
				'field' => 'price_decimals_num',
				'type'  => 'text',
				'desc'  => __( 'This sets the number of decimal points shown in the displayed price.', 'wp-express-checkout' ),
			)
		);
	}

	/**
	 * The section `ppdg-emails-section` callback.
	 */
	public function emails_note() {
		printf( '<p><i>%s</p></i>', esc_html__( 'The following options affect the emails that gets sent to your buyers after a purchase.', 'wp-express-checkout' ) );
	}

	/**
	 * The section `ppdg-disable-funding-section` callback.
	 */
	public function disable_funding_note() {
		echo '<p><i>';
		_e( 'By default, funding source eligibility is smartly decided based on a variety of factors. You can force disable funding options by selecting them below.', 'wp-express-checkout' );
		echo '</p></i>';
		echo '<p><i>';
		_e( 'Note: disabled options will disappear from button preview once you save changes.', 'wp-express-checkout' );
		echo '</p></i>';
	}

	/**
	 * The section `ppdg-debug-loggin-section` callback.
	 */
	public function debug_logging_note() {
		echo '<p><i>';
		_e( 'Debug logging can be useful to troubleshoot transaction processing related issues on your site.', 'wp-express-checkout' );
		echo '</p></i>';
	}

	/**
	 * Settings HTML
	 *
	 * @param array $args Field arguments passed into the add_settings_field().
	 */
	public function settings_field_callback( $args ) {
		$settings = (array) get_option( 'ppdg-settings' );
		$defaults = array(
			'type'        => 'text',
			'field'       => '',
			'size'        => 40,
			'vals'        => array( 1 => '' ),
			'desc'        => '',
			'placeholder' => '',
			'class'       => '',
		);

		$args = wp_parse_args( $args, $defaults );

		extract( $args );

		$field_value  = isset( $settings[ $field ] ) ? $settings[ $field ] : '';
		$_placeholder = $placeholder ? " placeholder='{$placeholder}'" : '';
		$_class       = $class ? "class='{$class}'" : '';

		switch ( $type ) {
			case 'checkbox':
				echo "<input type='checkbox' id='wp-ppdg-{$field}' name='ppdg-settings[{$field}]' {$_class} value='1' " . ( $field_value ? 'checked=checked' : '' ) . ' />';
				break;
			case 'checkboxes':
				foreach ( $vals as $key => $value ) {
					echo '<label><input type="checkbox" id="wp-ppdg-' . $field . '" ' . $_class . ' name="ppdg-settings[' . $field . '][]" value="' . $value . '"' . ( in_array( $value, $field_value ) ? ' checked' : '') . '>' . $texts[ $key ] . '</label> ';
				}
				break;
			case 'select':
				echo '<select id="wp-ppdg-' . $field . '" ' . $_class . ' name="ppdg-settings[' . $field . ']">';
				$opts = '';
				foreach ( $vals as $key => $value ) {
					$opts .= '<option value="' . $value . '"' . ( $value === $field_value ? ' selected' : '' ) . '>' . $texts[ $key ] . '</option>';
				}
				echo $opts;
				echo '</select>';
				break;
			case 'radio':
				foreach ( $vals as $key => $value ) {
					echo '<label><input type="radio" id="wp-ppdg-' . $field . '" ' . $_class . ' name="ppdg-settings[' . $field . ']" value="' . $value . '"' . ( $value === $field_value ? ' checked' : ( ( empty( $field_value ) && $value === "vertical" ) ? ' checked' : '' ) ) . '>' . $texts[ $key ] . '</label> ';
				}
				break;
			case 'textarea':
				echo "<textarea name='ppdg-settings[{$field}]' id='wp-ppdg-{$field}' {$_class} style='width:100%;' rows='7'>" . esc_textarea( $field_value ) . '</textarea>';
				break;
			default:
				echo "<input type='{$type}'{$_placeholder} id='wp-ppdg-{$field}' {$_class} name='ppdg-settings[{$field}]' value='{$field_value}' size='{$size}' />";
				break;
		}

		if ( $desc ) {
			echo "<p class='description'>{$desc}</p>";
		}
	}

	/**
	 * Validates the admin data
	 *
	 * @since    1.0.0
	 *
	 * @param array $input An array of options input.
	 */
	public function settings_sanitize_field_callback( $input ) {
		global $wp_settings_fields;

		$action_type = 'updated';

		$defaults = WPEC_Main::get_defaults();
		$output   = array_merge( $defaults, get_option( 'ppdg-settings' ) );

		// We can't validate fields if we don't know the current page tab.
		if ( ! isset( $_POST['ppdg_page_tab'] ) || ! isset( $wp_settings_fields[ $_POST['ppdg_page_tab'] ] ) ) {
			return $output;
		}

		$sections_for_tab = $wp_settings_fields[ $_POST['ppdg_page_tab'] ];

		// Go through the fields registered for the current section and validate
		// the user input.
		foreach ( $sections_for_tab as $fields ) {
			foreach ( $fields as $field => $args ) {
				if ( ! isset( $input[ $field ] ) ) {
					// The Fix for empty checkboxes.
					$input[ $field ] = '';
				}
				// Validate required fields.
				if ( ! empty( $args['args']['required'] ) && empty( $input[ $field ] ) ) {
					/* translators: "%s" - field title */
					$message = sprintf( __( 'You must specify "%s".', 'wp-express-checkout' ), $args['title'] );
					add_settings_error( 'ppdg-settings', 'invalid-' . $field, $message );
					$action_type = 'error';
				}

				$type = empty( $args['args']['type'] ) ? 'text' : $args['args']['type'];

				switch ( $type ) {
					case 'textarea':
						$input[ $field ] = wp_kses_post( $input[ $field ] );
						break;
					case 'radio':
					case 'select':
						$input[ $field ] = in_array( $input[ $field ], $args['args']['vals'] ) ? $input[ $field ] : null;
						break;
					case 'checkbox':
						$input[ $field ] = ! empty( $input[ $field ] ) ? 1 : 0;
						break;
					case 'checkboxes':
						if ( empty( $input[ $field ] ) ) {
							$input[ $field ] = array();
						} else {
							$input[ $field ] = array_intersect( $args['args']['vals'], (array) $input[ $field ] );
						}
						break;

					default:
						// Exception for `From Email Address` field as it should allow tags.
						if ( 'buyer_from_email' === $field ) {
							$input[ $field ] = stripslashes( $input[ $field ] );
						} else {
							$input[ $field ] = wp_kses_data( $input[ $field ] );
						}
						break;
				}
			}
		}

		if ( 'error' !== $action_type ) {
			$input  = wp_array_slice_assoc( $input, array_keys( $defaults ) );
			$output = array_merge( $output, $input );
		}

		return $output;
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once 'views/admin.php';
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 *
	 * @param string[] $links An array of plugin action links.
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array( 'settings' => '<a href="' . admin_url( 'edit.php?post_type=' . PPECProducts::$products_slug . '&page=ppec-settings-page' ) . '">' . __( 'Settings', 'wp-express-checkout' ) . '</a>' ),
			$links
		);
	}

	/**
	 * Prints out all settings sections added to a particular settings page
	 *
	 * @global $wp_settings_sections Storage array of all settings sections added to admin pages.
	 * @global $wp_settings_fields Storage array of settings fields and info about their pages/sections.
	 *
	 * @param string $page The slug name of the page whose settings sections you want to output.
	 */
	public function do_settings_sections( $page ) {
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_sections[ $page ] ) ) {
			return;
		}

		foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
			echo '<div class="postbox">';

			if ( $section['title'] ) {
				echo "<h3 class='hndle'><label for='title'>{$section['title']}</label></h3>\n";
			}

			echo '<div class="inside">';

			if ( $section['callback'] ) {
				call_user_func( $section['callback'], $section );
			}

			if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
				echo '</div></div>';
				continue;
			}
			echo '<table class="form-table" role="presentation">';
			do_settings_fields( $page, $section['id'] );
			echo '</table>';
			echo '</div></div>';
		}
	}

}
