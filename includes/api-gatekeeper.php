<?php
// pwa-kit/includes/api-gatekeeper.php
if (!defined('ABSPATH')) exit;

/**
 * =====================================================================
 * دیوار امنیتی جامع برای REST API
 * =====================================================================
 */
add_action('rest_api_init', function () {
    add_filter('rest_pre_dispatch', 'pk_comprehensive_api_permission_check', 10, 3);
});

/**
 * تابع اصلی برای بررسی تمام دسترسی‌ها قبل از اجرای منطق API.
 */
function pk_comprehensive_api_permission_check($result, $server, $request)
{
    $route = $request->get_route();
    $secure_api_route = '/pwa-kit/v1/send/transactional';

    // فقط برای endpoint امن ما اجرا شو.
    if (strpos($route, $secure_api_route) !== false) {

        // --- ۱. بررسی IP ---
        $request_ip = $_SERVER['REMOTE_ADDR'];
        $server_ip = $_SERVER['SERVER_ADDR'] ?? null;
        $allowed_ips_from_db = get_option('pk_api_allowed_ips', []);

        $base_allowed_ips = ['127.0.0.1', '::1'];
        if ($server_ip) {
            $base_allowed_ips[] = $server_ip;
        }
        $allowed_ips = array_merge($base_allowed_ips, $allowed_ips_from_db);

        if (!in_array($request_ip, $allowed_ips)) {
            // بازگرداندن یک خطای عمومی 500 برای پنهان کردن وجود واقعی endpoint
            return new \WP_Error(
                'internal_server_error',
                '<p>یک خطای مهم در این وب سایت رخ داده است.</p><p><a href="https://wordpress.org/documentation/article/faq-troubleshooting/">دربارهٔ عیب‌یابی در وردپرس بیشتر بدانید.</a></p>',
                ['status' => 500]
            );
        }

        // --- ۲. محدودیت نرخ درخواست (Rate Limiting) ---
        $transient_key = 'pk_api_rl_' . $request_ip;
        $request_count = get_transient($transient_key);

        if ($request_count === false) {
            set_transient($transient_key, 1, MINUTE_IN_SECONDS);
        } else {
            $request_count++;
            set_transient($transient_key, $request_count, MINUTE_IN_SECONDS);
            if ($request_count > 100) {
                return new \WP_Error('rest_too_many_requests', 'تعداد درخواست‌ها بیش از حد مجاز است.', ['status' => 429]);
            }
        }

        // --- ۳. بررسی کلید API ---
        $auth_header = $request->get_header('Authorization');
        if (empty($auth_header) || sscanf($auth_header, 'Bearer %s', $api_key) !== 1) {
            return new \WP_Error('rest_unauthorized', 'هدر احرازهویت نامعتبر است یا وجود ندارد.', ['status' => 401]);
        }

        $api_key_hash = get_option('pk_api_key_hash');
        if (empty($api_key) || empty($api_key_hash) || !wp_check_password($api_key, $api_key_hash)) {
            return new \WP_Error('rest_forbidden_key', 'کلید API نامعتبر است.', ['status' => 403]);
        }

        // --- ۴. بررسی پارامترهای ضروری ---
        $params = $request->get_json_params();
        if (empty($params['recipients']) || empty($params['content'])) {
            return new \WP_Error('rest_bad_request', 'پارامترهای "recipients" و "content" الزامی هستند.', ['status' => 400]);
        }
        if (!isset($params['content']['template_name']) && !isset($params['content']['payload'])) {
            return new \WP_Error('rest_bad_request', 'بخش "content" باید شامل "template_name" یا "payload" باشد.', ['status' => 400]);
        }
    }

    // اگر همه چیز درست بود، null برمی‌گردانیم تا وردپرس به کار خود ادامه دهد.
    return $result;
}