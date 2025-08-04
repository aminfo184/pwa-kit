<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * این تابع جداول مورد نیاز پلاگین را در دیتابیس ایجاد می‌کند.
 * نام تابع به pk_create_tables تغییر کرده است.
 */
function pk_create_tables()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // ما از همان پیشوند pk_ استفاده می‌کنیم تا با کوئری‌های موجود در سایر فایل‌ها هماهنگ باشد.
    $prefix = $wpdb->prefix . 'pk_';

    // جدول ۱: pk_notifications (برای ذخیره قالب‌های نوتیفیکیشن)
    $sql_notifications = "
    CREATE TABLE {$prefix}notifications (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        internal_name VARCHAR(100) NOT NULL,
        title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        message TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        url TEXT,
        image TEXT NULL DEFAULT NULL,
        badge TEXT NULL DEFAULT NULL,
        tag VARCHAR(100) NULL DEFAULT NULL,
        actions JSON NULL DEFAULT NULL,
        guest_fallback VARCHAR(255) DEFAULT NULL,
        status ENUM('draft','published', 'post_based','archived') DEFAULT 'published',
        total_sent INT UNSIGNED DEFAULT 0,
        total_failed INT UNSIGNED DEFAULT 0,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY `internal_name_idx` (`internal_name`)
    ) $charset_collate;";

    // جدول ۲: pk_subscriptions (برای مدیریت اشتراک‌های کاربران)
    $sql_subscriptions = "
    CREATE TABLE {$prefix}subscriptions (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED DEFAULT 0,
        guest_token VARCHAR(64) DEFAULT NULL,
        session_token_hash VARCHAR(64) DEFAULT NULL,
        endpoint MEDIUMTEXT NOT NULL,
        public_key MEDIUMTEXT,
        auth_token MEDIUMTEXT,
        browser VARCHAR(100),
        os VARCHAR(100),
        ip_address VARCHAR(100),
        status ENUM('active', 'expired', 'unsubscribed') DEFAULT 'active',
        created_at DATETIME NOT NULL,
        updated_at DATETIME,
        PRIMARY KEY (id),
        UNIQUE KEY endpoint_unique (endpoint(255)),
        KEY user_id_idx (user_id),
        KEY session_token_idx (session_token_hash),
        KEY status_idx (status),
        KEY guest_token_idx (guest_token)
    ) $charset_collate;";

    // جدول ۳: pk_queue (برای صف ارسال و گزارش‌گیری)
    $sql_queue = "
    CREATE TABLE {$prefix}queue (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        notification_id BIGINT(20) UNSIGNED NOT NULL,
        subscription_id BIGINT(20) UNSIGNED NOT NULL,
        status ENUM('queued', 'processing', 'sent', 'failed', 'expired') DEFAULT 'queued',
        priority ENUM('normal', 'high') DEFAULT 'normal',
        scheduled_for DATETIME NOT NULL,
        retry_count TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
        next_attempt_at DATETIME DEFAULT NULL,
        last_attempt_at DATETIME,
        sent_at DATETIME,
        status_message TEXT,
        PRIMARY KEY (id),
        KEY `subscriber_id_idx` (`subscription_id`),
        KEY `notification_id_idx` (`notification_id`),
        KEY `status_priority_scheduled_idx` (`status`,`priority`,`scheduled_for`),
        KEY `next_attempt_idx` (`next_attempt_at`)
    ) $charset_collate;";

//    $sql_campaigns = "
//    CREATE TABLE {$prefix}campaigns (
//      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
//      `notification_id` INT UNSIGNED NOT NULL,
//      `status` ENUM('draft', 'queuing', 'queued', 'sending', 'finished', 'paused', 'recurring') NOT NULL DEFAULT 'draft',
//      `targeting_args` JSON NULL,
//      `total_subscribers` INT UNSIGNED NOT NULL DEFAULT 0,
//      `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
//      `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
//      `expired_count` INT UNSIGNED NOT NULL DEFAULT 0,
//      `error_summary` JSON NULL,
//      `max_retries` TINYINT UNSIGNED NOT NULL DEFAULT 0,
//      `created_at` DATETIME NOT NULL,
//      `scheduled_for` DATETIME NULL,
//      `processing_started_at` DATETIME NULL,
//      `finished_at` DATETIME NULL,
//      `recurrence_rule` VARCHAR(100) NULL, -- e.g., 'daily', 'weekly', 'monthly'
//      `last_recurrence_at` DATETIME NULL,
//      PRIMARY KEY (`id`),
//      INDEX `idx_status` (`status`),
//      INDEX `idx_recurrence` (`recurrence_rule`, `last_recurrence_at`)
//    ) $charset_collate;";

    // اجرای کوئری‌ها با استفاده از dbDelta
    dbDelta($sql_notifications);
    dbDelta($sql_subscriptions);
    dbDelta($sql_queue);
//    dbDelta($sql_campaigns);
}
