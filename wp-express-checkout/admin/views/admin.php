<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 */
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'You do not have permission to access this settings page.' );
}
?>

<style>
    #wp-ppdg-preview-container {
	margin-top: 10px; width: 500px; padding: 10px;
	position: relative;
    }
    #wp-ppdg-preview-protect {
	width: 100%;
	height: 100%;
	position: absolute;
	top: 0;
	left: 0;
	z-index: 1000;
    }
    .wp-ppdg-button-style {
	min-width: 150px;
    }
</style>

<div class="wrap">

    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

    <form method="post" action="options.php">

	<?php settings_fields( 'ppdg-settings-group' ); ?>

	<?php do_settings_sections( 'paypal-for-digital-goods' ); ?>
	<div style="background-color: white; padding: 10px; border: 1px dashed silver;">
	    <?php _e( 'To get your client ID or set up a new one:', 'paypal-express-checkout' ); ?><br/>
	    <ol>
		<li><?php _e( 'Navigate to <a href="https://developer.paypal.com/developer/applications/" target="_blank">My Apps &amp; Credentials</a> and click <strong>Log into Dashboard</strong> in the top, right corner of the page.', 'paypal-express-checkout' ); ?></li>
		<li><?php _e( 'Scroll down to <strong>REST API Apps</strong> and click the name of your app to see the app\'s details. If you don\'t have any apps, create one now:', 'paypal-express-checkout' ); ?><br>
		    <?php _e( 'a. Click <strong>Create App</strong>.', 'paypal-express-checkout' ); ?><br>
		    <?php _e( 'b. In <strong>App Name</strong>, enter a name and then click <strong>Create App</strong> again. The app is created and your client ID is displayed.', 'paypal-express-checkout' ); ?></li>
		<li><?php _e( 'Click the <strong>Sandbox</strong> / <strong>Live</strong> toggle to display and copy the client ID for each environment.', 'paypal-express-checkout' ); ?></li>
	    </ol>
	</div>
	<?php
	$ppdg			 = PPDG::get_instance();
	$args			 = array();
	$disabled_funding	 = $ppdg->get_setting( 'disabled_funding' );
	if ( ! empty( $disabled_funding ) ) {
	    $arg = '';
	    foreach ( $disabled_funding as $funding ) {
		$arg .= $funding . ',';
	    }
	    $arg				 = rtrim( $arg, ',' );
	    $args[ 'disable-funding' ]	 = $arg;
	}
	//check if cards aren't disabled globally first
	if ( ! in_array( 'card', $disabled_funding ) ) {
	    $disabled_cards = $ppdg->get_setting( 'disabled_cards' );
	    if ( ! empty( $disabled_cards ) ) {
		$arg = '';
		foreach ( $disabled_cards as $card ) {
		    $arg .= $card . ',';
		}
		$arg			 = rtrim( $arg, ',' );
		$args[ 'disable-card' ]	 = $arg;
	    }
	}
	$script_url = add_query_arg( $args, 'https://www.paypal.com/sdk/js?client-id=123' );
	printf( '<script src="%s"></script>', $script_url );
	?>
	<script>
	    var wp_ppdg = {
		btn_container: jQuery('#paypal-button-container'),
		btn_height: 25,
		btn_color: 'gold',
		btn_type: 'checkout',
		btn_shape: 'pill',
		btn_layout: 'vertical',
		btn_sizes: {small: 25, medium: 35, large: 45, xlarge: 55}
	    };
	    function wp_ppdg_render_preview() {
		jQuery('#paypal-button-container').html('');
		var styleOpts = {
		    layout: wp_ppdg.btn_layout,
		    shape: wp_ppdg.btn_shape,
		    label: wp_ppdg.btn_type,
		    height: wp_ppdg.btn_height,
		    color: wp_ppdg.btn_color,
		};
		if (styleOpts.layout === 'horizontal') {
		    styleOpts.tagline = false;
		}
		paypal.Buttons({
		    style: styleOpts,

		    client: {
			sandbox: '123',
		    },
		    funding: 'paypal',

		}).render('#paypal-button-container');
	    }

	    jQuery('.wp-ppdg-button-style').change(function () {
		var btn_height = jQuery('#wp-ppdg-btn_height').val();
		wp_ppdg.btn_height = wp_ppdg.btn_sizes[btn_height];

		var btn_width = jQuery('#wp-ppdg-btn_width').val();
		if (btn_width) {
		    if (btn_width < 150) {
			btn_width = 150;
			jQuery('#wp-ppdg-btn_width').val(btn_width);
		    }
		    wp_ppdg.btn_container.css('width', btn_width);
		} else {
		    wp_ppdg.btn_container.css('width', 'auto');
		    jQuery('#wp-ppdg-btn_width').val('');
		}

		wp_ppdg.btn_layout = jQuery('#wp-ppdg-btn_layout:checked').val();
		wp_ppdg.btn_color = jQuery('#wp-ppdg-btn_color').val();
		wp_ppdg.btn_type = jQuery('#wp-ppdg-btn_type').val();
		wp_ppdg.btn_shape = jQuery('#wp-ppdg-btn_shape').val();
		wp_ppdg_render_preview();
	    }
	    );
	    jQuery('#wp-ppdg-btn_height').change();

	</script>
	<?php submit_button(); ?>
    </form>
</div>
