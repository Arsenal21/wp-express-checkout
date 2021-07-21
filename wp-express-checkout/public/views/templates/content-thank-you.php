<?php
/**
 * Thank You page template
 *
 * @package wp-express-checkout
 */
?>

<div class="wpec_thank_you_message">
	<p><?php esc_html_e( 'Thank you for your purchase.', 'wp-express-checkout' ); ?></p>
	<p><?php esc_html_e( 'Your purchase details are below:', 'wp-express-checkout' ); ?></p>

	[wpec_ty_product_details]

	<p><?php esc_html_e( 'Transaction ID: ', 'wp-express-checkout' ); ?>[wpec_ty_transaction_id]</p>

	[wpec_ty_downloads]

</div><!-- end .wpec_thank_you_message-->
