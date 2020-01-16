<?php
/**
 * Single product template
 *
 * @package wp-express-checkout
 */

$wpec_shortcode = WPECShortcode::get_instance();
?>

<div class="wpec-product-item">
	<div class="wpec-price-container">
		<span class="wpec-price-amount"><?php echo WPEC_Utility_Functions::price_format( $wpec_button_args['price'] ) ?></span> <span class="wpec-new-price-amount"></span> <!--<span class="wpec-quantity"></span>-->
		<!--<div class="wpec_under_price_line"></div>-->
	</div>
	<div class="wpec-product-buy-button">
		<?php echo $wpec_shortcode->generate_pp_express_checkout_button( $wpec_button_args ); ?>
	</div>
</div>
