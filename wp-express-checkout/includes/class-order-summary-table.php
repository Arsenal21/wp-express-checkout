<?php

namespace WP_Express_Checkout;

/**
 * Used to construct and display an order summary table for an order
 *
 * @since 1.9.5
 */
class Order_Summary_Table {

	protected $order, $currency;

	public function __construct( $order, $args = array() ) {

		$this->order = $order;
		$this->currency = $order->get_currency();

		$this->args = wp_parse_args( $args, array(
			'wrapper_html' => 'table',
			'header_wrapper' => 'thead',
			'body_wrapper' => 'tbody',
			'footer_wrapper' => 'tfoot',
			'row_html' => 'tr',
			'cell_html' => 'td',
		) );

	}

	protected function table( $items, $attributes = array(), $args = array() ) {

		$args = wp_parse_args( $args, array(
			'wrapper_html' => 'table',
			'header_wrapper' => 'thead',
			'body_wrapper' => 'tbody',
			'footer_wrapper' => 'tfoot',
		) );

		extract( $args );

		$table_body = '';

		$product_items = array();
		$other_items   = array();

		foreach ( $items as $item ) {
			$ptype_obj = get_post_type_object( get_post_type( $item['post_id'] ) );
			if ( $ptype_obj->public ) {
				$product_items[] = $item;
			} else {
				$other_items[] = $item;
			}
		}

		$table_body .= $this->html( $header_wrapper, array(), $this->header( $items ) );
		$table_body .= $this->html( $body_wrapper, array(), $this->rows( $product_items ) );
		$table_body .= $this->html( $header_wrapper, array(), $this->subtotal( $product_items ) );
		$table_body .= $this->html( $body_wrapper, array(), $this->rows( $other_items ) );
		$table_body .= $this->html( $footer_wrapper, array(), $this->footer( $items ) );

		return $this->html( $wrapper_html, $attributes, $table_body );

	}

	protected function header( $data ){

		$cells = array(
			__( 'Product', 'wp-express-checkout' ),
			__( 'Subtotal', 'wp-express-checkout' ),
		);

		return $this->html( 'tr', array(), $this->cells( $cells, 'th' ) );

	}


	protected function rows( array $items ) {

		$table_body = '';
		foreach ( $items as $item ) {
			$table_body .= $this->row( $item );
		}

		return $table_body;

	}

	protected function cells( $cells, $type = 'td' ) {

		$output = '';
		foreach ( $cells as $value ) {
			$output .= $this->html( $type, array(), $value );
		}
		return $output;

	}

	public function show( $attributes = array() ) {
		$items = $this->order->get_items();
		echo $this->table( $items, $attributes, $this->args );
	}

	protected function subtotal( $items ) {
		$subtotal = 0;
		foreach ( $items as $item ) {
			$subtotal += $item['price'] * $item['quantity'];
		}

		$cells = array(
			$this->html( 'strong', __( 'Subtotal', 'wp-express-checkout' ) ),
			$this->html( 'strong', Utils::price_format( $subtotal, $this->currency ) ),
		);

		return $this->html( $this->args['row_html'], array(), $this->cells( $cells, $this->args['cell_html'] ) );
	}

	protected function footer( $items ) {

		$cells = array(
			$this->html( 'strong', __( 'Total', 'wp-express-checkout' ) ),
			$this->html( 'strong', Utils::price_format( $this->order->get_total(), $this->currency ) ),
		);

		return $this->html( $this->args['row_html'], array(), $this->cells( $cells, $this->args['cell_html'] ) );
	}

	protected function row( $item ) {
		$quantity  = '';
		if ( $item['quantity'] > 1 ) {
			$quantity = $this->html( 'strong', sprintf( __( 'x %s', 'wp-express-checkout' ), $item['quantity'] ) );
		}

		$cells = array(
			$item['name'] . '&nbsp;' . $quantity,
			Utils::price_format( $item['price'] * $item['quantity'], $this->currency ),
		);

		return $this->html( $this->args['row_html'], array(), $this->cells( $cells ) );
	}

	protected function html( $tag ) {
		static $SELF_CLOSING_TAGS = array( 'area', 'base', 'basefont', 'br', 'hr', 'input', 'img', 'link', 'meta' );

		$args = func_get_args();

		$tag = array_shift( $args );

		if ( is_array( $args[0] ) ) {
			$closing = $tag;
			$attributes = array_shift( $args );
			foreach ( $attributes as $key => $value ) {
				if ( false === $value ) {
					continue;
				}

				if ( true === $value ) {
					$value = $key;
				}

				$tag .= ' ' . $key . '="' . esc_attr( $value ) . '"';
			}
		} else {
			list( $closing ) = explode( ' ', $tag, 2 );
		}

		if ( in_array( $closing, $SELF_CLOSING_TAGS ) ) {
			return "<{$tag} />"; // @codeCoverageIgnore
		}

		$content = implode( '', $args );

		return "<{$tag}>{$content}</{$closing}>";
	}

}
