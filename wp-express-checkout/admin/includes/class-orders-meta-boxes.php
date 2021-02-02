<?php
/**
 * Order page metaboxes
 *
 * @since 2.0.0
 */
class WPEC_Orders_Metaboxes {

	var $WPECAdmin;
	var $WPEC_Main;

	public function __construct() {
		$this->WPECAdmin = WPEC_Admin::get_instance();
		$this->WPEC_Main = WPEC_Main::get_instance();

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'admin_menu', array( $this, 'remove_meta_boxes' ) );
		add_action( 'save_post_' . OrdersWPEC::PTYPE, array( $this, 'save' ), 10, 3 );
	}

	function add_meta_boxes() {
		add_meta_box( 'wpec_order_items', __( 'Order Summary', 'wp-express-checkout' ), array( $this, 'display_summary_meta_box' ), OrdersWPEC::PTYPE, 'normal', 'high' );
	}

	function display_summary_meta_box( $post ) {
		global $post;

		if ( OrdersWPEC::PTYPE != $post->post_type ) {
			return;
		}

		$order = OrdersWPEC::retrieve( $_GET['post'] );

		?>
		<style type="text/css">
			#admin-order-summary tbody td{
				padding-top: 10px;
				padding-bottom: 10px;
			}
			#admin-order-summary{
				margin-bottom: 20px;
			}
		</style>
		<?php

		$table = new WPEC_Admin_Order_Summary_Table( $order );
		$table->show( array(
			'class' => 'widefat',
			'id' => 'admin-order-summary'
		) );
	}

	function save( $post_id, $post, $update ) {
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
		remove_meta_box( 'submitdiv', OrdersWPEC::PTYPE, 'side' );
		remove_meta_box( 'slugdiv', OrdersWPEC::PTYPE, 'normal' );
		remove_meta_box( 'authordiv', OrdersWPEC::PTYPE, 'normal');
	}

}
