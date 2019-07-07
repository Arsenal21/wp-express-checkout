<?php

class PPDG_Admin {

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

	/*
	 * Call $plugin_slug from public plugin class.
	 */
	$plugin			 = PPDG::get_instance();
	$this->plugin_slug	 = $plugin->get_plugin_slug();

	// Load admin style sheet and JavaScript.
	// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
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
	if ( null == self::$instance ) {
	    self::$instance = new self;
	}

	return self::$instance;
    }

    public function add_admin_notice( $text, $type = "notice", $dism = true ) {
	$msg_arr	 = get_transient( 'ppec_admin_msg_arr' );
	$msg_arr	 = empty( $msg_arr ) ? array() : $msg_arr;
	$msg_arr[]	 = array(
	    'type'	 => $type,
	    'text'	 => $text,
	    'dism'	 => $dism,
	);
	set_transient( 'ppec_admin_msg_arr', $msg_arr );
    }

    public function show_admin_notices() {
	$msg_arr = get_transient( 'ppec_admin_msg_arr' );

	if ( ! empty( $msg_arr ) ) {
	    delete_transient( 'ppec_admin_msg_arr' );
	    $tpl = '<div class="notice notice-%1$s%3$s"><p>%2$s</p></div>';
	    foreach ( $msg_arr as $msg ) {
		echo sprintf( $tpl, $msg[ 'type' ], $msg[ 'text' ], $msg[ 'dism' ] === true ? ' is-dismissible' : ''  );
	    }
	}
    }

    /**
     * Register and enqueue admin-specific style sheet.
     *
     * @since     1.0.0
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_styles() {

	if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
	    return;
	}

	$screen = get_current_screen();
	if ( $this->plugin_screen_hook_suffix == $screen->id ) {
	    wp_enqueue_style( $this->plugin_slug . '-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), PPDG::VERSION );
	}
    }

    /**
     * Register and enqueue admin-specific JavaScript.
     *
     * @TODO:
     *
     * - Rename "PPDG" to the name your plugin
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
	if ( $this->plugin_screen_hook_suffix == $screen->id ) {
	    wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), PPDG::VERSION );
	}
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {

	/*
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
	'edit.php?post_type=' . PPECProducts::$products_slug, __( 'PayPal Express Checkout Settings', 'paypal-express-checkout' ), __( 'Settings', 'paypal-express-checkout' ), 'manage_options', 'ppec-settings-page', array( $this, 'display_plugin_admin_page' )
	);
	add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Register Admin page settings
     */
    public function register_settings( $value = '' ) {
	register_setting( 'ppdg-settings-group', 'ppdg-settings', array( $this, 'settings_sanitize_field_callback' ) );

	add_settings_section( 'ppdg-documentation', __( 'Plugin Documentation', 'paypal-express-checkout' ), array( $this, 'general_documentation_callback' ), $this->plugin_slug );

	add_settings_section( 'ppdg-global-section', __( 'Global Settings', 'paypal-express-checkout' ), null, $this->plugin_slug );
	add_settings_section( 'ppdg-credentials-section', __( 'PayPal Credentials', 'paypal-express-checkout' ), null, $this->plugin_slug );
	add_settings_section( 'ppdg-button-style-section', __( 'Button Style', 'paypal-express-checkout' ), null, $this->plugin_slug );
	add_settings_section( 'ppdg-disable-funding-section', __( 'Disable Funding', 'paypal-express-checkout' ), array( $this, 'disable_funding_note' ), $this->plugin_slug );

	add_settings_field( 'currency_code', __( 'Currency Code', 'paypal-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-global-section', array( 'field' => 'currency_code', 'desc' => __( 'Example: USD, CAD etc', 'paypal-express-checkout' ), 'size' => 10 ) );

	add_settings_field( 'is_live', __( 'Live Mode', 'paypal-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-credentials-section', array( 'field' => 'is_live', 'desc' => __( 'Check this to run the transaction in live mode. When unchecked it will run in sandbox mode.', 'paypal-express-checkout' ) ) );
	add_settings_field( 'live_client_id', __( 'Live Client ID', 'paypal-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-credentials-section', array( 'field' => 'live_client_id', 'desc' => '' ) );
	add_settings_field( 'sandbox_client_id', __( 'Sandbox Client ID', 'paypal-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-credentials-section', array( 'field' => 'sandbox_client_id', 'desc' => '' ) );

//disable funding section
	add_settings_field( 'disabled_funding', __( 'Disabled Funding Options', 'paypal-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-disable-funding-section', array( 'field' => 'disabled_funding', 'desc' => '', 'vals' => array( 'card', 'credit', 'sepa' ), 'texts' => array( __( 'Credit or debit cards', 'paypal-express-checkout' ), __( 'PayPal Credit', 'paypal-express-checkout' ), __( 'SEPA-Lastschrift', 'paypal-express-checkout' ) ) ) );
	add_settings_field( 'disabled_cards', __( 'Disabled Cards', 'paypal-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-disable-funding-section', array( 'field' => 'disabled_cards', 'desc' => '', 'vals' => array( 'visa', 'mastercard', 'amex', 'discover', 'jcb', 'elo', 'hiper' ), 'texts' => array( __( 'Visa', 'paypal-express-checkout' ), __( 'Mastercard', 'paypal-express-checkout' ), __( 'American Express', 'paypal-express-checkout' ), __( 'Discover', 'paypal-express-checkout' ), __( 'JCB', 'paypal-express-checkout' ), __( 'Elo', 'paypal-express-checkout' ), __( 'Hiper', 'paypal-express-checkout' ) ) ) );
//button style section
	add_settings_field( 'btn_type', __( 'Button Type', 'paypal-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-button-style-section', array( 'field' => 'btn_type', 'desc' => '', 'vals' => array( 'checkout', 'pay', 'paypal', 'buynow' ), 'texts' => array( __( 'Checkout', 'paypal-express-checkout' ), __( 'Pay', 'paypal-express-checkout' ), __( 'PayPal', 'paypal-express-checkout' ), __( 'Buy Now', 'paypal-express-checkout' ) ) ) );
	add_settings_field( 'btn_shape', __( 'Button Shape', 'paypal-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-button-style-section', array( 'field' => 'btn_shape', 'desc' => '', 'vals' => array( 'pill', 'rect' ), 'texts' => array( __( 'Pill', 'paypal-express-checkout' ), __( 'Rectangle', 'paypal-express-checkout' ) ) ) );
	add_settings_field( 'btn_layout', __( 'Button Layout', 'paypal-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-button-style-section', array( 'field' => 'btn_layout', 'desc' => __( '', 'paypal-express-checkout' ), 'vals' => array( 'vertical', 'horizontal' ), 'texts' => array( __( 'Vertical', 'paypal-express-checkout' ), __( 'Horizontal', 'paypal-express-checkout' ) ) ) );
	add_settings_field( 'btn_height', __( 'Button Height', 'paypal-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-button-style-section', array( 'field' => 'btn_height', 'desc' => '', 'vals' => array( 'small', 'medium', 'large', 'xlarge' ), 'texts' => array( __( 'Small', 'paypal-express-checkout' ), __( 'Medium', 'paypal-express-checkout' ), __( 'Large', 'paypal-express-checkout' ), __( 'Extra Large', 'paypal-express-checkout' ) ) ) );
	add_settings_field( 'btn_width', __( 'Button Width', 'paypal-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-button-style-section', array( 'field' => 'btn_width', 'desc' => __( 'Button width in pixels. Minimum width is 150px. Leave it blank for auto width.', 'paypal-express-checkout' ), 'size' => 10 ) );
	add_settings_field( 'btn_color', __( 'Button Color', 'paypal-express-checkout' ), array( $this, 'settings_field_callback' ), $this->plugin_slug, 'ppdg-button-style-section', array( 'field' => 'btn_color', 'desc' => '<div id="wp-ppdg-preview-container"><p>' . __( 'Button preview:', 'paypal-express-checkout' ) . '</p><br /><div id="paypal-button-container"></div><div id="wp-ppdg-preview-protect"></div></div>', 'vals' => array( 'gold', 'blue', 'silver', 'white', 'black' ), 'texts' => array( __( 'Gold', 'paypal-express-checkout' ), __( 'Blue', 'paypal-express-checkout' ), __( 'Silver', 'paypal-express-checkout' ), __( 'White', 'paypal-express-checkout' ), __( 'Black', 'paypal-express-checkout' ) ) ) );
    }

    public function general_documentation_callback( $args ) {
	?>
	<div style="background: none repeat scroll 0 0 #FFF6D5;border: 1px solid #D1B655;color: #3F2502;margin: 10px 0;padding: 5px 5px 5px 10px;text-shadow: 1px 1px #FFFFFF;">
	    <p><?php _e( 'Please read the <a target="_blank" href="https://www.tipsandtricks-hq.com/paypal-for-digital-goods-wordpress-plugin">PayPal for Digital Goods</a> plugin setup instructions to configure and use it.', 'paypal-express-checkout' ); ?>
	    </p>
	</div>
	<?php
    }

    public function disable_funding_note() {
	echo '<p>';
	_e( 'By default, funding source eligibility is smartly decided based on a variety of factors. You can force disable funding options by selecting them below.', 'paypal-express-checkout' );
	echo '</p>';
	echo '<p>';
	_e( 'Note: disabled options will disappear from button preview once you save changes.', 'paypal-express-checkout' );
	echo '</p>';
    }

    /**
     * Settings HTML
     */
    public function settings_field_callback( $args ) {
	$settings = (array) get_option( 'ppdg-settings' );

	extract( $args );

	$field_value = isset( $settings[ $field ] ) ? $settings[ $field ] : '';

	if ( empty( $size ) )
	    $size = 40;

	switch ( $field ) {
	    case 'is_live':
		echo "<input type='checkbox' name='ppdg-settings[{$field}]' value='1' " . ($field_value ? 'checked=checked' : '') . " />";
		break;
	    case 'disabled_funding':
	    case 'disabled_cards':
		foreach ( $vals as $key => $value ) {
		    echo '<label><input type="checkbox" id="wp-ppdg-' . $field . '"  name="ppdg-settings[' . $field . '][]" value="' . $value . '"' . (in_array( $value, $field_value ) ? ' checked' : '') . '>' . $texts[ $key ] . '</label> ';
		}
		break;
	    case 'btn_height':
	    case 'btn_color':
	    case 'btn_type':
	    case 'btn_shape':
		echo '<select id="wp-ppdg-' . $field . '" class="wp-ppdg-button-style" name="ppdg-settings[' . $field . ']">';
		$opts = '';
		foreach ( $vals as $key => $value ) {
		    $opts .= '<option value="' . $value . '"' . ($value === $field_value ? ' selected' : '') . '>' . $texts[ $key ] . '</option>';
		}
		echo $opts;
		echo '</select>';
		break;
	    case 'btn_width':
		echo '<input type="number" id="wp-ppdg-' . $field . '" class="wp-ppdg-button-style" placeholder="Auto" name="ppdg-settings[' . $field . ']" value="' . $field_value . '">';
		break;
	    case 'btn_layout':
		foreach ( $vals as $key => $value ) {
		    echo '<label><input type="radio" id="wp-ppdg-' . $field . '" class="wp-ppdg-button-style" name="ppdg-settings[' . $field . ']" value="' . $value . '"' . ($value === $field_value ? ' checked' : (empty( $field_value ) && $value === "vertical") ? ' checked' : '') . '>' . $texts[ $key ] . '</label> ';
		}
		break;
	    default:
		// case 'currency_code':
		// case 'live_client_id':
		// case 'sandbox_client_id':
		echo "<input type='text' name='ppdg-settings[{$field}]' value='{$field_value}' size='{$size}' />";
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
     */
    public function settings_sanitize_field_callback( $input ) {
	$output = get_option( 'ppdg-settings' );

	if ( empty( $input[ 'is_live' ] ) )
	    $output[ 'is_live' ]	 = 0;
	else
	    $output[ 'is_live' ]	 = 1;

	$output[ 'btn_height' ]	 = $input[ 'btn_height' ];
	$output[ 'btn_width' ]	 = $input[ 'btn_width' ];
	$output[ 'btn_layout' ]	 = $input[ 'btn_layout' ];
	$output[ 'btn_color' ]	 = $input[ 'btn_color' ];
	$output[ 'btn_type' ]	 = $input[ 'btn_type' ];
	$output[ 'btn_shape' ]	 = $input[ 'btn_shape' ];

	$output[ 'disabled_funding' ]	 = empty( $input[ 'disabled_funding' ] ) ? array() : $input[ 'disabled_funding' ];
	$output[ 'disabled_cards' ]	 = empty( $input[ 'disabled_cards' ] ) ? array() : $input[ 'disabled_cards' ];


	if ( ! empty( $input[ 'currency_code' ] ) )
	    $output[ 'currency_code' ] = $input[ 'currency_code' ];
	else
	    add_settings_error( 'ppdg-settings', 'invalid-currency-code', __( 'You must specify payment curency.', 'paypal-express-checkout' ) );

	if ( ! empty( $input[ 'live_client_id' ] ) )
	    $output[ 'live_client_id' ] = $input[ 'live_client_id' ];

	if ( ! empty( $input[ 'sandbox_client_id' ] ) )
	    $output[ 'sandbox_client_id' ] = $input[ 'sandbox_client_id' ];

	return $output;
    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_page() {
	include_once( 'views/admin.php' );
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     */
    public function add_action_links( $links ) {

	return array_merge(
	array(
	    'settings' => '<a href="' . admin_url( 'edit.php?post_type=' . PPECProducts::$products_slug . '&page=ppec-settings-page' ) . '">' . __( 'Settings', 'paypal-express-checkout' ) . '</a>'
	), $links
	);
    }

}
