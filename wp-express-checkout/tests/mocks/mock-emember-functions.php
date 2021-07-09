<?php

define( 'WP_EMEMBER_PATH', __DIR__ . '/' );

function emember_get_all_membership_levels_list() {
	$level1 = new stdClass();
	$level1->id    = '1';
	$level1->alias = 'test';
	$level2 = new stdClass();
	$level2->id    = '42';
	$level2->alias = 'answer to life the universe and everything';
	$levels = [ $level1, $level2 ];

	return $levels;
}

function wp_emember_install() {}

function eMember_handle_subsc_signup_stand_alone( $ipn_data, $subsc_ref, $unique_ref, $swpm_id = '' ) {
	print( 'eMember_handle_subsc_signup_stand_alone' );
}
