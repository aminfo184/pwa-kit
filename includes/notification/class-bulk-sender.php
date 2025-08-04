<?php
// pwa-kit/includes/class-pk-bulk-sender.php
namespace PwaKit\Senders;

if (!defined('ABSPATH')) exit;

use PwaKit\Core\SenderEngine;
use PwaKit\Logger;
use Throwable;
use function delete_transient;
use function get_transient;
use function set_transient;
use function wp_list_pluck;

class BulkSender
{
    public static function process_queue()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (get_transient('pk_circuit_breaker_cooldown')) {
            Logger::log("BULK: Process halted by circuit breaker cooldown.");
            return;
        }
        if (get_transient('pk_sender_lock')) {
            Logger::log("BULK: Process already locked. Exiting.");
            return;
        }
        set_transient('pk_sender_lock', true, MINUTE_IN_SECONDS * 5);

        try {
            Logger::log("BULK: Lock acquired. Starting bulk processing.");
            global $wpdb;
            $queue_table = $wpdb->prefix . 'pk_queue';
            $stuck_items = $wpdb->query($wpdb->prepare("UPDATE {$queue_table} SET status = 'queued', last_attempt_at = NULL WHERE status = 'processing' AND last_attempt_at < %s", get_tehran_time(modify: '+' . (MINUTE_IN_SECONDS * 10) . ' seconds')));
            if ($stuck_items > 0) {
                Logger::log("BULK: Rescued {$stuck_items} stuck items.");
            }

            $settings = get_option('pk_notification_settings', []);
            $batch_size = !empty($settings['batch_size']) ? absint($settings['batch_size']) : 1000;

            $items_to_process = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$queue_table} WHERE status = 'queued' AND scheduled_for <= %s AND (next_attempt_at IS NULL OR next_attempt_at <= %s) ORDER BY priority DESC, id ASC LIMIT %d", get_tehran_time(), get_tehran_time(), $batch_size));

            if (empty($items_to_process)) {
                Logger::log("BULK: Queue is empty.");
            } else {
                Logger::log("BULK: Found " . count($items_to_process) . " items to process.");
                $item_ids = wp_list_pluck($items_to_process, 'id');
                $ids_placeholder = implode(',', array_fill(0, count($item_ids), '%d'));
                $query = "UPDATE {$queue_table} SET status = 'processing', last_attempt_at = %s WHERE id IN ({$ids_placeholder})";
                $wpdb->query($wpdb->prepare($query, get_tehran_time(), ...$item_ids));

                // کنترل هوشمند نرخ ارسال دسته‌ها
                $target_rate_hz = !empty($settings['send_rate_hz']) ? absint($settings['send_rate_hz']) : 20;
                $chunk_size = 100; // ارسال در دسته‌های ۱۰۰ تایی
                $item_chunks = array_chunk($items_to_process, $chunk_size);
                $engine = new SenderEngine();

                foreach ($item_chunks as $chunk) {
                    $start_time = microtime(true);
                    $engine->send_batch($chunk);
                    $processing_time = microtime(true) - $start_time;
                    $expected_time_for_chunk = $chunk_size / $target_rate_hz;
                    $sleep_time = $expected_time_for_chunk - $processing_time;
                    if ($sleep_time > 0) {
                        usleep($sleep_time * 1000000);
                    }
                }
            }
        } catch (Throwable $e) {
            Logger::log("BULK FATAL ERROR: " . $e->getMessage());
        } finally {
            Logger::log("BULK: Process finished. Releasing lock.");
            delete_transient('pk_sender_lock');
        }
    }
}
