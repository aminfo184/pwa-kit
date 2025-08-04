<?php

use PwaKit\Core\PK_Config as Config;

if (!defined('ABSPATH')) exit;

/**
 * تابع نهایی برای ارسال نوتیفیکیشن خوشامدگویی.
 * ابتدا تنظیمات را چک کرده و سپس در صورت نیاز قالب را ایجاد و ارسال می‌کند.
 */
function pk_send_welcome_notification($subscription_id)
{
    $settings = get_option('pk_notification_settings', []);
    // اگر ارسال خوشامدگویی در تنظیمات غیرفعال بود، خارج شو.
    if (!$settings['welcome_notification_enabled']) {
        return false;
    }

    // قالب را فقط در صورت نیاز ایجاد کن.
    if (is_user_logged_in()) {
        $template_id = \PwaKit\Core\TemplateManager::get_or_create('pk_welcome_notification', [
            'title' => '{first_name} عزیز، تبریک میگم، نوتیفیکیشن‌ها برای شما فعال شد.',
            'message' => 'از این به بعد، از همه اتفاقات وب‌سایت خیلی سریع با خبر می‌شی.'
        ]);

    } else {
        $template_id = \PwaKit\Core\TemplateManager::get_or_create('pk_welcome_notification_guest', [
            'title' => 'دوست عزیز، تبریک میگم، نوتیفیکیشن‌ها برای شما فعال شد.',
            'message' => 'هنوز وارد حساب کاربریت نشدی. اگه حساب کاربری داری، همین الان وارد حساب کاربریت شو، و اگر هم حساب کاربری نداری، به راحتی می‌تونی حساب کاربری بسازی.'
        ]);
    }

    if ($template_id) {
        // از تابع اصلی ارسال آنی (مبتنی بر قالب) استفاده کن.
        return pk_send_transactional_notification(['subscription_id' => $subscription_id], $template_id);
    }
    return false;
}


/**
 * تابع اصلی و قدرتمند برای ارسال نوتیفیکیشن آنی مبتنی بر قالب.
 *
 * @param array $recipients آرایه‌ای برای شناسایی گیرندگان. مثال: ['user_id' => 1] یا ['subscription_ids' => [1,2,3]]
 * @param int $notification_id ID قالب نوتیفیکیشن.
 * @return bool
 */
function pk_send_transactional_notification(array $recipients, $notification_id)
{
    if (empty($recipients) || empty($notification_id)) return false;

    // منطق شناسایی گیرندگان (این بخش را می‌توان بعداً پیچیده‌تر کرد)
    $subscription_ids = pk_resolve_recipients($recipients);

    if (empty($subscription_ids)) return false;

    $items_to_send = [];
    foreach ($subscription_ids as $sub_id) {
        $items_to_send[] = (object)[
            'id' => 0, 'subscription_id' => $sub_id, 'notification_id' => $notification_id, 'priority' => 'high'
        ];
    }

    if (!empty($items_to_send)) {
        \PwaKit\Senders\TransactionalSender::send_now($items_to_send);
        return true;
    }
    return false;
}

/**
 * تابع اصلی و قدرتمند برای ارسال نوتیفیکیشن آنی با محتوای سفارشی.
 *
 * @param array $recipients آرایه‌ای برای شناسایی گیرندگان.
 * @param array $payload آرایه‌ای حاوی محتوای سفارشی ['title' => '...', 'message' => '...', ...].
 * @return bool
 */
function pk_send_custom_transactional_notification(array $recipients, array $payload)
{
    if (empty($recipients) || empty($payload['title'])) return false;

    $subscription_ids = pk_resolve_recipients($recipients);

    if (empty($subscription_ids)) return false;

    $items_to_send = [];
    foreach ($subscription_ids as $sub_id) {
        $items_to_send[] = (object)[
            'id' => 0, 'subscription_id' => $sub_id, 'notification_id' => 0, 'priority' => 'high',
            'notification_data' => $payload
        ];
    }

    if (!empty($items_to_send)) {
        \PwaKit\Senders\TransactionalSender::send_now($items_to_send);
        return true;
    }
    return false;
}

/**
 * تابع کمکی برای پیدا کردن ID های اشتراک بر اساس ورودی‌های مختلف.
 * @param array $recipients
 * @return array
 */
function pk_resolve_recipients(array $recipients)
{
    global $wpdb;
    $subs_table = $wpdb->prefix . 'pk_subscriptions';
    $final_sub_ids = [];

    // ورودی‌ها را به آرایه تبدیل می‌کنیم تا کد ساده‌تر شود
    $user_ids = (array)($recipients['user_id'] ?? $recipients['user_ids'] ?? []);
    $subscription_ids = (array)($recipients['subscription_id'] ?? $recipients['subscription_ids'] ?? []);

    if (!empty($subscription_ids)) {
        $final_sub_ids = array_merge($final_sub_ids, array_map('absint', $subscription_ids));
    }

    if (!empty($user_ids)) {
        $user_ids_placeholder = implode(',', array_map('absint', $user_ids));
        $found_ids = $wpdb->get_col("SELECT id FROM {$subs_table} WHERE user_id IN ({$user_ids_placeholder}) AND status = 'active'");
        if (!empty($found_ids)) {
            $final_sub_ids = array_merge($final_sub_ids, $found_ids);
        }
    }

    return array_unique($final_sub_ids);
}

/**
 * تابع عمومی برای افزودن یک کمپین بزرگ به صف ارسال انبوه.
 * نسخه نهایی و بهینه شده.
 *
 * @param int $notification_id ID قالب نوتیفیکیشن.
 * @param array $subscription_ids آرایه‌ای از IDهای مشترکین هدف.
 * @return bool True on success, false on failure.
 */
function pk_queue_campaign($notification_id, array $subscription_ids)
{
    if (empty($notification_id) || empty($subscription_ids)) {
        return false;
    }

    global $wpdb;
    $queue_table = $wpdb->prefix . 'pk_queue';
    $current_time = get_tehran_time();

    // ساخت یک کوئری بزرگ و امن برای درج دسته‌ای
    $query = "INSERT INTO {$queue_table} (notification_id, subscription_id, scheduled_for) VALUES ";
    $placeholders = [];
    $values = [];

    foreach ($subscription_ids as $sub_id) {
        $placeholders[] = '(%d, %d, %s)';
        $values[] = $notification_id;
        $values[] = absint($sub_id); // پاکسازی برای امنیت بیشتر
        $values[] = $current_time;
    }

    $query .= implode(', ', $placeholders);

    // اجرای کوئری با prepare به روش صحیح و امن
    $wpdb->query($wpdb->prepare($query, $values));

    // **حذف شد:** فراخوانی مستقیم پردازشگر حذف گردید.
    // سیستم اکنون به Cron Job برای پردازش صف در پس‌زمینه متکی است.
    // \PwaKit\Senders\BulkSender::process_queue();

    return true;
}


/**
 * Generates a specified number of fake subscribers for testing purposes.
 *
 * @param int $count The number of fake subscribers to generate.
 * @return string A message indicating the result.
 */
function pk_generate_fake_subscribers($count = 2000)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'pk_subscriptions';

    if (!class_exists('\Minishlink\WebPush\VAPID')) {
        return "خطا: کتابخانه WebPush یافت نشد.";
    }

    $batch_size = 1000;
    $total_inserted = 0;
    $browsers = ['Chrome', 'Firefox', 'Edge'];
    $os_list = ['Windows', 'macOS', 'Android'];

    for ($i = 0; $i < $count; $i += $batch_size) {
        $values = [];
        $placeholders = [];
        $limit = min($batch_size, $count - $i);

        for ($j = 0; $j < $limit; $j++) {
            $placeholders[] = '(%d, %s, %s, %s, %s, %s, %s, %s, %s, %s)';

            // **اصلاحیه اصلی:** تولید کلیدهای معتبر
            $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
            $fake_auth_token = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');

            array_push(
                $values,
                0, // user_id
                'https://fcm.googleapis.com/fcm/send/fake_endpoint_' . uniqid(),
                $keys['publicKey'], // کلید عمومی
                $fake_auth_token, // <<-- کلید خصوصی با توکن معتبر جایگزین شد
                $browsers[array_rand($browsers)] . ' 126.0',
                $os_list[array_rand($os_list)],
                '127.0.0.1',
                'active',
                get_tehran_time(),
                get_tehran_time()
            );
        }

        if (!empty($values)) {
            $query = "INSERT INTO {$table_name} (user_id, endpoint, public_key, auth_token, browser, os, ip_address, status, created_at, updated_at) VALUES " . implode(', ', $placeholders);
            $inserted = $wpdb->query($wpdb->prepare($query, $values));
            if ($inserted) {
                $total_inserted += $inserted;
            }
        }
    }

    return number_format($total_inserted) . ' مشترک فیک با موفقیت به دیتابیس اضافه شد.';
}

/**
 * یک شنونده برای URL ماشه دستی ایجاد می‌کند تا پردازشگر کمپین را به صورت مستقیم اجرا کند.
 * هشدار: این روش باعث قفل شدن مرورگر تا پایان پردازش می‌شود.
 */
function pk_handle_simple_manual_trigger()
{
    // ۱. بررسی اینکه آیا پارامتر ماشه دستی در URL وجود دارد.
    if (isset($_GET['pk_manual_cron_trigger']) && $_GET['pk_manual_cron_trigger'] === 'run_now') {

        // ابتدا توکن امنیتی را بررسی می‌کنیم
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'pk_manual_trigger_nonce')) {
            wp_die('توکن امنیتی نامعتبر است. لطفاً از لینک معتبر استفاده کنید.');
        }

        // سپس دسترسی کاربر را بررسی می‌کنیم
        if (!current_user_can('manage_options')) {
            wp_die('شما دسترسی لازم برای انجام این کار را ندارید.');
        }

        // ۳. بررسی اینکه آیا یک فرآیند دیگر از قبل قفل است.
        if (\get_transient('pk_sender_lock')) {
            wp_die('یک فرآیند ارسال کمپین در حال حاضر فعال است. لطفاً چند دقیقه دیگر دوباره تلاش کنید.');
        }

        // ۴. نمایش پیام اولیه به کاربر
        echo "Manual trigger activated. Attempting to run the queue processor...<br>";
        echo "<b>Please keep this tab open. The page will remain in a loading state until the process is complete.</b><br><br>";

        // اطمینان از نمایش پیام‌ها قبل از شروع فرآیند سنگین
        if (ob_get_level() > 0) ob_end_flush();
        flush();

        // ۵. فراخوانی مستقیم متد پردازشگر کمپین از کلاس نهایی شما
        if (class_exists('\PwaKit\Senders\BulkSender')) {
            \PwaKit\Senders\BulkSender::process_queue();
            echo "<br><strong>Process finished!</strong> You can now close this tab. Check logs for details.";
        } else {
            echo "Error: BulkSender class not found! Make sure the plugin is initialized correctly.";
        }

        // ۶. پایان کامل اجرای اسکریپت
        exit;
    }
}

add_action('init', 'pk_handle_simple_manual_trigger');

/**
 * یک URL امن و حاوی توکن (nonce) برای ماشه دستی ساده تولید می‌کند.
 * @return string The secure URL.
 */
function pk_get_simple_manual_trigger_url()
{
    return add_query_arg([
        'pk_manual_cron_trigger' => 'run_now',
        '_wpnonce' => wp_create_nonce('pk_manual_trigger_nonce')
    ], admin_url('index.php'));
}

function pk_handle_manual_reset()
{
    // فقط در صورتی اجرا شو که پارامتر و توکن امنیتی معتبر باشند و کاربر ادمین باشد.
    if (isset($_GET['pk_manual_reset']) && $_GET['pk_manual_reset'] === 'true' && isset($_GET['_wpnonce'])) {
        if (!wp_verify_nonce($_GET['_wpnonce'], 'pk_manual_reset_nonce')) {
            wp_die('توکن امنیتی نامعتبر است.');
        }
        if (!current_user_can('manage_options')) {
            wp_die('شما دسترسی لازم برای انجام این کار را ندارید.');
        }

        // ۱. حذف قفل پردازشگر اصلی
        delete_transient('pk_sender_lock');

        // ۲. حذف دوره استراحت مدارشکن
        delete_transient('pk_circuit_breaker_cooldown');

        // ۳. (اختیاری) حذف شمارنده خطاهای مدارشکن
        delete_transient('pk_circuit_breaker_failures');

        // کاربر را با یک پیام موفقیت‌آمیز به صفحه‌ای که در آن بود بازمی‌گردانیم.
        wp_safe_redirect(add_query_arg('pk_reset_status', 'success', wp_get_referer()));
        exit;
    }
}

add_action('admin_init', 'pk_handle_manual_reset');

/**
 * پیام موفقیت‌آمیز را پس از ریست دستی در پنل ادمین نمایش می‌دهد.
 */
function pk_show_manual_reset_notice()
{
    if (isset($_GET['pk_reset_status']) && $_GET['pk_reset_status'] === 'success') {
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            'قفل‌های سیستم ارسال و دوره استراحت مدارشکن با موفقیت ریست شدند. پردازشگر اکنون می‌تواند دوباره شروع به کار کند.'
        );
    }
}

add_action('admin_notices', 'pk_show_manual_reset_notice');

/**
 * یک URL امن و حاوی توکن (nonce) برای دکمه ریست دستی تولید می‌کند.
 * @return string The secure URL.
 */
function pk_get_manual_reset_url(): string
{
    $nonce = wp_create_nonce('pk_manual_reset_nonce');
    return add_query_arg([
        'pk_manual_reset' => 'true',
        '_wpnonce' => $nonce,
    ], remove_query_arg(['pk_reset_status']));
}

/**
 * یک مقدار را از آرایه POST$ به صورت امن دریافت، اعتبارسنجی و بازگردانی می‌کند.
 * اگر مقدار وجود نداشت یا خالی بود، مقدار پیش‌فرض را برمی‌گرداند.
 *
 * @param string $key کلید مورد نظر در آرایه POST$.
 * @param callable $sanitize_callback تابعی که برای اعتبارسنجی استفاده می‌شود (مانند 'sanitize_text_field').
 * @param mixed $default_value مقدار پیش‌فرض در صورت عدم وجود کلید.
 * @return mixed مقدار اعتبارسنجی شده یا مقدار پیش‌فرض.
 */
function pk_get_sanitized_post(string $key, callable $sanitize_callback, mixed $default_value): mixed
{
    // بررسی می‌کند که آیا متغیر در POST$ وجود دارد و پس از حذف فاصله‌های خالی، تهی نیست.
    if (isset($_POST[$key]) && trim($_POST[$key]) !== '') {
        // اگر تابع اعتبارسنجی معتبر بود، آن را روی مقدار اجرا کن.
        if (is_callable($sanitize_callback)) {
            return call_user_func($sanitize_callback, $_POST[$key]);
        }
        // اگر تابع نامعتبر بود، به عنوان یک اقدام امنیتی، مقدار را فقط escape کن.
        return esc_html($_POST[$key]);
    }

    // اگر متغیر وجود نداشت، مقدار پیش‌فرض را برگردان.
    return $default_value;
}

/**
 * نام زبان را به یک تگ زبان استاندارد BCP 47 تبدیل می‌کند.
 * این تابع ابتدا در یک لیست از پیش تعریف شده جستجو کرده و در صورت عدم موفقیت،
 * تلاش می‌کند ورودی را با استفاده از کلاس Locale استانداردسازی کند.
 *
 * @param string $language_name نام زبان (مثلاً 'Persian' یا 'fa').
 * @return string تگ زبان استاندارد (مثلاً 'fa-IR').
 */
function pk_convert_language_to_locale_tag(string $language_name): string
{
    if (empty($language_name)) {
        return 'en-US'; // بازگشت یک مقدار پیش‌فرض امن
    }

    // ۱. لیست نگاشت نام‌های رایج به کدهای استاندارد
    $language_map = [
        // زبان‌های خاورمیانه و آسیای میانه
        'persian' => 'fa-IR',
        'arabic' => 'ar-SA',
        'hebrew' => 'he-IL',
        'turkish' => 'tr-TR',
        'azerbaijani' => 'az-AZ',
        'kurdish' => 'ku-TR',
        'pashto' => 'ps-AF',
        'urdu' => 'ur-PK',

        // زبان‌های اروپایی
        'english' => 'en-US', 'american' => 'en-US',
        'british' => 'en-GB',
        'spanish' => 'es-ES',
        'french' => 'fr-FR',
        'german' => 'de-DE',
        'italian' => 'it-IT',
        'portuguese' => 'pt-BR',
        'russian' => 'ru-RU',
        'dutch' => 'nl-NL',
        'swedish' => 'sv-SE',
        'norwegian' => 'no-NO',
        'danish' => 'da-DK',
        'finnish' => 'fi-FI',
        'polish' => 'pl-PL',
        'czech' => 'cs-CZ',
        'hungarian' => 'hu-HU',
        'greek' => 'el-GR',
        'romanian' => 'ro-RO',
        'bulgarian' => 'bg-BG',
        'ukrainian' => 'uk-UA',

        // زبان‌های آسیای شرقی و جنوب شرقی
        'chinese' => 'zh-CN', // ساده شده
        'traditional chinese' => 'zh-TW',
        'japanese' => 'ja-JP',
        'korean' => 'ko-KR',
        'hindi' => 'hi-IN',
        'bengali' => 'bn-BD',
        'indonesian' => 'id-ID',
        'vietnamese' => 'vi-VN',
        'thai' => 'th-TH',
        'malay' => 'ms-MY',
        'filipino' => 'fil-PH', 'tagalog' => 'tl-PH',

        // زبان‌های آفریقایی
        'swahili' => 'sw-KE',
        'afrikaans' => 'af-ZA',
        'zulu' => 'zu-ZA',
        'amharic' => 'am-ET',

        // زبان‌های دیگر
        'australian' => 'en-AU',
        'canadian' => 'en-CA',
    ];

    // جستجوی غیرحساس به حروف کوچک و بزرگ در لیست
    $normalized_name = strtolower(trim($language_name));
    if (isset($language_map[$normalized_name])) {
        return $language_map[$normalized_name];
    }

    // ۲. استفاده از کلاس Locale برای استانداردسازی (اگر ورودی یک کد بود)
    // این بخش تضمین می‌کند که اگر کاربر یک کد معتبر مانند "fa" یا "en_GB" وارد کرد، به درستی تبدیل شود.
    if (class_exists('Locale')) {
        // The Locale::canonicalize function standardizes a given locale string.
        $canonical_locale = \Locale::canonicalize($language_name);
        if (!empty($canonical_locale) && $canonical_locale !== 'root') {
            return str_replace('_', '-', $canonical_locale); // تبدیل en_US به en-US
        }
    }

    // ۳. اگر هیچکدام موفق نبودند، به زبان پیش‌فرض وردپرس یا یک مقدار امن بازگرد
    return get_locale() ? str_replace('_', '-', get_locale()) : 'en-US';
}

function get_tehran_time($format = 'Y-m-d H:i:s', $timestamp = false, $modify = null): int|string
{
    $dateTime = new DateTime('now', new DateTimeZone('+03:30'));
    if ($modify)
        $dateTime->modify($modify);
    if ($timestamp)
        return $dateTime->getTimestamp();
    else
        return $dateTime->format($format);
}

function pk_add_svg_to_allowed_html($allowed_tags, $context)
{
    if ('post' === $context) {
        $allowed_tags['svg'] = [
            'xmlns' => true,
            'fill' => true,
            'viewbox' => true,
            'role' => true,
            'aria-hidden' => true,
            'focusable' => true,
            'class' => true,
            'style' => true,
            'width' => true,
            'height' => true,
            'stroke-width' => true,
            'stroke' => true,
        ];
        $allowed_tags['path'] = [
            'd' => true,
            'fill' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
            'class' => true,
            'style' => true,
        ];
        $allowed_tags['circle'] = [
            'cx' => true,
            'cy' => true,
            'r' => true,
            'fill' => true,
            'stroke' => true,
        ];
        $allowed_tags['rect'] = [
            'x' => true,
            'y' => true,
            'width' => true,
            'height' => true,
            'fill' => true,
            'stroke' => true,
        ];
        $allowed_tags['style'] = [
            'type' => true,
        ];
    }
    return $allowed_tags;
}

add_filter('wp_kses_allowed_html', 'pk_add_svg_to_allowed_html', 10, 2);
