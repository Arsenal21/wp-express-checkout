<?php
/**
 * Abstract dynamic block for server-side rendering.
 *
 * @package WPEC
 */

namespace WP_Express_Checkout\Blocks;

/**
 * Dynamic block.
 */
abstract class Dynamic_Block {

	/**
	 * Block type name including namespace.
	 * @var string
	 */
	protected $name;

	/**
	 * Registers the block.
	 *
	 * @uses register_block_type() For WP support 5.0+
	 * @todo use register_block_type_from_metadata() for WP support 5.5+
	 *
	 * @see https://developer.wordpress.org/block-editor/tutorials/block-tutorial/writing-your-first-block-type/
	 * @see https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/creating-dynamic-blocks/
	 *
	 * @param array $args Optional. Array of block type arguments. Accepts any
	 *                    public property of `WP_Block_Type`.
	 *                    See WP_Block_Type::__construct() for information on
	 *                    accepted arguments. Default empty array.
	 */
	public function __construct( $args = array() ) {
		$args = array_merge( $args, array( 'render_callback' => array( $this, 'render_callback' ) ) );
		register_block_type( $this->name, $args );
	}

	/**
	 * Reneders the block content dynamically.
	 *
	 * @param array  $atts    The block attributes.
	 * @param string $content Generated block HTML.
	 *
	 * @return string
	 */
	abstract public function render_callback( $atts, $content );
}
