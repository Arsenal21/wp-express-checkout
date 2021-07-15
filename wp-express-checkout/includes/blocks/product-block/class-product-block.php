<?php
/**
 * Product block.
 *
 * @package WPEC
 */

namespace WP_Express_Checkout\Blocks;

use WP_Express_Checkout\Products;

/**
 * Product block.
 */
class Product_Block extends Dynamic_Block {

	/**
	 * Block type name including namespace.
	 * @var string
	 */
	protected $name = 'wp-express-checkout/product-block';

	/**
	 * Registers the block.
	 *
	 * @uses register_block_type() For WP support 5.0+
	 * @todo use register_block_type_from_metadata() for WP support 5.5+
	 *
	 * @see https://developer.wordpress.org/block-editor/tutorials/block-tutorial/writing-your-first-block-type/
	 * @see https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/creating-dynamic-blocks/
	 */
	public function __construct(  ) {
		wp_register_style( 'wpec-block-editor', WPEC_PLUGIN_URL . '/assets/css/blocks.css', array(), WPEC_PLUGIN_VER );
		wp_register_script( 'wpec-product-block', WPEC_PLUGIN_URL . '/assets/js/blocks/product-block.js', array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' ), WPEC_PLUGIN_VER );

		wp_localize_script( 'wpec-product-block', 'wpec_prod_opts', $this->get_products_array() );
		wp_localize_script( 'wpec-product-block', 'wpec_prod_template_opts', $this->get_product_templates_array() );
		wp_localize_script( 'wpec-product-block', 'wpec_block_prod_str', array(
			'title'    => 'WP Express Checkout Product',
			'product'  => __( 'Product', 'wp-express-checkout' ),
			'template' => __( 'Template', 'wp-express-checkout' ),
			'modal'    => __( 'Show in Modal', 'wp-express-checkout' ),
			'panel'    => __( 'Layout Options', 'wp-express-checkout' ),
		) );

		$args = array(
			'attributes' => array(
				'prod_id'  => array(
					'type'    => 'string',
					'default' => 0,
				),
				'template' => array(
					'type'    => 'string',
					'default' => 1,
				),
				'modal' => array(
					'type'    => 'boolean',
					'default' => true,
				),
			),
			'editor_script'   => 'wpec-product-block',
			'editor_style'    => 'wpec-block-editor',
		);

		parent::__construct( $args );
	}

	/**
	 * Reneders the block content dynamically.
	 *
	 * @param array  $atts    The block attributes.
	 * @param string $content Generated block HTML.
	 *
	 * @return string
	 */
	public function render_callback( $atts, $content ) {
		$prod_id = ! empty( $atts['prod_id'] ) ? intval( $atts['prod_id'] ) : 0;

		if ( empty( $prod_id ) ) {
			return '<p class="wpec-no-product-placeholder">' . __( 'Select product to view', 'wp-express-checkout' ) . '</p>';
		}

		$sc_str = 'wp_express_checkout product_id="%d"';
		$sc_str = sprintf( $sc_str, $prod_id );

		if ( ! empty( $atts['template'] ) ) {
			$sc_str .= ' template="' . intval( $atts['template'] ) . '"';
		}

		if ( empty( $atts['modal'] ) ) {
			$sc_str .= ' modal="0"';
		}

		return do_shortcode( '[' . $sc_str . ']' );
	}

	/**
	 * Retrieves the products array for the block options.
	 *
	 * @return array
	 */
	private function get_products_array() {
		$query = get_posts(
			array(
				'post_type'      => Products::$products_slug,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$prod_arr = array(
			array(
				'label' => __( '(Select product)', 'wp-express-checkout' ),
				'value' => 0,
			),
		);
		foreach ( $query as $post ) {
			$title      = html_entity_decode( $post->post_title );
			$prod_arr[] = array(
				'label' => esc_attr( $title ),
				'value' => $post->ID,
			);
		}
		wp_reset_postdata();
		return $prod_arr;
	}

	/**
	 * Retrieves the product templates array for the block options.
	 *
	 * @return array
	 */
	private function get_product_templates_array() {
		$templates = array(
			array(
				'label' => __( 'Show Button Only', 'wp-express-checkout' ),
				'value' => 0,
			),
			array(
				'label' => __( 'Compact Product template', 'wp-express-checkout' ),
				'value' => 1,
			),
			array(
				'label' => __( 'Full Product template', 'wp-express-checkout' ),
				'value' => 2,
			),
		);
		return $templates;
	}
}
