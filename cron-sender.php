<?php
// pwa-kit/cron-sender.php (نسخه نهایی)

/**
 * PWA Kit - Server Cron Job Entry Point (Fallback Method)
 */

// ۱. پیدا کردن و بارگذاری محیط وردپرس
// این بخش برای دسترسی به توابع و دیتابیس وردپرس ضروری است.
$wp_load_path = __DIR__ . '/../../../wp-load.php';
if (!file_exists($wp_load_path)) {
    // تلاش برای پیدا کردن مسیر در ساختارهای متفاوت نصب
    $wp_load_path = dirname(dirname(dirname(__DIR__))) . '/wp-load.php';
    if (!file_exists($wp_load_path)) {
        error_log("PWA Kit Cron Error: Could not find wp-load.php. Please check the path.");
        exit("Could not find wp-load.php");
    }
}
require_once($wp_load_path);

// ۲. اطمینان از اینکه فایل اصلی پلاگین برای بارگذاری کلاس‌ها در دسترس است.
if (!file_exists(plugin_dir_path(__FILE__) . 'pwa-kit.php')) {
    error_log("PWA Kit Cron Error: Main plugin file (pwa-kit.php) not found.");
    exit("Main plugin file not found.");
}
// فایل اصلی پلاگین را فراخوانی می‌کنیم که خود مسئول بارگذاری تمام کلاس‌هاست.
require_once plugin_dir_path(__FILE__) . 'pwa-kit.php';

// ۳. بررسی اینکه آیا کلاس مورد نظر ما بارگذاری شده است یا خیر.
if (!class_exists('\PwaKit\Senders\BulkSender')) {
    error_log("PWA Kit Cron Error: BulkSender class not found. Check plugin initialization.");
    exit("BulkSender class not found.");
}

// ۴. اجرای مستقیم متد پردازش صف کمپین.
\PwaKit\Senders\BulkSender::process_queue();

exit("PWA Kit Bulk Cron Finished.");