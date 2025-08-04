<?php

namespace PwaKit\Admin;

if (!defined('ABSPATH')) exit;

use PwaKit\Core\PK_Config as Config;

class Admin_Manager
{
    private static $instance = null;
    private $plugin_pages = [];

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        \add_action('admin_menu', [$this, 'create_admin_menu']);
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        \add_action('wp_ajax_pk_generate_vapid_keys', [$this, 'ajax_generate_vapid_keys']);
        \add_action('wp_ajax_pk_handle_template_action', [$this, 'ajax_handle_template_action']);
        \add_action('wp_ajax_pk_search_notification_templates', [$this, 'ajax_search_notification_templates']);
        \add_action('wp_ajax_pk_queue_campaign', [$this, 'ajax_queue_campaign']);
        \add_action('wp_ajax_pk_search_users_with_subscription', [$this, 'ajax_search_users_with_subscription']);
        \add_action('wp_ajax_pk_manage_custom_query', [$this, 'ajax_manage_custom_query']);
        \add_action('wp_ajax_pk_initiate_queue_job', [$this, 'ajax_initiate_queue_job']);
        \add_action('wp_ajax_pk_process_queue_batch', [$this, 'ajax_process_queue_batch']);
        \add_action('wp_footer', [$this, 'add_pwa_install_prompt_html']);
        \add_filter('wp_kses_post_tags', [$this, 'add_style_to_allowed_html_tags'], 10, 1);
        \add_action('admin_init', [$this, 'handle_api_key_generation']);
        \add_action('add_meta_boxes', [$this, 'add_notification_meta_box']);
//        \add_action('wp_ajax_pk_send_from_meta_box', [$this, 'ajax_send_from_meta_box']);
        add_action('wp_ajax_pk_initiate_metabox_job', [$this, 'ajax_initiate_metabox_job']);
        add_action('wp_ajax_pk_process_metabox_batch', [$this, 'ajax_process_metabox_batch']);
    }

    public function enqueue_admin_scripts($hook)
    {
        $is_plugin_page = in_array($hook, $this->plugin_pages, true);

        $is_post_edit_page = ($hook === 'post.php' || $hook === 'post-new.php');

        if (!$is_plugin_page && !$is_post_edit_page) {
            return;
        }

        \wp_enqueue_media();
        \wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0-rc.0');
        \wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0-rc.0', true);

        if ($hook === $this->plugin_pages['campaign']) {
            $code_editor_settings = \wp_enqueue_code_editor(['type' => 'text/x-sql']);
            \wp_localize_script('jquery', 'pk_code_editor_settings', $code_editor_settings);
        }
    }

    public function create_admin_menu()
    {
        $this->plugin_pages['dashboard'] = \add_menu_page('PWA Kit', 'وب اپلیکیشن', 'manage_options', 'pk-dashboard', [$this, 'render_dashboard_page'], 'dashicons-smartphone', 30);
        $this->plugin_pages['subscribers'] = \add_submenu_page('pk-dashboard', 'مشترکین', 'مشترکین', 'manage_options', 'pk-subscribers', [$this, 'render_subscribers_page']);
        $this->plugin_pages['templates'] = \add_submenu_page('pk-dashboard', 'قالب‌های نوتیفیکیشن', 'قالب‌ها', 'manage_options', 'pk-notification-templates', [$this, 'render_notification_templates_page']);
        $this->plugin_pages['campaign'] = \add_submenu_page('pk-dashboard', 'ارسال جدید', 'ارسال جدید', 'manage_options', 'pk-send-campaign', [$this, 'render_send_campaign_page']);
        $this->plugin_pages['settings'] = \add_submenu_page('pk-dashboard', 'تنظیمات', 'تنظیمات', 'manage_options', 'pk-settings', [$this, 'render_settings_page']);
    }

    public function render_dashboard_page()
    {
        global $wpdb;
        $subs_table = $wpdb->prefix . 'pk_subscriptions';
        $queue_table = $wpdb->prefix . 'pk_queue';
        $sub_stats = $wpdb->get_results("SELECT status, COUNT(id) as count FROM {$subs_table} GROUP BY status");
        $queue_stats = $wpdb->get_results("SELECT status, COUNT(id) as count FROM {$queue_table} GROUP BY status");
        require \PWA_KIT_PATH . 'templates/admin/dashboard-template.php';
    }

    public function render_settings_page()
    {
        if (isset($_POST['pk_settings_submit']) && isset($_POST['_wpnonce']) && \wp_verify_nonce($_POST['_wpnonce'], 'pkp_pwa_settings_action')) {
            $this->save_settings();
            $current_tab = isset($_POST['current_tab']) ? \sanitize_key($_POST['current_tab']) : 'pwa-main';
            $redirect_url = \add_query_arg(['page' => 'pk-settings', 'updated' => 'true', 'tab' => $current_tab], \admin_url('admin.php'));
            \wp_redirect($redirect_url);
            exit;
        }
        if (isset($_GET['updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
        }
        $all_pages = \get_pages();
        require_once \PWA_KIT_PATH . 'templates/admin/settings-template.php';
    }

    private function save_settings()
    {
        // pwa settings
        $pwa_options = \get_option('pk_pwa_settings');

        if (!is_array($pwa_options)) {
            $pwa_options = [];
        }

        $pwa_options['id'] = pk_get_sanitized_post('pk_id', 'sanitize_text_field', Config::get_pwa_defaults('id'));
        $pwa_options['name'] = pk_get_sanitized_post('pk_name', 'sanitize_text_field', Config::get_pwa_defaults('name'));
        $pwa_options['short_name'] = isset($_POST['pk_short_name']) ? \substr(\sanitize_text_field($_POST['pk_short_name']), 0, 12) : Config::get_pwa_defaults('short_name');
        $pwa_options['description'] = pk_get_sanitized_post('pk_description', 'sanitize_textarea_field', Config::get_pwa_defaults('description'));
        $pwa_options['start_url'] = pk_get_sanitized_post('pk_start_url', 'esc_url_raw', Config::get_pwa_defaults('start_url'));
        $pwa_options['categories'] = isset($_POST['pk_categories']) && is_array($_POST['pk_categories']) ? array_map('sanitize_text_field', $_POST['pk_categories']) : Config::get_pwa_defaults('categories');
        $pwa_options['display'] = pk_get_sanitized_post('pk_display', 'sanitize_key', Config::get_pwa_defaults('display'));
        $pwa_options['display_override'] = isset($_POST['pk_display_override']) && is_array($_POST['pk_display_override']) ? array_map('sanitize_key', $_POST['pk_display_override']) : Config::get_pwa_defaults('display_override');
        $pwa_options['orientation'] = pk_get_sanitized_post('pk_orientation', 'sanitize_key', Config::get_pwa_defaults('orientation'));
        $pwa_options['dir'] = pk_get_sanitized_post('pk_dir', 'sanitize_key', Config::get_pwa_defaults('dir'));
        $pwa_options['theme_color'] = pk_get_sanitized_post('pk_theme_color', 'sanitize_hex_color', Config::get_pwa_defaults('theme_color'));
        $pwa_options['background_color'] = pk_get_sanitized_post('pk_background_color', 'sanitize_hex_color', Config::get_pwa_defaults('background_color'));
        $pwa_options['icon_192'] = pk_get_sanitized_post('pk_icon_192', 'esc_url_raw', Config::get_pwa_defaults('icon_192'));
        $pwa_options['icon_512'] = pk_get_sanitized_post('pk_icon_512', 'esc_url_raw', Config::get_pwa_defaults('icon_512'));

        $allowed_purposes = ['any', 'any maskable'];
        $pwa_options['icon_purpose'] = isset($_POST['pk_icon_purpose']) && in_array($_POST['pk_icon_purpose'], $allowed_purposes, true) ? $_POST['pk_icon_purpose'] : Config::get_pwa_defaults('icon_purpose');
        $pwa_options['shortcuts'] = [];
        if (isset($_POST['pk_shortcuts']) && is_array($_POST['pk_shortcuts'])) {
            foreach ($_POST['pk_shortcuts'] as $shortcut) {
                if (!empty($shortcut['name']) && !empty($shortcut['url'])) {
                    $icon = ['src' => \esc_url_raw($shortcut['icons'][0]['src']), 'sizes' => \sanitize_text_field($shortcut['icons'][0]['sizes']), 'purpose' => 'any'];
                    $pwa_options['shortcuts'][] = ['name' => \sanitize_text_field($shortcut['name']), 'url' => \esc_url_raw($shortcut['url']), 'icons' => [$icon]];
                }
            }
        }
        $pwa_options['screenshots'] = [];
        if (isset($_POST['pk_screenshots']) && is_array($_POST['pk_screenshots'])) {
            foreach ($_POST['pk_screenshots'] as $ss) {
                if (!empty($ss['src']) && !empty($ss['sizes'])) {
                    $pwa_options['screenshots'][] = ['src' => \esc_url_raw($ss['src']), 'sizes' => \sanitize_text_field($ss['sizes']), 'type' => 'image/png', 'form_factor' => \sanitize_key($ss['form_factor'])];
                }
            }
        }

        // install popup contents & colors
        $pwa_options['popup_style'] = pk_get_sanitized_post('pk_popup_style', 'sanitize_key', Config::get_pwa_defaults('popup_style'));
        $pwa_options['popup_install_modal_title'] = pk_get_sanitized_post('pk_install_modal_title', 'sanitize_text_field', Config::get_pwa_defaults('popup_install_modal_title'));
        $pwa_options['popup_install_modal_text'] = pk_get_sanitized_post('pk_install_modal_text', 'sanitize_text_field', Config::get_pwa_defaults('popup_install_modal_text'));
        $pwa_options['popup_install_modal_button'] = pk_get_sanitized_post('pk_install_modal_button', 'sanitize_text_field', Config::get_pwa_defaults('popup_install_modal_button'));
        $pwa_options['popup_install_banner_title'] = pk_get_sanitized_post('pk_install_banner_title', 'sanitize_text_field', Config::get_pwa_defaults('popup_install_banner_title'));
        $pwa_options['popup_install_banner_text'] = pk_get_sanitized_post('pk_install_banner_text', 'sanitize_text_field', Config::get_pwa_defaults('popup_install_banner_text'));
        $pwa_options['popup_install_banner_button'] = pk_get_sanitized_post('pk_install_banner_button', 'sanitize_text_field', Config::get_pwa_defaults('popup_install_banner_button'));
        $pwa_options['popup_install_title_color'] = pk_get_sanitized_post('pk_install_title_color', 'sanitize_hex_color', Config::get_pwa_defaults('popup_install_title_color'));
        $pwa_options['popup_install_text_color'] = pk_get_sanitized_post('pk_install_text_color', 'sanitize_hex_color', Config::get_pwa_defaults('popup_install_text_color'));
        $pwa_options['popup_install_button_text_color'] = pk_get_sanitized_post('pk_install_button_text_color', 'sanitize_hex_color', Config::get_pwa_defaults('popup_install_button_text_color'));
        $pwa_options['popup_install_count'] = pk_get_sanitized_post('pk_popup_count', 'absint', Config::get_pwa_defaults('popup_install_count'));
        $pwa_options['popup_install_delay'] = pk_get_sanitized_post('pk_popup_delay', 'absint', Config::get_pwa_defaults('popup_install_delay'));

        // offline contents & colors & font
        $pwa_options['offline_content'] = pk_get_sanitized_post('pk_offline_content', 'wp_kses_post', Config::get_pwa_defaults('offline_content'));
        $pwa_options['offline_title_color'] = pk_get_sanitized_post('pk_offline_title_color', 'sanitize_hex_color', Config::get_pwa_defaults('offline_title_color'));
        $pwa_options['offline_text_color'] = pk_get_sanitized_post('pk_offline_text_color', 'sanitize_hex_color', Config::get_pwa_defaults('offline_text_color'));
        $pwa_options['offline_button_bg_color'] = pk_get_sanitized_post('pk_offline_button_bg_color', 'sanitize_hex_color', Config::get_pwa_defaults('offline_button_bg_color'));
        $pwa_options['offline_button_text_color'] = pk_get_sanitized_post('pk_offline_button_text_color', 'sanitize_hex_color', Config::get_pwa_defaults('offline_button_text_color'));
        $pwa_options['offline_button_border_color'] = pk_get_sanitized_post('pk_offline_button_border_color', 'sanitize_hex_color', Config::get_pwa_defaults('offline_button_border_color'));
        $pwa_options['offline_loader_color'] = pk_get_sanitized_post('pk_offline_loader_color', 'sanitize_hex_color', Config::get_pwa_defaults('offline_loader_color'));
        $pwa_options['offline_status_text_color'] = pk_get_sanitized_post('pk_offline_status_text_color', 'sanitize_hex_color', Config::get_pwa_defaults('offline_status_text_color'));
        $pwa_options['offline_main_font_url'] = isset($_POST['pk_offline_main_font_url']) ? esc_url_raw(stripslashes($_POST['pk_offline_main_font_url'])) : Config::get_pwa_defaults('offline_main_font_url');
        $pwa_options['offline_main_font_family'] = isset($_POST['pk_offline_main_font_family']) ? sanitize_text_field(stripslashes($_POST['pk_offline_main_font_family'])) : Config::get_pwa_defaults('offline_main_font_family');

        \update_option('pk_pwa_settings', $pwa_options);


        // notification settings 
        $notification_options = \get_option('pk_notification_settings', []);
        if (!is_array($notification_options)) $notification_options = [];

        $vapid_subject = pk_get_sanitized_post('pk_vapid_subject', 'sanitize_email', Config::get_notification_defaults('vapid_subject'));
        if ($vapid_subject && !str_starts_with($vapid_subject, 'mailto:')) {
            $vapid_subject = 'mailto:' . $vapid_subject;
        }
        $notification_options['vapid_subject'] = $vapid_subject;
        $notification_options['default_icon'] = pk_get_sanitized_post('pk_default_icon', 'esc_url_raw', Config::get_notification_defaults('default_icon'));
        $ttl_value = pk_get_sanitized_post('pk_ttl_value', 'absint', Config::get_notification_defaults('ttl_value'));
        $ttl_unit = pk_get_sanitized_post('pk_ttl_unit', 'sanitize_key', Config::get_notification_defaults('ttl_unit'));
        $multipliers = [
            'seconds' => 1,
            'minutes' => MINUTE_IN_SECONDS,
            'hours' => HOUR_IN_SECONDS,
            'days' => DAY_IN_SECONDS,
            'weeks' => WEEK_IN_SECONDS,
            'months' => DAY_IN_SECONDS * 30,
        ];
        $notification_options['default_ttl'] = $ttl_value * ($multipliers[$ttl_unit] ?? Config::get_notification_defaults('default_ttl'));
        $notification_options['default_urgency'] = pk_get_sanitized_post('pk_default_urgency', 'sanitize_key', Config::get_notification_defaults('default_urgency'));
        $notification_options['batch_size'] = pk_get_sanitized_post('pk_batch_size', 'absint', Config::get_notification_defaults('batch_size'));

        // notification subscribe popup
        $notification_options['popup_subscribe_title'] = pk_get_sanitized_post('pk_subscribe_title', 'sanitize_text_field', Config::get_notification_defaults('popup_subscribe_title'));
        $notification_options['popup_subscribe_text'] = pk_get_sanitized_post('pk_subscribe_text', 'sanitize_textarea_field', Config::get_notification_defaults('popup_subscribe_text'));
        $notification_options['popup_subscribe_accept_button'] = pk_get_sanitized_post('pk_subscribe_accept_button', 'sanitize_text_field', Config::get_notification_defaults('popup_subscribe_accept_button'));
        $notification_options['popup_subscribe_deny_button'] = pk_get_sanitized_post('pk_subscribe_deny_button', 'sanitize_text_field', Config::get_notification_defaults('popup_subscribe_deny_button'));
        $notification_options['popup_subscribe_title_color'] = pk_get_sanitized_post('pk_subscribe_title_color', 'sanitize_text_field', Config::get_notification_defaults('popup_subscribe_title_color'));
        $notification_options['popup_subscribe_text_color'] = pk_get_sanitized_post('pk_subscribe_text_color', 'sanitize_text_field', Config::get_notification_defaults('popup_subscribe_text_color'));
        $notification_options['popup_subscribe_accept_button_text_color'] = pk_get_sanitized_post('pk_subscribe_accept_button_text_color', 'sanitize_text_field', Config::get_notification_defaults('popup_subscribe_accept_button_text_color'));
        $notification_options['popup_subscribe_deny_button_text_color'] = pk_get_sanitized_post('pk_subscribe_deny_button_text_color', 'sanitize_text_field', Config::get_notification_defaults('popup_subscribe_deny_button_text_color'));
        $notification_options['popup_subscribe_deny_button_border_color'] = pk_get_sanitized_post('pk_subscribe_deny_button_border_color', 'sanitize_text_field', Config::get_notification_defaults('popup_subscribe_deny_button_border_color'));
        $notification_options['popup_subscribe_count'] = pk_get_sanitized_post('pk_subscribe_count', 'absint', Config::get_notification_defaults('popup_subscribe_count'));
        $notification_options['popup_subscribe_delay'] = pk_get_sanitized_post('pk_subscribe_delay', 'absint', Config::get_notification_defaults('popup_subscribe_delay'));
        $notification_options['subscribe_bell_content'] = pk_get_sanitized_post('pk_subscribe_bell_content', 'wp_kses_post', Config::get_notification_defaults('subscribe_bell_content'));
        // confirm get notification popup
        $notification_options['popup_confirmation_title'] = pk_get_sanitized_post('pk_confirmation_title', 'sanitize_text_field', Config::get_notification_defaults('popup_confirmation_title'));
        $notification_options['popup_confirmation_text'] = pk_get_sanitized_post('pk_confirmation_text', 'sanitize_textarea_field', Config::get_notification_defaults('popup_confirmation_text'));
        $notification_options['popup_confirmation_accept_button'] = pk_get_sanitized_post('pk_confirmation_accept_button', 'sanitize_text_field', Config::get_notification_defaults('popup_confirmation_accept_button'));
        $notification_options['popup_confirmation_deny_button'] = pk_get_sanitized_post('pk_confirmation_deny_button', 'sanitize_text_field', Config::get_notification_defaults('popup_confirmation_deny_button'));

        $notification_options['welcome_notification_enabled'] = isset($_POST['pk_welcome_notification_enabled']) ? 1 : 0;

        \update_option('pk_notification_settings', $notification_options);

        if (isset($_POST['pk_api_allowed_ips'])) {
            $ips_array = explode("\n", trim($_POST['pk_api_allowed_ips']));
            $sanitized_ips = [];
            foreach ($ips_array as $ip) {
                $trimmed_ip = trim($ip);
                if (filter_var($trimmed_ip, FILTER_VALIDATE_IP)) {
                    $sanitized_ips[] = $trimmed_ip;
                }
            }
            \update_option('pk_api_allowed_ips', $sanitized_ips);
        }
    }

    /**
     * به لیست تگ‌های مجاز وردپرس، اتریبیوت 'style' را اضافه می‌کند.
     * این روش استاندارد و امن برای حل مشکل حذف display:flex است.
     */
    public function add_style_to_allowed_html_tags($tags): array
    {
        $tags['div']['style'] = true;
        $tags['p']['style'] = true;
        $tags['span']['style'] = true;
        $tags['h1']['style'] = true;
        $tags['h2']['style'] = true;
        $tags['h3']['style'] = true;
        $tags['img']['style'] = true;
        return $tags;
    }

    public function ajax_generate_vapid_keys(): void
    {
        if (!\current_user_can('manage_options') || !\check_ajax_referer('pk_generate_vapid_nonce', 'nonce')) {
            \wp_send_json_error(['message' => 'درخواست نامعتبر است.']);
        }
        if (!PWA_KIT_VENDOR_EXISTS) {
            \wp_send_json_error(['message' => 'کتابخانه WebPush یافت نشد.']);
        }
        try {
            $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
            $notification_options = \get_option('pk_notification_settings', []);
            $notification_options['public_key'] = $keys['publicKey'];
            $notification_options['private_key'] = $keys['privateKey'];
            \update_option('pk_notification_settings', $notification_options);
            \wp_send_json_success(['message' => 'کلیدهای جدید VAPID با موفقیت ساخته و ذخیره شدند.', 'publicKey' => $keys['publicKey'], 'privateKey' => $keys['privateKey']]);
        } catch (\Exception $e) {
            \wp_send_json_error(['message' => 'خطا در ساخت کلیدها: ' . $e->getMessage()]);
        }
    }

    public function render_subscribers_page(): void
    {
        require_once \PWA_KIT_PATH . 'admin/class-subscribers-list-table.php';
        $list_table = new Subscribers_List_Table();
        $this->handle_subscriber_actions($list_table);
        $list_table->prepare_items();
        require_once \PWA_KIT_PATH . 'templates/admin/subscribers-template.php';
    }

    private function handle_subscriber_actions($list_table): void
    {
        global $wpdb;
        $action = $list_table->current_action();
        if ($action === 'delete' && isset($_GET['id']) && isset($_GET['_wpnonce'])) {
            if (\wp_verify_nonce($_GET['_wpnonce'], 'pk_delete_subscriber')) {
                $id = \absint($_GET['id']);
                $wpdb->delete($wpdb->prefix . 'pk_subscriptions', ['id' => $id]);
                echo '<div class="notice notice-success is-dismissible"><p>مشترک با موفقیت حذف شد.</p></div>';
            }
        }
    }

    public function render_notification_templates_page(): void
    {
        require_once \PWA_KIT_PATH . 'admin/class-notification-templates-list-table.php';
        $list_table = new Notification_Templates_List_Table();
        $list_table->prepare_items();
        require_once \PWA_KIT_PATH . 'templates/admin/notification-templates-template.php';
    }

    /**
     * Handles all AJAX actions for notification templates (Create, Update, Delete, Get).
     */
    public function ajax_handle_template_action(): void
    {
        if (!\current_user_can('manage_options') || !\check_ajax_referer('pk_template_nonce', 'nonce')) {
            \wp_send_json_error(['message' => 'درخواست نامعتبر است.']);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pk_notifications';
        $action = isset($_POST['sub_action']) ? \sanitize_key($_POST['sub_action']) : '';
        $id = isset($_POST['template_id']) ? \absint($_POST['template_id']) : 0;

        switch ($action) {
            case 'save':
                $data = $_POST['template'] ?? [];
                $internal_name = \sanitize_text_field($data['internal_name'] ?? '');
                $title = \sanitize_text_field($data['title'] ?? '');
                $message = \sanitize_textarea_field($data['message'] ?? '');
                $url = !empty($data['url']) ? \esc_url_raw($data['url']) : '';
                $image = !empty($data['image']) ? \esc_url_raw($data['image']) : '';
                $guest_fallback = \sanitize_text_field($data['guest_fallback'] ?? '');

                if (empty($internal_name) || empty($title) || empty($message)) {
                    \wp_send_json_error(['message' => 'نام داخلی، عنوان و متن پیام الزامی هستند.']);
                }

                $template_data = compact('internal_name', 'title', 'message', 'url', 'image', 'guest_fallback');

                if ($id > 0) {
                    $wpdb->update($table_name, $template_data, ['id' => $id]);
                } else {
                    $template_data['created_at'] = get_tehran_time();
                    $wpdb->insert($table_name, $template_data);
                    $id = $wpdb->insert_id;
                }

                $saved_template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $id));
                if (!$saved_template) {
                    \wp_send_json_error(['message' => 'خطا در ذخیره یا بازیابی قالب.']);
                }

                \wp_send_json_success(['message' => 'قالب با موفقیت ذخیره شد.', 'template' => $saved_template]);
                break;

            case 'delete':
                if ($id > 0) {
                    $wpdb->delete($table_name, ['id' => $id]);
                    \wp_send_json_success(['message' => 'قالب با موفقیت حذف شد.']);
                }
                break;

            case 'get_template':
                if ($id > 0) {
                    $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $id), ARRAY_A);
                    if ($template) {
                        \wp_send_json_success($template);
                    }
                }
                \wp_send_json_error(['message' => 'قالب یافت نشد.']);
                break;

            default:
                \wp_send_json_error(['message' => 'عملیات نامعتبر.']);
        }
    }

    public function render_send_campaign_page(): void
    {
        require_once \PWA_KIT_PATH . 'templates/admin/campaign-template.php';
    }

    public function ajax_search_notification_templates(): void
    {
        if (!\current_user_can('manage_options') || !\check_ajax_referer('pk_campaign_nonce', 'nonce')) {
            \wp_send_json_error();
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'pk_notifications';
        $search = isset($_GET['q']) ? \sanitize_text_field($_GET['q']) : '';
        $like = '%' . $wpdb->esc_like($search) . '%';
        $results = $wpdb->get_results($wpdb->prepare("SELECT id, internal_name as text FROM {$table_name} WHERE status = 'published' AND internal_name LIKE %s ORDER BY id DESC LIMIT 20", $like));
        \wp_send_json(['results' => $results]);
    }

    /**
     * AJAX Handler: Phase 2 - Initiates the queue population job with targeting.
     */
    public function ajax_initiate_queue_job(): void
    {
        if (!\current_user_can('manage_options') || !\check_ajax_referer('pk_campaign_nonce', 'nonce')) {
            \wp_send_json_error(['message' => 'درخواست نامعتبر است.']);
        }

        global $wpdb;
        $subs_table = $wpdb->prefix . 'pk_subscriptions';

        $template_id = isset($_POST['template_id']) ? \absint($_POST['template_id']) : 0;
        if (!$template_id) {
            \wp_send_json_error(['message' => 'لطفاً یک قالب انتخاب کنید.']);
        }

        // **اصلاحیه اصلی: ساخت کوئری فیلتر بر اساس ورودی کاربر**
        list($where_sql, $params) = $this->build_subscriber_where_clause($_POST);

        $total_subscribers = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$subs_table} sub {$where_sql}", $params));

        if ($total_subscribers === 0) {
            \wp_send_json_error(['message' => 'هیچ مشترک فعالی با شرایط انتخابی شما یافت نشد.']);
        }

        $job_details = [
            'template_id' => $template_id,
            'total_subscribers' => $total_subscribers,
            'processed_count' => 0,
            'scheduled_for' => isset($_POST['scheduled_for']) && !empty($_POST['scheduled_for']) ? \sanitize_text_field($_POST['scheduled_for']) : get_tehran_time(),
            'targeting_args' => $_POST, // ذخیره تمام آرگومان‌های هدف‌گذاری
            'status' => 'ready'
        ];
        \set_transient('pk_queue_population_job', $job_details, HOUR_IN_SECONDS);

        \wp_send_json_success([
            'status' => 'ready',
            'total' => $total_subscribers,
            'message' => 'عملیات آماده‌سازی با موفقیت انجام شد. در حال افزودن به صف...'
        ]);
    }

    /**
     * AJAX Handler: Phase 3 - Processes one batch of targeted subscribers.
     */
    public function ajax_process_queue_batch(): void
    {
        if (!\current_user_can('manage_options') || !\check_ajax_referer('pk_campaign_nonce', 'nonce')) {
            \wp_send_json_error(['message' => 'درخواست نامعتبر است.']);
        }

        $job_details = \get_transient('pk_queue_population_job');
        if (!$job_details) {
            \wp_send_json_error(['message' => 'جلسه کاری منقضی شده است. لطفاً دوباره تلاش کنید.']);
        }

        global $wpdb;
        $subs_table = $wpdb->prefix . 'pk_subscriptions';
        $queue_table = $wpdb->prefix . 'pk_queue';
        $notification_settings = \get_option('pk_notification_settings', []);
        $batch_size = !empty($notification_settings['batch_size']) ? \absint($notification_settings['batch_size']) : Config::get_notification_defaults('batch_size');

        // **اصلاحیه اصلی: بازسازی کوئری فیلتر از transient**
        list($where_sql, $params) = $this->build_subscriber_where_clause($job_details['targeting_args']);

        $query = "SELECT id FROM {$subs_table} sub {$where_sql} ORDER BY id ASC LIMIT %d OFFSET %d";
        $query_params = array_merge($params, [$batch_size, $job_details['processed_count']]);

        $subscribers = $wpdb->get_results($wpdb->prepare($query, $query_params));

        if (!empty($subscribers)) {
            $values = [];
            $placeholders = [];
            foreach ($subscribers as $subscriber) {
                $placeholders[] = '(%d, %d, %s, %s, %s)';
                \array_push($values, $job_details['template_id'], $subscriber->id, 'queued', 'normal', $job_details['scheduled_for']);
            }

            $query = "INSERT INTO {$queue_table} (notification_id, subscription_id, status, priority, scheduled_for) VALUES " . \implode(', ', $placeholders);
            $wpdb->query($wpdb->prepare($query, $values));

            $job_details['processed_count'] += count($subscribers);
            \set_transient('pk_queue_population_job', $job_details, HOUR_IN_SECONDS);
        }

        $is_finished = ($job_details['processed_count'] >= $job_details['total_subscribers']);

        if ($is_finished) {
            \delete_transient('pk_queue_population_job');
            if (function_exists('\PwaKit\Background\pk_spawn_runner_if_needed')) {
                \PwaKit\Background\pk_spawn_runner_if_needed();
            }
        }

        \wp_send_json_success([
            'status' => $is_finished ? 'finished' : 'processing',
            'processed' => $job_details['processed_count'],
            'total' => $job_details['total_subscribers'],
        ]);
    }

    /**
     * Helper function to build the WHERE clause for subscriber queries.
     */
    private function build_subscriber_where_clause($post_data): array
    {
        global $wpdb;
        $where_clauses = ["status = 'active'"];
        $params = [];

        $target_type = isset($post_data['target_type']) ? \sanitize_key($post_data['target_type']) : 'all';
        $custom_query_key = isset($post_data['custom_query_key']) ? \sanitize_key($post_data['custom_query_key']) : '';

        if (!empty($custom_query_key)) {
            $custom_queries = get_option('pk_custom_queries', []);
            if (isset($custom_queries[$custom_query_key])) {
                $query_data = $custom_queries[$custom_query_key];
                $subquery = rtrim($query_data['query'], ';'); // کوئری ذخیره شده
                $column = $query_data['column']; // نام ستون شناسایی شده (user_id, id, etc.)

                // بر اساس نوع ستون، بخش WHERE را می‌سازیم
                if ($column === 'subscription_id' || $column === 'subscriber_id' || $column === 'id') {
                    $where_clauses[] = "id IN ({$subquery})";
                } else { // فرض می‌کنیم user_id یا ID است
                    $where_clauses[] = "user_id IN ({$subquery})";
                }
            }
        } else {
            switch ($target_type) {
                case 'registered':
                    $where_clauses[] = "sub.user_id > 0";
                    break;
                case 'guests':
                    $where_clauses[] = "sub.user_id = 0";
                    break;
                case 'specific_users':
                    $user_ids = isset($post_data['target_users']) && is_array($post_data['target_users']) ? array_map('absint', $post_data['target_users']) : [];
                    if (!empty($user_ids)) {
                        $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));
                        $where_clauses[] = "sub.user_id IN ({$placeholders})";
                        $params = $user_ids;
                    }
                    break;
            }
        }

        return ["WHERE " . implode(' AND ', $where_clauses), $params];
    }


    /**
     * AJAX handler to manage custom queries (save, delete, validate).
     */
    public function ajax_manage_custom_query(): void
    {
        if (!\current_user_can('manage_options') || !\check_ajax_referer('pk_campaign_nonce', 'nonce')) {
            \wp_send_json_error(['message' => 'درخواست نامعتبر است.']);
        }

        $custom_queries = get_option('pk_custom_queries', []);
        $query_action = isset($_POST['query_action']) ? sanitize_key($_POST['query_action']) : '';
        $key = isset($_POST['key']) && !empty($_POST['key']) ? sanitize_key($_POST['key']) : uniqid('pk_query_');
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $query = isset($_POST['query']) ? trim(stripslashes($_POST['query'])) : '';

        switch ($query_action) {
            case 'save':
                if (empty($name) || empty($query)) {
                    \wp_send_json_error(['message' => 'نام و متن کوئری نمی‌توانند خالی باشند.']);
                }

                // ۱. اعتبارسنجی امنیتی بهبود یافته
                $clean_query = rtrim($query, ';');
                if (stripos($clean_query, 'SELECT') !== 0) {
                    \wp_send_json_error(['message' => 'فقط کوئری‌های SELECT مجاز هستند.']);
                }
                // جلوگیری از اجرای چند دستور
                if (substr_count($clean_query, ';') > 0) {
                    \wp_send_json_error(['message' => 'اجرای چندین دستور SQL مجاز نیست.']);
                }
                // جلوگیری از کلمات کلیدی خطرناک
                $disallowed_keywords = ['INTO', 'UPDATE', 'DELETE', 'INSERT', 'DROP', 'TRUNCATE', 'ALTER'];
                foreach ($disallowed_keywords as $keyword) {
                    if (preg_match('/\b' . $keyword . '\b/i', $clean_query)) {
                        \wp_send_json_error(['message' => "کلمه کلیدی {$keyword} در کوئری مجاز نیست."]);
                    }
                }

                // ۲. اعتبارسنجی هوشمند ستون خروجی
                global $wpdb;
                $test_result = $wpdb->get_row($clean_query . " LIMIT 1", ARRAY_A);

                if ($wpdb->last_error) {
                    \wp_send_json_error(['message' => 'خطا در اجرای کوئری: ' . esc_html($wpdb->last_error)]);
                }
                if (!$test_result) {
                    \wp_send_json_error(['message' => 'کوئری شما هیچ نتیجه‌ای برنگرداند.']);
                }
                // به دنبال هر یک از نام‌های ستون معتبر می‌گردیم
                $valid_columns = ['user_id', 'ID', 'id', 'subscription_id', 'subscriber_id'];
                $found_column = null;
                foreach ($valid_columns as $column) {
                    if (isset($test_result[$column])) {
                        $found_column = $column;
                        break;
                    }
                }

                if (!$found_column) {
                    \wp_send_json_error(['message' => 'کوئری شما باید حداقل یکی از ستون‌های زیر را برگرداند: ' . implode(', ', $valid_columns)]);
                }

                // ۳. ذخیره کوئری به همراه نام ستون شناسایی شده
                $custom_queries[$key] = [
                    'name' => $name,
                    'query' => $query,
                    'column' => $found_column // ذخیره نام ستون برای استفاده در آینده
                ];
                update_option('pk_custom_queries', $custom_queries);
                \wp_send_json_success(['message' => 'کوئری سفارشی با موفقیت ذخیره شد.', 'queries' => $custom_queries]);
                break;

            case 'delete':
                if (isset($custom_queries[$key])) {
                    unset($custom_queries[$key]);
                    update_option('pk_custom_queries', $custom_queries);
                    \wp_send_json_success(['message' => 'کوئری با موفقیت حذف شد.', 'queries' => $custom_queries]);
                }
                break;
        }
        \wp_send_json_error(['message' => 'عملیات نامعتبر.']);
    }

    /**
     * AJAX handler to search for registered users with active subscriptions.
     */
    public function ajax_search_users_with_subscription(): void
    {
        if (!\current_user_can('manage_options') || !isset($_GET['q'])) {
            \wp_send_json_error();
        }
        global $wpdb;
        $search_term = '%' . $wpdb->esc_like(sanitize_text_field($_GET['q'])) . '%';
        $users = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT u.ID, u.display_name, u.user_email FROM {$wpdb->users} u INNER JOIN {$wpdb->prefix}pk_subscriptions s ON u.ID = s.user_id WHERE s.status = 'active' AND (u.display_name LIKE %s OR u.user_login LIKE %s OR u.user_email LIKE %s) LIMIT 10", $search_term, $search_term, $search_term));
        $results = [];
        foreach ($users as $user) {
            $results[] = ['id' => $user->ID, 'text' => $user->display_name . ' (' . $user->user_email . ')'];
        }
        \wp_send_json_success(['results' => $results]);
    }

    /**
     * Handles the request to generate a new API key.
     * Runs on 'admin_init' to process the request before the page loads.
     */
    public function handle_api_key_generation(): void
    {
        if (isset($_POST['pk_generate_new_api_key'])) {

            // ایجاد یک کلید امن و تصادفی ۴۰ کاراکتری
            $new_api_key = \wp_generate_password(40, false);
            // هش کردن کلید برای ذخیره‌سازی امن
            $hashed_key = \wp_hash_password($new_api_key);
            // ذخیره هش در دیتابیس
            \update_option('pk_api_key_hash', $hashed_key);

            // نمایش کلید اصلی به کاربر فقط برای همین یک بار
            \add_settings_error(
                'pk-api-keys',
                'api-key-generated',
                'کلید API جدید شما با موفقیت ایجاد شد. لطفاً آن را در جای امنی کپی کنید. این کلید دیگر نمایش داده نخواهد شد: <br><pre class="pk-code-display">' . \esc_html($new_api_key) . '</pre>',
                'success'
            );
        }
    }

    // این سه متد جدید را به انتهای کلاس Admin_Manager خود اضافه کنید.

    /**
     * متاباکس ارسال نوتیفیکیشن را به تمام پست تایپ‌های عمومی اضافه می‌کند.
     */
    public function add_notification_meta_box()
    {
        $post_types = get_post_types();
        foreach ($post_types as $post_type) {
            add_meta_box(
                'pk_notification_sender_metabox',       // ID یکتای متاباکس
                'ارسال نوتیفیکیشن',                 // عنوان متاباکس
                [$this, 'render_notification_meta_box'], // تابعی که محتوای HTML را می‌سازد
                $post_type,                             // افزودن به این پست تایپ
                'side',                                 // موقعیت (سایدبار)
                'core'                                  // اولویت نمایش
            );
        }
    }

    /**
     * [نسخه نهایی و بازنویسی شده]
     * محتوای HTML متاباکس را با منطق کامل جاوااسکریپت برای پردازش دسته‌ای رندر می‌کند.
     * @param \WP_Post $post
     */
    public function render_notification_meta_box($post)
    {
        wp_nonce_field('pk_metabox_nonce', 'pk_metabox_nonce_field');

        global $wpdb;
        $notif_table = $wpdb->prefix . 'pk_notifications';
        $internal_name = 'pk_' . $post->post_type . '_' . $post->ID . '_notification';
        $data = $wpdb->get_row($wpdb->prepare("SELECT title, message, guest_fallback FROM {$notif_table} WHERE internal_name = %s", $internal_name));

        $default_title = mb_strimwidth($post->post_title, 0, 60, '...');
        $excerpt = has_excerpt($post->ID) ? get_the_excerpt($post->ID) : wp_strip_all_tags(mb_strimwidth($post->post_content, 0, 200, '...'));

        ?>
        <div id="pk-metabox-wrapper">
            <div id="pk-metabox-response" style="display:none; margin-top:10px;"></div>
            <p><label for="pk-metabox-title"><strong>عنوان:</strong></label><input type="text" id="pk-metabox-title"
                                                                                   class="widefat"
                                                                                   value="<?= $data->title ?? esc_attr($default_title); ?>">
            </p>
            <p><label for="pk-metabox-message"><strong>متن:</strong></label><textarea id="pk-metabox-message"
                                                                                      class="widefat"
                                                                                      rows="6"><?= $data->message ?? esc_textarea($excerpt); ?></textarea>
            </p>
            <div class="pk-patterns-guide">
                <p><strong>الگوهای در دسترس (برای شخصی‌سازی):</strong></p>
                <code>{first_name}</code>, <code>{last_name}</code> <!-- , <code>{display_name}</code>, <code>{user_email}</code> -->
            </div>
            <div id="pk-metabox-guest-fallback-wrapper" style="display: none;">
                <p><label for="pk-metabox-guest-fallback"><strong>متن جایگزین برای مهمان:</strong></label>
                    <input type="text" id="pk-metabox-guest-fallback" class="widefat" value="<?= $data->guest_fallback ?>" placeholder="دوست">
                    <small>این متن جایگزین پترن‌های نام برای کاربران مهمان می‌شود.</small></p>
            </div>
            <hr>

            <div id="pk-metabox-targeting">
                <p><strong>ارسال به:</strong></p>
                <fieldset id="pk-metabox-target-type-fieldset">
                    <label><input type="radio" name="pk_metabox_target_type" value="all" checked> همه
                        مشترکین</label><br>
                    <label><input type="radio" name="pk_metabox_target_type" value="registered"> کاربران عضو</label><br>
                    <label><input type="radio" name="pk_metabox_target_type" value="guests"> کاربران مهمان</label><br>
                    <label><input type="radio" name="pk_metabox_target_type" value="specific_users"> کاربران خاص</label>
                </fieldset>

                <div id="pk-metabox-specific-users-row" style="display: none; margin-top:10px;">
                    <label for="pk-metabox-users-selector" class="screen-reader-text">انتخاب کاربران</label>
                    <select id="pk-metabox-users-selector" name="pk_metabox_target_users[]" multiple="multiple"
                            style="width: 100%;"></select>
                </div>

                <p style="margin-top: 15px;">
                    <label for="pk-metabox-custom-query"><strong>یا ارسال به دسته سفارشی:</strong></label>
                    <select id="pk-metabox-custom-query" name="pk_metabox_custom_query" class="widefat">
                        <option value="">-- انتخاب دسته --</option>
                        <?php
                        $custom_queries = get_option('pk_custom_queries', []);
                        foreach ($custom_queries as $key => $data) {
                            printf('<option value="%s">%s</option>', esc_attr($key), esc_html($data['name']));
                        }
                        ?>
                    </select>
                    <small>با انتخاب دسته، گزینه‌های بالا نادیده گرفته می‌شوند.</small>
                </p>
            </div>
            <hr>

            <p><label for="pk-metabox-schedule"><strong>زمان‌بندی:</strong></label><input type="datetime-local"
                                                                                          id="pk-metabox-schedule"
                                                                                          class="widefat"></p>
            <div id="pk-image-accordion" class="pk-accordion-wrapper">
                <h4 class="pk-accordion-title">تصویر بزرگ نوتیفیکیشن <span class="dashicons dashicons-arrow-down-alt2"></span></h4>
                <div class="pk-accordion-content" style="display: none;">
                    <fieldset>
                        <label><input type="radio" name="pk_metabox_image_option" value="featured" checked> استفاده از تصویر شاخص</label><br>
                        <label><input type="radio" name="pk_metabox_image_option" value="none"> بدون تصویر</label><br>
                        <label><input type="radio" name="pk_metabox_image_option" value="custom"> تصویر سفارشی</label>
                    </fieldset>
                    <div id="pk-metabox-custom-image-wrapper" style="display: none; margin-top: 10px;">
                        <label for="pk-metabox-custom-image">آدرس تصویر سفارشی:</label>
                        <div class="pk-image-uploader">
                            <input type="text" id="pk-metabox-custom-image" name="pk_metabox_custom_image" class="widefat">
                            <button type="button" class="button button-secondary pk-upload-button" data-target="pk-metabox-custom-image">انتخاب</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="pk-metabox-actions">
                <span class="spinner"></span>
                <button type="button" id="pk-metabox-send-btn" class="button button-primary">شروع صف‌بندی</button>
            </div>
            <div id="pk-metabox-progress-wrapper" style="display: none; margin-top: 15px;">
                <strong id="pk-metabox-progress-status"></strong>
                <div class="pk-progress-bar">
                    <div id="pk-metabox-progress-bar-inner" style="width: 0%;"></div>
                </div>
                <p id="pk-metabox-progress-text"></p>
            </div>
        </div>

        <style> /* استایل‌های مشابه صفحه کمپین */
            .pk-metabox-actions {
                display: flex;
                justify-content: flex-end;
                align-items: center;
                gap: 5px;
            }

            .pk-metabox-actions .spinner {
                float: none;
                margin: 0;
            }

            .pk-progress-bar {
                width: 100%;
                background-color: #ddd;
                border-radius: 4px;
                overflow: hidden;
                margin: 5px 0;
            }

            #pk-metabox-wrapper label {
                margin-bottom: 5px;
            }

            #pk-metabox-progress-bar-inner {
                width: 0;
                height: 12px;
                background-color: #0073aa;
                transition: width 0.3s ease;
            }

            .pk-patterns-guide {
                background-color: #f0f0f1;
                border: 1px solid #ddd;
                padding: 10px 15px;
                border-radius: 4px;
                font-size: 13px;
                margin-top: -15px;
                margin-bottom: 15px;
            }

            .pk-patterns-guide p {
                margin-top: 0;
            }

            .pk-patterns-guide code {
                background: rgba(0, 0, 0, 0.07);
                padding: 2px 5px;
                border-radius: 3px;
            }
            .pk-accordion-wrapper { border: 1px solid #ddd; margin: 15px 0; }
            .pk-accordion-title { font-size: 13px; font-weight: 600; margin: 0; padding: 8px 12px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #f9f9f9; }
            .pk-accordion-title .dashicons { transition: transform 0.2s ease; }
            .pk-accordion-wrapper.open .pk-accordion-title .dashicons { transform: rotate(-180deg); }
            .pk-accordion-content { padding: 12px; border-top: 1px solid #ddd; }
            .pk-image-uploader { display: flex; gap: 5px; }
        </style>

        <script>
            jQuery(document).ready(function ($) {
                const $sendButton = $('#pk-metabox-send-btn');
                const $spinner = $sendButton.siblings('.spinner');
                const $responseDiv = $('#pk-metabox-response');
                const $progressWrapper = $('#pk-metabox-progress-wrapper');
                const $progressStatus = $('#pk-metabox-progress-status');
                const $progressBarInner = $('#pk-metabox-progress-bar-inner');
                const $progressText = $('#pk-metabox-progress-text');

                const $titleInput = $('#pk-metabox-title');
                const $messageInput = $('#pk-metabox-message');
                const $fallbackWrapper = $('#pk-metabox-guest-fallback-wrapper');
                const patterns = /\{first_name\}|\{last_name\}|\{display_name\}|\{user_email\}/;

                function checkPatternsForFallback() {
                    const combinedText = ($titleInput.val() || '') + ' ' + ($messageInput.val() || '');
                    if (patterns.test(combinedText)) {
                        $fallbackWrapper.slideDown('fast');
                    } else {
                        $fallbackWrapper.slideUp('fast');
                    }
                }

                checkPatternsForFallback();
                $titleInput.on('input', checkPatternsForFallback);
                $messageInput.on('input', checkPatternsForFallback);

                function updateProgress(processed, total) {
                    const percentage = total > 0 ? (processed / total) * 100 : 0;
                    $progressBarInner.css('width', percentage + '%');
                    $progressText.text(`${processed.toLocaleString()} / ${total.toLocaleString()}`);
                }

                function processMetaboxBatch() {
                    $progressStatus.text('در حال افزودن به صف...');
                    $.post(ajaxurl, {action: 'pk_process_metabox_batch', nonce: $('#pk_metabox_nonce_field').val()})
                        .done(function (res) {
                            if (!res.success) {
                                $progressStatus.text('خطا!');
                                $responseDiv.html(`<div class="notice notice-error is-dismissible"><p>${res.data.message}</p></div>`).show();
                                $sendButton.prop('disabled', false);
                                return;
                            }
                            updateProgress(res.data.processed, res.data.total);
                            if (res.data.status === 'processing') {
                                processMetaboxBatch();
                            } else {
                                $progressStatus.text('عملیات با موفقیت تمام شد!');
                                $progressBarInner.css('background-color', '#00a32a');
                                $responseDiv.html(`<div class="notice notice-success is-dismissible"><p>نوتیفیکیشن با موفقیت در صف قرار گرفت.</p></div>`).show();
                                $sendButton.prop('disabled', false);
                                setTimeout(() => {
                                    $progressWrapper.hide();
                                }, 5000);
                            }
                        }).fail(() => {
                        $progressStatus.text('خطای ارتباط با سرور!');
                        $sendButton.prop('disabled', false);
                    });
                }

                $('#pk-metabox-users-selector').select2({
                    placeholder: 'جستجوی کاربر...',
                    minimumInputLength: 2,
                    ajax: {
                        url: ajaxurl,
                        dataType: 'json',
                        delay: 250,
                        data: params => ({
                            action: 'pk_search_users_with_subscription',
                            nonce: $('#pk_metabox_nonce_field').val(),
                            q: params.term
                        }),
                        processResults: data => ({results: data.data.results})
                    }
                });

                $('#pk-image-accordion .pk-accordion-title').on('click', function() {
                    $(this).parent().toggleClass('open');
                    $(this).siblings('.pk-accordion-content').slideToggle('fast');
                });

                $('input[name="pk_metabox_image_option"]').on('change', function() {
                    $('#pk-metabox-custom-image-wrapper').toggle($(this).val() === 'custom');
                });

                // --- شروع بخش جدید: رفع باگ دکمه انتخاب تصویر ---
                let mediaUploader;
                $('#pk-metabox-wrapper').on('click', '.pk-upload-button', function(e) {
                    e.preventDefault();
                    const button = $(this);
                    const targetInput = $('#' + button.data('target'));

                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }

                    mediaUploader = wp.media({
                        title: 'انتخاب تصویر',
                        button: {
                            text: 'انتخاب این تصویر'
                        },
                        multiple: false
                    });

                    mediaUploader.on('select', function() {
                        const attachment = mediaUploader.state().get('selection').first().toJSON();
                        targetInput.val(attachment.url);
                    });

                    mediaUploader.open();
                });

                // نمایش/پنهان کردن بخش انتخاب کاربر
                $('input[name="pk_metabox_target_type"]').on('change', function () {
                    $('#pk-metabox-specific-users-row').toggle($(this).val() === 'specific_users');
                });

                $sendButton.on('click', function () {
                    $sendButton.prop('disabled', true);
                    $spinner.addClass('is-active');
                    $responseDiv.hide();
                    $progressWrapper.show();
                    $progressStatus.text('در حال آماده‌سازی...');
                    updateProgress(0, 0);

                    const postData = {
                        action: 'pk_initiate_metabox_job',
                        nonce: $('#pk_metabox_nonce_field').val(),
                        post_id: <?php echo $post->ID; ?>,
                        title: $titleInput.val(),
                        message: $messageInput.val(),
                        guest_fallback: $('#pk-metabox-guest-fallback').val(),
                        scheduled_for: $('#pk-metabox-schedule').val(),
                        target_type: $('input[name="pk_metabox_target_type"]:checked').val(),
                        custom_query_key: $('#pk-metabox-custom-query').val(),
                        target_users: $('#pk-metabox-users-selector').val(),
                        image_option: $('input[name="pk_metabox_image_option"]:checked').val(),
                        custom_image_url: $('#pk-metabox-custom-image').val()
                    };

                    $.post(ajaxurl, postData)
                        .done(function (res) {
                            if (res.success) {
                                $responseDiv.html(`<div class="notice notice-info is-dismissible"><p>${res.data.message}</p></div>`).show();
                                updateProgress(0, res.data.total);
                                processMetaboxBatch();
                            } else {
                                $responseDiv.html(`<div class="notice notice-error is-dismissible"><p>${res.data.message}</p></div>`).show();
                                $sendButton.prop('disabled', false);
                            }
                        }).fail(() => {
                        alert('خطا در ارتباط با سرور.');
                        $sendButton.prop('disabled', false);
                    })
                        .always(() => {
                            $spinner.removeClass('is-active');
                        });
                });
            });
        </script>
        <?php
    }

    /**
     * [جدید] AJAX Handler: فاز اول - آماده‌سازی کار برای ارسال از متاباکس.
     */
    public function ajax_initiate_metabox_job()
    {
        // ۱. بررسی‌های امنیتی اولیه (بدون تغییر)
        if (!current_user_can('edit_post', $_POST['post_id'] ?? 0) || !check_ajax_referer('pk_metabox_nonce', 'nonce')) {
            wp_send_json_error(['message' => 'درخواست نامعتبر است.']);
        }

        $title = sanitize_text_field($_POST['title']);
        $message = sanitize_textarea_field($_POST['message']);
        $post_id = absint($_POST['post_id']);
        $guest_fallback = sanitize_text_field($_POST['guest_fallback']);

        if (empty($title) || empty($message)) {
            wp_send_json_error(['message' => 'عنوان و متن نمی‌توانند خالی باشند.']);
        }

        global $wpdb;
        $notif_table = $wpdb->prefix . 'pk_notifications';
        $template_id_from_meta = get_post_meta($post_id, '_pk_notification_template_id', true);

        $image_option = isset($_POST['image_option']) ? sanitize_key($_POST['image_option']) : 'featured';

        switch ($image_option) {
            case 'featured':
                // دریافت URL تصویر شاخص پست (در یک اندازه مناسب)
                $final_image_url = get_the_post_thumbnail_url($post_id, 'medium_large');
                break;
            case 'custom':
                // دریافت URL تصویر سفارشی
                $final_image_url = isset($_POST['custom_image_url']) ? esc_url_raw($_POST['custom_image_url']) : '';
                break;
            case 'none':
            default:
                $final_image_url = ''; // برای حالت "بدون تصویر"
                break;
        }

        $template_data = [
            'title' => $title,
            'message' => $message,
            'url' => get_permalink($post_id),
            'image'   => $final_image_url ?: '',
            'guest_fallback'  => $guest_fallback,
        ];

        // ۲. بررسی می‌کنیم که آیا ID ذخیره شده معتبر است و به یک قالب واقعی اشاره دارد.
        $template_exists = false;
        if (!empty($template_id_from_meta) && is_numeric($template_id_from_meta)) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$notif_table} WHERE id = %d", $template_id_from_meta));
            if ($count > 0) {
                $template_exists = true;
            }
        }

        // ۳. بر اساس وجود یا عدم وجود قالب، تصمیم می‌گیریم.
        if ($template_exists) {
            // اگر قالب وجود داشت، آن را آپدیت می‌کنیم.
            $template_id = absint($template_id_from_meta);
            $wpdb->update($notif_table, $template_data, ['id' => $template_id]);
        } else {
            // در تمام حالات دیگر، یک قالب جدید ایجاد می‌کنیم.
            $post_type_slug = get_post_type($post_id);
            $new_internal_name = 'pk_' . $post_type_slug . '_' . $post_id . '_notification';

            $template_data['internal_name'] = $new_internal_name;
            $template_data['status'] = 'post_based';
            $template_data['created_at'] = get_tehran_time();

            $wpdb->insert($notif_table, $template_data);
            $new_template_id = $wpdb->insert_id;

            if ($new_template_id) {
                // post meta را با ID جدید و صحیح آپدیت یا ایجاد می‌کنیم.
                update_post_meta($post_id, '_pk_notification_template_id', $new_template_id);
                $template_id = $new_template_id;
            } else {
                $template_id = null; // اگر insert شکست خورد
            }
        }

        if (!$template_id) {
            wp_send_json_error(['message' => 'خطا در مدیریت قالب نوتیفیکیشن.']);
        }

        // ۲. شمارش کاربران هدف
        list($where_sql, $params) = $this->build_subscriber_where_clause($_POST);
        $subs_table = $wpdb->prefix . 'pk_subscriptions';
        $total_subscribers = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$subs_table} AS sub {$where_sql}", $params));
        if ($total_subscribers === 0) {
            wp_send_json_error(['message' => 'هیچ مشترکی برای این گروه هدف یافت نشد.']);
        }

        // ۳. اصلاح تاریخ و ذخیره جزئیات کار در transient
        $scheduled_for = sanitize_text_field($_POST['scheduled_for']);
        // تبدیل فرمت datetime-local (YYYY-MM-DDTHH:mm) به فرمت MySQL (YYYY-MM-DD HH:mm:ss)
        if (!empty($scheduled_for)) {
            $scheduled_for = str_replace('T', ' ', $scheduled_for) . ':00';
        } else {
            $scheduled_for = get_tehran_time();
        }

        $job_details = [
            'template_id' => $template_id,
            'total_subscribers' => $total_subscribers,
            'processed_count' => 0,
            'scheduled_for' => $scheduled_for,
            'targeting_args' => $_POST,
            'status' => 'ready'
        ];
        \set_transient('pk_metabox_population_job', $job_details, HOUR_IN_SECONDS);

        \wp_send_json_success([
            'status' => 'ready',
            'total' => $total_subscribers,
            'message' => 'در حال افزودن ' . number_format($total_subscribers) . ' کاربر به صف...'
        ]);
    }

    /**
     * [جدید] AJAX Handler: فاز دوم - پردازش یک دسته از صف برای متاباکس.
     */
    public function ajax_process_metabox_batch()
    {
        if (!current_user_can('manage_options') || !check_ajax_referer('pk_metabox_nonce', 'nonce')) {
            wp_send_json_error(['message' => 'درخواست نامعتبر است.']);
        }

        // این متد کاملاً مشابه ajax_process_queue_batch است، فقط از transient دیگری استفاده می‌کند
        $job_details = \get_transient('pk_metabox_population_job');
        if (!$job_details) {
            wp_send_json_error(['message' => 'جلسه کاری منقضی شده است.']);
        }

        global $wpdb;
        $subs_table = $wpdb->prefix . 'pk_subscriptions';
        $queue_table = $wpdb->prefix . 'pk_queue';
        $notification_settings = \get_option('pk_notification_settings', []);
        $batch_size = !empty($notification_settings['batch_size']) ? \absint($notification_settings['batch_size']) : Config::get_notification_defaults('batch_size');

        // **اصلاحیه اصلی: بازسازی کوئری فیلتر از transient**
        list($where_sql, $params) = $this->build_subscriber_where_clause($job_details['targeting_args']);

        $query = "SELECT id FROM {$subs_table} sub {$where_sql} ORDER BY id ASC LIMIT %d OFFSET %d";
        $query_params = array_merge($params, [$batch_size, $job_details['processed_count']]);

        $subscribers = $wpdb->get_results($wpdb->prepare($query, $query_params));

        if (!empty($subscribers)) {
            $values = [];
            $placeholders = [];
            foreach ($subscribers as $subscriber) {
                $placeholders[] = '(%d, %d, %s, %s, %s)';
                \array_push($values, $job_details['template_id'], $subscriber->id, 'queued', 'normal', $job_details['scheduled_for']);
            }

            $query = "INSERT INTO {$queue_table} (notification_id, subscription_id, status, priority, scheduled_for) VALUES " . \implode(', ', $placeholders);
            $wpdb->query($wpdb->prepare($query, $values));

            $job_details['processed_count'] += count($subscribers);
            \set_transient('pk_metabox_population_job', $job_details, HOUR_IN_SECONDS);
        }

        $is_finished = ($job_details['processed_count'] >= $job_details['total_subscribers']);

        if ($is_finished) {
            \delete_transient('pk_metabox_population_job');
            if (function_exists('\PwaKit\Background\pk_spawn_runner_if_needed')) {
                \PwaKit\Background\pk_spawn_runner_if_needed();
            }
        }

        \wp_send_json_success([
            'status' => $is_finished ? 'finished' : 'processing',
            'processed' => $job_details['processed_count'],
            'total' => $job_details['total_subscribers'],
        ]);
    }
}
