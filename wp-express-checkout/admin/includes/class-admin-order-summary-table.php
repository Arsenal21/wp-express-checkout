<?php
/**
 * Used to construct and display an order summary table for an order on the
 * admin page.
 *
 * @since 2.0.0
 */
class WPEC_Admin_Order_Summary_Table extends WPEC_Order_Summary_Table {

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
			$this->html( 'strong', WPEC_Utility_Functions::price_format( $this->order->get_total(), $this->currency ) ),
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

		$cells = array(
			$item['name'] . '&nbsp;' . $quantity,
			WPEC_Utility_Functions::price_format( $item['price'] * $item['quantity'], $this->currency ),
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
			$this->html( 'strong', WPEC_Utility_Functions::price_format( $subtotal, $this->currency ) ),
			''
		);

		return $this->html( $this->args['row_html'], array(), $this->cells( $cells, $this->args['cell_html'] ) );
	}

}
