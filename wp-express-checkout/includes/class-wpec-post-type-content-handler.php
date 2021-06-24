<?php
/**
 * Product single page handler.
 *
 * Adds a filter to a product single page the_content hook and generates the
 * product HTML with the PayPal button.
 */

namespace WP_Express_Checkout;

use WP_Post;

/**
 * Product single page handler class.
 */
class Post_Type_Content_Handler {

	/**
	 * The class instance.
	 *
	 * @var Post_Type_Content_Handler
	 */
	protected static $instance = null;

	/**
	 * Construct the instance.
	 */
	public function __construct() {
		// handle single product page display.
		add_filter( 'the_content', array( __CLASS__, 'filter_post_type_content' ), 10 );
	}

	/**
	 * Retrieves the instance.
	 *
	 * @return Post_Type_Content_Handler
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Replaces the content with generated product HTML
	 *
	 * @global WP_Post $post
	 *
	 * @param string $content The product content to be replaced.
	 * @return string
	 */
	public static function filter_post_type_content( $content ) {
		global $post, $wp_query;
		if ( isset( $post ) && $wp_query->is_main_query() ) {
			if ( is_single( $post ) && is_singular( Products::$products_slug ) && Products::$products_slug === $post->post_type ) { // Handle the content for product type post.
				remove_filter( 'the_content', array( __CLASS__, 'filter_post_type_content' ), 10 );
				$content = do_shortcode( '[wp_express_checkout product_id="' . $post->ID . '" template="2" is_post_tpl="1" in_the_loop="' . + in_the_loop() . '"]' );
				add_filter( 'the_content', array( __CLASS__, 'filter_post_type_content' ), 10 );
			}
		}
		return $content;
	}

}
