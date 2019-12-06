<?php

/*
 * This class handles various init time (init hook) tasks.
 */

class WPEC_Init_Time_Tasks {

    public function __construct() {

        add_action('init', array($this, 'do_init_time_tasks'));
    }

    public function do_init_time_tasks() {
        //General init time tasks
        add_action('wp_ajax_wpec_reset_log', array($this, 'wpec_handle_reset_log'));

        if ( is_admin() ) {
            //Do admin side only tasks
            
            $this->handle_view_log_action();
            
        } else {
            //Front-end only tasks
            //NOP
        }
        
    }

    public function wpec_handle_reset_log() {
        WPEC_Debug_Logger::reset_log();
        echo '1';
        wp_die();
    }

    public function handle_view_log_action() {
        
	if ( user_can( wp_get_current_user(), 'administrator' ) ) {
	    // user is an admin
	    if ( isset( $_GET[ 'wpec-debug-action' ] ) ) {
		if ( $_GET[ 'wpec-debug-action' ] === 'view_log' ) {
		    $logfile = fopen( WPEC_LOG_FILE, 'rb' );
		    header( 'Content-Type: text/plain' );
		    fpassthru( $logfile );
		    die;
		}
	    }
	}        
    }

}
