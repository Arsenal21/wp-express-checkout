<?php
/**
 * The product item template
 *
 * @package wp-express-checkout
 */

$wpec_shortcode = WPECShortcode::get_instance();
?>

<div class="wpec-title-container">
	<?php the_title( sprintf( '<h3 class="wpec-entry-title"><a href="%1$s" title="%2$s" rel="bookmark">', esc_url( get_permalink() ), esc_attr( get_the_title() ) ), '</a></h3>' ); ?>
</div>


<div class="wpec-thumbnail-container">
	<?php the_post_thumbnail( 'thumbnail' ); ?>
</div>

<div class="wpec-content-container">
	<?php the_excerpt(); ?>
</div>

<div class="wpec-price-container">
	<span class="wpec-price">
		<?php echo WPEC_Utility_Functions::price_format( $wpec_button_args['price'] ) ?>
	</span>
</div>
<?php

echo $wpec_shortcode->generate_pp_express_checkout_button( $wpec_button_args );
