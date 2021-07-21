<?php

namespace WP_Express_Checkout\Integrations;

use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Products;

class Simple_WP_Membership extends Emember {

	public function handle_signup( $payment, $order_id, $product_id ) {

		// let's check if Membership Level is set for this product
		$level_id = get_post_meta( $product_id, 'wpec_product_swpm_level', true );
		if ( empty( $level_id ) ) {
			return;
		}

		$ipn_data = $this->get_member_info_from_api( $payment );

		Logger::log( 'Calling swpm_handle_subsc_signup_stand_alone' );

		$swpm_id = '';
		if ( \SwpmMemberUtils::is_member_logged_in() ) {
			$swpm_id = \SwpmMemberUtils::get_logged_in_members_id();
		}

		if ( defined( 'SIMPLE_WP_MEMBERSHIP_PATH' ) ) {
			require_once SIMPLE_WP_MEMBERSHIP_PATH . 'ipn/swpm_handle_subsc_ipn.php';
			swpm_handle_subsc_signup_stand_alone( $ipn_data, $level_id, $payment['id'], $swpm_id );
		}

	}

	public function add_meta_boxes() {
		add_meta_box( 'wpec_swpm_meta_box', __( 'Simple Membership Level', 'wp-express-checkout' ), array( $this, 'display_meta_box' ), Products::$products_slug, 'normal', 'high' );
	}

	public function display_meta_box( $post ) {
		$current_val = get_post_meta( $post->ID, 'wpec_product_swpm_level', true );
		?>
<p><?php esc_html_e( 'If you want this product to be connected to a membership level then select the membership Level here.', 'wp-express-checkout' ); ?></p>
<select name="wpec_product_swpm_level">
<option value=""><?php esc_html_e( 'None', 'wp-express-checkout' ); ?></option>
		<?php
		echo \SwpmUtils::membership_level_dropdown( $current_val );
		?>
</select>
		<?php
	}

	function save_product_handler( $post_id ) {
		update_post_meta( $post_id, 'wpec_product_swpm_level', ! empty( $_POST['wpec_product_swpm_level'] ) ? intval( $_POST['wpec_product_swpm_level'] ) : '' );
	}

}
