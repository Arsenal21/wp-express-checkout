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
		'ppec-settings-page&action=paypal-settings' => __( 'PayPal Settings', 'wp-express-checkout' ),
		'ppec-settings-page&action=email-settings'    => __( 'Email Settings', 'wp-express-checkout' ),
		'ppec-settings-page&action=advanced-settings' => __( 'Advanced Settings', 'wp-express-checkout' ),
		'ppec-settings-page&action=manual-checkout' => __( 'Manual/Offline Checkout', 'wp-express-checkout' ),
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
			<a class="nav-tab<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( WPEC_MENU_PARENT_SLUG . '&page=' . $location ); ?>"><?php echo esc_html( $tabname ); ?></a>
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
						case 'paypal-settings':
                            $wpec_admin->do_settings_sections_no_wrap( 'paypal-for-digital-goods-pp-settings' );
							require WPEC_PLUGIN_PATH . '/admin/views/settings-tabs/paypal-settings.php';
							break;
						case 'email-settings':
							$wpec_admin->do_settings_sections( 'paypal-for-digital-goods-emails' );
							echo "<input type='hidden' name='ppdg_page_tab' value='" . esc_attr( 'paypal-for-digital-goods-emails' ) . "' />";
							break;
						case 'advanced-settings':
							$wpec_admin->do_settings_sections( 'paypal-for-digital-goods-advanced' );
							echo "<input type='hidden' name='ppdg_page_tab' value='" . esc_attr( 'paypal-for-digital-goods-advanced' ) . "' />";
							break;
						case 'manual-checkout':
							$wpec_admin->do_settings_sections( 'paypal-for-digital-goods-manual-checkout' );
							echo "<input type='hidden' name='ppdg_page_tab' value='" . esc_attr( 'paypal-for-digital-goods-manual-checkout' ) . "' />";
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
                    ?>
                    <script>
                        jQuery( document ).ready( function( $ ) {
                            $( 'a#wpec-reset-log' ).click( function( e ) {
                                e.preventDefault();
                                $.post( ajaxurl,
                                        { 'action': 'wpec_reset_log', 'nonce': wpecAdminSideVars.wpec_settings_ajax_nonce },
                                        function( result ) {
                                            if ( result === '1' ) {
                                                alert( 'Log file has been reset.' );
                                            } else {
                                                alert( 'Error trying to reset log: ' + result );
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
