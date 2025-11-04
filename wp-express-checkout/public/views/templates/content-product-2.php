<?php
/**
 * Single product template
 *
 * @package wp-express-checkout
 *
 * @var array $wpec_sc_args See $wp_query->set( 'wpec_sc_args', $args ) at wp-express-checkout/public/includes/class-shortcodes.php:166
 */

$wpec_shortcode = WP_Express_Checkout\Shortcodes::get_instance();
?>

<div class = "wpec-post-item wpec-post-item-<?php echo esc_attr( $wpec_sc_args['product_id'] ); ?>">
	<div class = "wpec-post-item-top">
		<div class = "wpec-post-thumbnail">
			<?php if ( ! empty( $wpec_sc_args['thumbnail_url'] ) ) { ?>
				<img src="<?php echo esc_url( $wpec_sc_args['thumbnail_url'] ); ?>" class="attachment-thumbnail size-thumbnail wp-post-image" alt="">
			<?php } ?>
		</div>
		<div class = "wpec-post-title">
			<?php the_title(); ?>
		</div>
		<div class = "wpec-post-description">
			<?php the_content(); ?>
		</div>
		<div class="wpec-price-container <?php echo esc_attr( $wpec_sc_args['price_class'] );?>">
			<?php echo $wpec_shortcode->generate_price_tag( $wpec_sc_args ); ?>
		</div>
		<div class="wpec-product-buy-button">
			<?php echo $wpec_shortcode->generate_express_checkout_buttons( $wpec_sc_args ); ?>
		</div>
	</div>
</div>
