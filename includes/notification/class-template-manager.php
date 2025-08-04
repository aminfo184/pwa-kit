<?php
// pwa-kit/includes/class-pk-template-manager.php
namespace PwaKit\Core;

if (!defined('ABSPATH')) exit;

class TemplateManager
{
    public static function get_or_create($name, $defaults = [])
    {
        global $wpdb;
        $notif_table = $wpdb->prefix . 'pk_notifications';
        $cache_key = 'pk_template_id_' . $name;
        $template_id = wp_cache_get($cache_key, 'pwa-kit');
        if (false !== $template_id) {
            return (int) $template_id;
        }
        $template_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$notif_table} WHERE internal_name = %s", $name));
        if (!$template_id && !empty($defaults)) {
            $data = wp_parse_args($defaults, ['internal_name' => $name, 'title' => '', 'message' => '', 'url' => home_url(), 'status' => 'archived', 'created_at' => get_tehran_time()]);
            if ($wpdb->insert($notif_table, $data)) {
                $template_id = $wpdb->insert_id;
            }
        }
        if ($template_id) {
            wp_cache_set($cache_key, $template_id, 'pwa-kit', HOUR_IN_SECONDS);
            return (int) $template_id;
        }
        return null;
    }
}
