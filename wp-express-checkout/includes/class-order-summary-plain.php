<?php

namespace WP_Express_Checkout;

/**
 * Used to construct and display an order summary table for an order
 *
 * @since 2.1.1
 */
class Order_Summary_Plain extends Order_Summary_Table {

	protected function table( $items, $attributes = array(), $args = array() ) {

		$table_body = '';

		$product_item  = array(
			'price'    => 0,
			'quantity' => 0,
			'name'     => 'n/a',
		);
		$product_items = array();
		$other_items   = array();

		foreach ( $items as $item ) {
			if ( $item['type'] === Products::$products_slug ) {
				$product_item = $item;
				continue;
			}
			$ptype_obj = get_post_type_object( get_post_type( $item['post_id'] ) );
			if ( $ptype_obj->public ) {
				$product_items[] = $item;
			} else {
				$other_items[] = $item;
			}
		}

		$subtotal = $product_item['price'] * $product_item['quantity'];

		$table_body .= $this->cells( array( __( 'Product Name', 'wp-express-checkout' ), $product_item['name'] ) );
		$table_body .= $this->cells( array( __( 'Quantity', 'wp-express-checkout' ), $product_item['quantity'] ) );
		$table_body .= $this->cells( array( __( 'Price', 'wp-express-checkout' ), Utils::price_format( $product_item['price'], $this->currency ) ) );

		$table_body .= $this->html( '', array(), $this->rows( $product_items ) );

		$product_items[] = $product_item;

		if ( $subtotal !== $product_item['price'] ) {
			$table_body .= '--------------------------------' . "\n";
			$table_body .= $this->html( '', array(), $this->subtotal( $product_items ) );
			$table_body .= '--------------------------------' . "\n";
		}

		$table_body .= $this->html( '', array(), $this->rows( $other_items ) );
		$table_body .= '--------------------------------' . "\n";
		$table_body .= $this->html( '', array(), $this->footer( $items ) );

		return $this->html( '', $attributes, $table_body );
	}

	protected function cells( $cells, $type = 'td' ) {
		/* translators: {Order Summary Item Name}: {Value} */
		$template = __( '%1$s: %2$s', 'wp-express-checkout' );
		$output   = sprintf( $template, $cells[0], $cells[1] ) . "\n";

		return $output;
	}

	protected function row( $item ) {
		$cells = array(
			$item['name'],
			Utils::price_format( $item['price'], $this->currency ),
		);

		return $this->html( '', array(), $this->cells( $cells ) );
	}

	protected function html( $tag ) {

		$args = func_get_args();

		$tag = array_shift( $args );

		if ( is_array( $args[0] ) ) {
			array_shift( $args );
		}

		$content = implode( '', $args );

		return $content;
	}

}
