<?php

namespace WP_Express_Checkout;

/**
 * Order tags generator with plain text output.
 *
 * @since 2.1.1
 */
class Order_Tags_Plain extends Order_Tags_Html {

	/**
	 * Generates Order Summary Table.
	 *
	 * @return string
	 */
	public function product_details() {
		$table = new Order_Summary_Plain( $this->order );
		ob_start();
		$table->show();
		$output = ob_get_clean();

		// Adding a download link b/c it was not provided with own merge tag.
		$output .= $this->download_link();

		return $output;
	}

	/**
	 * Generates download links.
	 *
	 * @param array $args {
	 *     Optional. An array of additional parameters.
	 *
	 *     @type string $anchor_text Default 'Click here to download'.
	 *     @type string $target      Default '_blank'.
	 * }
	 *
	 * @return string
	 */
	public function download_link( $args = array() ) {
		$downloads = View_Downloads::get_order_downloads_list( $this->order->get_id() );

		if ( ! $downloads ) {
			return '';
		}

		$content = "\n\n";
		// Include the download links in the product details.
		foreach ( $downloads as $name => $download_url ) {
			/* Translators:  %1$s - download item name; %2$s - download URL */
			$content .= sprintf( __( '%1$s - download link: %2$s', 'wp-express-checkout' ), $name, $download_url ) . "\n";
		}

		return $content;

	}

}
