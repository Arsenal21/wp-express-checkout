<?php
/*
 * Settings page sidebar
 */
?>
<div id="side-sortables" class="meta-box-sortables ui-sortable">

	<div class="postbox" style="min-width: inherit;">
		<h3 class="hndle"><label for="title"><?php _e( 'Plugin Documentation', 'wp-express-checkout' ); ?></label></h3>
		<div class="inside">
			<?php echo sprintf( __( 'Please read the <a target="_blank" href="%s">WP Express Checkout</a> plugin setup instructions and tutorials to learn how to configure and use it.', 'wp-express-checkout' ), 'https://wp-express-checkout.com/wp-express-checkout-plugin-documentation/' ); ?>
		</div>
	</div>
	<!--<div class="postbox" style="min-width: inherit;">
		<h3 class="hndle"><label for="title"><?php _e( 'Add-ons', 'wp-express-checkout' ); ?></label></h3>
		<div class="inside">
			<?php echo sprintf( __( 'Want additional functionality? Check out our <a target="_blank" href="%s">Add-Ons!</a>', 'wp-express-checkout' ), 'edit.php?post_type=ppec-products&page=wpec-addons' ); ?>
		</div>
	</div>-->
	<div class="postbox yellowish" style="min-width: inherit;">
		<h3 class="hndle"><label for="title"><?php echo __( 'Need Something Bigger?', 'wp-express-checkout' ); ?></label></h3>
		<div class="inside">
			<?php _ex( 'If you need a feature rich plugin (with good support) for selling your products and services then check out our', 'Followed by a link to eStore plugin', 'wp-express-checkout' ); ?>
			<a target="_blank" href="https://www.tipsandtricks-hq.com/wordpress-estore-plugin-complete-solution-to-sell-digital-products-from-your-wordpress-blog-securely-1059">WP eStore Plugin</a>.
		</div>
	</div>
	<div class="postbox" style="min-width: inherit;">
		<h3 class="hndle"><label for="title"><?php _e( 'Rate Us', 'wp-express-checkout' ); ?></label></h3>
		<div class="inside">
			<?php echo sprintf( __( 'Like the plugin? Please give us a <a href="%s" target="_blank">rating!</a>', 'wp-express-checkout' ), 'https://wordpress.org/support/plugin/wp-express-checkout/reviews/?filter=5' ); ?>
			<div class="wpec-stars-container">
				<a href="https://wordpress.org/support/plugin/wp-express-checkout/reviews/?filter=5" target="_blank">
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
		<h3 class="hndle"><label for="title"><?php _e( 'Our Other Plugins', 'wp-express-checkout' ); ?></label></h3>
		<div class="inside">
			<?php echo sprintf( __( 'Check out <a target="_blank" href="%s">our other plugins</a>', 'wp-express-checkout' ), 'https://www.tipsandtricks-hq.com/development-center' ); ?>
		</div>
	</div>
</div>