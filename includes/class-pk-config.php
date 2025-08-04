<?php
// pwa-kit/includes/class-pk-config.php
namespace PwaKit\Core;

if (!defined('ABSPATH')) exit;

/**
 * Class PK_Config
 * این کلاس به عنوان "یک منبع حقیقت واحد" برای تمام مقادیر پیش‌فرض در پلاگین عمل می‌کند.
 * این نسخه نهایی، قابلیت دریافت یک مقدار خاص را نیز دارد.
 */
class PK_Config
{
    /**
     * مقادیر پیش‌فرض برای تنظیمات PWA (مانیفست) را برمی‌گرداند.
     * اگر یک کلید مشخص شود، فقط مقدار آن کلید را برمی‌گرداند.
     *
     * @param string|null $key کلید مورد نظر برای دریافت مقدار.
     * @return string|array|null اگر کلید مشخص شود، مقدار آن یا null را برمی‌گرداند. در غیر این صورت، کل آرایه پیش‌فرض‌ها را برمی‌گرداند.
     */
    public static function get_pwa_defaults(string $key = null): array|string|null
    {
        $defaults = [
            // required items for manifest.json
            'id' => \home_url('/'),
            'name' => get_bloginfo('name'),
            'short_name' => substr(get_bloginfo('name'), 0, 12),
            'description' => get_bloginfo('description'),
            'start_url' => \home_url('/'), // internal url
            'display' => 'standalone', // standalone, fullscreen, minimal-ui, browser
            'orientation' => 'natural', // any, natural, portrait-primary, landscape-primary
            'dir' => 'auto', // auto, ltr, rtl
            'lang' => str_replace('_', '-', \get_locale()),
            'theme_color' => '#0284c7', // HEX
            'background_color' => '#f9fafb', // HEX
            'icon_192' => \get_site_icon_url(192),
            'icon_512' => \get_site_icon_url(512),
            'icon_purpose' => 'any maskable', // any, maskable, monochrome
            'categories' => [], // list of category: https://github.com/w3c/manifest/wiki/Categories
            'display_override' => [], // browser, fullscreen, minimal-ui, standalone, window-controls-overlay
            'shortcuts' => [],
            'screenshots' => [],

            // install pwa popup
            'popup_style' => 'modal', // modal, banner-top, banner-bottom
            'popup_install_modal_title' => 'نصب وب اپلیکیشن',
            'popup_install_modal_text' => 'با افزودن اپلیکیشن به صفحه اصلی، تجربه‌ای سریع‌تر و راحت‌تر داشته باشید.',
            'popup_install_modal_button' => 'افزودن به صفحه اصلی',
            'popup_install_banner_title' => 'نصب وب اپلیکیشن',
            'popup_install_banner_text' => 'همن الان نصبش کن تا سایت راحت‌تر در دسترست باشه.',
            'popup_install_banner_button' => 'نصب',
            'popup_install_title_color' => '#000',
            'popup_install_text_color' => '#475569',
            'popup_install_button_text_color' => '#f9fafb',
            'popup_install_count' => 2,
            'popup_install_delay' => 720, // = 60 * 12 Hour

            // offline pwa popup
            'offline_content' => '<div class="offline-logo-wrapper"><img style="width: 7rem;height: 7rem" src="{{app_icon_url}}" alt="{{app_name}}" /></div><strong style="font-size: 24px;font-weight: 800;margin-top: 16px">{{app_name}}</strong><p style="font-size: 20px;margin: 0;font-weight: 500">برای دسترسی به محتوای بروز، از اتصال اینترنت خود مطمئن شوید.</p>',
            'offline_title_color' => '#0284c7',
            'offline_text_color' => '#475569',
            'offline_button_bg_color' => '#f9fafb',
            'offline_button_text_color' => '#475569',
            'offline_button_border_color' => '#e6e6e6',
            'offline_loader_color' => '#0284c7',
            'offline_status_text_color' => '#475569',
            'offline_main_font_url' => 'https://fonts.googleapis.com/css2?family=Vazirmatn&display=swap',
            'offline_main_font_family' => "'Vazirmatn', sans-serif",
        ];

        // اگر کلیدی ارسال نشده بود، کل آرایه را برگردان
        if ($key === null) {
            return $defaults;
        }

        // اگر کلید ارسال شده بود، فقط مقدار آن را برگردان
        return $defaults[$key] ?? null; // استفاده از Null Coalescing Operator برای جلوگیری از خطا
    }

    /**
     * مقادیر پیش‌فرض برای تنظیمات نوتیفیکیشن را برمی‌گرداند.
     * اگر یک کلید مشخص شود، فقط مقدار آن کلید را برمی‌گرداند.
     *
     * @param string|null $key کلید مورد نظر برای دریافت مقدار.
     * @return string|array|null
     */
    public static function get_notification_defaults(string $key = null): array|string|null
    {
        $defaults = [
            'vapid_subject' => 'mailto:' . get_option('admin_email'),
            'default_icon' => '',
            'ttl_value' => 7, // a week
            'ttl_unit' => 'days', // seconds - minutes - hours - days - weeks - months // ttl_value * ttl_unit = default_ttl
            'default_ttl' => WEEK_IN_SECONDS, // a week
            'default_urgency' => 'normal', // high - normal - low
            'batch_size' => 1000, // in minutes

            'popup_subscribe_title' => 'آخرین اخبار را از دست ندهید!',
            'popup_subscribe_text' => 'با فعال کردن نوتیفیکیشن‌ها، اولین نفری باشید که از جدیدترین مطالب و تخفیف‌ها مطلع می‌شوید.',
            'popup_subscribe_accept_button' => 'بله، فعال کن',
            'popup_subscribe_deny_button' => 'شاید بعداً',
            'popup_subscribe_title_color' => '#000',
            'popup_subscribe_text_color' => '#475569',
            'popup_subscribe_accept_button_text_color' => '#0284c7',
            'popup_subscribe_deny_button_text_color' => '#f9fafb',
            'popup_subscribe_deny_button_border_color' => '#e6e6e6',
            'popup_subscribe_count' => 2,
            'popup_subscribe_delay' => 720, // = 60 * 12 Hour

            'subscribe_bell_content' => '<div class="pk-subscribe-bell"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="bell-icon"><path d="M21,19V20H3V19L5,17V11C5,7.9 7.03,5.17 10,4.29V4A2,2 0 0,1 12,2A2,2 0 0,1 14,4V4.29C16.97,5.17 19,7.9 19,11V17L21,19M12,22A2,2 0 0,1 10,20H14A2,2 0 0,1 12,22Z"/></svg></div><style>#pk-subscribe-bell-wrapper{position:fixed;bottom:28.5px !important;left:200px;z-index:9998}.pk-subscribe-bell{padding:15px;background-color:#0284c7!important;border-radius:999px;cursor:pointer}.pk-subscribe-bell svg{width:2rem;height:2rem;fill:#f9fafb}</style>',
            // sangarzadeh.com:
            // <div class="pk-subscribe-bell flex gap-1 notification-btn cursor-pointer sm:m-10 m-5 px-4 py-2.5 rounded-2xl text-black shadow group flex items-center gap-2 z-20"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 bell-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg><div class="text-base font-semibold max-sm:hidden">فعالسازی اعلان‌ها</div></div><style>#pk-subscribe-bell-wrapper{bottom:20px}.pk-subscribe-bell{background-color:rgb(254 207 67 / var(--tw-bg-opacity))!important}</style>
            'popup_confirmation_title' => 'آیا نوتیفیکیشن را دریافت کردید؟',
            'popup_confirmation_text' => 'ما یک پیام تأیید فعال‌سازی نوتیفیکیشن برای شما ارسال کردیم. آیا آن را مشاهده کردید؟',
            'popup_confirmation_accept_button' => 'بله، دریافت کردم',
            'popup_confirmation_deny_button' => 'نه، دریافت نکردم',

            'welcome_notification_enabled' => 1,
        ];

        if ($key === null) {
            return $defaults;
        }

        return $defaults[$key] ?? null;
    }

    /**
     * لیستی کامل از زبان‌های پشتیبانی شده را برای استفاده در لیست‌های انتخابی برمی‌گرداند.
     * کلید آرایه، تگ استاندارد BCP 47 است و مقدار آن، نام قابل نمایش زبان است.
     *
     * @return array
     */
    public static function get_supported_languages(): array
    {
        return [
            'persian' => 'fa-IR',
            'arabic' => 'ar-SA',
            'hebrew' => 'he-IL',
            'turkish' => 'tr-TR',
            'azerbaijani' => 'az-AZ',
            'kurdish' => 'ku-TR',
            'pashto' => 'ps-AF',
            'urdu' => 'ur-PK',
            'american' => 'en-US',
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
            'chinese' => 'zh-CN',
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
            'swahili' => 'sw-KE',
            'afrikaans' => 'af-ZA',
            'zulu' => 'zu-ZA',
            'amharic' => 'am-ET',
            'australian' => 'en-AU',
            'canadian' => 'en-CA',
        ];
    }
}