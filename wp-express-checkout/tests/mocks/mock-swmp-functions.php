<?php

define( 'SIMPLE_WP_MEMBERSHIP_PATH', __DIR__ . '/' );
define( 'SIMPLE_WP_MEMBERSHIP_VER', '1.0.0-test' );

function swpm_handle_subsc_signup_stand_alone( $ipn_data, $subsc_ref, $unique_ref, $swpm_id = '' ) {
	print( 'swpm_handle_subsc_signup_stand_alone' );
}