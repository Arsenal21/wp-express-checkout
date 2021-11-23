<?php

use WP_Express_Checkout\Admin\Admin;
use WP_Express_Checkout\Main;
use WP_Express_Checkout\Products;

/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 */
if ( ! current_user_can( Main::get_instance()->get_setting( 'access_permission' ) ) ) {
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

    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors(); ?>

	<?php
	$wpec_admin = Admin::get_instance();

	/**
	 * Filters the plugin settings tabs
	 *
	 * @param array $tabs An array of settings tabs titles keyed with the tab slug.
	 */
	$wpec_plugin_tabs = apply_filters( 'wpec_settings_tabs', array(
		'ppec-settings-page'                          => __( 'General Settings', 'wp-express-checkout' ),
		'ppec-settings-page&action=email-settings'    => __( 'Email Settings', 'wp-express-checkout' ),
		'ppec-settings-page&action=advanced-settings' => __( 'Advanced Settings', 'wp-express-checkout' ),
	) );

	$current = "";
	if ( isset( $_GET['page'] ) ) {
		$current = sanitize_text_field( $_GET['page'] );
		if ( isset( $_GET['action'] ) ) {
			$current .= "&action=" . sanitize_text_field( $_GET['action'] );
		}
	}
	?>

	<h2 class="nav-tab-wrapper">
		<?php
		foreach ( $wpec_plugin_tabs as $location => $tabname ) {
			$class = ( $current == $location ) ? ' nav-tab-active' : '';
			?>
			<a class="nav-tab<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( 'edit.php?post_type=' . Products::$products_slug . '&page=' . $location ); ?>"><?php echo esc_html( $tabname ); ?></a>
			<?php
		}
		?>
	</h2>

	<div id="poststuff">

		<div id="post-body" class="metabox-holder columns-2">

			<div id="postbox-container-1" class="postbox-container">

				<?php include 'sidebar.php'; ?>

			</div>

			<div id="postbox-container-2" class="postbox-container">

				<form method="post" action="options.php">

				<?php settings_fields( 'ppdg-settings-group' ); ?>

				<?php
				if ( isset( $_GET['action'] ) ) {
					$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
					switch ( $action ) {
						case 'email-settings':
							$wpec_admin->do_settings_sections( 'paypal-for-digital-goods-emails' );
							echo "<input type='hidden' name='ppdg_page_tab' value='" . esc_attr( 'paypal-for-digital-goods-emails' ) . "' />";
							break;
						case 'advanced-settings':
							$wpec_admin->do_settings_sections( 'paypal-for-digital-goods-advanced' );
							echo "<input type='hidden' name='ppdg_page_tab' value='" . esc_attr( 'paypal-for-digital-goods-advanced' ) . "' />";
							break;
						default:
							/**
							 * Fires on the custom settings tab.
							 * Dynamic portion of the hook name refers to current tab slug (action).
							 */
							do_action( "wpec_settings_tab_{$action}" );
							break;
					}
				} else {
					$wpec_admin->do_settings_sections( 'paypal-for-digital-goods' );
					echo "<input type='hidden' name='ppdg_page_tab' value='" . esc_attr( 'paypal-for-digital-goods' ) . "' />";

				$ppdg			 = Main::get_instance();
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

					jQuery( document ).ready( function( $ ) {
						$( 'a#wpec-reset-log' ).click( function( e ) {
							e.preventDefault();
							$.post( ajaxurl,
									{ 'action': 'wpec_reset_log' },
									function( result ) {
										if ( result === '1' ) {
											alert( 'Log file has been reset.' );
										}
									} );
						} );
					} );

				</script>

				<?php
				}
				?>

				<?php submit_button(); ?>

				</form>

			</div>

		</div>

	</div>

</div>
