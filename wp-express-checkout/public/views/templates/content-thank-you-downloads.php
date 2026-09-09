<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Thank You page template
 *
 * @package wp-express-checkout
 */
?>
<div class='wpec-thank-you-page-download-link'>
	<span><?php echo esc_html(_n( 'Download link', 'Download links', count( $downloads ), 'wp-express-checkout' )); ?>:</span>
	<br/>
	[wpec_ty field=download_link]
</div>
