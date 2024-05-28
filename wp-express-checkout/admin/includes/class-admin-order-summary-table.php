<?php

namespace WP_Express_Checkout\Admin;

use WP_Express_Checkout\Order_Summary_Table;
use WP_Express_Checkout\Utils;

/**
 * Used to construct and display an order summary table for an order on the
 * admin page.
 *
 * @since 1.9.5
 */
class Admin_Order_Summary_Table extends Order_Summary_Table {

	protected function header( $data ){

		$cells = array(
			__( 'Item', 'wp-express-checkout' ),
			__( 'Price', 'wp-express-checkout' ),
			__( 'Affects', 'wp-express-checkout' ),
		);

		return $this->html( 'tr', array(), $this->cells( $cells, 'th' ) );

	}

	protected function footer( $data ){

		$cells = array(
			$this->html( 'strong', __( 'Total', 'wp-express-checkout' ) ),
			$this->html( 'strong', Utils::price_format( $this->order->get_total(), $this->currency ) ),
			''
		);

		return $this->html( 'tr', array(), $this->cells( $cells, 'th' ) );

	}

	protected function row( $item ){
		$ptype_obj = get_post_type_object( get_post_type( $item['post_id'] ) );
		$item_link = '';
		$quantity  = '';

		if ( $ptype_obj->public ) {
			$item_link = '<a href="' . esc_url( get_edit_post_link( $item['post_id'] ) ) . '">' . get_the_title( $item['post_id'] ) . '</a>';
		}

		if ( $item['quantity'] > 1 ) {
			$quantity = $this->html( 'strong', sprintf( __( 'x %s', 'wp-express-checkout' ), $item['quantity'] ) );
		}

		//Add additional item details to the row if this row is for a product (not tax or shipping row)
		if( isset( $item['type'] ) && $item['type'] == WPEC_PRODUCT_POST_TYPE_SLUG ){
			$item_thumb_url = get_post_meta( $item['post_id'], 'wpec_product_thumbnail', true );
			if (empty($item_thumb_url)) {
				$item_thumb_url = WPEC_PLUGIN_URL .'/assets/img/product-thumb-placeholder.png';
			}
			
			$item_column_data = '';
			$item_column_data .= '<div class="wpec-admin-order-summary-item-thumbnail" style="display: flex; align-items: start;">';
			$item_column_data .= '<img src="'. $item_thumb_url .'" alt="'.__('Product thumbnail', 'wp-express-checkout').'" style="margin-right: 6px; height: 2.5rem; width: auto"/>';
			$item_column_data .= '<div>';
			$item_column_data .= '<div class="wpec-admin-order-summary-item">';
			$item_column_data .= $item['name'];
			$item_column_data .= '&nbsp;' . $quantity;
			$item_column_data .= '</div>';
			$item_column_data .= '<div class="wpec-admin-additional-item-details">'. __( 'Unit Price: ', 'wp-express-checkout' ) . esc_attr(Utils::price_format( $item['price'], $this->currency )) ."</div>";
			$item_column_data .= '</div>';
			$item_column_data .= '</div>';
		} else {
			$item_column_data = $item['name'] . '&nbsp;' . $quantity;
		}

		$cells = array(
			$item_column_data,
			Utils::price_format( $item['price'] * $item['quantity'], $this->currency ),
			$item_link
		);

		return $this->html( 'tr', array(), $this->cells( $cells ) );
	}

	protected function subtotal( $items ) {
		$subtotal = 0;
		foreach ( $items as $item ) {
			$subtotal += $item['price'] * $item['quantity'];
		}

		$cells = array(
			$this->html( 'strong', __( 'Subtotal', 'wp-express-checkout' ) ),
			$this->html( 'strong', Utils::price_format( $subtotal, $this->currency ) ),
			''
		);

		return $this->html( $this->args['row_html'], array(), $this->cells( $cells, $this->args['cell_html'] ) );
	}

}
