<?php

namespace PwaKit;

if (!defined('ABSPATH')) exit;

use PwaKit\Core\PK_Config as Config;

class PWA_Handler
{
    private static $instance = null;
    private $defaults;

    public static function get_instance() // checked!
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() // checked!
    {
        // \add_action('init', [$this, 'add_rewrite_rules']);
        \add_action('parse_request', [$this, 'serve_virtual_files_direct']);
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        \add_action('wp_head', [$this, 'add_manifest_link']);
        \add_action('wp_head', [$this, 'add_ios_meta_tags']);
        \add_action('wp_footer', [$this, 'add_pwa_install_prompt_html']);
        $this->defaults = Config::get_pwa_defaults();
    }

    // public function add_rewrite_rules() // checked!
    // {
    //     add_rewrite_rule('^service-worker\.js$', 'index.php?pk_file=service-worker', 'top');
    //     add_rewrite_rule('^manifest\.json$', 'index.php?pk_file=manifest', 'top');
    // }

    public function enqueue_scripts() // checked!
    {
        $settings = \get_option('pk_pwa_settings', []);
        \wp_enqueue_script('pk-pwa-install-js', \PWA_KIT_URL . 'assets/js/pk-pwa-install.js', [], \PWA_KIT_VERSION, true);
        \wp_enqueue_style('pk-pwa-install-css', \PWA_KIT_URL . 'assets/css/pk-pwa-install.css', [], \PWA_KIT_VERSION);

        \wp_localize_script('pk-pwa-install-js', 'pwa_install_data', [
            'sw_url' => \home_url('/service-worker.js'),
            'scope' => \trailingslashit(\wp_parse_url(\home_url('/'), PHP_URL_PATH)),
            'short_name' => \esc_html($settings['short_name'] ?? Config::get_pwa_defaults('short_name')),
            'icon_192' => \esc_url($settings['icon_192'] ?? Config::get_pwa_defaults('icon_192')),
            'theme_color' => \esc_attr($settings['theme_color'] ?? Config::get_pwa_defaults('theme_color')),
            'background_color' => \esc_attr($settings['background_color'] ?? Config::get_pwa_defaults('background_color')),
            'popup_style' => !empty($settings['popup_style']) ? $settings['popup_style'] : Config::get_pwa_defaults('popup_style'),
            'popup_count' => !empty($settings['popup_install_count']) ? $settings['popup_install_count'] : Config::get_pwa_defaults('popup_install_count'),
            'popup_delay' => !empty($settings['popup_install_delay']) ? $settings['popup_style'] : Config::get_pwa_defaults('popup_install_delay'),
            'modal_title' => \esc_attr($settings['popup_install_modal_title'] ?? Config::get_pwa_defaults('popup_install_modal_title')),
            'modal_text' => \esc_attr($settings['popup_install_modal_text'] ?? Config::get_pwa_defaults('popup_install_modal_text')),
            'modal_button' => \esc_attr($settings['popup_install_modal_button'] ?? Config::get_pwa_defaults('popup_install_modal_button')),
            'banner_title' => \esc_attr($settings['popup_install_banner_title'] ?? Config::get_pwa_defaults('popup_install_banner_title')),
            'banner_text' => \esc_attr($settings['popup_install_banner_text'] ?? Config::get_pwa_defaults('popup_install_banner_text')),
            'banner_button' => \esc_attr($settings['popup_install_banner_button'] ?? Config::get_pwa_defaults('popup_install_banner_button')),
            'title_color' => \esc_attr($settings['popup_install_title_color'] ?? Config::get_pwa_defaults('popup_install_title_color')),
            'text_color' => \esc_attr($settings['popup_install_text_color'] ?? Config::get_pwa_defaults('popup_install_text_color')),
            'button_text_color' => \esc_attr($settings['popup_install_button_text_color'] ?? Config::get_pwa_defaults('popup_install_button_text_color')),
        ]);

        \wp_enqueue_script('pk-offline-handler', \PWA_KIT_URL . 'assets/js/pk-offline-handler.js', [], \PWA_KIT_VERSION, true);

        $defaults = $this->defaults;

        // [اصلاح کلیدی] ارسال تمام تنظیمات داینامیک به اسکریپت آفلاین
        $offline_content = !empty($settings['offline_content']) ? $settings['offline_content'] : $defaults['offline_content'];
        $offline_content = str_replace(
            ['{{app_name}}', '{{app_icon_url}}'],
            [\esc_html($settings['name'] ?? $defaults['name']), \esc_url($settings['icon_192'] ?? '')],
            $offline_content
        );

        \wp_localize_script('pk-offline-handler', 'pk_offline_data', [
            'content_html' => $offline_content,
            'background_color' => \esc_attr($settings['background_color'] ?? $defaults['background_color']),
            'offline_title_color' => \esc_attr($settings['offline_title_color'] ?? $defaults['offline_title_color']),
            'offline_text_color' => \esc_attr($settings['offline_text_color'] ?? $defaults['offline_text_color']),
            'offline_button_bg_color' => \esc_attr($settings['offline_button_bg_color'] ?? $defaults['offline_button_bg_color']),
            'offline_button_text_color' => \esc_attr($settings['offline_button_text_color'] ?? $defaults['offline_button_text_color']),
            'offline_button_border_color' => \esc_attr($settings['offline_button_border_color'] ?? $defaults['offline_button_border_color']),
            'offline_loader_color' => \esc_attr($settings['offline_loader_color'] ?? $defaults['offline_loader_color']),
            'offline_status_text_color' => \esc_attr($settings['offline_status_text_color'] ?? $defaults['offline_status_text_color']),
        ]);
    }

    public function add_ios_meta_tags() // checked!
    {
        $settings = \get_option('pk_pwa_settings', []);
        echo "\n\n";
        echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
        echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
        echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
        $name = !empty($settings['name']) ? $settings['name'] : \get_bloginfo('name');
        echo '<meta name="apple-mobile-web-app-title" content="' . \esc_attr($name) . '">' . "\n";
        $icon_192 = !empty($settings['icon_192']) ? $settings['icon_192'] : \get_site_icon_url(192);
        if ($icon_192) {
            echo '<link rel="apple-touch-icon" href="' . \esc_url($icon_192) . '">' . "\n";
        }
        echo "\n";
    }

    public function add_pwa_install_prompt_html() // checked!
    {
        if (is_admin()) return;
        require_once \PWA_KIT_PATH . 'templates/frontend/pwa-install-prompt.php';
    }

    public function serve_virtual_files_direct() // checked!
    {
        if (isset($_GET['pwa_serviceworker']) && $_GET['pwa_serviceworker'] == 1) {
            $this->serve_sw();
            exit;
        }
        if (isset($_GET['pwa_manifest']) && $_GET['pwa_manifest'] == 1) {
            $this->serve_manifest();
            exit;
        }

        $home_path = \trailingslashit(\wp_parse_url(\home_url(), PHP_URL_PATH));
        $request_path = \trailingslashit($_SERVER['REQUEST_URI']);
        $relative_path = \preg_replace('#^' . \preg_quote($home_path, '#') . '#', '', $request_path);
        if (\trim($relative_path, '/') === 'manifest.json') {
            $this->serve_manifest();
            exit;
        }
        if (\trim($relative_path, '/') === 'service-worker.js') {
            $this->serve_sw();
            exit;
        }
    }

    public function add_manifest_link() // checked!
    {
        echo '<link rel="manifest" href="' . \home_url('/manifest.json') . '">';
    }

    private function serve_manifest() // checked!
    {
        $settings = \get_option('pk_pwa_settings', []);
        $base_path = \trailingslashit(\wp_parse_url(\home_url(), PHP_URL_PATH));
        $manifest = [
            'id' => !empty($settings['id']) ? $settings['id'] : Config::get_pwa_defaults('id'),
            'name' => !empty($settings['name']) ? $settings['name'] : Config::get_pwa_defaults('name'),
            'short_name' => !empty($settings['short_name']) ? $settings['short_name'] : Config::get_pwa_defaults('short_name'),
            'description' => !empty($settings['description']) ? $settings['description'] : Config::get_pwa_defaults('description'),
            'dir' => !empty($settings['dir']) ? $settings['dir'] : Config::get_pwa_defaults('dir'),
            'lang' => !empty($settings['lang']) ? $settings['lang'] : Config::get_pwa_defaults('lang'),
            'start_url' => !empty($settings['start_url']) ? $settings['start_url'] : Config::get_pwa_defaults('start_url'),
            'scope' => $base_path,
            'display' => !empty($settings['display']) ? $settings['display'] : Config::get_pwa_defaults('display'),
            'display_override' => !empty($settings['display_override']) ? $settings['display_override'] : Config::get_pwa_defaults('display_override'),
            'orientation' => !empty($settings['orientation']) ? $settings['orientation'] : Config::get_pwa_defaults('orientation'),
            'theme_color' => !empty($settings['theme_color']) ? $settings['theme_color'] : Config::get_pwa_defaults('theme_color'),
            'background_color' => !empty($settings['background_color']) ? $settings['background_color'] : Config::get_pwa_defaults('background_color'),
            'categories' => !empty($settings['categories']) ? $settings['categories'] : Config::get_pwa_defaults('categories'),
            'icons' => [],
            'shortcuts' => Config::get_pwa_defaults('shortcuts'),
            'screenshots' => Config::get_pwa_defaults('screenshots'),
        ];
        $icon_192 = !empty($settings['icon_192']) ? $settings['icon_192'] : Config::get_pwa_defaults('icon_192');
        $icon_512 = !empty($settings['icon_512']) ? $settings['icon_512'] : Config::get_pwa_defaults('icon_512');
        $purpose = !empty($settings['icon_purpose']) ? $settings['icon_purpose'] : Config::get_pwa_defaults('icon_purpose');
        if ($icon_192) {
            $manifest['icons'][] = ['src' => $icon_192, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => $purpose];
        }
        if ($icon_512) {
            $manifest['icons'][] = ['src' => $icon_512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => $purpose];
        }
        if (!empty($settings['shortcuts']) && is_array($settings['shortcuts'])) {
            foreach ($settings['shortcuts'] as $shortcut) {
                $icon = ['src' => \esc_url_raw($shortcut['icons'][0]['src']), 'sizes' => \sanitize_text_field($shortcut['icons'][0]['sizes']), 'purpose' => 'any'];
                $manifest['shortcuts'][] = ['name' => \sanitize_text_field($shortcut['name']), 'url' => \wp_make_link_relative($shortcut['url']), 'icons' => [$icon]];
            }
        }
        if (!empty($settings['screenshots']) && is_array($settings['screenshots'])) {
            $manifest['screenshots'] = $settings['screenshots'];
        }
        \wp_send_json($manifest, 200);
    }

    private function serve_sw()
    {
        $settings = \get_option('pk_pwa_settings', []);
        $defaults = $this->defaults;

        // ۱. آماده‌سازی محتوای داینامیک
        $offline_content = !empty($settings['offline_content']) ? $settings['offline_content'] : $defaults['offline_content'];
        $offline_content = str_replace(
            ['{{app_name}}', '{{app_icon_url}}'],
            [\esc_html($settings['name'] ?? $defaults['name']), \esc_url($settings['icon_192'] ?? '')],
            $offline_content
        );

        // ۲. جمع‌آوری تمام تنظیمات در یک آرایه PHP
        $config_data = [
            'content_html' => $offline_content,
            'background_color' => \esc_attr($settings['background_color'] ?? $defaults['background_color']),
            'title_color' => \esc_attr($settings['offline_title_color'] ?? $defaults['offline_title_color']),
            'text_color' => \esc_attr($settings['offline_text_color'] ?? $defaults['offline_text_color']),
            'button_bg_color' => \esc_attr($settings['offline_button_bg_color'] ?? $defaults['offline_button_bg_color']),
            'button_text_color' => \esc_attr($settings['offline_button_text_color'] ?? $defaults['offline_button_text_color']),
            'button_border_color' => \esc_attr($settings['offline_button_border_color'] ?? $defaults['offline_button_border_color']),
            'loader_color' => \esc_attr($settings['offline_loader_color'] ?? $defaults['offline_loader_color']),
            'status_text_color' => \esc_attr($settings['offline_status_text_color'] ?? $defaults['offline_status_text_color']),
            'main_font_url' => \esc_attr($settings['offline_main_font_url'] ?? $defaults['offline_main_font_url']),
            'main_font_family' => $settings['offline_main_font_family'] ?? $defaults['offline_main_font_family'],
        ];

        // ۳. خواندن قالب سرویس ورکر
        $sw_template_path = \PWA_KIT_PATH . 'service-worker.js';
        if (!\file_exists($sw_template_path)) {
            status_header(500);
            exit("Service worker template file not found.");
        }
        $sw_content = file_get_contents($sw_template_path);

        // ۴. تزریق امن تنظیمات به صورت JSON به سرویس ورکر
        $sw_content = str_replace(
            [
                "'{{CACHE_NAME}}'",
                "/*{{CONFIG_JSON}}*/" // پلیس‌هولدر جدید برای تنظیمات
            ],
            [
                "'pwa-kit-cache-v" . PWA_KIT_VERSION . "'",
                "const CONFIG = " . \wp_json_encode($config_data) . ";" // تبدیل آرایه PHP به آبجکت جاوا اسکریپت
            ],
            $sw_content
        );

        header('Content-Type: application/javascript; charset=utf-8');
        header('Service-Worker-Allowed: /');
        echo $sw_content;
        exit;
    }
}
