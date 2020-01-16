<?php
/**
 * Single product template
 *
 * @package wp-express-checkout
 */

$wpec_shortcode = WPECShortcode::get_instance();
?>

<div class="wpec_price_container">
	<span class="wpec_price_container">
		<?php echo WPEC_Utility_Functions::price_format( $wpec_button_args['price'] ) ?>
	</span>
</div>
<?php

echo $wpec_shortcode->generate_pp_express_checkout_button( $wpec_button_args );
