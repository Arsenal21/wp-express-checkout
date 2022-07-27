<?php
/**
 * The product item template 3
 *
 * @package wp-express-checkout
 */

$wpec_shortcode = WP_Express_Checkout\Shortcodes::get_instance();
 
$wpec_button_args["button_text"] = __( "Buy Now" , "wp-express-checkout" );

?>

<div class="wpec-product-item-template-3 wpec-product-item wpec-product-item-<?php echo esc_attr( $wpec_button_args['product_id'] ); ?>">

    <div class="wpec-product-inner-cont-template-3">
        <div class="wpec-product-item-thumbnail-3">
            <?php if ( ! empty( $wpec_button_args['thumbnail_url'] ) ) { ?>
				<img width="150" height="150" src="<?php echo esc_url( $wpec_button_args['thumbnail_url'] ); ?>" class="attachment-thumbnail size-thumbnail wp-post-image" alt="">
			<?php } ?>
		</div>
		<div class="wpec-post-title wpec-post-title-template-3">
			<?php the_title(); ?>
		</div>

    
        
        <div style="clear:both;"></div>
        
        <div class="wpec-price-container <?php echo esc_attr( $wpec_button_args['price_class'] );?>">
            <?php echo $wpec_shortcode->generate_price_tag( $wpec_button_args ); ?>
        </div>
        <div class="wpec-product-buy-button">
            <?php echo $wpec_shortcode->generate_pp_express_checkout_button( $wpec_button_args ); ?>
        </div>
    </div>
</div>


<style>

.wpec-product-item-thumbnail-3 {
   
}
.wpec-post-title-template-3 {
    font-size: 16px;
    margin-bottom: 0px;
   
}
.wpec-product-item-template-3 {
  
	width: 250px;
    
}

.wpec-product-inner-cont-template-3 {
    text-align: center;
}

.wpec-product-inner-cont-template-3 .wpec-modal-open {
    background-color: #fff;
    border-radius: 4px;
    font-weight: 700;
    padding-top: 8px;
    padding-bottom: 8px;
    padding-left: 16px;
    padding-right: 16px;
    color: #000;
}

.wpec-product-inner-cont-template-3 .wpec-modal-open:hover {
    background-color: #f0f0f0;
    color: #000;
    font-weight: bold;
    cursor: pointer;
}
</style>