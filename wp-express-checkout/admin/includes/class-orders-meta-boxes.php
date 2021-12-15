<?php

namespace WP_Express_Checkout\Admin;

use Exception;
use WP_Express_Checkout\Emails;
use WP_Express_Checkout\Main;
use WP_Express_Checkout\Order_Tags_Html;
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

		add_action( 'wp_ajax_wpec_order_action_resend_email', array( $this, 'resend_email_callback' ) );
		add_action( 'wp_ajax_wpec_order_action_reset_download_counts', array( $this, 'reset_download_counts_callback' ) );
	}

	public function add_meta_boxes() {
		add_meta_box( 'wpec_order_items', __( 'Order Summary', 'wp-express-checkout' ), array( $this, 'display_summary_meta_box' ), Orders::PTYPE, 'normal', 'high' );
		add_meta_box( 'wpec_order_downloads', __( 'Order Downloads', 'wp-express-checkout' ), array( $this, 'display_downloads_meta_box' ), Orders::PTYPE, 'normal', 'default' );
		add_meta_box( 'wpec_order_actions', __( 'Order Actions', 'wp-express-checkout' ), array( $this, 'display_actions_meta_box' ), Orders::PTYPE, 'side', 'high' );
		add_meta_box( 'wpec_order_status', __( 'Order Status', 'wp-express-checkout' ), array( $this, 'display_status_meta_box' ), Orders::PTYPE, 'side', 'high' );
		add_meta_box( 'wpec_order_author', __( 'Order Author', 'wp-express-checkout' ), array( $this, 'display_author_meta_box' ), Orders::PTYPE, 'side', 'low' );

		wp_enqueue_script( 'wpec-admin-scripts', WPEC_PLUGIN_URL . '/assets/js/admin.js', array(), WPEC_PLUGIN_VER, true );
	}

	public function display_summary_meta_box( $post ) {
		global $post;

		try {
			$order = Orders::retrieve( $post->ID );
		} catch ( Exception $exc ) {
			return;
		}

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

	public function display_downloads_meta_box( $post ) {
		global $post;

		$error_msg = __( 'There are no downloads for this order.', 'wp-express-checkout' );

		try {
			$order = Orders::retrieve( $post->ID );
		} catch ( Exception $exc ) {
			echo $error_msg;
			return;
		}

		$renderer = new Order_Tags_Html( $order );

		$output = $renderer->download_link( array(
			'anchor_text' => '',
		) );

		$output = ! empty( $output ) ? $output : $error_msg;

		echo $output;
	}

	/**
	 * Displays the order status summary
	 *
	 * @param  WP_Post $post Post object
	 *
	 * @return void
	 */
	public function display_status_meta_box( $post ){

		try {
			$order = Orders::retrieve( $post->ID );
		} catch ( Exception $exc ) {
			return;
		}
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
					<td><?php echo get_post_time( 'F j, Y, g:i a', false, $order->get_id() ); ?></td>
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
	 * Displays the order status summary
	 *
	 * @param  WP_Post $post Post object
	 *
	 * @return void
	 */
	public function display_actions_meta_box( $post ){

		try {
			$order = Orders::retrieve( $post->ID );
		} catch ( Exception $exc ) {
			return;
		}
		?>
		<ul>
			<li>
				<a class="button wpec-order-action" data-action="resend_email" data-order="<?php echo $order->get_id() ?>" data-nonce="<?php echo wp_create_nonce( 'resend-email' ); ?>" href="#">
					<span class="dashicons dashicons-email" style="line-height:1.8;font-size:16px;"></span>
					<span class="wpec-order-action-label">
						<?php esc_html_e( 'Resend sale notification email', 'wp-express-checkout' ); ?>
					</span>
				</a>
			</li>
			<li>
				<a class="button wpec-order-action" data-action="reset_download_counts" data-order="<?php echo $order->get_id() ?>" data-nonce="<?php echo wp_create_nonce( 'reset-download-counts' ); ?>" href="#">
					<span class="dashicons dashicons-update" style="line-height:1.8;font-size:16px;"></span>
					<span class="wpec-order-action-label">
						<?php esc_html_e( 'Regenerate Download Permissions', 'wp-express-checkout' ); ?>
					</span>
				</a>
			</li>
		</ul>
		<?php

	}

	/**
	 * Displays the order author box
	 * @param  object $post Wordpress Post object
	 * @return void
	 */
	function display_author_meta_box ( $post ){

		try {
			$order = Orders::retrieve( $post->ID );
		} catch ( Exception $exc ) {
			return;
		}
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
		if ( $payer ) {
			$username = implode( ' ', array( $payer['name']['given_name'], $payer['name']['surname'] ) );
			$useremail = $payer['email_address'];
		} else if ( $user ) {
			$username  = $user->user_login !== $user->display_name ? $user->display_name . ' (' . $user->user_login . ') ' : $user->user_login;
			$useremail = $user->user_email;
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

	public function resend_email_callback() {

		check_ajax_referer( 'resend-email', 'nonce' );

		try {
			$order = Orders::retrieve( $_POST['order'] );
		} catch ( Exception $exc ) {
			wp_send_json_error( $exc->getMessage() );
		}

		$response = Emails::send_buyer_email( $order );

		if ( ! $response ) {
			wp_send_json_error( __( 'Something went wrong, email is not sent!', 'wp-express-checkout' ) );
		}

		wp_send_json_success( __( 'Email successfully sent!', 'wp-express-checkout' ) );
	}

	public function reset_download_counts_callback() {

		check_ajax_referer( 'reset-download-counts', 'nonce' );

		try {
			$order = Orders::retrieve( $_POST['order'] );
		} catch ( Exception $exc ) {
			wp_send_json_error( $exc->getMessage() );
		}

		// Reset counter and duration.
		$order->add_data( 'downloads_counter', array() );
		$order->add_data( 'download_start_date', time() );

		wp_send_json_success( __( 'Download permissions reset!', 'wp-express-checkout' ) );
	}

}
