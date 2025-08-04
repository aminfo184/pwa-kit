<?php

namespace PwaKit\Admin;

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Notification_Templates_List_Table extends \WP_List_Table
{

    public function __construct()
    {
        parent::__construct([
            'singular' => 'قالب',
            'plural'   => 'قالب‌ها',
            'ajax'     => false
        ]);
    }

    protected function get_views()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pk_notifications';
        $current_status = isset($_GET['status']) ? $_GET['status'] : 'all';
        $total_count = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
        $published_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_name WHERE status = %s", 'published'));
        $archived_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_name WHERE status = %s", 'archived'));
        $post_based_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_name WHERE status = %s", 'post_based'));
        $draft_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_name WHERE status = %s", 'draft'));

        $views = [
            'all' => sprintf('<a href="%s" class="%s">همه <span class="count">(%s)</span></a>', remove_query_arg('status'), $current_status === 'all' ? 'current' : '', $total_count),
            'published' => sprintf('<a href="%s" class="%s">فعال <span class="count">(%s)</span></a>', add_query_arg('status', 'published'), $current_status === 'published' ? 'current' : '', $published_count),
            'archived' => sprintf('<a href="%s" class="%s">بایگانی شده<span class="count">(%s)</span></a>', add_query_arg('status', 'archived'), $current_status === 'archived' ? 'current' : '', $archived_count),
            'post_based' => sprintf('<a href="%s" class="%s">پست‌تایپ<span class="count">(%s)</span></a>', add_query_arg('status', 'post_based'), $current_status === 'post_based' ? 'current' : '', $post_based_count),
            'draft' => sprintf('<a href="%s" class="%s">پیش‌نویس <span class="count">(%s)</span></a>', add_query_arg('status', 'draft'), $current_status === 'draft' ? 'current' : '', $draft_count),
        ];
        return $views;
    }

    public function get_columns()
    {
        return [
            'cb'            => '<input type="checkbox" />',
            'image'         => '<span class="dashicons dashicons-format-image"></span>',
            'internal_name' => 'نام داخلی',
            'title'         => 'عنوان نوتیفیکیشن',
            'stats'         => 'آمار (ارسالی/ناموفق)',
            'status'        => 'وضعیت',
            'created_at'    => 'تاریخ ایجاد',
        ];
    }

    protected function column_default($item, $column_name)
    {
        return esc_html($item[$column_name]);
    }

    protected function column_image($item)
    {
        if (!empty($item['image'])) {
            return sprintf('<img src="%s" class="pk-template-thumbnail" />', esc_url($item['image']));
        }
        return '<span class="pk-template-thumbnail pk-placeholder-icon dashicons dashicons-format-image"></span>';
    }

    protected function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="id[]" value="%s" />', $item['id']);
    }

    protected function column_internal_name($item)
    {
        $actions = [
            'edit' => sprintf('<a href="#" class="edit-template" data-id="%s">ویرایش</a>', $item['id']),
            'delete' => sprintf('<a href="#" class="delete-template" data-id="%s" data-nonce="%s">حذف</a>', $item['id'], \wp_create_nonce('pk_template_nonce'))
        ];
        return '<strong><a href="#" class="edit-template" data-id="' . $item['id'] . '">' . esc_html($item['internal_name']) . '</a></strong> ' . $this->row_actions($actions);
    }

    protected function column_stats($item)
    {
        return sprintf('%s / %s', number_format($item['total_sent']), number_format($item['total_failed']));
    }

    protected function column_status($item)
    {
        $class = ($item['status'] === 'published') ? 'success' : (($item['status'] === 'archived') ? 'warning' : (($item['status'] === 'post_based') ? 'info' : 'error'));
        $text = ($item['status'] === 'published') ? 'فعال' : (($item['status'] === 'archived') ? 'آرشیو' : (($item['status'] === 'post_based') ? 'پست‌تایپ' : 'پیش‌نویس'));
        return '<mark class="' . $class . '">' . $text . '</mark>';
    }

    public function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pk_notifications';

        $this->_column_headers = [$this->get_columns(), [], []];

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $where_clauses = [];
        $params = [];

        if (isset($_GET['status']) && in_array($_GET['status'], ['published', 'archived', 'post_based', 'draft'])) {
            $where_clauses[] = "status = %s";
            $params[] = $_GET['status'];
        }

        if (!empty($_REQUEST['s'])) {
            $search = '%' . $wpdb->esc_like(\sanitize_text_field($_REQUEST['s'])) . '%';
            $where_clauses[] = '(internal_name LIKE %s OR title LIKE %s OR message LIKE %s)';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $where_sql = (count($where_clauses) > 0) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        $total_items_query = "SELECT COUNT(id) FROM {$table_name} {$where_sql}";
        if (!empty($params)) {
            $total_items_query = $wpdb->prepare($total_items_query, ...$params);
        }
        $total_items = $wpdb->get_var($total_items_query);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);

        $query = "SELECT * FROM {$table_name} {$where_sql} ORDER BY status ASC, created_at DESC LIMIT %d OFFSET %d";
        $final_params = \array_merge($params, [$per_page, $offset]);
        $this->items = $wpdb->get_results($wpdb->prepare($query, ...$final_params), ARRAY_A);
    }
}
