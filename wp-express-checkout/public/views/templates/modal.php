<?php
/**
 * Modal window template
 *
 * @package wp-express-checkout
 */
?>

<!--Modal-->
<div id="wpec-modal-<?php echo esc_attr( $button_id ); ?>" class="wpec-modal wpec-opacity-0 wpec-pointer-events-none wpec-modal-product-<?php echo esc_attr( $product_id ); ?>">

	<div class="wpec-modal-overlay"></div>

	<div class="wpec-modal-container">
		<div class="wpec-modal-content">
			<!--Title-->
			<div class="wpec-modal-content-title">
				<p><?php echo esc_html( $modal_title ); ?></p>
				<div class="wpec-modal-close">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
						<path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
					</svg>
				</div>
			</div>
			<?php if ( ! empty( $thumbnail_url ) ) { ?>
				<div class="wpec-modal-item-info-wrap">
					<div class="wpec-modal-item-thumbnail">
						<img width="150" height="150" src="<?php echo esc_url( $thumbnail_url ); ?>" class="attachment-thumbnail size-thumbnail wp-post-image" alt="">
					</div>
					<div class="wpec-modal-item-excerpt">
						<?php echo wp_trim_words( get_the_content(), 30 ); ?>
					</div>
				</div>
			<?php } ?>
			<?php echo $output; ?>
		</div>
	</div>
</div>

<button data-wpec-modal="wpec-modal-<?php echo esc_attr( $button_id ); ?>" class="wpec-modal-open wpec-modal-open-product-<?php echo esc_attr( $product_id ); ?>">
	<?php echo esc_html( $button_text ); ?>
</button>
