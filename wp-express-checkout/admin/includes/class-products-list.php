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
	}

	public static function manage_columns( $columns ) {
		unset( $columns );
		$columns = array(
			'cb'        => '<input type="checkbox">',
			'thumbnail' => __( 'Thumbnail', 'wp-express-checkout' ),
			'title'     => __( 'Product Name', 'wp-express-checkout' ),
			'id'        => __( 'ID', 'wp-express-checkout' ),
			'price'     => __( 'Price', 'wp-express-checkout' ),
			'shortcode' => __( 'Shortcode', 'wp-express-checkout' ),
			'date'      => __( 'Date', 'wp-express-checkout' ),
		);
		return $columns;
	}

	public static function manage_custom_columns( $column, $post_id ) {
		$main = Main::get_instance();

		try {
			$product = Products::retrieve( intval( $post_id ) );
		} catch ( Exception $exc ) {
			echo $exc->getMessage();
			return;
		}

		switch ( $column ) {
			case 'id':
				echo $post_id;
				break;
			case 'thumbnail':
				$thumb_url = get_post_meta( $post_id, 'wpec_product_thumbnail', true );
				if ( ! $thumb_url ) {
					$thumb_url = WPEC_PLUGIN_URL . '/assets/img/product-thumb-placeholder.png';
				}
				$edit_link = get_edit_post_link( $post_id );
				$title     = __( 'Edit Product', 'wp-express-checkout' );
				?>
				<span class="wpec-product-thumbnail-container">
					<a href="<?php echo esc_attr( $edit_link ); ?>" title="<?php echo $title; ?>">
						<div style="padding: 50px 0; max-width: 100px; background-image: url(<?php echo esc_url( $thumb_url ); ?>); background-size: cover; background-position: center;"></div>
					</a>
				</span>
				<?php
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
						'shipping'        => ( '' === get_post_meta( $post_id, 'wpec_product_shipping', true ) ) ? $main->get_setting( 'shipping' ) : get_post_meta( $post_id, 'wpec_product_shipping', true ),
						'tax'             => ( '' === get_post_meta( $post_id, 'wpec_product_tax', true ) ) ? $main->get_setting( 'tax' ) : get_post_meta( $post_id, 'wpec_product_tax', true ),
						'quantity'        => get_post_meta( $post_id, 'ppec_product_quantity', true ),
						'product_id'      => $post_id,
					)
				);
				$wpec_shortcode = Shortcodes::get_instance();
				$output = $wpec_shortcode->generate_price_tag( $price_args );
				$output = apply_filters( 'ppec_products_table_price_column', $output, $price_args, $post_id );
				echo $output;
				break;
			case 'shortcode':
				?>
				<input type="text" name="ppec_product_shortcode" class="ppec-select-on-click" readonly value="[wp_express_checkout product_id=&quot;<?php echo $post_id; ?>&quot;]">
				<?php
				break;
		}
	}

	public static function manage_sortable_columns( $columns ) {
		$columns['id']    = 'id';
		$columns['price'] = 'price';
		return $columns;
	}

}
