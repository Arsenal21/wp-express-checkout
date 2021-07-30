<?php
/**
 * Thank You page template
 *
 * @package wp-express-checkout
 */
?>

<div class="wpec_thank_you_message">
	<p><?php esc_html_e( 'Thank you for your purchase.', 'wp-express-checkout' ); ?></p>
	<br />
	<p><?php esc_html_e( 'Your purchase details are below:', 'wp-express-checkout' ); ?></p>
	<br />

	[wpec_ty field=product_details]
	<br />

	<p><?php esc_html_e( 'Transaction ID: ', 'wp-express-checkout' ); ?>[wpec_ty field=transaction_id]</p>
	<br />

	[wpec_ty_downloads]
	<br />

</div><!-- end .wpec_thank_you_message-->
