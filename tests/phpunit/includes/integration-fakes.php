<?php
/**
 * Fakes of the functions and classes the integrations call in the other plugins (Simple Membership, WP eMember, Software License Manager).
 * Each fake records its call in $GLOBALS['wpec_test_integration_calls'], so the tests can check what the integration sent.
 * Loaded only by test-integrations.php.
 */

$GLOBALS['wpec_test_integration_calls'] = array();

function wpec_test_record_call( $name, $args ) {
	$GLOBALS['wpec_test_integration_calls'][] = array(
		'name' => $name,
		'args' => $args,
	);
}

// ---- Simple Membership ----

if ( ! defined( 'SIMPLE_WP_MEMBERSHIP_PATH' ) ) {
	define( 'SIMPLE_WP_MEMBERSHIP_PATH', __DIR__ . '/fakes/simple-membership/' );
}

class SwpmMemberUtils {
	public static $logged_in_member_id = 0;

	public static function is_member_logged_in() {
		return ! empty( self::$logged_in_member_id );
	}

	public static function get_logged_in_members_id() {
		return self::$logged_in_member_id;
	}
}

function swpm_handle_subsc_signup_stand_alone( $ipn_data, $level_id, $unique_ref, $swpm_id = '' ) {
	wpec_test_record_call( 'swpm_handle_subsc_signup_stand_alone', func_get_args() );
}

function swpm_handle_subsc_cancel_stand_alone( $ipn_data, $refund = false ) {
	wpec_test_record_call( 'swpm_handle_subsc_cancel_stand_alone', func_get_args() );
}

function swpm_update_member_subscription_start_date_if_applicable( $ipn_data ) {
	wpec_test_record_call( 'swpm_update_member_subscription_start_date_if_applicable', func_get_args() );
}

// ---- WP eMember ----

if ( ! defined( 'WP_EMEMBER_PATH' ) ) {
	//The integration loads WP_EMEMBER_PATH . 'ipn/eMember_handle_subsc_ipn_stand_alone.php', which declares the signup function.
	define( 'WP_EMEMBER_PATH', __DIR__ . '/fakes/emember/' );
}

class Emember_Auth {
	public static $member_id = '';

	public static function getInstance() {
		return new self();
	}

	public function getUserInfo( $key ) {
		return 'member_id' === $key ? self::$member_id : '';
	}
}

// ---- Software License Manager ----

class SLM_API_Utility {
	public static function insert_license_data_internal( $fields ) {
		wpec_test_record_call( 'insert_license_data_internal', array( $fields ) );
	}
}

class WPEC_Test_SLM_Logger {
	public function log_debug( $message ) {
	}
}
$GLOBALS['slm_debug_logger'] = new WPEC_Test_SLM_Logger();
