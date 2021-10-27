<?php

namespace WP_Express_Checkout\Admin;

use Exception;
use WP_Express_Checkout\Orders;
use WP_Express_Checkout\Utils;

/**
 * Order list class
 */
class Orders_List {

	private static $search_term = false;

	public static function init() {
		add_filter( 'manage_' . Orders::PTYPE . '_posts_columns', array( __CLASS__, 'order_manage_columns' ) );
		add_filter( 'manage_edit-' . Orders::PTYPE . '_sortable_columns', array( __CLASS__, 'order_manage_sortable_columns' ) );
		add_action( 'manage_' . Orders::PTYPE . '_posts_custom_column', array( __CLASS__, 'order_add_column_data' ), 10, 2 );
		add_filter( 'list_table_primary_column',  array( __CLASS__, 'primary_column' ), 10, 2 );
	}

	/**
	 * Sets the columns for the orders page
	 * @param  array $columns Currently available columns
	 * @return array          New column order
	 */
	public static function order_manage_columns( $columns ) {

		unset( $columns['author'] );
		unset( $columns['date'] );
		unset( $columns['title'] );

		$columns['order']        = __( 'Order', 'wp-express-checkout' );
		$columns['trans_id']     = __( 'PayPal Transaction ID', 'wp-express-checkout' );
		$columns['title']        = __( 'Description', 'wp-express-checkout' );
		$columns['order_author'] = __( 'Author', 'wp-express-checkout' );
		$columns['total']        = __( 'Total', 'wp-express-checkout' );
		$columns['order_date']   = __( 'Date', 'wp-express-checkout' );
		$columns['status']       = __( 'Status', 'wp-express-checkout' );

		return $columns;
	}

	/**
	 * Sets the columns for the orders page
	 * @param  array $columns Currently available columns
	 * @return array          New column order
	 */
	public static function order_manage_sortable_columns( $columns ) {
		$columns['order']        = 'ID';
		$columns['order_date']   = 'post_date';
		return $columns;

	}


	/**
	 * Outputs column data for orders
	 * @param  string $column_index Name of the column being processed
	 * @param  int $post_id         ID of order being dispalyed
	 * @return void
	 */
	public static function order_add_column_data( $column_index, $post_id ) {

		static $order_hash = array();

		if ( isset( $order_hash[ $post_id ] ) ) {
			$order = $order_hash[ $post_id ];
		} else {
			try {
				$order = Orders::retrieve( $post_id );
			} catch ( Exception $exc ) {
				return;
			}

			$order_hash[ $post_id ] = $order;
		}

		switch( $column_index ){

			case 'order' :
				if ( current_user_can( 'edit_post', $order->get_id() ) ) {
					echo '<a href="' . get_edit_post_link( $post_id ) . '">' . $order->get_id() . '</a>';
				} else {
					echo $order->get_id();
				}
				break;

			case 'trans_id' :
				echo $order->get_resource_id();
				break;

			case 'order_author':
				$user = get_userdata( $order->get_author() );
				if ( $user ) {
					echo $user->display_name;
				} else {
					$payer = $order->get_data( 'payer' );
					if ( $payer ) {
						echo implode( ' ', array( $payer['name']['given_name'], $payer['name']['surname'] ) );
					}
				}
				echo '<br>';
				echo $order->get_ip_address();
				break;

			case 'total':
				$currency = $order->get_currency();
				if ( ! empty( $currency ) ) {
					echo Utils::price_format( $order->get_total(), $order->get_currency() );
				} else {
					echo Utils::price_format( $order->get_total() );
				}
				break;

			case 'status':
				echo $order->get_display_status();
				break;

			case 'order_date':
				$order_post = get_post( $order->get_id() );
				if ( '0000-00-00 00:00:00' == $order_post->post_date ) {
					$t_time = $h_time = __( 'Unpublished', 'wp-express-checkout' );
					$time_diff = 0;
				} else {
					$t_time = get_the_time( _x( 'Y/m/d g:i:s A', 'Order Date Format', 'wp-express-checkout' ) );
					$m_time = $order_post->post_date;
					$time = get_post_time( 'G', true, $order_post );

					$time_diff = time() - $time;

					if ( $time_diff > 0 && $time_diff < 24*60*60 )
						$h_time = sprintf( __( '%s ago', 'wp-express-checkout' ), human_time_diff( $time ) );
					else
						$h_time = mysql2date( _x( 'Y/m/d', 'Order Date Format', 'wp-express-checkout' ), $m_time );
				}
				echo '<abbr title="' . $t_time . '">' . $h_time . '</abbr>';

				break;
		}

	}

	/**
	 * Set the first column as primary
	 */
	public static function primary_column( $default, $screen ) {
		if ( 'edit-ppdgorder' === $screen ) {
			$default = 'order';
		}
		return $default;
	}

}
