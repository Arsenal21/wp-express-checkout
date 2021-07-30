<?php

/**
 * Thank You page template
 *
 * @package wp-express-checkout
 */
?>
<div class='wpec-thank-you-page-download-link'>
	<span><?php echo _n( 'Download link', 'Download links', count( $downloads ), 'wp-express-checkout' ); ?>:</span>
	<br/>
	[wpec_ty field=download_link]
</div>
