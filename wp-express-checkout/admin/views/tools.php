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
	$wpec_plugin_tabs = apply_filters( 'wpec_tools_tabs', array(
		'wpec-tools-page' => __( 'General Tools', 'wp-express-checkout' ),
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

				<?php
				if ( isset( $_GET['action'] ) ) {
					$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
					/**
					 * Fires on the custom tols tab.
					 * Dynamic portion of the hook name refers to current tab slug (action).
					 */
					do_action( "wpec_tools_tab_{$action}" );
				} else {
					echo '<form method="post" action="options.php">';
					settings_fields( 'wpec-tools-group' );
					$wpec_admin->do_settings_sections( 'wpec-tools-page-emails' );
					echo "<input type='hidden' name='ppdg_page_tab' value='" . esc_attr( 'wpec-tools-page-emails' ) . "' />";
					echo '</form>';
				}
				?>

			</div>

		</div>

	</div>

</div>
