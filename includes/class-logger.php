<?php

namespace PwaKit;

if (!defined('ABSPATH')) exit;

class Logger
{
    private static $log_file;

    public static function init()
    {
        $upload_dir = \wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/pk-logs';
        if (!file_exists($log_dir)) {
            \wp_mkdir_p($log_dir);
        }
        self::$log_file = $log_dir . '/sender.log';
    }

    public static function log($message)
    {
        if (!self::$log_file) {
            self::init();
        }
        $timestamp = get_tehran_time();
        $formatted_message = "[{$timestamp}] - {$message}\n";
        \error_log($formatted_message, 3, self::$log_file);
    }
}
