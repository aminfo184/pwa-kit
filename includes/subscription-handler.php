<?php
if (!defined('ABSPATH')) exit;

// AJAX handler for saving subscription
add_action('wp_ajax_pk_save_subscription', 'pk_ajax_save_subscription');
add_action('wp_ajax_nopriv_pk_save_subscription', 'pk_ajax_save_subscription');

function pk_ajax_save_subscription()
{
    \check_ajax_referer('pk_subscribe_nonce', 'nonce');

    $subscription_data = isset($_POST['subscription']) ? json_decode(stripslashes($_POST['subscription']), true) : null;

    if (!$subscription_data || empty($subscription_data['endpoint'])) {
        \wp_send_json_error(['message' => 'اطلاعات اشتراک نامعتبر است.']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'pk_subscriptions';

    $endpoint = \esc_sql($subscription_data['endpoint']);
    $public_key = isset($subscription_data['keys']['p256dh']) ? \esc_sql($subscription_data['keys']['p256dh']) : null;
    $auth_token = isset($subscription_data['keys']['auth']) ? \esc_sql($subscription_data['keys']['auth']) : null;

    $user_id = \get_current_user_id();

    $session_token = $user_id > 0 ? \wp_get_session_token() : null;
    $session_token_hash = $session_token ? \wp_hash($session_token) : null;

    $browser = 'Unknown';
    $os = 'Unknown';
    if (class_exists('\WhichBrowser\Parser')) {
        try {
            $headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
            $user_agent_parser = new \WhichBrowser\Parser($headers);
            $browser = $user_agent_parser->browser->toString();
            $os = $user_agent_parser->os->toString();
        } catch (\Exception $e) {
        }
    }

    $data = [
        'user_id' => $user_id,
        'session_token_hash' => $session_token_hash,
        'endpoint' => $endpoint,
        'public_key' => $public_key,
        'auth_token' => $auth_token,
        'browser' => $browser,
        'os' => $os,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'status' => 'active',
        'updated_at' => get_tehran_time(),
    ];

    $guest_token = null;
    if ($data['user_id'] === 0) {
        $guest_token = \wp_generate_password(64, false, false);
        $data['guest_token'] = $guest_token;
    }

    $existing_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table_name} WHERE endpoint = %s", $data['endpoint']));
    $is_new_subscription = false;

    if ($existing_id) {
        $result = $wpdb->update($table_name, $data, ['id' => $existing_id]);
    } else {
        $data['created_at'] = get_tehran_time();
        $result = $wpdb->insert($table_name, $data);
        $is_new_subscription = true;
    }

    if ($result !== false && $is_new_subscription) {
        $subscription_id = $existing_id ?: $wpdb->insert_id;
        pk_send_welcome_notification($subscription_id);
    }

    $response_data = ['message' => 'اشتراک با موفقیت ذخیره شد.'];
    setcookie('pk_subscription_synced', 'true', time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);

    if ($guest_token) {
        $response_data['guest_token'] = $guest_token;
    }

    if ($result !== false) {
        \wp_send_json_success($response_data);
    } else {
        \wp_send_json_error(['message' => 'خطا در ذخیره اشتراک در دیتابیس.']);
    }

    // **اصلاحیه اصلی:** ما داده‌ها را در یک آرایه مشخص برای ارسال قرار می‌دهیم
    $response_data = ['message' => 'اشتراک با موفقیت ذخیره شد.'];
    if ($guest_token) {
        $response_data['guest_token'] = $guest_token;
    }

    if ($result !== false) {
        // تابع wp_send_json_success به صورت خودکار این آرایه را داخل یک کلید 'data' قرار می‌دهد
        \wp_send_json_success($response_data);
    } else {
        \wp_send_json_error(['message' => 'خطا در ذخیره اشتراک در دیتابیس.']);
    }
}

// Hook into user login to associate guest subscriptions
add_action('wp_login', 'pk_associate_guest_token_on_login', 10, 2);

function pk_associate_guest_token_on_login($user_login, $user)
{

    if (isset($_COOKIE['pk_guest_token'])) {
        $guest_token = \sanitize_text_field($_COOKIE['pk_guest_token']);

        if (empty($guest_token)) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pk_subscriptions';
        $user_id = $user->ID;

        $subscription = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE guest_token = %s", $guest_token));

        // 4. اگر ردیف پیدا شد و هنوز به کاربری متصل نبود، آن را آپدیت می‌کند
        if ($subscription && $subscription->user_id == 0) {

            $wpdb->update(
                $table_name,
                [
                    'user_id'     => $user_id, // آی‌دی کاربر لاگین کرده را ثبت می‌کند
                    'guest_token' => null     // توکن مهمان را پاک می‌کند چون دیگر نیازی به آن نیست
                ],
                [
                    'id' => $subscription->id // شرط آپدیت
                ]
            );
        }

        // 5. کوکی را منقضی می‌کند تا دیگر ارسال نشود
        unset($_COOKIE['pk_guest_token']);
        setcookie('pk_guest_token', '', time() - 3600, '/');
    }
}
