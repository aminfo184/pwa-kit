<?php

/**
 * Plugin Name:       PWA Kit
 * Plugin URI:        https://zhinotech.com/
 * Description:       مجموعه ابزارهای پیشرفته PWA و پوش نوتیفیکیشن برای وردپرس.
 * Version:           2.1.0
 * Author:            ZHinotech
 * Author URI:        https://zhinotech.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pwa-kit
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PWA_KIT_VERSION', '2.1.0');
define('PWA_KIT_PATH', plugin_dir_path(__FILE__));
define('PWA_KIT_URL', plugin_dir_url(__FILE__));

define('PWA_KIT_VENDOR_EXISTS', file_exists(PWA_KIT_PATH . 'vendor/autoload.php'));
if (PWA_KIT_VENDOR_EXISTS) {
    require_once PWA_KIT_PATH . 'vendor/autoload.php';
}

require_once PWA_KIT_PATH . 'includes/db-schema.php';

register_activation_hook(__FILE__, 'pk_create_tables');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

add_action('plugins_loaded', 'pk_init');

function add_theme_color_meta_tag(): void
{
    $settings = 'pk_pwa_settings';

    $theme_color = get_option($settings, [])['theme_color'];

    echo '<meta name="theme-color" content="' . esc_attr($theme_color) . '">' . "\n";
}

add_action('wp_head', 'add_theme_color_meta_tag');

/**
 * اسکریپت‌های مورد نیاز برای فرانت‌اند را بارگذاری می‌کند.
 */
function pk_enqueue_frontend_assets()
{
    // ... (کدهای enqueue شما برای اسکریپت‌های دیگر مانند pk-subscribe.js) ...

    // بارگذاری اسکریپت جدید برای مدیریت پخش مدیا در پس‌زمینه
    wp_enqueue_script(
        'pwa-kit-media-session', // نام منحصر به فرد اسکریپت
        PWA_KIT_URL . 'assets/js/pwa-media-session.js', // مسیر فایل
        [], // وابستگی‌ها
        PWA_KIT_VERSION, // نسخه
        true // در فوتر بارگذاری شود
    );
}

add_action('wp_enqueue_scripts', 'pk_enqueue_frontend_assets');

function pk_init()
{
    require_once PWA_KIT_PATH . 'includes/api-gatekeeper.php';
    require_once PWA_KIT_PATH . 'includes/class-pk-config.php';

    require_once PWA_KIT_PATH . 'includes/class-logger.php';
    require_once PWA_KIT_PATH . 'includes/subscription-handler.php';
    require_once PWA_KIT_PATH . 'includes/functions.php';
    require_once PWA_KIT_PATH . 'includes/class-pwa-handler.php';
    require_once PWA_KIT_PATH . 'includes/class-notification-handler.php';

    require_once PWA_KIT_PATH . 'includes/notification/class-template-manager.php';
    require_once PWA_KIT_PATH . 'includes/notification/class-sender-engine.php';
    require_once PWA_KIT_PATH . 'includes/notification/class-transactional-sender.php';
    require_once PWA_KIT_PATH . 'includes/notification/class-bulk-sender.php';
    require_once PWA_KIT_PATH . 'includes/notification/rest-api-controller.php';

    \PwaKit\PWA_Handler::get_instance();

    \PwaKit\Notification_Handler::get_instance();

    // راه‌اندازی کنترل‌کننده API
    PwaKit\API\PK_REST_API_Controller::init();

    add_action('pk_process_bulk_queue_hook', ['\PwaKit\Senders\BulkSender', 'process_queue']);


    if (is_admin()) {

        require_once PWA_KIT_PATH . 'admin/class-admin-manager.php';

        \PwaKit\Admin\Admin_Manager::get_instance();
    }
}

add_action('init', function () {
    if (isset($_GET['pk_clear_fake_data']) && $_GET['pk_clear_fake_data'] === 'yes') {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز.');
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'pk_subscriptions';
        $deleted_count = $wpdb->query("DELETE FROM {$table_name} WHERE endpoint LIKE 'https://fcm.googleapis.com/fcm/send/fake_endpoint_%'");
        wp_die(number_format($deleted_count) . ' مشترک فیک با موفقیت حذف شدند.', 'عملیات موفق');
    }

    if (isset($_GET['pk_generate_fake_data']) && $_GET['pk_generate_fake_data'] === 'yes') {
        if (!current_user_can('manage_options')) {

            wp_die('دسترسی غیرمجاز.');
        }
        if (function_exists('pk_generate_fake_subscribers')) {
            $message = pk_generate_fake_subscribers(15000);
            wp_die($message, 'عملیات موفق');
        } else {
            wp_die('خطا: تابع تولید داده یافت نشد.');
        }
    }
});
