<?php
/**
 * The product item template
 *
 * @package wp-express-checkout
 *
 * @var array $wpec_sc_args See $wp_query->set( 'wpec_sc_args', $args ) at wp-express-checkout/public/includes/class-shortcodes.php:166
 */

$wpec_shortcode = WP_Express_Checkout\Shortcodes::get_instance();
?>
<!-- the .wpec-product-item-wrapper class is needed for the JS code to find the .wpec-price-container within it -->
<div class="wpec-product-default-template wpec-product-item-wrapper wpec-product-item-<?php echo esc_attr( $wpec_sc_args['product_id'] ); ?>">
	
	<div class="wpec-price-container <?php echo esc_attr( $wpec_sc_args['price_class'] );?>" style="display:none">
		<?php echo $wpec_shortcode->generate_price_tag( $wpec_sc_args ); ?>
	</div>
	<div class="wpec-product-buy-button">
		<?php echo $wpec_shortcode->generate_express_checkout_buttons( $wpec_sc_args ); ?>
	</div>
</div>
