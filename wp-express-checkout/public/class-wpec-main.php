<?php

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-plugin-name-admin.php`
 *
 *
 */
class WPEC_Main {

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since   1.0.0
     *
     * @var     string
     */
    const VERSION = '1.0.0';

    /**
     *
     * Unique identifier for your plugin.
     *
     * The variable name is used as the text domain when internationalizing strings
     * of text. Its value should match the Text Domain file header in the main
     * plugin file.
     *
     * @since    1.0.0
     *
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
    protected static $instance	 = null;
    private $settings		 = null;

    /**
     * Initialize the plugin by setting localization and loading public scripts
     * and styles.
     *
     * @since     1.0.0
     */
    private function __construct() {
	$this->settings = (array) get_option( 'ppdg-settings' );

	// Load plugin text domain
	add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

	// Activate plugin when new blog is added
	add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

	// Load public-facing style sheet and JavaScript.
	if ( ! is_admin() ) {
	    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}
	// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	add_action( 'after_switch_theme', array( __CLASS__, 'rewrite_flush' ) );
    }

    public function enqueue_styles() {
	wp_register_script( 'wp-ppec-frontend-script', WPEC_PLUGIN_URL . '/public/assets/js/public.js', array( 'jquery' ), false, true );

	wp_register_style( 'wp-ppec-frontend-style', WPEC_PLUGIN_URL . '/public/assets/css/public.css' );

	wp_enqueue_script( 'wp-ppec-frontend-script' );

	wp_enqueue_style( 'wp-ppec-frontend-style' );
    }

    public function get_setting( $field ) {
	if ( isset( $this->settings[ $field ] ) )
	    return $this->settings[ $field ];
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
	if ( null == self::$instance ) {
	    self::$instance = new self;
	}

	return self::$instance;
    }

    /**
     * Fired when the plugin is activated.
     *
     * @since    1.0.0
     *
     * @param    boolean    $network_wide    True if WPMU superadmin uses
     *                                       "Network Activate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       activated on an individual blog.
     */
    public static function activate( $network_wide ) {

	if ( function_exists( 'is_multisite' ) && is_multisite() ) {

	    if ( $network_wide ) {

		// Get all blog ids
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
     * @param    boolean    $network_wide    True if WPMU superadmin uses
     *                                       "Network Deactivate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       deactivated on an individual blog.
     */
    public static function deactivate( $network_wide ) {

	if ( function_exists( 'is_multisite' ) && is_multisite() ) {

	    if ( $network_wide ) {

		// Get all blog ids
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
     * @param    int    $blog_id    ID of the new blog.
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

	// get an array of blog ids
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
			'currency_code'        => 'USD',
			'thank_you_url'        => '',
			'btn_shape'            => 'pill',
			'btn_color'            => 'gold',
			'btn_type'             => 'checkout',
			'btn_height'           => 'xlarge',
			'btn_width'            => 0,
			'btn_layout'           => 'vertical',
			'disabled_funding'     => array(),
			'disabled_cards'       => array(),
			'enable_debug_logging' => 0,
			'send_buyer_email'     => 1,
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

		// Check and create required pages
		self::check_and_create_thank_you_page(); // Create the thank you page.

		// Explicitly register post types and flush rewrite rules.
		$PPECProducts = PPECProducts::get_instance();
		$PPECProducts->register_post_type();
		$OrdersWPEC = OrdersWPEC::get_instance();
		$OrdersWPEC->register_post_type();
		self::rewrite_flush();
	}

	public static function check_and_create_thank_you_page(){
		//Check if Thank You page exists. Create new if it doesn't exist.
		$args = array(
			'post_type' => 'page',
		);
		$pages = get_pages( $args );

		$ty_page_id = '';
		foreach ( $pages as $page ) {
                        //Check if there is a page that contins our thank you page shortcode.
			if ( strpos( $page->post_content, 'wpec_thank_you' ) !== false ) {
				$ty_page_id = $page->ID;
			}
		}
		if ( $ty_page_id === '') {
                        //Thank you page missing. Create a new one.
			$ty_page_id  = self::create_post( 'page', 'Thank You', 'Thank-You-Transaction-Result', '[wpec_thank_you]' );
			$ty_page     = get_post( $ty_page_id );
			$ty_page_url = $ty_page->guid;

                        //Save the Thank you page URL in settings.
			$settings = get_option( 'ppdg-settings' );
			if ( ! empty( $settings ) ) {//Settings should already be initialized when this function is called.
				$settings['thank_you_url'] = $ty_page_url;
				$settings['thank_you_page_id'] = $ty_page_id;
				update_option( 'ppdg-settings', $settings );
			}
		}

        }

        public static function create_post( $postType, $title, $name, $content, $parentId = NULL ) {
	$post = array(
	    'post_title'	 => $title,
	    'post_name'	 => $name,
	    'comment_status' => 'closed',
	    'ping_status'	 => 'closed',
	    'post_content'	 => $content,
	    'post_status'	 => 'publish',
	    'post_type'	 => $postType
	);

	if ( $parentId !== NULL ) {
	    $post[ 'post_parent' ] = $parentId;
	}
	$postId = wp_insert_post( $post );
	return $postId;
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

	$domain	 = 'paypal-express-checkout';
	$locale	 = apply_filters( 'plugin_locale', get_locale(), $domain );

	load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
	load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
    }

	/**
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
