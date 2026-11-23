<?php
/**
 * Fake of WP eMember's ipn/eMember_handle_subsc_ipn_stand_alone.php (see ../../../integration-fakes.php).
 */
if ( ! function_exists( 'eMember_handle_subsc_signup_stand_alone' ) ) {
	function eMember_handle_subsc_signup_stand_alone( $ipn_data, $level_id, $unique_ref, $emember_id = '' ) {
		wpec_test_record_call( 'eMember_handle_subsc_signup_stand_alone', func_get_args() );
	}
}
