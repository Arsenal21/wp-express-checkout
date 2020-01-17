<?php
/**
 * Single product template
 *
 * @package wp-express-checkout
 */

$wpec_shortcode = WPECShortcode::get_instance();
?>

<div class = "wpec-post-item">
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
			<span class="wpec-price-amount"><?php echo WPEC_Utility_Functions::price_format( $wpec_button_args['price'] ) ?></span> <span class="wpec-new-price-amount"></span> <!--<span class="wpec-quantity"></span>-->
			<!--<div class="wpec_under_price_line"></div>-->
		</div>
		<div class="wpec-product-buy-button">
			<?php echo $wpec_shortcode->generate_pp_express_checkout_button( $wpec_button_args ); ?>
		</div>
	</div>
</div>
