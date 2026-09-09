<?php
/*
 * Settings page sidebar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="side-sortables" class="meta-box-sortables ui-sortable">

	<div class="postbox yellowish" style="min-width: inherit;">
		<h3 class="hndle"><label for="title"><?php esc_html_e( 'Plugin Documentation', 'wp-express-checkout' ); ?></label></h3>
		<div class="inside">
			<?php
			/* translators: %s is a link to the WP Express Checkout documentation. */
			echo sprintf( esc_html__( 'Please read the %s plugin setup instructions and tutorials to learn how to configure and use it.', 'wp-express-checkout' ), '<a target="_blank" href="' . esc_url( 'https://wp-express-checkout.com/wp-express-checkout-plugin-documentation/' ) . '">WP Express Checkout</a>' );
			?>
		</div>
	</div>
	<!--<div class="postbox" style="min-width: inherit;">
		<h3 class="hndle"><label for="title"><?php // _e( 'Add-ons', 'wp-express-checkout' ); ?></label></h3>
		<div class="inside">
			<?php // echo sprintf( __( 'Want additional functionality? Check out our <a target="_blank" href="%s">Add-Ons!</a>', 'wp-express-checkout' ), 'edit.php?post_type=ppec-products&page=wpec-addons' ); ?>
		</div>
	</div>-->
	<div class="postbox" style="min-width: inherit;">
		<h3 class="hndle"><label for="title"><?php esc_html_e( 'Explore Our Other E-Commerce Plugins', 'wp-express-checkout' ); ?></label></h3>
		<div class="inside">
			<ul>
				<li><a target="_blank" href="https://wordpress.org/plugins/stripe-payments/">Accept Stripe Payments</a></li>
				<li><a target="_blank" href="https://wordpress.org/plugins/wordpress-simple-paypal-shopping-cart/">Simple Shopping Cart</a></li>
				<li><a target="_blank" href="https://www.tipsandtricks-hq.com/wordpress-estore-plugin-complete-solution-to-sell-digital-products-from-your-wordpress-blog-securely-1059">WP eStore</a></li>
			</ul>
		</div>
	</div>
	<div class="postbox" style="min-width: inherit;">
		<h3 class="hndle"><label for="title"><?php esc_html_e( 'Help Us Keep the Plugin Free & Maintained', 'wp-express-checkout' ); ?></label></h3>
		<div class="inside">
			<?php
			/* translators: %s is a link to the plugin reviews page. */
			echo sprintf( esc_html__( 'Like the plugin? Please give it a good %s!', 'wp-express-checkout' ), '<a href="' . esc_url( 'https://wordpress.org/support/plugin/wp-express-checkout/reviews/' ) . '" target="_blank">rating</a>' );
			?>
			<div class="wpec-stars-container">
				<a href="<?php echo esc_url( 'https://wordpress.org/support/plugin/wp-express-checkout/reviews/' ); ?>" target="_blank">
					<span class="dashicons dashicons-star-filled"></span>
					<span class="dashicons dashicons-star-filled"></span>
					<span class="dashicons dashicons-star-filled"></span>
					<span class="dashicons dashicons-star-filled"></span>
					<span class="dashicons dashicons-star-filled"></span>
				</a>
			</div>
		</div>
	</div>
	<div class="postbox" style="min-width: inherit;">
		<h3 class="hndle"><label for="title"><?php esc_html_e( 'Our Other Plugins', 'wp-express-checkout' ); ?></label></h3>
		<div class="inside">
			<?php
			/* translators: %s is a link to the developer plugins page. */
			echo sprintf( esc_html__( 'Check out %s', 'wp-express-checkout' ), '<a target="_blank" href="' . esc_url( 'https://www.tipsandtricks-hq.com/development-center' ) . '">our other plugins</a>' );
			?>
		</div>
	</div>
</div>