<?php
// pwa-kit/includes/class-pk-sender-engine.php
namespace PwaKit\Core;

if (!defined('ABSPATH')) exit;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use PwaKit\Logger;

class SenderEngine
{
    private $webPush;
    private $settings;
    private $db;
    private $queue_table;
    private $subs_table;
    private $notif_table;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->queue_table = $wpdb->prefix . 'pk_queue';
        $this->subs_table = $wpdb->prefix . 'pk_subscriptions';
        $this->notif_table = $wpdb->prefix . 'pk_notifications';
        $this->settings = get_option('pk_notification_settings', []);
        $auth = ['VAPID' => ['subject' => $this->settings['vapid_subject'] ?? home_url(), 'publicKey' => $this->settings['public_key'] ?? '', 'privateKey' => $this->settings['private_key'] ?? '']];
        if (empty($auth['VAPID']['publicKey']) || empty($auth['VAPID']['privateKey'])) {
            throw new \Exception("VAPID keys are not configured.");
        }
        $this->webPush = new WebPush($auth);
        $this->webPush->setReuseVAPIDHeaders(true);
    }

    public function send_batch(array $items)
    {
        if (empty($items)) return;
        $notification_ids = array_filter(array_unique(\wp_list_pluck($items, 'notification_id')));
        $subscription_ids = array_filter(array_unique(\wp_list_pluck($items, 'subscription_id')));
        if (empty($notification_ids) || empty($subscription_ids)) {
            Logger::log("ENGINE ERROR: Batch contains invalid items with no IDs.");
            return;
        }
        $notif_ids_placeholder = implode(',', array_fill(0, count($notification_ids), '%d'));
        $notifications = $this->db->get_results($this->db->prepare("SELECT * FROM {$this->notif_table} WHERE id IN ({$notif_ids_placeholder})", ...$notification_ids), OBJECT_K);
        $sub_ids_placeholder = implode(',', array_fill(0, count($subscription_ids), '%d'));
        $subscriptions = $this->db->get_results($this->db->prepare("SELECT * FROM {$this->subs_table} WHERE id IN ({$sub_ids_placeholder})", ...$subscription_ids), OBJECT_K);

        $user_ids = array_filter(array_unique(\wp_list_pluck($subscriptions, 'user_id')));
        $users_by_id = [];

        if (!empty($user_ids)) {
            Logger::log("ENGINE: Found " . count($user_ids) . " unique users in this batch. Fetching user data.");
            $user_query = new \WP_User_Query(['include' => $user_ids]);
            $users_found = $user_query->get_results();
            foreach ($users_found as $user) {
                $users_by_id[$user->ID] = $user->data;
            }
        }

        Logger::log("ENGINE: Pre-fetched data for " . count($items) . " items. Queuing for parallel sending.");

        $failed_items = [];
        $item_map = [];
        foreach ($items as $item) {
            try {
                $notification = null;
                if (!empty($item->notification_id)) {
                    $notification = $notifications_from_db[$item->notification_id] ?? null;
                } elseif (!empty($item->notification_data)) {
                    $notification = (object) $item->notification_data;
                }

                $notification = $notifications[$item->notification_id] ?? null;
                $subscription = $subscriptions[$item->subscription_id] ?? null;
                if (!$notification || !$subscription || $subscription->status !== 'active') {
                    if ($item->id > 0) $failed_items[$item->id] = 'Invalid template or subscription.';
                    continue;
                }

                $personalized_title = $this->personalize_text($notification, $subscription->user_id, $users_by_id);
                $personalized_message = $this->personalize_text($notification, $subscription->user_id, $users_by_id, 'message');

                $payload_array = ['title' => $personalized_title, 'body'  => $personalized_message, 'icon'  => $notification->icon ?: ($this->settings['default_icon'] ?? ''), 'url' => $notification->url, 'image' => $notification->image];
                $payload = json_encode(array_filter($payload_array));
                $webpush_subscription = Subscription::create(['endpoint' => $subscription->endpoint, 'publicKey' => $subscription->public_key, 'authToken' => $subscription->auth_token]);
                $this->webPush->queueNotification(
                    $webpush_subscription,
                    $payload,
                    ['TTL' => $this->settings['default_ttl'] ?? 2419200, 'urgency' => ($item->priority ?? 'normal') === 'high' ? 'high' : 'normal']
                );
                $item_map[$subscription->endpoint] = $item;
            } catch (\Throwable $e) {
                if ($item->id > 0) $failed_items[$item->id] = 'Catastrophic: ' . $e->getMessage();
                Logger::log("ENGINE PREP ERROR: Queue ID {$item->id} failed. Reason: " . $e->getMessage());
            }
        }

        Logger::log("ENGINE: Flushing notifications in parallel.");
        $reports = $this->webPush->flush();

        $sent_ids = [];
        $expired_sub_ids = [];
        $retry_items = [];
        $consecutive_failure_threshold = 20;
        $consecutive_failures = 0;

        foreach ($reports as $report) {
            $endpoint = $report->getEndpoint();
            $item = $item_map[$endpoint] ?? null;
            if (!$item) {
                continue;
            }
            if ($report->isSuccess()) {
                if ($item->id > 0) $sent_ids[] = $item->id;
                $consecutive_failures = 0;
            } else {
                $reason = $report->getReason();
                $statusCode = $report->getResponse() ? $report->getResponse()->getStatusCode() : 0;
                if ($report->isSubscriptionExpired()) {
                    if ($item->id > 0) $failed_items[$item->id] = 'Expired';
                    $expired_sub_ids[] = $item->subscription_id;
                    $consecutive_failures = 0;
                } elseif ($statusCode === 429 || $statusCode >= 500) {
                    if ($item->id > 0) $retry_items[$item->id] = ($item->retry_count ?? 0) + 1;
                    $consecutive_failures = 0;
                } else {
                    if ($item->id > 0) $failed_items[$item->id] = $reason;
                    $consecutive_failures++;
                    Logger::log("ENGINE CRITICAL FAILURE: Count {$consecutive_failures}. Reason: {$reason}");
                }
            }

            if ($consecutive_failures >= $consecutive_failure_threshold) {
                \set_transient('pk_circuit_breaker_cooldown', true, MINUTE_IN_SECONDS * 10);
                Logger::log("ENGINE CIRCUIT BREAKER: Tripped after {$consecutive_failures} consecutive critical failures. Halting process for 10 minutes.");
                break;
            }
        }

        $this->batch_update_statuses($sent_ids, $failed_items, $retry_items, $expired_sub_ids);
        Logger::log("ENGINE: Batch finished processing.");
    }

    /**
     * متد هوشمند برای جایگزینی پترن‌ها در متن با مدیریت کاربران مهمان.
     *
     * @param object $notification آبجکت کامل قالب نوتیفیکیشن.
     * @param int $user_id ID کاربر.
     * @param array $users_by_id آرایه داده‌های کاربران.
     * @param string $field فیلدی که باید شخصی‌سازی شود ('title' یا 'message').
     * @return string متن شخصی‌سازی شده.
     */
    private function personalize_text($notification, $user_id, $users_by_id, $field = 'title')
    {
        $text = $notification->{$field} ?? '';

        if (strpos($text, '{') === false) {
            return $text;
        }

        if ($user_id > 0 && isset($users_by_id[$user_id])) {
            $user_data = $users_by_id[$user_id];
            $first_name = get_user_meta($user_id, 'first_name', true) ?: '';
            $last_name = get_user_meta($user_id, 'last_name', true) ?: '';

            $replacements = [
                '{first_name}'   => $first_name,
                '{last_name}'    => $last_name,
                '{display_name}' => $user_data->display_name,
                '{user_email}'   => $user_data->user_email,
            ];

            $text = str_replace('{first_name}{last_name}', $first_name . $last_name, $text);
            $text = str_replace('{last_name}{first_name}', $last_name . $first_name, $text);
            $text = str_replace('{first_name} {last_name}', trim($first_name . ' ' . $last_name), $text);
            $text = str_replace('{last_name} {first_name}', trim($last_name . ' ' . $first_name), $text);

            return str_replace(array_keys($replacements), array_values($replacements), $text);
        } else {
            $fallback = !empty($notification->guest_fallback) ? $notification->guest_fallback : 'کاربر';

            $name_pattern = '/(?:\{first_name\}|\{last_name\}|\{display_name\})(?:\s*|)(?:\{first_name\}|\{last_name\}|\{display_name\})*/';
            $text = preg_replace($name_pattern, $fallback . ' ', $text);
            $text = str_replace('{user_email}', '', $text);

            return $text;
        }
    }

    private function batch_update_statuses($sent_ids, $failed_items, $retry_items, $expired_sub_ids)
    {
        if (!empty($sent_ids)) {
            $ids = implode(',', array_map('absint', $sent_ids));
            $this->db->query($this->db->prepare("UPDATE {$this->queue_table} SET status = 'sent', sent_at = %s WHERE id IN ({$ids})", get_tehran_time()));
        }
        if (!empty($failed_items)) {
            $updates_by_reason = [];
            foreach ($failed_items as $id => $reason) {
                $safe_reason = mb_substr($reason, 0, 255);
                $updates_by_reason[$safe_reason][] = absint($id);
            }
            foreach ($updates_by_reason as $reason => $ids) {
                $this->db->query($this->db->prepare("UPDATE {$this->queue_table} SET status = 'failed', status_message = %s WHERE id IN (" . implode(',', $ids) . ")", $reason));
            }
        }
        if (!empty($retry_items)) {
            $max_retries = 5;
            $final_failed_ids = [];
            $retry_updates = ['retry_count' => [], 'next_attempt_at' => []];
            $ids_to_retry = [];
            foreach ($retry_items as $id => $count) {
                if ($count > $max_retries) {
                    $final_failed_ids[] = $id;
                } else {
                    $ids_to_retry[] = $id;
                    $delay_seconds = (60 * pow(2, $count - 1)) + rand(0, 10);
                    $next_attempt = get_tehran_time(modify: '+ ' . $delay_seconds . ' seconds');
                    $retry_updates['retry_count'][$id] = $count;
                    $retry_updates['next_attempt_at'][$id] = $next_attempt;
                }
            }
            if (!empty($ids_to_retry)) {
                $sql = "UPDATE {$this->queue_table} SET status = 'queued', ";
                $sql .= "retry_count = CASE id ";
                foreach ($retry_updates['retry_count'] as $id => $val) {
                    $sql .= $this->db->prepare("WHEN %d THEN %d ", $id, $val);
                }
                $sql .= "END, next_attempt_at = CASE id ";
                foreach ($retry_updates['next_attempt_at'] as $id => $val) {
                    $sql .= $this->db->prepare("WHEN %d THEN %s ", $id, $val);
                }
                $sql .= "END WHERE id IN (" . implode(',', array_map('absint', $ids_to_retry)) . ")";
                $this->db->query($sql);
            }
            if (!empty($final_failed_ids)) {
                $this->db->query("UPDATE {$this->queue_table} SET status = 'failed', status_message = 'Max retries reached' WHERE id IN (" . implode(',', array_map('absint', $final_failed_ids)) . ")");
            }
        }
        if (!empty($expired_sub_ids)) {
            $ids = implode(',', array_map('absint', $expired_sub_ids));
            $this->db->query("UPDATE {$this->subs_table} SET status = 'expired' WHERE id IN ({$ids})");
        }
    }
}
