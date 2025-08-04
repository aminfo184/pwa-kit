<?php

namespace PwaKit\Admin;

if (!defined('ABSPATH')) exit;

// اطمینان از اینکه کلاس پایه وردپرس در دسترس است
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * کلاس مدیریت جدول نمایش مشترکین در پنل ادمین.
 * این کلاس از WP_List_Table ارث‌بری می‌کند تا از تمام قابلیت‌های استاندارد وردپرس بهره‌مند شود.
 */
class Subscribers_List_Table extends \WP_List_Table
{

    public function __construct()
    {
        parent::__construct([
            'singular' => 'مشترک',    // نام تکی آیتم
            'plural'   => 'مشترکین', // نام جمع آیتم‌ها
            'ajax'     => false      // ما از AJAX برای این جدول استفاده نمی‌کنیم
        ]);
    }

    /**
     * تعریف ستون‌های جدول
     */
    public function get_columns()
    {
        return [
            'cb'           => '<input type="checkbox" />', // چک‌باکس برای عملیات گروهی
            'user'         => 'کاربر',
            'browser_os'   => 'مرورگر / سیستم‌عامل',
            'ip_address'   => 'آدرس IP',
            'created_at'   => 'تاریخ اشتراک',
            'status'       => 'وضعیت',
        ];
    }

    /**
     * مقدار پیش‌فرض برای هر ستون
     */
    protected function column_default($item, $column_name)
    {
        return isset($item[$column_name]) ? \esc_html($item[$column_name]) : '';
    }

    /**
     * رندر کردن ستون چک‌باکس
     */
    protected function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="id[]" value="%s" />', $item['id']);
    }

    /**
     * رندر کردن ستون "کاربر" با اطلاعات و لینک‌های عملیاتی
     */
    protected function column_user($item)
    {
        if (!empty($item['display_name'])) {
            $user_info = '<strong><a href="' . \get_edit_user_link($item['user_id']) . '">' . \esc_html($item['display_name']) . '</a></strong>';
            $user_info .= '<br><small>' . \esc_html($item['user_email']) . '</small>';
        } else {
            $user_info = '<strong>کاربر مهمان</strong>';
        }

        // افزودن لینک "حذف"
        $delete_nonce = \wp_create_nonce('pk_delete_subscriber');
        $actions = [
            'delete' => \sprintf('<a href="?page=%s&action=delete&id=%s&_wpnonce=%s">حذف</a>', \esc_attr($_REQUEST['page']), \absint($item['id']), $delete_nonce)
        ];
        return $user_info . $this->row_actions($actions);
    }

    /**
     * رندر کردن ستون "مرورگر / سیستم‌عامل"
     */
    protected function column_browser_os($item)
    {
        $browser_icon = '';
        $os_icon = '';
        // در آینده می‌توانیم بر اساس نام مرورگر و سیستم‌عامل، آیکون نمایش دهیم
        return \esc_html($item['browser']) . '<br><small>' . \esc_html($item['os']) . '</small>';
    }

    /**
     * رندر کردن ستون "وضعیت" با استایل رنگی
     */
    protected function column_status($item)
    {
        $class = ($item['status'] === 'active') ? 'success' : 'error';
        $text = ($item['status'] === 'active') ? 'فعال' : 'منقضی شده';
        return '<mark class="' . $class . '">' . $text . '</mark>';
    }

    /**
     * آماده‌سازی داده‌ها برای نمایش در جدول (کوئری اصلی)
     */
    public function prepare_items()
    {
        global $wpdb;

        $this->_column_headers = [$this->get_columns(), [], []];

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $subs_table = $wpdb->prefix . 'pk_subscriptions';
        $users_table = $wpdb->users;

        $where_clauses = [];
        $params = [];

        // مدیریت جستجو
        if (!empty($_REQUEST['s'])) {
            $search = '%' . $wpdb->esc_like(\sanitize_text_field($_REQUEST['s'])) . '%';
            $where_clauses[] = '(u.display_name LIKE %s OR u.user_email LIKE %s OR sub.ip_address LIKE %s)';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $where_sql = (count($where_clauses) > 0) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        // شمارش تعداد کل آیتم‌ها برای صفحه‌بندی (کوئری بهینه)
        $total_items_query = "SELECT COUNT(sub.id) FROM {$subs_table} sub LEFT JOIN {$users_table} u ON sub.user_id = u.ID {$where_sql}";
        if (!empty($params)) {
            $total_items_query = $wpdb->prepare($total_items_query, ...$params);
        }
        $total_items = $wpdb->get_var($total_items_query);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        // دریافت داده‌های صفحه فعلی
        $query = "
            SELECT sub.id, sub.user_id, sub.browser, sub.os, sub.ip_address, sub.created_at, sub.status, u.display_name, u.user_email
            FROM {$subs_table} as sub
            LEFT JOIN {$users_table} as u ON sub.user_id = u.ID
            {$where_sql}
            ORDER BY sub.id DESC
            LIMIT %d OFFSET %d
        ";

        $final_params = \array_merge($params, [$per_page, $offset]);
        $this->items = $wpdb->get_results($wpdb->prepare($query, ...$final_params), ARRAY_A);
    }
}
