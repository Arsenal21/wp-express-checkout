<?php
/**
 * Modal window template
 *
 * @package wp-express-checkout
 */
?>

<!--Modal-->
<div id="wpec-modal-<?php echo esc_attr( $button_id ); ?>" class="wpec-modal wpec-opacity-0 wpec-pointer-events-none">

	<div class="wpec-modal-overlay"></div>

	<div class="wpec-modal-container">
		<div class="wpec-modal-content">
			<!--Title-->
			<div class="wpec-modal-content-title">
				<p><?php the_title(); ?></p>
				<div class="wpec-modal-close">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
						<path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
					</svg>
				</div>
			</div>
			<?php echo $output; ?>
		</div>
	</div>
</div>

<button data-wpec-modal="wpec-modal-<?php echo esc_attr( $button_id ); ?>" class="wpec-modal-open">
	<?php esc_html_e( 'Buy Now', 'wp-express-checkout' ); ?>
</button>
