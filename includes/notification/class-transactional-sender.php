<?php
// pwa-kit/includes/class-pk-transactional-sender.php
namespace PwaKit\Senders;

if (!defined('ABSPATH')) exit;

use PwaKit\Core\SenderEngine;
use PwaKit\Logger;

class TransactionalSender
{

    /**
     * این متد اصلی برای ارسال آنی است.
     * ابتدا پاسخ را به کلاینت برمی‌گرداند و سپس کار ارسال را در پس‌زمینه انجام می‌دهد.
     *
     * @param array $items آرایه‌ای از آیتم‌ها برای ارسال.
     */
    public static function send_now(array $items)
    {
        if (empty($items)) {
            return;
        }

        if (!is_callable('fastcgi_finish_request')) {
            Logger::log('TRANSACTIONAL WARNING: fastcgi_finish_request is not available. Sending synchronously.');
            try {
                $engine = new SenderEngine();
                $engine->send_batch($items);
            } catch (\Throwable $e) {
                Logger::log("TRANSACTIONAL SYNC ERROR: " . $e->getMessage());
            }
            return;
        }

        if (session_id()) session_write_close();
        header('Connection: close');
        header('Content-Length: 0');

        fastcgi_finish_request();

        try {
            Logger::log("TRANSACTIONAL BACKGROUND: Starting background send for " . count($items) . " item(s).");
            $engine = new SenderEngine();
            $engine->send_batch($items);
        } catch (\Throwable $e) {
            Logger::log("TRANSACTIONAL BACKGROUND ERROR: " . $e->getMessage());
        }
    }
}
