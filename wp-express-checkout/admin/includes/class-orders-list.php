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
		add_filter( 'months_dropdown_results', '__return_empty_array' );	//removing months dropdown 
		add_action( 'restrict_manage_posts', array( __CLASS__, 'add_order_export_daterange' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'wpec_filter_order_daterange' ) );
		add_action( 'pre_get_posts', array(__CLASS__, 'wpec_order_export' ) );
	}
	
	public static function add_order_export_daterange( ) {
		global $typenow;
		if ( $typenow === 'ppdgorder' ) {
		?>
			<script>
				jQuery(function($) {
					var from = $('input[name="order_date_from"]'),
						to = $('input[name="order_date_to"]');

					if (from !== undefined && to !== undefined) {
						$('input[name="order_date_from"], input[name="order_date_to"]').datepicker({
							dateFormat: "yy-mm-dd"
						});
						from.on('change', function() {
							to.datepicker('option', 'minDate', from.val());
						});

						to.on('change', function() {
							from.datepicker('option', 'maxDate', to.val());
						});
					}

					$('#wpec_order_export_button').insertAfter('#post-query-submit');
					$('#wpec_before_export_orders_submit').insertAfter('#wpec_order_export_button');

				});
			</script>

			<style>
				input[name="order_date_from"],
				input[name="order_date_to"] {
					
					height: 30px;	
					width: 125px;
				}
				input[name="order_date_from"]{margin-right:5px}
				.alignleft.actions{
					display: flex;
				align-items: baseline;
				}
			</style>

			<div class="alignleft actions">
				<input type="text" autocomplete="off" id="order_date_from" name="order_date_from" class="" value="<?php echo isset($_GET['order_date_from']) ? esc_attr($_GET['order_date_from']) : ''; ?>" placeholder="<?php _e('From Date'); ?>" />
				<label for="order_date_to" class="screen-reader-text"><?php _e('Filter orders by date to'); ?></label>
				<input type="text" autocomplete="off" id="order_date_to" name="order_date_to" class="" value="<?php echo isset($_GET['order_date_to']) ? esc_attr($_GET['order_date_to']) : ''; ?>" placeholder="<?php _e('To Date'); ?>" />
				<input type="hidden" name="wpec_order_export_nonce" value="<?php echo wp_create_nonce( 'wpec_order_export_nonce' ); ?>">
				<input type="submit" id="wpec_order_export_button" name="wpec_order_export_button" class="button button-primary" value="<?php _e('Export Orders'); ?>">

                <div id="wpec_before_export_orders_submit">
                    <?php do_action('wpec_before_export_orders_submit'); ?>
                </div>
			</div>
		<?php
		}
	}

	public static function wpec_order_export( $query ) {
		if ($query->is_main_query() && isset( $_GET['wpec_order_export_button'] )
		 && 'ppdgorder' === $query->query_vars['post_type']
		 && wp_verify_nonce( $_GET['wpec_order_export_nonce'], 'wpec_order_export_nonce' ) ) {		

			$query->set( 'post_type', 'ppdgorder' );
			$query->set( 'post_status', 'publish' );
			$query->query_vars['suppress_filters'] = false;
			$query->set( 'posts_per_page', -1 );

			// Check if date range is set
			if (isset($_REQUEST['order_date_from'], $_REQUEST['order_date_to'])) {
			$query->set(
				'date_query',
				array(
					array(
						'after'     => sanitize_text_field( wp_unslash( $_GET['order_date_from'] ) ),
						'before'    => sanitize_text_field( wp_unslash( $_GET['order_date_to'] ) ),
						'inclusive' => true,
						'column'    => 'post_date',
					),
				)
			);
		}

			global $post;
			$args  = $query->query_vars;
			
			// Get the orders
			$orders = get_posts( $args );
						
			$filename = 'orders-' . date('Ymd-his') . '.csv';
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename=' . $filename);
			
			// Create the CSV
			$fp = fopen('php://output', 'w');

			// Headers
			$headers = array(
				'Order ID',
				'PayPal Transaction ID',
				'Date',
				'Item Name',
				'Quantity',
				'Unit Price',
				'Item Amount',
				'Tax',
				'Shipping',
				'Total Amount',
				'Customer Email',
				'Customer Name',
				'IP Address',
				'Billing Address',
				'Shipping Address',
			);

			$headers = apply_filters('wpec_export_order_headers', $headers);

			fputcsv($fp, $headers);
			
			// Loop through the orders and add them to the CSV
			foreach ($orders as $order) {
				$order_obj = Orders::retrieve($order->ID);
				$items = $order_obj->get_items();				
				$payer = $order_obj->get_data( 'payer' );
				$billing_address = ! empty( $payer['address'] ) ? implode( ', ', (array) $payer['address'] ) : '';
				$shipping_address = $order_obj->get_data("shipping_address");
				$ip = $order_obj->get_ip_address();
				$tax = 0;
				$shipping_amount = 0;
				$item_name = "";
				$item_quantity = 1;
				$unit_price = 0;
				$item_amount = 0;
				$total_amount = 0;
				

				foreach ($items as $item) {
					if($item["type"]=="tax"){
						$tax = $item["price"];
					}					
					else if($item["type"]=="shipping"){
						$shipping_amount = $item["price"];
					}		
					else if($item["type"] == WPEC_PRODUCT_POST_TYPE_SLUG){
						$item_name = $item["name"];
						$item_quantity = $item["quantity"];
						$unit_price = $item["price"];
						$item_amount = $item["price"] * $item["quantity"];
					}
				}
				
				$total_amount = $order_obj->get_total();
				
				$data = array(
						$order->ID,
						$order_obj->get_capture_id(),
						$order->post_date,
						$item_name,
						$item_quantity,
						$unit_price,
						$item_amount,
						$tax,
						$shipping_amount,
						$total_amount,
						$order_obj->get_email_address(),
						$order_obj->get_username(),
						$ip,
						$billing_address,
						$shipping_address,
					);

				    $data = apply_filters('wpec_export_order_data', $data, $order_obj);

					fputcsv($fp, $data);
			}

			fclose($fp);
			exit;
		}
	}

	public static function wpec_filter_order_daterange($query){

		global $typenow;
		if ($typenow === 'ppdgorder' && isset($_GET['order_date_from']) && isset($_GET['order_date_to'])) {
			
			$from_date = sanitize_text_field($_GET['order_date_from']);
			$to_date = sanitize_text_field($_GET['order_date_to']);
			$date_query = array(
				array(
					'after' => $from_date,
					'before' => $to_date,
					'inclusive' => true,
					'column' => 'post_date'
				)
			);
			$query->set('date_query', $date_query);
		}
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
		$columns['customer'] = __( 'Customer', 'wp-express-checkout' );
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
				echo $order->get_capture_id();
				break;

			case 'customer':
                $output = '';
                $payer = $order->get_data( 'payer' );
                if ( $payer ) {
	                $output .= implode( ' ', array( $payer['name']['given_name'], $payer['name']['surname'] ) );
                }
				// $user = get_userdata( $order->get_author() );
				// if ( $user ) {
				// 	$output .= ' (' . $user->display_name . ')';
				// }
                echo $output;
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
