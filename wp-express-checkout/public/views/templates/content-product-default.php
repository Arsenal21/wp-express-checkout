<?php
/**
 * The product item template
 *
 * @package wp-express-checkout
 */

$wpec_shortcode = WP_Express_Checkout\Shortcodes::get_instance();
?>

<div class="wpec-product-item wpec-product-item-<?php echo esc_attr( $wpec_button_args['product_id'] ); ?> wpec-product-default-template">
	
	<div class="wpec-price-container <?php echo esc_attr( $wpec_button_args['price_class'] );?>" style="display:none">
		<?php echo $wpec_shortcode->generate_price_tag( $wpec_button_args ); ?>
	</div>
	<div class="wpec-product-buy-button">
		<?php echo $wpec_shortcode->generate_pp_express_checkout_button( $wpec_button_args ); ?>
	</div>
</div>
