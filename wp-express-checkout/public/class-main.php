<?php

namespace WP_Express_Checkout;

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-plugin-name-admin.php`
 *
 *
 */
class Main {

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 * @var      string
	 */
	protected $plugin_slug = 'paypal-for-digital-goods';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * The Plugin settings array.
	 *
	 * @var array
	 */
	private $settings = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added.
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 99 );
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'after_switch_theme', array( __CLASS__, 'rewrite_flush' ) );
	}

	/**
	 * Enqueue public styles and scripts.
	 *
	 * @since     1.0.0
	 */
	public function enqueue_styles() {
		// Minimize prod or show expanded in dev.
		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'wp-ppec-frontend-script', WPEC_PLUGIN_URL . "/assets/js/public{$min}.js", array( 'jquery' ), WPEC_PLUGIN_VER, true );

		wp_enqueue_style( 'wp-ppec-frontend-style', WPEC_PLUGIN_URL . "/assets/css/public{$min}.css", array(), WPEC_PLUGIN_VER );
		wp_style_add_data( 'wp-ppec-frontend-style', 'rtl', 'replace' );
		wp_style_add_data( 'wp-ppec-frontend-style', 'suffix', $min );
		wp_localize_script( 'wp-ppec-frontend-script', 'ppecFrontVars', array(
			'str' => array(
				'errorOccurred'    => __( 'Error occurred', 'wp-express-checkout' ),
				'paymentFor'       => __( 'Payment for', 'wp-express-checkout' ),
				'enterQuantity'    => __( 'Please enter a valid quantity', 'wp-express-checkout' ),
				'enterAmount'      => __( 'Please enter a valid amount', 'wp-express-checkout' ),
				'acceptTos'        => __( 'Please accept the terms and conditions', 'wp-express-checkout' ),
				'paymentCompleted' => __( 'Payment Completed', 'wp-express-checkout' ),
				'redirectMsg'      => __( 'You are now being redirected to the order summary page.', 'wp-express-checkout' ),
				'strRemoveCoupon'  => __( 'Remove coupon', 'wp-express-checkout' ),
				'strRemove'        => __( 'Remove', 'wp-express-checkout' ),
				'required'         => __( 'This field is required', 'wp-express-checkout' ),
			),
			'ajaxUrl' => get_admin_url() . 'admin-ajax.php',
		) );
	}

	/**
	 * Load the PayPal scripts.
	 *
	 * Called in the shordcode when at least one button generated.
	 */
	public function load_paypal_sdk() {
		$args = array();
		$args['client-id'] = $this->get_setting( 'is_live' ) ? $this->get_setting( 'live_client_id' ) : $this->get_setting( 'sandbox_client_id' );
		$args['intent']    = 'capture';
		$args['currency']  = $this->get_setting( 'currency_code' );
		$disabled_funding  = $this->get_setting( 'disabled_funding' );

		// Enable Venmo by default (could be disabled by 'disable-funding' option).
		$args['enable-funding']  = 'venmo';
		// Required for Venmo in sandbox.
		if ( ! $this->get_setting( 'is_live' ) ) {
			$args['buyer-country']  = 'US';
		}

		if ( ! empty( $disabled_funding ) ) {
			$arg = '';
			foreach ( $disabled_funding as $funding ) {
				$arg .= $funding . ',';
			}
			$arg = rtrim( $arg, ',' );
			$args['disable-funding'] = $arg;
		}
		// check if cards aren't disabled globally first.
		if ( ! in_array( 'card', $disabled_funding, true ) ) {
			$disabled_cards = $this->get_setting( 'disabled_cards' );
			if ( ! empty( $disabled_cards ) ) {
				$arg = '';
				foreach ( $disabled_cards as $card ) {
					$arg .= $card . ',';
				}
				$arg = rtrim( $arg, ',' );
				$args['disable-card'] = $arg;
			}
		}

		/**
		 * Filters arguments to be passed to PayPal SDK.
		 *
		 * @param array $args The PayPal SDK arguments.
		 */
		$args = apply_filters( 'wpec_paypal_sdk_args', $args );

		$script_url = add_query_arg( $args, 'https://www.paypal.com/sdk/js' );
		?>
		<script type="text/javascript">
			var script = document.createElement( 'script' );
			script.type = 'text/javascript';
			script.setAttribute( 'data-partner-attribution-id', 'TipsandTricks_SP' );
			script.async = true;
			script.src = '<?php echo esc_url_raw( $script_url ); ?>';
			script.onload = function() {
				jQuery( function( $ ) { $( document ).trigger( 'wpec_paypal_sdk_loaded' ) } );
			};
			document.getElementsByTagName( 'head' )[0].appendChild( script );
		</script>
		<?php
	}

	/**
	 * Retrieves the setting field value.
	 *
	 * @param string $field The field name.
	 *
	 * @return mixed
	 */
	public function get_setting( $field ) {
		$settings = (array) get_option( 'ppdg-settings', self::get_defaults() );
		$settings = array_merge( self::get_defaults(), $settings );

		if ( isset( $settings[ $field ] ) ) {
			return $settings[ $field ];
		}
		return false;
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
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

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $network_wide True if WPMU super admin uses "Network
	 *                              Activate" action, false if WPMU is disabled
	 *                              or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids.
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();
			} else {
				self::single_activate();
			}
		} else {
			self::single_activate();
		}
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param boolean $network_wide True if WPMU super admin uses "Network
	 *                              Deactivate" action, false if WPMU is
	 *                              disabled or plugin is deactivated on an
	 *                              individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids.
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();
				}

				restore_current_blog();
			} else {
				self::single_deactivate();
			}
		} else {
			self::single_deactivate();
		}
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param int $blog_id ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();
	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids.
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );
	}

	/**
	 * Retrieves the plugin defaults/
	 *
	 * @return array
	 */
	public static function get_defaults() {
		$defaults = array(
			'is_live'              => 0,
			'live_client_id'       => '',
			'sandbox_client_id'    => '',
			'live_secret_key'      => '',
			'sandbox_secret_key'   => '',
			'currency_code'        => 'USD',
			'currency_symbol'      => '$',
			'price_currency_pos'   => 'left',
			'price_decimal_sep'    => '.',
			'price_thousand_sep'   => ',',
			'price_decimals_num'   => '2',
			'thank_you_url'        => '',
			'shipping'             => '',
			'tax'                  => '',
			'button_text'          => __( 'Pay', 'wp-express-checkout' ),
			'use_modal'            => 1,
			'btn_shape'            => 'pill',
			'btn_color'            => 'gold',
			'btn_type'             => 'checkout',
			'btn_height'           => 'xlarge',
			'btn_width'            => 0,
			'btn_layout'           => 'vertical',
			'disabled_funding'     => array( 'card' ),
			'disabled_cards'       => array(),
			'enable_debug_logging' => 0,
			'send_buyer_email'     => 1,
			'buyer_email_type'     => 'text',
			'buyer_from_email'     => get_bloginfo( 'name' ) . ' <sales@your-domain.com>',
			'buyer_email_subj'     => 'Thank you for the purchase',
			'buyer_email_body'     => ''
									. "Dear {first_name} {last_name}\n"
									. "\nThank you for your purchase! You ordered the following item(s):\n"
									. "\n{product_details}",
			'send_seller_email'    => '',
			'notify_email_address' => get_bloginfo( 'admin_email' ),
			'seller_email_subj'    => 'Notification of product sale',
			'seller_email_body'    => ''
									. "Dear Seller\n"
									. "\nThis mail is to notify you of a product sale.\n"
									. "\n{product_details}"
									. "\n\nThe sale was made to {first_name} {last_name} ({payer_email})"
									. "\n\nThanks",
			'coupons_enabled'      => 0,
			'tos_enabled'          => 0,
			'tos_text'             => __( 'I accept the <a href="https://example.com/terms-and-conditions/" target="_blank">Terms and Conditions</a>', 'wp-express-checkout' ),
			'download_duration'    => '',
			'download_count'       => '',
			'access_permission'    => 'manage_options',
		);

		return $defaults;
	}

	/**
	 * Fired for each blog when the plugin is activated.
	 */
	private static function single_activate() {
		// Plugin activation.

		// Get the default values of the various settings fields. Then check if
		// first-time install or an upgrade.
		$settings = get_option( 'ppdg-settings', self::get_defaults() );
		$settings = array_merge( self::get_defaults(), $settings );

		update_option( 'ppdg-settings', $settings );

		// Check and create required pages.
		self::check_and_create_thank_you_page(); // Create the thank you page.

		// Explicitly register post types and flush rewrite rules.
		Products::register_post_type();
		Orders::register_post_type();
		self::rewrite_flush();
	}

	/**
	 * Creates the Thank You page.
	 */
	public static function check_and_create_thank_you_page() {
		// Check if Thank You page exists. Create new if it doesn't exist.
		$args  = array(
			'post_type' => 'page',
		);
		$pages = get_pages( $args );

		$ty_page_id = '';
		foreach ( $pages as $page ) {
			// Check if there is a page that contins our thank you page shortcode.
			if ( strpos( $page->post_content, 'wpec_thank_you' ) !== false ) {
				$ty_page_id = $page->ID;
			}
		}
		if ( '' === $ty_page_id ) {
			// Thank you page missing. Create a new one.
			$ty_page_id  = self::create_post( 'page', 'Thank You', 'Thank-You-Transaction-Result', '[wpec_thank_you]' );
			$ty_page     = get_post( $ty_page_id );
			$ty_page_url = $ty_page->guid;

			// Save the Thank you page URL in settings.
			$settings = get_option( 'ppdg-settings' );
			if ( ! empty( $settings ) ) { // Settings should already be initialized when this function is called.
				$settings['thank_you_url']     = $ty_page_url;
				$settings['thank_you_page_id'] = $ty_page_id;
				update_option( 'ppdg-settings', $settings );
			}
		}
	}

	/**
	 * Creates a single post by given parameters.
	 *
	 * @param string $post_type The post type.
	 * @param string $title     The post title.
	 * @param string $name      The post name.
	 * @param string $content   The post content.
	 * @param int    $parent_id Set this for the post it belongs to, if any.
	 *
	 * @return int The post ID
	 */
	public static function create_post( $post_type, $title, $name, $content, $parent_id = null ) {
		$post = array(
			'post_title'     => $title,
			'post_name'      => $name,
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_content'   => $content,
			'post_status'    => 'publish',
			'post_type'      => $post_type,
		);

		if ( null !== $parent_id ) {
			$post['post_parent'] = $parent_id;
		}
		$post_id = wp_insert_post( $post );
		return $post_id;
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = 'wp-express-checkout';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
	}

	/**
	 *  Remove rewrite rules and then recreate rewrite rules.
	 *
	 * @since    1.0.0
	 */
	public static function rewrite_flush() {
		flush_rewrite_rules();
	}

	// public function get_plugin_slug()
	// {
	// 	return $this->plugin_slug;
	// }
}
