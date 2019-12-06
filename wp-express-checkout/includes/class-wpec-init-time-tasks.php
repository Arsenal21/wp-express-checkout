<?php

/*
 * This class handles various init time (init hook) tasks.
 */
class WPEC_Init_Time_Tasks {

    public function __construct() {

        add_action('init', array($this, 'do_init_time_tasks'));
    }

    public function do_init_time_tasks() {
        add_action('wp_ajax_wpec_reset_log', array($this, 'wpec_handle_reset_log'));
    }

    public function wpec_handle_reset_log() {
        WPEC_Debug_Logger::reset_log();
        echo '1';
        wp_die();
    }

}
