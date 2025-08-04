<?php

namespace PwaKit;

if (!defined('ABSPATH')) exit;

use PwaKit\Core\PK_Config as Config;

class Notification_Handler
{
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_subscription_scripts']);
        add_action('wp_footer', [$this, 'add_subscription_modal_html']);
    }

    public function enqueue_subscription_scripts()
    {
        if (is_admin()) return;

        \wp_enqueue_script('ua-parser-js', PWA_KIT_URL . 'assets/js/package/ua-parser.min.js', [], '2.0.4', true);
        \wp_enqueue_script('pk-subscribe-js', \PWA_KIT_URL . 'assets/js/pk-subscribe.js', ['ua-parser-js'], \PWA_KIT_VERSION, true);
        \wp_enqueue_style('pk-subscribe-css', \PWA_KIT_URL . 'assets/css/pk-subscribe.css', [], \PWA_KIT_VERSION);

        $notification_settings = \get_option('pk_notification_settings', []);
        $public_key = !empty($notification_settings['public_key']) ? $notification_settings['public_key'] : '';
        $settings = \get_option('pk_pwa_settings', []);

        \wp_localize_script('pk-subscribe-js', 'pk_subscribe_data', [
            'ajax_url' => \admin_url('admin-ajax.php'),
            'nonce' => \wp_create_nonce('pk_subscribe_nonce'),
            'public_key' => $public_key,
            'is_user_logged_in' => is_user_logged_in(),
            'theme_color' => \esc_attr($settings['theme_color'] ?? Config::get_pwa_defaults('theme_color')),
            'background_color' => \esc_attr($settings['background_color'] ?? Config::get_pwa_defaults('background_color')),
            'title' => \esc_html($notification_settings['popup_subscribe_title'] ?? Config::get_pwa_defaults('popup_subscribe_title')),
            'text' => \esc_html($notification_settings['popup_subscribe_text'] ?? Config::get_pwa_defaults('popup_subscribe_text')),
            'accept_button' => \esc_html($notification_settings['popup_subscribe_accept_button'] ?? Config::get_pwa_defaults('popup_subscribe_accept_button')),
            'deny_button' => \esc_html($notification_settings['popup_subscribe_deny_button'] ?? Config::get_pwa_defaults('popup_subscribe_deny_button')),

            'popup_count' => \esc_html($notification_settings['popup_subscribe_count'] ?? Config::get_pwa_defaults('popup_subscribe_count')),
            'popup_delay' => \esc_html($notification_settings['popup_subscribe_delay'] ?? Config::get_pwa_defaults('popup_subscribe_delay')),

            'title_color' => \esc_html($notification_settings['popup_subscribe_title_color'] ?? Config::get_pwa_defaults('popup_subscribe_title_color')),
            'text_color' => \esc_html($notification_settings['popup_subscribe_text_color'] ?? Config::get_pwa_defaults('popup_subscribe_text_color')),
            'accept_button_text_color' => \esc_html($notification_settings['popup_subscribe_accept_button_text_color'] ?? Config::get_pwa_defaults('popup_subscribe_accept_button_text_color')),
            'deny_button_text_color' => \esc_html($notification_settings['popup_subscribe_deny_button_text_color'] ?? Config::get_pwa_defaults('popup_subscribe_deny_button_text_color')),
            'deny_button_border_color' => \esc_html($notification_settings['popup_subscribe_deny_button_border_color'] ?? Config::get_pwa_defaults('popup_subscribe_deny_button_border_color')),

            'subscribe_bell_content' => \esc_html($notification_settings['subscribe_bell_content'] ?? Config::get_pwa_defaults('subscribe_bell_content')),

            'confirmation_title' => \esc_html($notification_settings['popup_confirmation_title'] ?? Config::get_pwa_defaults('popup_confirmation_title')),
            'confirmation_text' => \esc_html($notification_settings['popup_confirmation_text'] ?? Config::get_pwa_defaults('popup_confirmation_text')),
            'confirmation_deny_button' => \esc_html($notification_settings['popup_confirmation_deny_button'] ?? Config::get_pwa_defaults('popup_confirmation_deny_button')),
            'confirmation_accept_button' => \esc_html($notification_settings['popup_confirmation_accept_button'] ?? Config::get_pwa_defaults('popup_confirmation_accept_button')),

            'welcome_notification_enabled' => !empty($notification_settings['welcome_notification_enabled']),
        ]);
    }

    public function add_subscription_modal_html()
    {
        if (is_admin()) return;
        require_once \PWA_KIT_PATH . 'templates/frontend/subscription-prompt.php';
    }
}
