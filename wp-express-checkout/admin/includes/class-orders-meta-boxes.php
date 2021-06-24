<?php

namespace WP_Express_Checkout\Admin;

use WP_Express_Checkout\Main;
use WP_Express_Checkout\Orders;
use WP_Post;

/**
 * Order page metaboxes
 *
 * @since 1.9.5
 */
class Orders_Meta_Boxes {

	var $WPECAdmin;
	var $WPEC_Main;

	public function __construct() {
		$this->WPECAdmin = Admin::get_instance();
		$this->WPEC_Main = Main::get_instance();

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'admin_menu', array( $this, 'remove_meta_boxes' ) );
		add_action( 'save_post_' . Orders::PTYPE, array( $this, 'save' ), 10, 3 );
	}

	public function add_meta_boxes() {
		add_meta_box( 'wpec_order_items', __( 'Order Summary', 'wp-express-checkout' ), array( $this, 'display_summary_meta_box' ), Orders::PTYPE, 'normal', 'high' );
		add_meta_box( 'wpec_order_status', __( 'Order Status', 'wp-express-checkout' ), array( $this, 'display_status_meta_box' ), Orders::PTYPE, 'side', 'high' );
		add_meta_box( 'wpec_order_author', __( 'Order Author', 'wp-express-checkout' ), array( $this, 'display_author_meta_box' ), Orders::PTYPE, 'side', 'low' );
	}

	public function display_summary_meta_box( $post ) {
		global $post;

		if ( Orders::PTYPE != $post->post_type ) {
			return;
		}

		$order = Orders::retrieve( $_GET['post'] );

		?>
		<style type="text/css">
			#admin-order-summary tbody td{
				padding-top: 10px;
				padding-bottom: 10px;
			}
			#admin-order-summary{
				margin-bottom: 20px;
			}
			#post-body-content{
				display: none;
			}
		</style>
		<?php

		$table = new Admin_Order_Summary_Table( $order );
		$table->show( array(
			'class' => 'widefat',
			'id' => 'admin-order-summary'
		) );
	}

	/**
	 * Displays the order status summary
	 *
	 * @param  WP_Post $post Post object
	 *
	 * @return void
	 */
	public function display_status_meta_box( $post ){

		$order = Orders::retrieve( $post->ID );
		?>
		<style type="text/css">
			#admin-order-status th{
				padding-right: 10px;
				text-align: right;
				width: 40%;
			}
		</style>
		<table id="admin-order-status">
			<tbody>
				<tr>
					<th><?php _e( 'ID', 'wp-express-checkout' ); ?>: </th>
					<td><?php echo $order->get_id(); ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Status', 'wp-express-checkout' ); ?>: </th>
					<td><?php echo $order->get_display_status(); ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Currency', 'wp-express-checkout' ); ?>: </th>
					<td><?php echo $order->get_currency(); ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Order Time', 'wp-express-checkout' ); ?>: </th>
					<td><?php echo date_format( get_post_datetime( $order->get_id() ), 'F j, Y, g:i a' ); ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Transaction ID', 'wp-express-checkout' ); ?>: </th>
					<td><?php echo $order->get_resource_id(); ?></td>
				</tr>
			</tbody>
		</table>
		<?php

	}

	/**
	 * Displays the order author box
	 * @param  object $post Wordpress Post object
	 * @return void
	 */
	function display_author_meta_box ( $post ){

		$order = Orders::retrieve( $post->ID );
		?>
		<style type="text/css">
			#admin-order-author{
				padding-left: 10px;
				text-align: left;
			}
			.avatar{
				float: left;
			}
		</style>
		<?php
		$user      = get_userdata( $order->get_author() );
		$payer     = $order->get_data( 'payer' );
		$username  = '';
		$useremail = '';
		$billing   = ! empty( $payer['address'] ) ? implode( ', ', (array) $payer['address'] ) : __( 'N/A', 'wp-express-checkout' );
		$shipping  = $order->get_data( 'shipping_address' );
		if ( $user ) {
			$username  = $user->user_login !== $user->display_name ? $user->display_name . ' (' . $user->user_login . ') ' : $user->user_login;
			$useremail = $user->user_email;
		} else if ( $payer ) {
			$username = implode( ' ', array( $payer['name']['given_name'], $payer['name']['surname'] ) );
			$useremail = $payer['email_address'];
		}
		?>
		<?php echo get_avatar( $useremail, 72 ); ?>
		<table id="admin-order-author">
			<tbody>
				<tr>
					<td><?php echo $username; ?></td>
				</tr>
				<tr>
					<td><?php echo $useremail; ?></td>
				</tr>
				<tr>
					<td><?php echo $order->get_ip_address(); ?></td>
				</tr>
				<?php if ( $billing ) { ?>
					<tr>
						<td><strong><?php esc_html_e( 'Billing Address:', 'wp-express-checkout' ); ?></strong></td>
					</tr>
					<tr>
						<td><?php echo $billing; ?></td>
					</tr>
				<?php } ?>
				<?php if ( $shipping ) { ?>
				<tr>
					<td><strong><?php esc_html_e( 'Shipping Address:', 'wp-express-checkout' ); ?></strong></td>
				</tr>
				<tr>
					<td><?php echo $shipping; ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<div class="clear"></div>
		<?php

	}

	public function save( $post_id, $post, $update ) {
		if ( ! isset( $_POST['action'] ) ) {
			// this is probably not edit or new post creation event.
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $post_id ) ) {
			return;
		}

	}

	public function remove_meta_boxes() {
		remove_meta_box( 'submitdiv', Orders::PTYPE, 'side' );
		remove_meta_box( 'slugdiv', Orders::PTYPE, 'normal' );
		remove_meta_box( 'authordiv', Orders::PTYPE, 'normal');
	}

}
