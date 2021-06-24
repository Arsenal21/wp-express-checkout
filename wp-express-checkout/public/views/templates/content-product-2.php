<?php
/**
 * Single product template
 *
 * @package wp-express-checkout
 */

$wpec_shortcode = WP_Express_Checkout\Shortcodes::get_instance();
?>

<div class = "wpec-post-item wpec-post-item-<?php echo esc_attr( $wpec_button_args['product_id'] ); ?>">
	<div class = "wpec-post-item-top">
		<div class = "wpec-post-thumbnail">
			<?php if ( ! empty( $wpec_button_args['thumbnail_url'] ) ) { ?>
				<img src="<?php echo esc_url( $wpec_button_args['thumbnail_url'] ); ?>" class="attachment-thumbnail size-thumbnail wp-post-image" alt="">
			<?php } ?>
		</div>
		<div class = "wpec-post-title">
			<?php the_title(); ?>
		</div>
		<div class = "wpec-post-description">
			<?php the_content(); ?>
		</div>
		<div class="wpec-price-container">
			<?php echo $wpec_shortcode->generate_price_tag( $wpec_button_args ); ?>
		</div>
		<div class="wpec-product-buy-button">
			<?php echo $wpec_shortcode->generate_pp_express_checkout_button( $wpec_button_args ); ?>
		</div>
	</div>
</div>
