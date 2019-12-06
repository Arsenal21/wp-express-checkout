<?php

class WPEC_Debug_Logger {

    public function __construct() {
        
    }

    static function log($msg, $success = true) {
        $wpec_plugin = WPEC_Main::get_instance();

        $enable_debug_logging = $wpec_plugin->get_setting('enable_debug_logging');
        if ($enable_debug_logging) {
            file_put_contents(WPEC_LOG_FILE, date('Y-m-d H:i:s', time()) . ': [' . ($success === true ? 'SUCCESS' : 'FAIL') . '] ' . $msg . "\r\n", FILE_APPEND);
        }
    }

    static function reset_log() {
        file_put_contents(WPEC_LOG_FILE, date('Y-m-d H:i:s', time()) . ': Log has been reset.' . "\r\n");
        file_put_contents(WPEC_LOG_FILE, '-------------------------------------------------------' . "\r\n", FILE_APPEND);
    }

}
