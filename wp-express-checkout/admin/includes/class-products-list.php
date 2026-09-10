<?php

namespace WP_Express_Checkout\Admin;

use Exception;
use WP_Express_Checkout\Main;
use WP_Express_Checkout\Products;
use WP_Express_Checkout\Shortcodes;

class Products_List {

	public static function init() {
		// add custom columns for list view.
		add_filter( 'manage_' . Products::$products_slug . '_posts_columns', array( __CLASS__, 'manage_columns' ) );
		add_action( 'manage_' . Products::$products_slug . '_posts_custom_column', array( __CLASS__, 'manage_custom_columns' ), 10, 2 );
		// set custom columns sortable.
		add_filter( 'manage_edit-' . Products::$products_slug . '_sortable_columns', array( __CLASS__, 'manage_sortable_columns' ) );
		add_filter( 'list_table_primary_column',  array( __CLASS__, 'primary_column' ), 10, 2 );
	}

	public static function manage_columns( $columns ) {
		unset( $columns );
		$columns = array(
			'cb'        => '<input type="checkbox">',
			'thumbnail' => esc_html__( 'Thumbnail', 'wp-express-checkout' ),
			'title'     => esc_html__( 'Product Name', 'wp-express-checkout' ),
			'type'      => esc_html__( 'Product Type', 'wp-express-checkout' ),
			'id'        => esc_html__( 'ID', 'wp-express-checkout' ),
			'price'     => esc_html__( 'Price', 'wp-express-checkout' ),
			'stock'     => esc_html__( 'Stock', 'wp-express-checkout' ),
			'shortcode' => esc_html__( 'Shortcode', 'wp-express-checkout' ),
			'date'      => esc_html__( 'Date', 'wp-express-checkout' ),
		);
		return $columns;
	}

	public static function manage_custom_columns( $column, $post_id ) {

		try {
			$product = Products::retrieve( intval( $post_id ) );
		} catch ( Exception $exc ) {
			if ( 1003 === $exc->getCode() ) {
				$product = new Products\Stub_Product( get_post( $post_id ) );
			} else {
				echo esc_html( $exc->getMessage() );
				return;
			}
		}

		switch ( $column ) {
			case 'id':
				echo esc_attr( $post_id );
				break;
			case 'thumbnail':
				$thumb_url = get_post_meta( $post_id, 'wpec_product_thumbnail', true );
				if ( ! $thumb_url ) {
					$thumb_url = WPEC_PLUGIN_URL . '/assets/img/product-thumb-placeholder.png';
				}
				$edit_link = get_edit_post_link( $post_id );
				$title     = esc_html__( 'Edit Product', 'wp-express-checkout' );
				?>
				<span class="wpec-product-thumbnail-container">
					<a href="<?php echo esc_url( $edit_link ); ?>" title="<?php echo esc_attr( $title ); ?>">
						<div style="padding: 50px 0; max-width: 100px; background-image: url(<?php echo esc_url( $thumb_url ); ?>); background-size: cover; background-position: center;"></div>
					</a>
				</span>
				<?php
				break;
			case 'stock':
				$stock_enabled = $product->is_stock_control_enabled();
				$stock_items   = $product->get_stock_items();
				if ( $stock_enabled ) {
					echo ! $stock_items ? esc_html__( 'Out of stock', 'wp-express-checkout' ) : esc_html( $stock_items );
				} else {
					echo '—';
				}
				break;
			case 'price':
				$price_args = array_merge(
					array(
						'price'           => 0,
						'shipping'        => 0,
						'tax'             => 0,
						'quantity'        => 1,
					),
					array(
						'name'            => get_the_title( $post_id ),
						'price'           => (float) $product->get_price(),
						'shipping'        => $product->get_shipping(),
						'tax'             => $product->get_tax(),
						'quantity'        => $product->get_quantity(),
						'product_id'      => $post_id,
					)
				);
				$wpec_shortcode = Shortcodes::get_instance();
				$output = $wpec_shortcode->generate_price_tag( $price_args );
				$output = apply_filters( 'wpec_products_table_price_column', $output, $price_args, $post_id );
				echo wp_kses_post( $output );
				break;
			case 'shortcode':
				?>
				<input type="text" name="ppec_product_shortcode" class="wpec-select-on-click large-text code" onfocus="this.select();" readonly value="<?php echo esc_attr( '[wp_express_checkout product_id="' . $post_id . '"]' ); ?>">
				<?php
				break;
			case 'type':
				echo wp_kses_post( $product->get_type() );
				break;
		}
	}

	public static function manage_sortable_columns( $columns ) {
		$columns['id']    = 'id';
		$columns['price'] = 'price';
		return $columns;
	}

	/**
	 * Set the first column as primary
	 */
	public static function primary_column( $default, $screen ) {
		if ( 'edit-' . WPEC_PRODUCT_POST_TYPE_SLUG === $screen ) {
			$default = 'thumbnail';
		}
		return $default;
	}

}
