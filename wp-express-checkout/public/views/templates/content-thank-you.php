<?php

/**
 * Thank You page template
 *
 * @package wp-express-checkout
 */

// @todo: Replace following variables usage with appropriate merge tag and shortcode:
$order_id  = (int) $_GET['order_id'];
$downloads = WP_Express_Checkout\View_Downloads::get_order_downloads_list( $order_id );
?>

<div class="wpec_thank_you_message">
	<p><?php esc_html_e( 'Thank you for your purchase.', 'wp-express-checkout' ); ?></p>
	<p><?php esc_html_e( 'Your purchase details are below:', 'wp-express-checkout' ); ?></p>
	<p>{product_details}</p>
	<p><?php esc_html_e( 'Transaction ID: ', 'wp-express-checkout' ); ?>{transaction_id}</p>

	<?php
	if ( ! empty( $downloads ) ) {
	?>
		<br />
		<div class='wpec-thank-you-page-download-link'>
			<span><?php echo _n( 'Download link', 'Download links', count( $downloads ), 'wp-express-checkout' ); ?>:</span>
			<br/>
			<?php
			$download_txt      = __( 'Click here to download', 'wp-express-checkout' );
			$link_tpl          = apply_filters( 'wpec_downloads_list_item_template', '%1$s - <a href="%2$s">%3$s</a><br/>' );
			foreach ( $downloads as $name => $download_url ) {
				printf( $link_tpl, $name, $download_url, $download_txt );
			}
			?>
		</div>
	<?php
	}
	?>

</div><!-- end .wpec_thank_you_message-->
