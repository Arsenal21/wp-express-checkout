<?php

namespace WP_Express_Checkout\Admin;

use WP_Express_Checkout\Products;
use WP_List_Table;

class Coupons_List extends WP_List_Table {

	public function prepare_items() {
		$columns = $this->get_columns();
		//  $hidden          = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$args = array(
			'posts_per_page' => -1,
			'offset'         => 0,
			'post_type'      => 'wpec_coupons',
		);

		$coupons = get_posts( $args );

		$data = array();

		foreach ( $coupons as $coupon ) {
			$id     = $coupon->ID;
			$data[] = array(
				'id'            => $id,
				'coupon'        => get_post_meta( $id, 'wpec_coupon_code', true ),
				'active'        => get_post_meta( $id, 'wpec_coupon_active', true ),
				'discount'      => get_post_meta( $id, 'wpec_coupon_discount', true ),
				'discount_type' => get_post_meta( $id, 'wpec_coupon_discount_type', true ),
				'red_limit'     => get_post_meta( $id, 'wpec_coupon_red_limit', true ),
				'red_count'     => get_post_meta( $id, 'wpec_coupon_red_count', true ),
				'start_date'    => get_post_meta( $id, 'wpec_coupon_start_date', true ),
				'exp_date'      => get_post_meta( $id, 'wpec_coupon_exp_date', true ),
			);
		}
		wp_reset_postdata();
		usort( $data, array( &$this, 'sort_data' ) );
		$perPage     = 10;
		$currentPage = $this->get_pagenum();
		$totalItems  = count( $data );
		$this->set_pagination_args(
			array(
				'total_items' => $totalItems,
				'per_page'    => $perPage,
			)
		);
		$data                  = array_slice( $data, ( ( $currentPage - 1 ) * $perPage ), $perPage );
		$this->_column_headers = array( $columns, array(), $sortable );
		$this->items           = $data;
	}

	public function get_columns() {
		$columns = array(
			'coupon'     => __( 'Coupon Code', 'wp-express-checkout' ),
			'id'         => 'ID',
			'active'     => __( 'Active', 'wp-express-checkout' ),
			'discount'   => __( 'Discount Value', 'wp-express-checkout' ),
			'red_count'  => __( 'Redemption Count', 'wp-express-checkout' ),
			'red_limit'  => __( 'Redemption Limit', 'wp-express-checkout' ),
			'start_date' => __( 'Start Date', 'wp-express-checkout' ),
			'exp_date'   => __( 'Expiry Date', 'wp-express-checkout' ),
		);
		return $columns;
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'exp_date':
				return $item[ $column_name ] == 0 ? __( 'No expiry', 'wp-express-checkout' ) : $item[ $column_name ];
			case 'active':
				return $item[ $column_name ] == 0 ? __( 'No', 'wp-express-checkout' ) : __( 'Yes', 'wp-express-checkout' );
			case 'coupon':
				$str = '';
				// translators: %s is coupon code
				$confirm_coupon_delete_msg = sprintf( __( 'Are you sure you want to delete "%s" coupon? This can\'t be undone.', 'wp-express-checkout' ), $item['coupon'] );
				ob_start();
				?>
<a href="edit.php?post_type=<?php echo esc_attr( Products::$products_slug ); ?>&page=wpec-coupons&action=wpec_add_edit_coupon&wpec_coupon_id=<?php echo esc_attr( $item['id'] ); ?>" aria-label="<?php echo esc_attr( __( 'Edit coupon', 'wp-express-checkout' ) ); ?>"><?php echo esc_html( $item[ $column_name ] ); ?></a>
<div class="row-actions">
	<span class="edit">
		<a href="edit.php?post_type=<?php echo esc_attr( Products::$products_slug ); ?>&page=wpec-coupons&action=wpec_add_edit_coupon&wpec_coupon_id=<?php echo esc_attr( $item['id'] ); ?>" aria-label="<?php echo esc_attr( __( 'Edit coupon', 'wp-express-checkout' ) ); ?>"><?php echo esc_html( __( 'Edit', 'wp-express-checkout' ) ); ?></a> |
	</span>
	<span class="trash">
		<a href="<?php echo esc_attr( wp_nonce_url( 'edit.php?post_type=' . Products::$products_slug . '&page=wpec-coupons&action=wpec_delete_coupon&wpec_coupon_id=' . $item['id'], 'delete-coupon_' . $item['id'] ) ); ?>" class="submitdelete" aria-label="<?php echo esc_attr( __( 'Delete coupon', 'wp-express-checkout' ) ); ?>" onclick="return confirm('<?php echo esc_js( $confirm_coupon_delete_msg ); ?>');"><?php echo esc_attr( __( 'Delete', 'wp-express-checkout' ) ); ?></a>
	</span>
</div>
				<?php
				$str .= ob_get_clean();
				return $str;
			case 'discount':
				if ( $item['discount_type'] === 'perc' ) {
					return $item[ $column_name ] . '%';
				}
				return $item[ $column_name ];
			case 'red_limit':
				return ! empty( $item[ $column_name ] ) ? $item[ $column_name ] : '—';
			default:
				return $item[ $column_name ];
		}
	}

	public function get_sortable_columns() {
		return array(
			'id'     => array( 'id', false ),
			'coupon' => array( 'coupon', false ),
			'active' => array( 'active', false ),
		);
	}

	private function sort_data( $a, $b ) {
		// Set defaults
		$orderby = 'id';
		$order   = 'desc';
		// If orderby is set, use this as the sort column
		if ( ! empty( $_GET['orderby'] ) ) {
			$orderby = $_GET['orderby'];
		}
		// If order is set use this as the order
		if ( ! empty( $_GET['order'] ) ) {
			$order = $_GET['order'];
		}
		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
		if ( $order === 'asc' ) {
			return $result;
		}
		return -$result;
	}

}
