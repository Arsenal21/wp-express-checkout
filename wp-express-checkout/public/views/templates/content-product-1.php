<?php
/**
 * The product item template
 *
 * @package wp-express-checkout
 */

$wpec_shortcode = WP_Express_Checkout\Shortcodes::get_instance();
?>

<div class="wpec-product-item wpec-product-item-<?php echo esc_attr( $wpec_button_args['product_id'] ); ?>">
	<div class="wpec-product-item-top">
		<div class="wpec-product-item-thumbnail">
			<?php if ( ! empty( $wpec_button_args['thumbnail_url'] ) ) { ?>
				<img width="150" height="150" src="<?php echo esc_url( $wpec_button_args['thumbnail_url'] ); ?>" class="attachment-thumbnail size-thumbnail wp-post-image" alt="">
			<?php } ?>
		</div>
		<div class="wpec-product-name">
			<?php the_title( sprintf( '<h3 class="wpec-entry-title"><a href="%1$s" title="%2$s" rel="bookmark">', esc_url( get_permalink() ), esc_attr( get_the_title() ) ), '</a></h3>' ); ?>
		</div>
	</div>
	<div style="clear:both;"></div>
	<div class="wpec-product-description">
		<?php the_content(); ?>
	</div>
	<div class="wpec-price-container">
		<?php echo $wpec_shortcode->generate_price_tag( $wpec_button_args ); ?>
	</div>
	<div class="wpec-product-buy-button">
		<?php echo $wpec_shortcode->generate_pp_express_checkout_button( $wpec_button_args ); ?>
	</div>
</div>
