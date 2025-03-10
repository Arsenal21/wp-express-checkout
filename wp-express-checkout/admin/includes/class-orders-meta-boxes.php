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
		add_action( 'wp_ajax_wpec_order_action_paypal_refund', array( $this, 'paypal_refund_callback' ) );
		add_action( 'wp_ajax_wpec_add_order_note', array( $this, 'wpec_add_order_note_callback' ) );
		add_action( 'wp_ajax_wpec_delete_order_note', array( $this, 'wpec_delete_order_note_callback' ) );
	}

	public function add_meta_boxes() {
		add_meta_box( 'wpec_order_items', __( 'Order Summary', 'wp-express-checkout' ), array( $this, 'display_summary_meta_box' ), Orders::PTYPE, 'normal', 'high' );
		add_meta_box( 'wpec_order_downloads', __( 'Order Downloads', 'wp-express-checkout' ), array( $this, 'display_downloads_meta_box' ), Orders::PTYPE, 'normal', 'default' );
		add_meta_box( 'wpec_order_actions', __( 'Order Actions', 'wp-express-checkout' ), array( $this, 'display_actions_meta_box' ), Orders::PTYPE, 'side', 'high' );
		add_meta_box( 'wpec_order_status', __( 'Order Status', 'wp-express-checkout' ), array( $this, 'display_status_meta_box' ), Orders::PTYPE, 'side', 'high' );
		add_meta_box( 'wpec_customer', __( 'Customer', 'wp-express-checkout' ), array( $this, 'display_customer_meta_box' ), Orders::PTYPE, 'side', 'low' );
		add_meta_box( 'wpec_order_notes', __( 'Order Notes', 'wp-express-checkout' ), array( $this, 'display_notes_meta_box' ), Orders::PTYPE, 'side', 'low' );

		wp_enqueue_script( 'wpec-admin-scripts', WPEC_PLUGIN_URL . '/assets/js/admin.js', array(), WPEC_PLUGIN_VER, true );
		wp_localize_script( 'wpec-admin-scripts', 'wpecAdminSideVars', array(
			'ajaxurl' => get_admin_url() . 'admin-ajax.php',			
			'add_order_note_nonance' => wp_create_nonce('wpec_add_order_note_ajax_nonce'),
			'delete_order_note_nonance' => wp_create_nonce('wpec_delete_order_note_ajax_nonce'),
		) );
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
				vertical-align: top;
				width: 40%;
			}
			#admin-order-status td{
				vertical-align: top;
			}
		</style>
		<table id="admin-order-status">
			<tbody>
				<tr>
					<th><?php _e( 'Order ID', 'wp-express-checkout' ); ?>: </th>
					<td><?php echo esc_attr($order->get_id()); ?></td>
				</tr>
				<tr>
					<th style="vertical-align: middle;"><?php _e( 'Status', 'wp-express-checkout' ); ?>: </th>
					<td><?php echo wp_kses_post($order->get_display_status()); ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Currency', 'wp-express-checkout' ); ?>: </th>
					<td><?php echo esc_attr($order->get_currency()); ?></td>
				</tr>
				<tr>
					<th><?php _e( 'Order Time', 'wp-express-checkout' ); ?>: </th>
					<td><?php echo esc_attr(get_post_time( 'F j, Y, g:i a', false, $order->get_id() )); ?></td>
				</tr>
				<?php if($order->get_refund_date()):?>
				<tr>
					<th><?php _e( 'Refund Time', 'wp-express-checkout' ); ?>: </th>
					<td><?php echo esc_attr($order->get_refund_date('F j, Y, g:i a')); ?></td>
				</tr>
				<?php endif;?>
				<tr>
					<th><?php _e( 'Transaction ID', 'wp-express-checkout' ); ?>: </th>
					<td><?php echo esc_attr($order->get_capture_id()); ?></td>
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
						<?php esc_html_e( 'Resend Sale Notification Email', 'wp-express-checkout' ); ?>
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
			<li>
				<?php if( $order->get_status()=="refunded" ){ ?>
					<div class="wpec-grey-box">
						<div class="wpec-order-action-txn-refunded-msg"><?php esc_html_e( 'Transaction Refunded', 'wp-express-checkout' ); ?></div>
					</div>
				<?php }else{ ?>				
				<a class="button wpec-order-action" data-action="paypal_refund" data-order="<?php echo $order->get_id() ?>" data-nonce="<?php echo wp_create_nonce( 'paypal-refund' ); ?>" href="#">
					<span class="dashicons dashicons-money" style="line-height:1.8;font-size:16px;"></span>
					<span class="wpec-order-action-label">
						<?php esc_html_e( 'Refund Transaction', 'wp-express-checkout' ); ?>
					</span>
				</a>
				<?php } ?>
			</li>
		</ul>
		<?php

	}

	/**
	 * Displays the order author box
	 * @param  object $post Wordpress Post object
	 * @return void
	 */
	function display_customer_meta_box ( $post ){

		try {
			$order = Orders::retrieve( $post->ID );
		} catch ( Exception $exc ) {
			return;
		}
		?>
		<style type="text/css">
			#admin-order-author{
				text-align: left;
			}
			.avatar{
				float: left;
				margin-right: 10px;
			}
		</style>
		<?php
		$wp_user      = get_userdata( $order->get_author() );
		$payer     = $order->get_data( 'payer' );
		$payer_name  = '';
		$wp_username  = '';
		$payer_email = '';
		
		$ip_address = ! empty($order->get_ip_address()) ? $order->get_ip_address() : __( 'N/A', 'wp-express-checkout' );
		$billing_address   = ! empty( $payer['address'] ) ? implode( ', ', (array) $payer['address'] ) : __( 'N/A', 'wp-express-checkout' );
		$shipping_address  = ! empty( $order->get_shipping_address() ) ? $order->get_shipping_address() : __( 'N/A', 'wp-express-checkout' );;
		if ( $payer ) {
            $payer_name_array = array();
			$payer_name_array[] = isset($payer['name']['given_name']) ? sanitize_text_field($payer['name']['given_name']) : '';
			$payer_name_array[] = isset($payer['name']['surname']) ? sanitize_text_field($payer['name']['surname']) : '';

            $payer_name = implode( ' ', array_filter($payer_name_array) ); // Filters empty value and implode the remaining.

            $payer_email = isset($payer['email_address']) ? sanitize_email($payer['email_address']) : '';
		}
        if ( $wp_user ) {
			// $wp_username  = $wp_user->user_login !== $wp_user->display_name ? $wp_user->display_name . ' (' . $wp_user->user_login . ') ' : $wp_user->user_login;
			$wp_username  = $wp_user->user_login;
		}
		?>
		<?php 
		//WP might be sunsetting Gravatar, so let's not use it anymore. It isn't necessary for this customer box.
		//echo get_avatar( $useremail, 72 ); 
		?>
		<table id="admin-order-author">
			<tbody>
                <?php if (!empty($payer_name)) {?>
				<tr>
					<td><?php echo esc_attr($payer_name); ?></td>
				</tr>
				<tr>
					<td><?php echo esc_attr($payer_email); ?></td>
				</tr>				
                <?php } ?>
                <?php if (!empty($wp_username)) {?>
                    <tr>
					<td>
						<strong><?php esc_html_e( 'WP Username:', 'wp-express-checkout' ); ?></strong></td>
                    </tr>
					<tr>
						<td><?php echo esc_attr($wp_username); ?></td>
					</tr>					
                <?php } ?>

				<?php if ( !empty($ip_address) ) { ?>
					<tr>
						<td><strong><?php esc_html_e( 'IP Address:', 'wp-express-checkout' ); ?></strong></td>
					</tr>
					<tr>
						<td><?php echo esc_attr($order->get_ip_address()); ?></td>
					</tr>
				<?php } ?>

				<?php if ( !empty($billing_address) ) { ?>
					<tr>
						<td><strong><?php esc_html_e( 'Billing Address:', 'wp-express-checkout' ); ?></strong></td>
					</tr>
					<tr>
						<td><?php echo esc_attr($billing_address); ?></td>
					</tr>
				<?php } ?>

				<?php if ( !empty($shipping_address) ) { ?>
				<tr>
					<td><strong><?php esc_html_e( 'Shipping Address:', 'wp-express-checkout' ); ?></strong></td>
				</tr>
				<tr>
					<td><?php echo esc_attr($shipping_address); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<div class="clear"></div>
		<?php

	}

	/**
	 * Displays the order notes box
	 * @param  object $post Wordpress Post object
	 * @return void
	 */
	function display_notes_meta_box ( $post ){

		try {
			$order = Orders::retrieve( $post->ID );
		} catch ( Exception $exc ) {
			return;
		}
		?>
		<style type="text/css">
			#wpec_order_note{
				width:100%;
				min-height: 70px;
			}
			.wpec-single-note{
				margin:20px 0;
			}
			.wpec-single-note p{
				margin:0;
				background: #d7cad3;
				color:#000;
				padding:10px 10px
			}
			.wpec-single-note .wpec-single-note-meta{
				color: #c0c4c7;
			}
			.wpec-single-note .wpec-single-note-meta span{
				border-bottom: 1px dotted #c0c4c7;				
			}
			.wpec-single-note .wpec-single-note-meta a{
				color:#a57178
			}
		</style>
		
		<div class="wpec-order-note-form">			
				<div><textarea id="wpec_order_note" name="wpec_order_note"></textarea></div>
				<input type="hidden" value="<?php echo esc_attr($post->ID); ?>" id="wpec_order_id" name="wpec_order_id" />
				<input type="submit" class="button" id="wpec_order_note_btn_submit"  value="<?php echo esc_html_e( 'Add', 'wp-express-checkout' ); ?>" />			
		</div>

		<div id="wpec-admin-order-notes">		
            <?php
            $order_notes = get_post_meta( $post->ID, 'wpec_order_notes', true );

			if(!is_array($order_notes))
			{
				$order_notes = array();
			}

            foreach ( $order_notes as $note ) {
                $admin_name  = get_userdata( $note['admin_id'] )->display_name;
                $date_time   = date('F j, Y \a\t g:ia', $note['timestamp'] );				
                $note_content = $note['content'];

				?>
				<div class="wpec-single-note" id="wpec_single_note_<?php echo esc_attr($note["id"]); ?>">
					<p><?php echo esc_html( $note_content ); ?></p>
					<div class="wpec-single-note-meta">
						<span title="Added by <?php echo esc_html($admin_name); ?>">added on <?php echo esc_html( $date_time ); ?></span>
						<a href="#" class="wpec-delete-order-note" data-orderid="<?php echo esc_attr($post->ID); ?>" data-note-id="<?php echo esc_attr( $note['id'] ) ?>">Delete</a>
					</div>
				</div>
				<?php                
            }
            ?>        
		</div>
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

	public function paypal_refund_callback()
	{
		check_ajax_referer( 'paypal-refund', 'nonce' );

		try {
			$order = Orders::retrieve( $_POST['order'] );						
		} catch ( Exception $exc ) {
			wp_send_json_error( $exc->getMessage() );
		}

		$response = Orders::refund( $order );		
		
		if(is_wp_error($response) ){
				wp_send_json_error( $response->get_error_message() );
		}		

		wp_send_json_success( __( 'Order refunded successfully!', 'wp-express-checkout' ) );
	}

	public function wpec_add_order_note_callback()
	{
		check_ajax_referer( 'wpec_add_order_note_ajax_nonce', 'nonce' );

		$note = isset( $_POST['wpec_note'] ) ? sanitize_textarea_field( $_POST['wpec_note'] ) : '';
		
		if ( $note !== '' ) {
			$current_time = current_time( 'timestamp' );
			$note_data = array(
				'id'         => uniqid("",true),
				'admin_id'   => get_current_user_id(),
				'timestamp'  => $current_time,
				'content'    => $note
			);
	
			$order_id = isset( $_POST['wpec_order_id'] ) ? intval( $_POST['wpec_order_id'] ) : 0;
			$order_notes = get_post_meta( $order_id, 'wpec_order_notes', true );
	
			if ( ! is_array( $order_notes ) ) {
				$order_notes = array();
			}
	
			$order_notes[] = $note_data;
	
			update_post_meta( $order_id, 'wpec_order_notes', $order_notes );
	
			$admin_user = get_userdata(get_current_user_id());
			$username  = $admin_user->user_login !== $admin_user->display_name ? $admin_user->display_name . ' (' . $admin_user->user_login . ') ' : $admin_user->user_login;
			
			$note_data["admin_name"]=$username;
			$note_data["note_date"]=date('F j, Y \a\t g:ia', $current_time);		
			$note_data["order_id"]	=$order_id;

			wp_send_json_success( array( 'note' => $note_data ) );
		} else {
			wp_send_json_error( 'Invalid order note.' );
		}

	}

	public function wpec_delete_order_note_callback()
	{
		check_ajax_referer( 'wpec_delete_order_note_ajax_nonce', 'nonce' ); // Verify the AJAX request's nonce

		$note_id = isset( $_POST['wpec_note_id'] ) ? sanitize_text_field( $_POST['wpec_note_id'] ) : '';
	
		if ( $note_id !== '' ) {
			$order_id = isset( $_POST['wpec_order_id'] ) ? intval( $_POST['wpec_order_id'] ) : 0;
			$order_notes = get_post_meta( $order_id, 'wpec_order_notes', true );
	
			if ( ! empty( $order_notes ) ) {
				foreach ( $order_notes as $index => $note ) {
					if ( $note['id'] === $note_id ) {
						unset( $order_notes[ $index ] );
						break;
					}
				}
	
				// Update the order notes meta
				update_post_meta( $order_id, 'wpec_order_notes', array_values( $order_notes ) );
	
				wp_send_json_success();
			} else {
				wp_send_json_error( 'No order notes found.' );
			}
		} else {
			wp_send_json_error( 'Invalid note ID.' );
		}
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
