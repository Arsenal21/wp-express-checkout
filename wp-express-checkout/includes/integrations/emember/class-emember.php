<?php

namespace WP_Express_Checkout\Integrations;

use WP_Express_Checkout\Debug\Logger;
use WP_Express_Checkout\Products;
use WP_Express_Checkout\Utils;

class Emember {

	public function __construct() {
		add_action( 'wpec_payment_completed', array( $this, 'handle_signup' ), 10, 3 );

		if ( is_admin() ) {
			$this->admin();
		}
	}

	public function handle_signup( $payment, $order_id, $product_id ) {

		// let's check if Membership Level is set for this product.
		$level_id = get_post_meta( $product_id, 'wpec_product_emember_level', true );
		if ( empty( $level_id ) ) {
			return;
		}

		$ipn_data = $this->get_member_info_from_api( $payment );

		Logger::log( 'Calling eMember_handle_subsc_signup_stand_alone' );

		$emember_id = '';
		if ( class_exists( 'Emember_Auth' ) ) {
			// Check if the user is logged in as a member.
			$emember_auth = \Emember_Auth::getInstance();
			$emember_id   = $emember_auth->getUserInfo( 'member_id' );
		}

		if ( defined( 'WP_EMEMBER_PATH' ) ) {
			require_once WP_EMEMBER_PATH . 'ipn/eMember_handle_subsc_ipn_stand_alone.php';
			eMember_handle_subsc_signup_stand_alone( $ipn_data, $level_id, $payment['id'], $emember_id );
		}

	}

	public function get_member_info_from_api( $payment ) {
		// let's form data required for eMember_handle_subsc_signup_stand_alone function and call it.
		$first_name   = ! empty( $payment['payer']['name']['given_name'] ) ? $payment['payer']['name']['given_name'] : '';
		$last_name    = ! empty( $payment['payer']['name']['surname'] ) ? $payment['payer']['name']['surname'] : '';
		$addr_street  = ! empty( $payment['payer']['address']['address_line_1'] ) ? $payment['payer']['address']['address_line_1'] : '';
		$addr_zip     = ! empty( $payment['payer']['address']['postal_code'] ) ? $payment['payer']['address']['postal_code'] : '';
		$addr_city    = ! empty( $payment['payer']['address']['admin_area_2'] ) ? $payment['payer']['address']['admin_area_2'] : '';
		$addr_state   = ! empty( $payment['payer']['address']['admin_area_1'] ) ? $payment['payer']['address']['admin_area_1'] : '';
		$addr_country = ! empty( $payment['payer']['address']['country_code'] ) ? $payment['payer']['address']['country_code'] : '';

		if ( ! empty( $addr_country ) ) {
			// convert country code to country name.
			$countries = Utils::get_countries_untranslated();
			if ( isset( $countries[ $addr_country ] ) ) {
				$addr_country = $countries[ $addr_country ];
			}
		}

		$ipn_data = array(
			'payer_email'     => $payment['payer']['email_address'],
			'first_name'      => $first_name,
			'last_name'       => $last_name,
			'txn_id'          => $payment['id'],
			'address_street'  => $addr_street,
			'address_city'    => $addr_city,
			'address_state'   => $addr_state,
			'address_zip'     => $addr_zip,
			'address_country' => $addr_country,
		);

		return $ipn_data;
	}

	public function admin() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'wpec_save_product_handler', array( $this, 'save_product_handler' ) );
	}

	public function add_meta_boxes() {
		add_meta_box( 'wpec_emember_meta_box', __( 'WP eMember Membership Level', 'wp-express-checkout' ), array( $this, 'display_meta_box' ), Products::$products_slug, 'normal', 'high' );
	}

	public function display_meta_box( $post ) {
		$current_val = get_post_meta( $post->ID, 'wpec_product_emember_level', true );

		if ( ! function_exists( 'emember_get_all_membership_levels_list' ) ) {
			_e( 'Notice: You need to update your copy of the WP eMember plugin before this feature can be used.', 'wp-express-checkout' );
			return;
		}

		$all_levels = emember_get_all_membership_levels_list();
		$levels_str = '<option value="">(' . __( 'None', 'wp-express-checkout' ) . ')</option>' . "\r\n";

		foreach ( $all_levels as $level ) {
			$levels_str .= '<option value="' . $level->id . '"' . ( $level->id === $current_val ? ' selected' : '' ) . '>' . stripslashes( $level->alias ) . '</option>' . "\r\n";
		}
		?>
<p><?php esc_html_e( 'If you want this product to be connected to a membership level then select the membership Level here.', 'wp-express-checkout' ); ?></p>
<select name="wpec_product_emember_level">
		<?php
		echo wp_kses(
			$levels_str,
			array(
				'option' => array(
					'value'    => array(),
					'selected' => array(),
				),
			)
		);
		?>
</select>
		<?php
	}

	function save_product_handler( $post_id ) {
		update_post_meta( $post_id, 'wpec_product_emember_level', ! empty( $_POST['wpec_product_emember_level'] ) ? intval( $_POST['wpec_product_emember_level'] ) : '' );
	}

}
