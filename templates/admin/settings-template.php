<?php

use PwaKit\Core\PK_Config as Config;

if (!defined('ABSPATH')) exit;

\wp_enqueue_media();
\wp_enqueue_style('wp-color-picker');
\wp_enqueue_script('wp-color-picker');
\wp_enqueue_script('jquery-ui-sortable');

function pk_format_ttl_for_display($seconds)
{
    $units = [
        'months' => 2592000,
        'weeks' => 604800,
        'days' => 86400,
        'hours' => 3600,
        'minutes' => 60,
        'seconds' => 1
    ];
    foreach ($units as $unit => $value) {
        if ($seconds >= $value && $seconds % $value === 0) {
            return ['value' => $seconds / $value, 'unit' => $unit];
        }
    }
    return ['value' => $seconds, 'unit' => 'seconds'];
}

// --- PWA Settings ---
$pwa_settings = \wp_parse_args(\get_option('pk_pwa_settings', []), Config::get_pwa_defaults());

// --- Notification Settings ---
$notification_settings = \wp_parse_args(\get_option('pk_notification_settings', []), Config::get_notification_defaults());

$current_tab = isset($_GET['tab']) ? \sanitize_key($_GET['tab']) : 'pwa-main';
$valid_categories = ['books', 'business', 'education', 'entertainment', 'finance', 'fitness', 'food', 'games', 'government', 'health', 'kids', 'lifestyle', 'magazines', 'medical', 'music', 'navigation', 'news', 'personalization', 'photo', 'politics', 'productivity', 'security', 'shopping', 'social', 'sports', 'travel', 'utilities', 'weather'];
$shortcut_icon_sizes = ['72x72', '96x96', '128x128', '144x144', '152x152', '192x192', '384x384', '512x512'];
$screenshot_sizes = ['320x350', '390x844', '540x720', '720x540', '1080x1920', '1280x800', '1920x1080'];
$all_display_modes = ['fullscreen' => 'Fullscreen', 'standalone' => 'Standalone (پیشنهادی)', 'minimal-ui' => 'Minimal UI', 'browser' => 'Browser'];
$all_display_overrides = ['window-controls-overlay' => 'Window Controls Overlay', 'minimal-ui' => 'Minimal UI', 'fullscreen' => 'Fullscreen', 'standalone' => 'Standalone', 'browser' => 'Browser'];
?>

<div class="wrap pk-settings-wrap">
    <h1>تنظیمات وب اپلیکیشن</h1>

    <?php if (!PWA_KIT_VENDOR_EXISTS): ?>
        <div class="notice notice-error">
            <p><strong>هشدار:</strong> کتابخانه‌های مورد نیاز (پکیج Composer) نصب نشده‌اند. قابلیت ارسال نوتیفیکیشن کار
                نخواهد کرد. لطفاً دستور <code>composer install</code> را در پوشه افزونه اجرا کنید.</p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="current_tab" id="pk_current_tab" value="<?php echo esc_attr($current_tab); ?>">
        <?php \wp_nonce_field('pkp_pwa_settings_action'); ?>
        <h2 class="nav-tab-wrapper">
            <a href="#pwa-main" data-tab="pwa-main" class="nav-tab">اطلاعات اصلی PWA</a>
            <a href="#pwa-appearance" data-tab="pwa-appearance" class="nav-tab">ظاهر و آیکون‌ها</a>
            <a href="#pwa-shortcuts" data-tab="pwa-shortcuts" class="nav-tab">میانبرها</a>
            <a href="#pwa-screenshots" data-tab="pwa-screenshots" class="nav-tab">اسکرین‌شات‌ها</a>
            <a href="#notifications" data-tab="notifications" class="nav-tab">نوتیفیکیشن</a>
            <a href="#api" data-tab="api" class="nav-tab <?php echo $current_tab === 'api' ? 'nav-tab-active' : ''; ?>">API</a>
        </h2>

        <div id="pwa-main" class="tab-content">
            <div class="postbox">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="pk_id">ID اپلیکیشن</label></th>
                        <td><input name="pk_id" type="text" id="pk_id"
                                   value="<?php echo \esc_attr($pwa_settings['id']); ?>"
                                   class="regular-text ltr">
                            <p class="description">یک شناسه منحصر به فرد برای اپ شما. بهتر است آن را تغییر ندهید.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pk_name">نام کامل اپلیکیشن</label></th>
                        <td><input name="pk_name" type="text" id="pk_name"
                                   value="<?php echo \esc_attr($pwa_settings['name']); ?>" class="regular-text"
                                   required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pk_short_name">نام کوتاه</label></th>
                        <td><input name="pk_short_name" type="text" id="pk_short_name"
                                   value="<?php echo \esc_attr($pwa_settings['short_name']); ?>" class="regular-text"
                                   maxlength="12" required>
                            <p class="description">حداکثر ۱۲ کاراکتر. <span id="short_name_counter" dir="ltr"></span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pk_description">توضیحات اپلیکیشن</label></th>
                        <td><textarea name="pk_description" id="pk_description" class="regular-text"
                                      rows="3"><?php echo \esc_textarea($pwa_settings['description']); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pk_start_url">صفحه شروع</label></th>
                        <td><select name="pk_start_url" id="pk_start_url" class="pk-select2"
                                    style="width: 100%; max-width: 350px;">
                                <option value="<?php echo \home_url('/'); ?>" <?php selected($pwa_settings['start_url'], \home_url('/')); ?>>
                                    صفحه اصلی سایت
                                </option><?php foreach ($all_pages as $page): ?>
                                    <option
                                    value="<?php echo \get_permalink($page->ID); ?>" <?php selected($pwa_settings['start_url'], \get_permalink($page->ID)); ?>><?php echo \esc_html($page->post_title); ?></option><?php endforeach; ?>
                            </select>
                            <p class="description">صفحه‌ای که پس از باز کردن اپلیکیشن نمایش داده می‌شود.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pk_categories">دسته‌بندی‌ها</label></th>
                        <td><select name="pk_categories[]" id="pk_categories" multiple="multiple" class="pk-select2"
                                    data-placeholder="یک یا چند دسته‌بندی انتخاب کنید..."
                                    style="width: 100%;"><?php foreach ($valid_categories as $cat): ?>
                                    <option
                                    value="<?php echo $cat; ?>" <?php selected(in_array($cat, $pwa_settings['categories'])); ?>><?php echo ucfirst($cat); ?></option><?php endforeach; ?>
                            </select>
                            <p class="description">به اپ استورها کمک می‌کند تا اپ شما را دسته‌بندی کنند.</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div id="pwa-appearance" class="tab-content">
            <div class="postbox">
                <h2 class="hndle">تنظیمات نمایش</h2>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="pk_display">حالت نمایش اصلی</label></th>
                            <td><select name="pk_display"
                                        id="pk_display"><?php foreach ($all_display_modes as $key => $label): ?>
                                        <option
                                        value="<?php echo $key; ?>" <?php selected($pwa_settings['display'], $key); ?>><?php echo $label; ?></option><?php endforeach; ?>
                                </select>
                                <p class="description">حالت نمایش پیش‌فرض و نهایی اپلیکیشن.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_display_override">اولویت‌بندی نمایش <span class="pk-tooltip">?<span
                                                class="pk-tooltip-text">یک زنجیره جایگزین برای حالت نمایش تعریف کنید. مرورگر از اولین مورد در لیست که پشتیبانی کند، استفاده خواهد کرد. ترتیب مهم است.</span></span></label>
                            </th>
                            <td><select name="pk_display_override[]" id="pk_display_override" multiple="multiple"
                                        class="pk-select2-sortable" style="width: 100%;"
                                        data-placeholder="حالت‌های نمایش جایگزین را انتخاب و مرتب کنید..."><?php foreach ($pwa_settings['display_override'] as $override_val): if (isset($all_display_overrides[$override_val])): ?>
                                        <option value="<?php echo esc_attr($override_val); ?>"
                                                selected="selected"><?php echo esc_html($all_display_overrides[$override_val]); ?></option><?php endif;
                                    endforeach; ?></select></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_orientation">جهت‌گیری صفحه</label></th>
                            <td><select name="pk_orientation" id="pk_orientation">
                                    <option value="any" <?php selected($pwa_settings['orientation'], 'any'); ?>>Any
                                    </option>
                                    <option value="natural" <?php selected($pwa_settings['orientation'], 'natural'); ?>>
                                        Natural (پیشنهادی)
                                    </option>
                                    <option value="portrait-primary" <?php selected($pwa_settings['orientation'], 'portrait-primary'); ?>>
                                        Portrait
                                    </option>
                                    <option value="landscape-primary" <?php selected($pwa_settings['orientation'], 'landscape-primary'); ?>>
                                        Landscape
                                    </option>
                                </select></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_dir">جهت متن</label></th>
                            <td><select name="pk_dir" id="pk_dir">
                                    <option value="auto" <?php selected($pwa_settings['dir'], 'auto'); ?>>Auto</option>
                                    <option value="rtl" <?php selected($pwa_settings['dir'], 'rtl'); ?>>RTL</option>
                                    <option value="ltr" <?php selected($pwa_settings['dir'], 'ltr'); ?>>LTR</option>
                                </select></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_lang">زبان برنامه</label></th>
                            <td>
                                <select name="pk_lang" id="pk_lang" class="pk-select2">
                                    <?php
                                    $supported_languages = Config::get_supported_languages();

                                    foreach ($supported_languages as $language_name => $locale_tag) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($locale_tag),
                                            selected($pwa_settings['lang'], $locale_tag, false),
                                            ucfirst(esc_html($language_name))
                                        );
                                    }
                                    ?>
                                </select>
                                <p class="description">زبان اصلی که در فایل مانیفست برنامه شما تعریف می‌شود.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_theme_color">رنگ تم</label></th>
                            <td><input name="pk_theme_color" type="text"
                                       value="<?php echo \esc_attr($pwa_settings['theme_color']); ?>"
                                       class="pk-color-picker">
                                <p class="description">رنگ نوار ابزار بالای اپلیکیشن.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_background_color">رنگ پس‌زمینه</label></th>
                            <td><input name="pk_background_color" type="text"
                                       value="<?php echo \esc_attr($pwa_settings['background_color']); ?>"
                                       class="pk-color-picker">
                                <p class="description">رنگ پس‌زمینه اسپلش اسکرین.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="postbox">
                <h2 class="hndle">آیکون‌ها</h2>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row">آیکون اصلی (192x192)</th>
                            <td>
                                <div class="pk-image-uploader"><input name="pk_icon_192" type="text" id="pk_icon_192"
                                                                      value="<?php echo \esc_attr($pwa_settings['icon_192']); ?>"
                                                                      dir="ltr"
                                                                      class="pk-image-url regular-text">
                                    <button type="button" class="button button-secondary pk-upload-button"
                                            data-target="pk_icon_192">انتخاب
                                    </button>
                                </div>
                                <div class="pk-icon-preview-wrapper" data-preview-for="pk_icon_192"><img
                                            src="<?php echo \esc_attr($pwa_settings['icon_192']); ?>" alt="Preview 192">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">آیکون بزرگ (512x512)</th>
                            <td>
                                <div class="pk-image-uploader"><input name="pk_icon_512" type="text" id="pk_icon_512"
                                                                      value="<?php echo \esc_attr($pwa_settings['icon_512']); ?>"
                                                                      dir="ltr"
                                                                      class="pk-image-url regular-text">
                                    <button type="button" class="button button-secondary pk-upload-button"
                                            data-target="pk_icon_512">انتخاب
                                    </button>
                                </div>
                                <div class="pk-icon-preview-wrapper" data-preview-for="pk_icon_512"><img
                                            src="<?php echo \esc_attr($pwa_settings['icon_512']); ?>" alt="Preview 512">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_icon_purpose">حالت آیکون‌ها</label></th>
                            <td><select name="pk_icon_purpose" id="pk_icon_purpose">
                                    <option value="maskable" <?php selected($pwa_settings['icon_purpose'], 'any maskable'); ?>>
                                        Maskable
                                    </option>
                                    <option value="any" <?php selected($pwa_settings['icon_purpose'], 'any'); ?>>Any
                                    </option>
                                </select>
                                <p class="description">با ابزار <a href="https://maskable.app/" target="_blank"
                                                                   id="pk-maskable-link">Maskable.app</a> آیکون خود را
                                    تست کنید.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="postbox">
                <h2 class="hndle">تنظیمات پاپ‌آپ نصب</h2>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="pk_popup_style">استایل پاپ‌آپ</label></th>
                            <td>
                                <select name="pk_popup_style" id="pk_popup_style">
                                    <option value="modal" <?php selected($pwa_settings['popup_style'], 'modal'); ?>>
                                        مودال مینیمال
                                    </option>
                                    <option value="banner-bottom" <?php selected($pwa_settings['popup_style'], 'banner-bottom'); ?>>
                                        بنر (پایین صفحه)
                                    </option>
                                    <option value="banner-top" <?php selected($pwa_settings['popup_style'], 'banner-top'); ?>>
                                        بنر (بالای صفحه)
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_popup_count">تعداد نمایش در فاصله زمانی</label></th>
                            <td><input name="pk_popup_count" type="number" id="pk_popup_count" value="<?php echo \esc_attr($pwa_settings['popup_install_count']); ?>" class="small-text">
                                <p class="description">حداکثر تعداد دفعاتی که پاپ‌آپ نصب در فاصله زمانی مشخص به کاربر نمایش داده می‌شود.</p></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_popup_delay">فاصله زمانی نمایش (دقیقه)</label></th>
                            <td><input name="pk_popup_delay" type="number" id="pk_popup_delay" value="<?php echo \esc_attr($pwa_settings['popup_install_delay']); ?>" class="small-text">
                                <p class="description">حداقل فاصله زمانی بین نمایش‌های متوالی پاپ‌آپ به یک کاربر (بر حسب دقیقه).</p></td>
                        </tr>
                    </table>

                    <div id="pk-pwa-prompt-texts-modal" class="pk-prompt-texts">
                        <h4>متن‌های حالت مودال:</h4>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="pk_install_modal_title">عنوان</label></th>
                                <td><input name="pk_install_modal_title" type="text" id="pk_install_modal_title"
                                           value="<?php echo \esc_attr($pwa_settings['popup_install_modal_title']); ?>"
                                           class="regular-text"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="pk_install_modal_text">توضیحات</label></th>
                                <td><textarea name="pk_install_modal_text" id="pk_install_modal_text"
                                              class="regular-text"
                                              rows="3"><?php echo \esc_textarea($pwa_settings['popup_install_modal_text']); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="pk_install_modal_button">متن دکمه نصب</label></th>
                                <td><input name="pk_install_modal_button" type="text" id="pk_install_modal_button"
                                           value="<?php echo \esc_attr($pwa_settings['popup_install_modal_button']); ?>"
                                           class="regular-text"></td>
                            </tr>
                        </table>
                    </div>

                    <div id="pk-pwa-prompt-texts-banner" class="pk-prompt-texts">
                        <h4>متن حالت بنر:</h4>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="pk_install_banner_title">عنوان</label></th>
                                <td><input name="pk_install_banner_title" type="text" id="pk_install_banner_title"
                                           value="<?php echo \esc_attr($pwa_settings['popup_install_banner_title']); ?>"
                                           class="regular-text"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="pk_install_banner_text">توضیحات</label></th>
                                <td><textarea name="pk_install_banner_text" id="pk_install_banner_text"
                                              class="regular-text"
                                              rows="3"><?php echo \esc_textarea($pwa_settings['popup_install_banner_text']); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="pk_install_banner_button">متن دکمه نصب</label></th>
                                <td><input name="pk_install_banner_button" type="text" id="pk_install_banner_button"
                                           value="<?php echo \esc_attr($pwa_settings['popup_install_banner_button']); ?>"
                                           class="regular-text"></td>
                            </tr>
                        </table>
                    </div>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="pk_install_title_color">رنگ متن عنوان</label></th>
                            <td><input name="pk_install_title_color" type="text"
                                       value="<?php echo \esc_attr($pwa_settings['popup_install_title_color']); ?>"
                                       class="pk-color-picker">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_install_text_color">رنگ متن توضیحات</label></th>
                            <td><input name="pk_install_text_color" type="text"
                                       value="<?php echo \esc_attr($pwa_settings['popup_install_text_color']); ?>"
                                       class="pk-color-picker">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_install_button_text_color">رنگ متن دکمه نصب</label>
                            </th>
                            <td><input name="pk_install_button_text_color" type="text"
                                       value="<?php echo \esc_attr($pwa_settings['popup_install_button_text_color']); ?>"
                                       class="pk-color-picker">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="postbox">
                <h2 class="hndle"><span>محتوای صفحه آفلاین</span></h2>
                <div class="inside">
                    <p class="description">محتوایی که در زمان آفلاین بودن به کاربر نمایش داده می‌شود را در اینجا طراحی
                        کنید.</p>
                    <p>برای داشتن یک افکت حالت آفلاین زیبا روی تصاویر خود، تصویر خود را درون یک ظرف (div, section) با
                        اتریبیوت <code>class="offline-logo-wrapper"</code> قرار دهید!</p>
                    <?php \wp_editor($pwa_settings['offline_content'], 'pk_offline_content', ['textarea_rows' => 10]); ?>
                    <p class="description">الگوهای در دسترس: <code>{{app_name}}</code>, <code>{{app_icon_url}}</code>
                    </p>
                </div>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="pk_offline_main_font_url">مسیر فونت صفحه آفلاین</label></th>
                        <td>
                            <input name="pk_offline_main_font_url" type="text"
                                   id="pk_offline_main_font_url"
                                   placeholder="https://fonts.googleapis.com/..." dir="ltr"
                                   value="<?php echo \esc_attr($pwa_settings['offline_main_font_url']); ?>"
                                   class="pk-image-url regular-text">
                            <p class="description">برای صفحه آفلاین خود یک آدرس فونت معتبر (مانند Google Fonts) وارد
                                کنید.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pk_offline_main_font_family">نام رسمی فونت</label></th>
                        <td>
                            <input name="pk_offline_main_font_family" type="text"
                                   id="pk_offline_main_font_family"
                                   placeholder="'<font-name>', [sans-serif, serif, monospace, cursive]" dir="ltr"
                                   value="<?php echo \esc_attr($pwa_settings['offline_main_font_family']); ?>"
                                   class="pk-image-url regular-text">
                            <p class="description">دقت کنید که نام فونت را درست وارد کنید. در غیر اینصورت فونت به
                                درستی اعمال نخواهد شد!</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pk_offline_title_color">رنگ متن عنوان</label></th>
                        <td><input name="pk_offline_title_color" type="text"
                                   value="<?php echo \esc_attr($pwa_settings['offline_title_color']); ?>"
                                   class="pk-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pk_offline_text_color">رنگ متن توضیحات</label></th>
                        <td><input name="pk_offline_text_color" type="text"
                                   value="<?php echo \esc_attr($pwa_settings['offline_text_color']); ?>"
                                   class="pk-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pk_offline_button_bg_color">رنگ دکمه</label></th>
                        <td><input name="pk_offline_button_bg_color" type="text"
                                   value="<?php echo \esc_attr($pwa_settings['offline_button_bg_color']); ?>"
                                   class="pk-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pk_offline_button_text_color">رنگ متن دکمه</label></th>
                        <td><input name="pk_offline_button_text_color" type="text"
                                   value="<?php echo \esc_attr($pwa_settings['offline_button_text_color']); ?>"
                                   class="pk-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pk_offline_button_border_color">رنگ حاشیه دکمه</label></th>
                        <td><input name="pk_offline_button_border_color" type="text"
                                   value="<?php echo \esc_attr($pwa_settings['offline_button_border_color']); ?>"
                                   class="pk-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pk_offline_loader_color">رنگ انیمیشن لودینگ</label></th>
                        <td><input name="pk_offline_loader_color" type="text"
                                   value="<?php echo \esc_attr($pwa_settings['offline_loader_color']); ?>"
                                   class="pk-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pk_offline_status_text_color">رنگ متن وضعیت اتصال</label></th>
                        <td><input name="pk_offline_status_text_color" type="text"
                                   value="<?php echo \esc_attr($pwa_settings['offline_status_text_color']); ?>"
                                   class="pk-color-picker">
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div id="pwa-shortcuts" class="tab-content">
            <div class="postbox">
                <div class="inside">
                    <p>میانبرها به کاربران اجازه می‌دهند با نگه داشتن آیکون اپ، به سرعت به بخش‌های مهم سایت دسترسی پیدا
                        کنند. (حداکثر ۴ میانبر)</p>
                    <div id="pk-shortcuts-wrapper">
                        <?php if (!empty($pwa_settings['shortcuts'])) : foreach ($pwa_settings['shortcuts'] as $index => $shortcut) : ?>
                            <div class="pk-shortcut-item">
                                <span class="pk-shortcut-handle dashicons dashicons-menu" title="جابجایی"></span>
                                <div class="pk-shortcut-fields">
                                    <div class="pk-shortcut-fields-row">
                                        <input type="text" name="pk_shortcuts[<?php echo $index; ?>][name]"
                                               placeholder="* نام میانبر"
                                               value="<?php echo esc_attr($shortcut['name']); ?>" class="pk-input-name">
                                        <select name="pk_shortcuts[<?php echo $index; ?>][url]"
                                                class="pk-select-url pk-select2" style="width:100%">
                                            <option value="">-- انتخاب صفحه --</option>
                                            <?php foreach ($all_pages as $page): ?>
                                                <option value="<?php echo \get_permalink($page->ID); ?>" <?php selected($shortcut['url'], \get_permalink($page->ID)); ?>>
                                                    <?php echo esc_html($page->post_title); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="pk-shortcut-fields-row">
                                        <div class="pk-shortcut-icon-preview"
                                             style="background-image: url('<?php echo esc_attr($shortcut['icons'][0]['src']); ?>')"></div>
                                        <input type="text" name="pk_shortcuts[<?php echo $index; ?>][icons][0][src]"
                                               value="<?php echo esc_attr($shortcut['icons'][0]['src']); ?>"
                                               class="pk-shortcut-icon-url" placeholder="آدرس آیکون">
                                        <select name="pk_shortcuts[<?php echo $index; ?>][icons][0][sizes]">
                                            <?php foreach ($shortcut_icon_sizes as $size): ?>
                                                <option value="<?php echo $size; ?>" <?php selected($shortcut['icons'][0]['sizes'], $size); ?>><?php echo $size; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="button button-small pk-shortcut-upload-button">
                                            انتخاب
                                        </button>
                                    </div>
                                </div>
                                <span class="pk-remove-item dashicons dashicons-trash" title="حذف"></span>
                            </div>
                        <?php endforeach;
                        endif; ?>
                    </div>
                    <button type="button" id="pk-add-shortcut" class="button button-secondary">افزودن میانبر جدید
                    </button>
                </div>
            </div>
        </div>

        <div id="pwa-screenshots" class="tab-content">
            <div class="postbox">
                <div class="inside">
                    <p>برای نمایش پنجره نصب جذاب‌تر (Richer Install UI)، اسکرین‌شات‌هایی از محیط اپ خود آپلود کنید.</p>
                    <div id="pk-screenshots-wrapper">
                        <?php if (!empty($pwa_settings['screenshots'])) : foreach ($pwa_settings['screenshots'] as $index => $ss) : ?>
                            <div class="pk-screenshot-item">
                                <input type="text" name="pk_screenshots[<?php echo $index; ?>][src]"
                                       placeholder="آدرس اسکرین شات" value="<?php echo esc_attr($ss['src']); ?>"
                                       class="widefat pk-ss-src">
                                <select name="pk_screenshots[<?php echo $index; ?>][form_factor]">
                                    <option value="wide" <?php selected($ss['form_factor'], 'wide'); ?>>دسکتاپ (wide)
                                    </option>
                                    <option value="narrow" <?php selected($ss['form_factor'], 'narrow'); ?>>موبایل
                                        (narrow)
                                    </option>
                                </select>
                                <select name="pk_screenshots[<?php echo $index; ?>][sizes]">
                                    <?php foreach ($screenshot_sizes as $size): ?>
                                        <option value="<?php echo $size; ?>" <?php selected($ss['sizes'], $size); ?>><?php echo $size; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="button pk-ss-upload-button">انتخاب</button>
                                <button type="button" class="button pk-remove-item">حذف</button>
                            </div>
                        <?php endforeach;
                        endif; ?>
                    </div>
                    <button type="button" id="pk-add-screenshot" class="button button-secondary">افزودن اسکرین‌شات
                    </button>
                </div>
            </div>
        </div>

        <div id="notifications" class="tab-content">
            <div id="pk-notification-message-area"></div>
            <div class="postbox">
                <h3 class="hndle">پیکربندی VAPID</h3>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="pk_vapid_subject">VAPID Subject (Email)</label></th>
                            <td><input name="pk_vapid_subject" type="email" id="pk_vapid_subject"
                                       value="<?php echo \esc_attr(str_replace('mailto:', '', $notification_settings['vapid_subject'])); ?>"
                                       class="regular-text ltr" placeholder="you@example.com" required>
                                <p class="description">پیشوند `mailto:` به صورت خودکار اضافه می‌شود.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_public_key">کلید عمومی</label></th>
                            <td><textarea id="pk_public_key" class="regular-text ltr" rows="3"
                                          readonly><?php echo \esc_textarea($notification_settings['public_key']); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_private_key">کلید خصوصی</label></th>
                            <td><textarea id="pk_private_key" class="regular-text ltr" rows="3"
                                          readonly><?php echo \esc_textarea($notification_settings['private_key']); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"></th>
                            <td>
                                <button type="button" id="pk-generate-keys-btn"
                                        class="button button-secondary" <?php disabled(!PWA_KIT_VENDOR_EXISTS); ?>>ساخت
                                    کلیدهای جدید
                                </button>
                                <span class="spinner"></span>
                                <p class="description"><strong>هشدار:</strong> ساخت کلیدهای جدید، تمام مشترکین فعلی شما
                                    را غیرفعال می‌کند.</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="postbox">
                <h3 class="hndle">تنظیمات پیش‌فرض ارسال</h3>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="pk_default_icon">آیکون پیش‌فرض</label></th>
                            <td>
                                <div class="pk-image-uploader"><input name="pk_default_icon" type="text"
                                                                      id="pk_default_icon"
                                                                      dir="ltr"
                                                                      value="<?php echo \esc_attr($notification_settings['default_icon']) ?: \get_site_icon_url(192); ?>"
                                                                      class="pk-image-url regular-text">
                                    <button type="button" class="button button-secondary pk-upload-button"
                                            data-target="pk_default_icon">انتخاب
                                    </button>
                                </div>
                                <div class="pk-icon-preview-wrapper" data-preview-for="pk_default_icon"><img
                                            src="<?php echo \esc_attr($notification_settings['default_icon'] ?: \get_site_icon_url(192)); ?>"
                                            alt="Default Icon Preview"></div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_ttl_value">طول عمر پیام (TTL)</label></th>
                            <td>
                                <?php $ttl_parts = pk_format_ttl_for_display($notification_settings['default_ttl']); ?>
                                <input name="pk_ttl_value" type="number" id="pk_ttl_value"
                                       value="<?php echo \esc_attr($ttl_parts['value']); ?>" class="small-text">
                                <select name="pk_ttl_unit" id="pk_ttl_unit">
                                    <option value="seconds" <?php selected($ttl_parts['unit'], 'seconds'); ?>>ثانیه
                                    </option>
                                    <option value="minutes" <?php selected($ttl_parts['unit'], 'minutes'); ?>>دقیقه
                                    </option>
                                    <option value="hours" <?php selected($ttl_parts['unit'], 'hours'); ?>>ساعت</option>
                                    <option value="days" <?php selected($ttl_parts['unit'], 'days'); ?>>روز</option>
                                    <option value="weeks" <?php selected($ttl_parts['unit'], 'weeks'); ?>>هفته</option>
                                    <option value="months" <?php selected($ttl_parts['unit'], 'months'); ?>>ماه</option>
                                </select>
                                <p class="description">مدت زمانی که پیام برای کاربر آفلاین نگه داشته می‌شود. (پیش‌فرض: 4
                                    هفته)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_default_urgency">فوریت پیام</label></th>
                            <td><select name="pk_default_urgency" id="pk_default_urgency">
                                    <option value="high" <?php selected($notification_settings['default_urgency'], 'high'); ?>>
                                        بالا
                                    </option>
                                    <option value="normal" <?php selected($notification_settings['default_urgency'], 'normal'); ?>>
                                        معمولی
                                    </option>
                                    <option value="low" <?php selected($notification_settings['default_urgency'], 'low'); ?>>
                                        پایین
                                    </option>
                                </select>
                                <p class="description">اهمیت پیام برای بهینه‌سازی مصرف باتری دستگاه کاربر.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_batch_size">اندازه دسته ارسال</label></th>
                            <td><input name="pk_batch_size" type="number" id="pk_batch_size"
                                       value="<?php echo \esc_attr($notification_settings['batch_size']); ?>"
                                       class="small-text">
                                <p class="description">تعداد نوتیفیکیشن ارسالی در هر دقیقه. (پیش‌فرض: 1000)</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="postbox">
                <h3 class="hndle">محتوا و ظاهر پاپ‌آپ فعال‌سازی</h3>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="pk_subscribe_title">عنوان</label></th>
                            <td><input name="pk_subscribe_title" type="text" id="pk_subscribe_title"
                                       value="<?php echo \esc_attr($notification_settings['popup_subscribe_title']); ?>"
                                       class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_subscribe_text">توضیحات</label></th>
                            <td><textarea name="pk_subscribe_text" id="pk_subscribe_text" rows="3"
                                          class="regular-text"><?php echo \esc_textarea($notification_settings['popup_subscribe_text']); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_subscribe_accept_button">متن دکمه تایید</label></th>
                            <td><input name="pk_subscribe_accept_button" type="text" id="pk_subscribe_accept_button"
                                       value="<?php echo \esc_attr($notification_settings['popup_subscribe_accept_button']); ?>"
                                       class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_subscribe_deny_button">متن دکمه لغو</label></th>
                            <td><input name="pk_subscribe_deny_button" type="text" id="pk_subscribe_deny_button"
                                       value="<?php echo \esc_attr($notification_settings['popup_subscribe_deny_button']); ?>"
                                       class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_subscribe_count">تعداد نمایش در فاصله زمانی</label></th>
                            <td><input name="pk_subscribe_count" type="number" id="pk_subscribe_count" value="<?php echo \esc_attr($notification_settings['popup_subscribe_count']); ?>" class="small-text">
                                <p class="description">حداکثر تعداد دفعاتی که پاپ‌آپ فعال‌سازی نوتیفیکیشن در فاصله زمانی مشخص به کاربر نمایش داده می‌شود.</p></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_subscribe_delay">فاصله زمانی نمایش (دقیقه)</label></th>
                            <td><input name="pk_subscribe_delay" type="number" id="pk_subscribe_delay" value="<?php echo \esc_attr($notification_settings['popup_subscribe_delay']); ?>" class="small-text">
                                <p class="description">حداقل فاصله زمانی بین نمایش‌های متوالی پاپ‌آپ به یک کاربر (بر حسب دقیقه).</p></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_subscribe_title_color">رنگ متن عنوان</label></th>
                            <td><input name="pk_subscribe_title_color" type="text"
                                       value="<?php echo \esc_attr($notification_settings['popup_subscribe_title_color']); ?>"
                                       class="pk-color-picker">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_subscribe_text_color">رنگ متن توضیحات</label></th>
                            <td><input name="pk_subscribe_text_color" type="text"
                                       value="<?php echo \esc_attr($notification_settings['popup_subscribe_text_color']); ?>"
                                       class="pk-color-picker">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_subscribe_accept_button_text_color">رنگ متن دکمه
                                    تایید</label></th>
                            <td><input name="pk_subscribe_accept_button_text_color" type="text"
                                       value="<?php echo \esc_attr($notification_settings['popup_subscribe_accept_button_text_color']); ?>"
                                       class="pk-color-picker">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_subscribe_deny_button_text_color">رنگ متن دکمه لغو</label>
                            </th>
                            <td><input name="pk_subscribe_deny_button_text_color" type="text"
                                       value="<?php echo \esc_attr($notification_settings['popup_subscribe_deny_button_text_color']); ?>"
                                       class="pk-color-picker">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_subscribe_deny_button_border_color">رنگ خط حاشیه دکمه
                                    لغو</label></th>
                            <td><input name="pk_subscribe_deny_button_border_color" type="text"
                                       value="<?php echo \esc_attr($notification_settings['popup_subscribe_deny_button_border_color']); ?>"
                                       class="pk-color-picker">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="postbox">
                <h3 class="hndle">محتوا و ظاهر دکمه شناور</h3>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <td>
                                <?php \wp_editor($notification_settings['subscribe_bell_content'], 'pk_subscribe_bell_content', ['textarea_rows' => 10]); ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="postbox">
                <h3 class="hndle">نوتیفیکیشن تایید فعالسازی نوتیفیکیشن</h3>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row">فعالسازی</th>
                            <td>
                                <label>
                                    <input type="checkbox" id="pk_welcome_notification_toggle"
                                           name="pk_welcome_notification_enabled"
                                           value="1" <?php checked($notification_settings['welcome_notification_enabled'] ?? 0, 1); ?>>
                                    ارسال یک پیام پس از فعالسازی موفق نوتیفکیشن برای کاربر
                                </label>
                                <p class="description">در صورت فعال بودن، یک نوتیفیکیشن تایید استاندارد بلافاصله پس از
                                    فعالسازی برای کاربر ارسال می‌شود.</p>
                                <p class="description">این قالب را می‌توان در صفحه قالب نوتیفیکیشن تغییر داد. (کاربران
                                    ثبت‌نام شده: pk_welcome_notification) - (کاربران مهمان:
                                    pk_welcome_notification_guest)</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="postbox pk-welcome-notification-fields">
                <h3 class="hndle">محتوای پاپ‌آپ تأیید فعالسازی نوتیف</h3>
                <div class="inside">
                    <p>بعد از اینکه کاربر نوتیفیکیشن را فعال می‌کند، یک پاپ‌آپ به کاربر نمایش داده می‌شود که مطمئن شویم
                        کاربر نوتیفیکیشن را به درستی دریافت کرده، در غیر اینصورت یک راهنمای متنی برای فعالسازی نوتیفیکشن
                        مرورگر کاربر در تنظیمات سیستم عامل نمایش داده می‌شود.</p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="pk_confirmation_title">عنوان</label></th>
                            <td><input name="pk_confirmation_title" type="text" id="pk_confirmation_title"
                                       value="<?php echo \esc_attr($notification_settings['popup_confirmation_title']); ?>"
                                       class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_confirmation_text">توضیحات</label></th>
                            <td><textarea name="pk_confirmation_text" id="pk_confirmation_text" rows="3"
                                          class="regular-text"><?php echo \esc_textarea($notification_settings['popup_confirmation_text']); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_confirmation_accept_button">متن دکمه تایید</label></th>
                            <td><input name="pk_confirmation_accept_button" type="text"
                                       id="pk_confirmation_accept_button"
                                       value="<?php echo \esc_attr($notification_settings['popup_confirmation_accept_button']); ?>"
                                       class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_confirmation_deny_button">متن دکمه لغو</label></th>
                            <td><input name="pk_confirmation_deny_button" type="text" id="pk_confirmation_deny_button"
                                       value="<?php echo \esc_attr($notification_settings['popup_confirmation_deny_button']); ?>"
                                       class="regular-text"></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div id="api" class="tab-content">
            <div id="pk-api-message-area">
                <?php settings_errors('pk-api-keys'); ?>
            </div>
            <div class="postbox">
                <h2 class="hndle"><span>مدیریت کلید API</span></h2>
                <div class="inside">
                    <p>برای استفاده از API، شما به یک کلید امن نیاز دارید. این کلید باید در هدر درخواست‌های شما ارسال
                        شود.</p>
                    <table class="form-table">
                        <tr>
                            <th scope="row">وضعیت کلید API</th>
                            <td>
                                <?php if (get_option('pk_api_key_hash')) : ?>
                                    <p style="color: green;"><strong>یک کلید API فعال برای شما تنظیم شده است.</strong>
                                    </p>
                                <?php else : ?>
                                    <p style="color: red;"><strong>هیچ کلید API تنظیم نشده است.</strong></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">ایجاد کلید جدید</th>
                            <td>
                                <form method="post" action="">
                                    <p class="description"><strong>هشدار:</strong> با کلیک روی این دکمه، یک کلید جدید
                                        ساخته شده و کلید قبلی (در صورت وجود) باطل خواهد شد.</p>
                                    <p><input type="submit" name="pk_generate_new_api_key" class="button button-primary"
                                              value="ساخت کلید API جدید"></p>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="pk_api_allowed_ips">لیست IP های مجاز</label></th>
                            <td>
                                <textarea name="pk_api_allowed_ips" id="pk_api_allowed_ips" class="regular-text ltr"
                                          rows="5" placeholder="8.8.8.8"><?php
                                    $allowed_ips = get_option('pk_api_allowed_ips', []);
                                    echo esc_textarea(implode("\n", $allowed_ips));
                                    ?></textarea>
                                <p class="description">
                                    هر آدرس IP را در یک خط جدید وارد کنید. فقط این IP ها و سرور شما مجاز به استفاده از
                                    API خواهند بود.
                                    <br>
                                    <strong>IP سرور شما:</strong>
                                    <code><?php echo esc_html($_SERVER['SERVER_ADDR'] ?? 'Not Available'); ?></code>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" name="pk_settings_submit" id="submit"
                                             class="button button-primary" value="ذخیره تنظیمات"></p>
                </div>
            </div>

            <div class="postbox">
                <h2 class="hndle"><span>راهنمای استفاده از API</span></h2>
                <div class="inside pk-api-docs">
                    <h3>۱. آدرس API (Endpoint)</h3>
                    <p>شما باید درخواست‌های خود را با متد <code>POST</code> به آدرس زیر ارسال کنید:</p>
                    <pre class="pk-code-display"><?php echo \esc_html(\get_rest_url(null, 'pwa-kit/v1/send/transactional')); ?></pre>

                    <h3>۲. احرازهویت (Authentication)</h3>
                    <p>احرازهویت از طریق کلید API انجام می‌شود. شما باید این کلید را در هدر درخواست خود و با فرمت Bearer
                        Token ارسال کنید.</p>
                    <ul>
                        <li><strong>نام هدر:</strong> <code>Authorization</code></li>
                        <li><strong>مقدار هدر:</strong> <code>Bearer YOUR_SECRET_API_KEY</code></li>
                    </ul>
                    <p>مثال هدر:</p>
                    <pre class="pk-code-display">Authorization: Bearer a1b2c3d4...x9y8z7</pre>

                    <h3>۳. پارامترها (Parameters)</h3>
                    <p>پارامترها باید به صورت یک آبجکت JSON در بدنه (Body) درخواست <code>POST</code> شما ارسال شوند.</p>
                    <h4>بخش <code>recipients</code> (گیرندگان):</h4>
                    <p>یکی از موارد زیر را مشخص کنید:</p>
                    <ul>
                        <li><code>"user_id": 123</code> (ارسال به تمام دستگاه‌های فعال کاربر با ID 123)</li>
                        <li><code>"user_ids": [123, 124, 125]</code> (ارسال به چندین کاربر)</li>
                        <li><code>"subscription_id": 456</code> (ارسال فقط به یک اشتراک خاص)</li>
                        <li><code>"subscription_ids": [456, 457, 458]</code> (ارسال به چند اشتراک خاص)</li>
                    </ul>
                    <h4>بخش <code>content</code> (محتوا):</h4>
                    <p>یکی از دو شیوه زیر را مشخص کنید:</p>
                    <ul>
                        <li><strong>ارسال با قالب:</strong> <code>"template_id": 15</code></li>
                        <li><strong>ارسال با محتوای سفارشی:</strong> <code>"payload": { "title": "...", "message":
                                "...", "url": "...", "image": "..." }</code></li>
                    </ul>

                    <h3>مثال کامل با کد PHP</h3>
                    <p>نمونه فراخوانی API از داخل کد وردپرس:</p>
                    <pre class="pk-code-display"><?php echo esc_html(
                            "function send_order_shipped_notification(\$user_id, \$order_id) {\n" .
                            "    \$api_key = 'YOUR_SECRET_API_KEY';\n" .
                            "    \$url = get_rest_url(null, 'pwa-kit/v1/send/transactional');\n\n" .
                            "    \$headers = [\n" .
                            "        'Authorization' => 'Bearer ' . \$api_key,\n" .
                            "        'Content-Type'  => 'application/json',\n" .
                            "    ];\n\n" .
                            "    \$body = [\n" .
                            "        'recipients' => ['user_id' => \$user_id],\n" .
                            "        'content' => [\n" .
                            "            'payload' => [\n" .
                            "                'title'   => 'سفارش شما ارسال شد!',\n" .
                            "                'message' => 'سفارش #' . \$order_id . ' هم‌اکنون در مسیر شماست.',\n" .
                            "                'url'     => home_url('/my-account/orders/' . \$order_id),\n" .
                            "            ]\n" .
                            "        ]\n" .
                            "    ];\n\n" .
                            "    wp_remote_post(\$url, [\n" .
                            "        'method'  => 'POST',\n" .
                            "        'headers' => \$headers,\n" .
                            "        'body'    => json_encode(\$body),\n" .
                            "    ]);\n" .
                            "}"
                        ); ?></pre>
                </div>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="pk_settings_submit" id="submit" class="button button-primary"
                   value="ذخیره تنظیمات">
        </p>
    </form>
</div>

<template id="pk-shortcut-template">
    <div class="pk-shortcut-item">
        <span class="pk-shortcut-handle dashicons dashicons-menu"></span>
        <div class="pk-shortcut-fields">
            <div class="pk-shortcut-fields-row">
                <input type="text" name="pk_shortcuts[__i__][name]" placeholder="* نام میانبر">
                <select name="pk_shortcuts[__i__][url]" class="pk-select-url pk-select2" style="width:100%">
                    <option value="<?php echo \home_url('/'); ?>">صفحه اصلی</option>
                    <?php foreach ($all_pages as $page): ?>
                        <option value="<?php echo \get_permalink($page->ID); ?>"><?php echo \esc_html($page->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pk-shortcut-fields-row">
                <div class="pk-shortcut-icon-preview"></div>
                <input type="text" name="pk_shortcuts[__i__][icons][0][src]" class="pk-shortcut-icon-url"
                       placeholder="آدرس آیکون">
                <select name="pk_shortcuts[__i__][icons][0][sizes]">
                    <?php foreach ($shortcut_icon_sizes as $size): ?>
                        <option value="<?php echo $size; ?>"><?php echo $size; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button button-small pk-shortcut-upload-button">انتخاب</button>
            </div>
        </div>
        <span class="pk-remove-item dashicons dashicons-trash"></span>
    </div>
</template>

<template id="pk-screenshot-template">
    <div class="pk-screenshot-item">
        <input type="text" name="pk_screenshots[__i__][src]" placeholder="آدرس اسکرین شات" class="widefat pk-ss-src">
        <select name="pk_screenshots[__i__][form_factor]">
            <option value="narrow">موبایل</option>
            <option value="wide">دسکتاپ</option>
        </select>
        <select name="pk_screenshots[__i__][sizes]">
            <?php foreach ($screenshot_sizes as $size): ?>
                <option value="<?php echo $size; ?>"><?php echo $size; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="button pk-ss-upload-button">انتخاب</button>
        <button type="button" class="button pk-remove-item">حذف</button>
    </div>
</template>

<svg id="pk-maskable-mask" style="position: absolute; width: 0; height: 0;">
    <defs>
        <mask id="mask-circle" maskUnits="objectBoundingBox" maskContentUnits="objectBoundingBox">
            <circle cx="0.5" cy="0.5" r="0.395" fill="white"/>
        </mask>
    </defs>
</svg>

<style>
    .pk-settings-wrap .form-table th {
        width: 200px;
        padding: 15px 10px;
    }

    .pk-settings-wrap .form-table td {
        padding: 10px;
    }

    .pk-settings-wrap .pk-settings-section {
        margin-top: 1.5rem;
    }

    .pk-settings-wrap .pk-section-title {
        font-size: 1.2rem;
        border-bottom: 1px solid #c3c4c7;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }

    .pk-settings-wrap .nav-tab {
        cursor: pointer
    }

    .pk-settings-wrap .postbox .hndle {
        cursor: default
    }

    .pk-field-group {
        margin-bottom: 15px
    }

    .pk-field-group strong {
        display: block;
        margin-bottom: 5px
    }

    .pk-image-uploader {
        display: flex;
        gap: 0.5rem
    }

    .pk-image-uploader .pk-image-url {
        margin-left: 0;
    }

    .pk-icon-preview-wrapper {
        width: 128px;
        height: 128px;
        margin: 12px 0 5px;
        background-color: #f0f0f1;
        border: 1px solid #ddd;
        position: relative
    }

    .pk-icon-preview-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: mask .2s ease, -webkit-mask .2s ease
    }

    .pk-icon-preview-wrapper.is-maskable img {
        -webkit-mask: url(#mask-circle);
        mask: url(#mask-circle)
    }

    .pk-tooltip {
        position: relative;
        display: inline-block;
        cursor: help;
        color: #0073aa;
        font-weight: 700
    }

    .pk-tooltip .pk-tooltip-text {
        visibility: hidden;
        width: 220px;
        background-color: #333;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px;
        position: absolute;
        z-index: 1;
        bottom: 125%;
        left: 50%;
        margin-left: -110px;
        opacity: 0;
        transition: opacity .3s
    }

    .pk-tooltip:hover .pk-tooltip-text {
        visibility: visible;
        opacity: 1
    }

    .pk-shortcut-item,
    .pk-screenshot-item {
        background: #f9f9f9;
        border: 1px solid #e5e5e5;
        padding: 10px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 10px
    }

    .pk-shortcut-handle {
        cursor: move;
        color: #888
    }

    .pk-shortcut-fields {
        flex-grow: 1
    }

    .pk-shortcut-fields-row {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-bottom: 5px
    }

    .pk-shortcut-fields-row input,
    .pk-shortcut-fields-row select {
        flex: 1
    }

    .pk-shortcut-icon-preview {
        width: 24px;
        height: 24px;
        border: 1px solid #ddd;
        background-size: contain;
        background-color: #fff;
        flex-shrink: 0
    }

    .pk-remove-item {
        cursor: pointer;
        color: #a00
    }

    #pk-screenshots-wrapper .pk-screenshot-item select,
    #pk-screenshots-wrapper .pk-screenshot-item input {
        flex: 1
    }

    .select2-container--default .select2-selection--multiple {
        border-color: #8c8f94 !important;
        padding: 1px
    }

    .select2-container {
        width: 350px !important;
    }

    .select2-container .select2-selection--multiple {
        min-height: 30px !important;
        padding-bottom: 5px !important
    }

    #tab-appearance .postbox {
        margin-top: 0;
    }

    .pk-code-display {
        background: #f5f5f5;
        border: 1px solid #ccc;
        padding: 10px;
        user-select: all;
        direction: ltr;
        text-align: left;
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    .pk-api-docs h3 {
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
        margin-top: 25px;
    }
</style>

<script>
    jQuery(document).ready(function ($) {
        // --- Tab Management (NEW LOGIC) ---
        function activateTab(tabId) {
            // Hide all content
            $('.tab-content').hide();
            // Deactivate all tabs
            $('.nav-tab').removeClass('nav-tab-active');

            // Activate the correct tab and content
            $('#' + tabId).show();
            $('.nav-tab[data-tab="' + tabId + '"]').addClass('nav-tab-active');

            // Update hidden input for saving
            $('input[name="current_tab"]').val(tabId);
        }

        // On Click
        $('.nav-tab-wrapper a').on('click', function (e) {
            e.preventDefault();
            const tabId = $(this).data("tab");
            activateTab(tabId);
            // Update URL without reloading the page
            const url = "?page=pk-settings&tab=" + tabId;
            window.history.replaceState({
                path: url
            }, '', url);
        });

        // On Page Load
        const initialTab = $('input[name="current_tab"]').val();
        activateTab(initialTab);

        // --- General UI ---
        $('.pk-color-picker').wpColorPicker();
        const shortNameInput = $("#pk_short_name"),
            counterSpan = $("#short_name_counter");

        function updateCounter() {
            if (shortNameInput.length) {
                counterSpan.text(`(${shortNameInput.val().length}/${shortNameInput.attr("maxlength")})`);
            }
        }

        updateCounter();
        shortNameInput.on("input", updateCounter);

        // --- Media Uploader (Universal) ---
        let mediaUploader;
        $('body').on('click', '.pk-upload-button, .pk-shortcut-upload-button, .pk-ss-upload-button', function (e) {
            e.preventDefault();
            const button = $(this);
            let targetInput;
            if (button.hasClass('pk-shortcut-upload-button')) {
                targetInput = button.closest('.pk-shortcut-fields-row').find('.pk-shortcut-icon-url');
            } else if (button.hasClass('pk-ss-upload-button')) {
                targetInput = button.siblings('.pk-ss-src');
            } else {
                targetInput = $('#' + button.data('target'));
            }

            mediaUploader = wp.media({
                title: 'انتخاب تصویر',
                button: {
                    text: 'انتخاب'
                },
                multiple: false
            })
                .on('select', function () {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    targetInput.val(attachment.url).trigger('input');
                    if (button.hasClass('pk-ss-upload-button')) {
                        button.siblings('select[name*="[sizes]"]').val(attachment.width + 'x' + attachment.height);
                    }
                }).open();
        });

        // --- PWA Icon Preview ---
        const purposeSelect = $('#pk_icon_purpose');

        function updateMaskableLink() {
            const icon512 = $("#pk_icon_512").val(),
                icon192 = $("#pk_icon_192").val();
            const targetIcon = icon512 || icon192 || '<?php echo \get_site_icon_url(512); ?>';
            const link = $("#pk-maskable-link");
            let baseUrl = 'https://maskable.app/';
            if (targetIcon) {
                baseUrl += '?demo=' + encodeURIComponent(targetIcon);
            }
            link.attr('href', baseUrl);
        }

        function updateAllPreviews() {
            const isMaskable = purposeSelect.val() === 'any maskable';
            $('#pwa-appearance .pk-icon-preview-wrapper').each(function () {
                const wrapper = $(this),
                    inputId = wrapper.data('preview-for'),
                    inputField = $('#' + inputId);
                let defaultUrl = '';
                if (inputId === 'pk_icon_192' || inputId === 'pk_default_icon') {
                    defaultUrl = '<?php echo \get_site_icon_url(192); ?>';
                } else if (inputId === 'pk_icon_512') {
                    defaultUrl = '<?php echo \get_site_icon_url(512); ?>';
                }
                const iconUrl = inputField.val() || defaultUrl;
                wrapper.find('img').attr('src', iconUrl);
                wrapper.toggleClass('is-maskable', isMaskable);
            });
        }

        $('input.pk-image-url').on('input', function () {
            const inputId = $(this).attr('id');
            const wrapper = $(`.pk-icon-preview-wrapper[data-preview-for="${inputId}"]`);
            const iconUrl = $(this).val();
            if (iconUrl) {
                wrapper.find('img').attr('src', iconUrl);
            }
            updateMaskableLink();
        });
        purposeSelect.on('change', updateAllPreviews);
        updateMaskableLink();
        updateAllPreviews();

        // --- Shortcuts Management ---
        const shortcutsWrapper = $("#pk-shortcuts-wrapper");
        const shortcutTemplate = $("#pk-shortcut-template").html();

        function initShortcutSelect2() {
            shortcutsWrapper.find(".pk-select-url:not(.select2-hidden-accessible)").select2({
                width: '100%',
                dropdownCssClass: 'pk-select2-dropdown'
            });
        }

        initShortcutSelect2();
        $("#pk-add-shortcut").on('click', function () {
            if (shortcutsWrapper.children('.pk-shortcut-item').length >= 4) {
                alert('حداکثر ۴ میانبر مجاز است.');
                return;
            }
            const newIndex = shortcutsWrapper.children().length;
            const newShortcut = shortcutTemplate.replace(/__i__/g, newIndex);
            shortcutsWrapper.append(newShortcut);
            initShortcutSelect2();
        });
        $('body').on('input', '.pk-shortcut-icon-url', function () {
            const iconUrl = $(this).val();
            $(this).closest('.pk-shortcut-fields-row').find('.pk-shortcut-icon-preview').css('background-image', `url(${iconUrl})`);
        });
        if (shortcutsWrapper.length) shortcutsWrapper.sortable({
            handle: '.pk-shortcut-handle',
            axis: 'y'
        });

        // --- Screenshots Management ---
        const screenshotsWrapper = $("#pk-screenshots-wrapper");
        const screenshotTemplate = $("#pk-screenshot-template").html();
        $("#pk-add-screenshot").on('click', function () {
            const newIndex = screenshotsWrapper.children().length;
            const newScreenshot = screenshotTemplate.replace(/__i__/g, newIndex);
            screenshotsWrapper.append(newScreenshot);
        });
        $('body').on('click', '.pk-remove-item', function () {
            $(this).closest('.pk-shortcut-item, .pk-screenshot-item').remove();
        });

        // --- Display Override Logic ---
        const allOverrides = <?php echo json_encode($all_display_overrides); ?>;
        const displaySelect = $('#pk_display');
        const overrideSelect = $('#pk_display_override');

        function updateOverrideOptions() {
            const currentDisplay = displaySelect.val();
            const currentValues = overrideSelect.val() || [];
            overrideSelect.empty();
            $.each(allOverrides, function (key, label) {
                if (key !== currentDisplay) {
                    const isSelected = currentValues.includes(key);
                    overrideSelect.append(new Option(label, key, isSelected, isSelected));
                }
            });
            overrideSelect.val(currentValues).trigger('change.select2');
        }

        overrideSelect.select2({
            width: '100%'
        }).on('select2:select', function (e) {
            const element = $(this).find('option[value="' + e.params.data.id + '"]');
            $(this).append(element);
            $(this).trigger('change');
        });
        overrideSelect.next().find('ul.select2-selection__rendered').sortable({
            containment: 'parent',
            update: function () {
                $(this).parent().prev().trigger('change');
            }
        });
        displaySelect.on('change', updateOverrideOptions);
        updateOverrideOptions();

        // --- General Select2 ---
        $(".pk-select2").select2({
            width: '100%'
        });

        // --- VAPID Keys Generation ---
        $('#pk-generate-keys-btn').on('click', function () {
            if (!confirm('آیا مطمئن هستید؟ این کار تمام مشترکین فعلی را از دسترس خارج می‌کند.')) return;
            const button = $(this);
            const spinner = button.siblings('.spinner');
            button.prop('disabled', true);
            spinner.addClass('is-active');

            $.post(ajaxurl, {
                action: 'pk_generate_vapid_keys',
                nonce: '<?php echo wp_create_nonce("pk_generate_vapid_nonce"); ?>'
            })
                .done(function (response) {
                    let messageType = response.success ? 'notice-success' : 'notice-error';
                    $('#pk-notification-message-area').html('<div class="notice ' + messageType + ' is-dismissible"><p>' + response.data.message + '</p></div>');
                    if (response.success) {
                        $('#pk_public_key').val(response.data.publicKey);
                        $('#pk_private_key').val(response.data.privateKey);
                    }
                })
                .fail(function () {
                    alert('خطا در ارتباط با سرور.');
                })
                .always(function () {
                    button.prop('disabled', false);
                    spinner.removeClass('is-active');
                });
        });

        // --- مدیریت نمایش فیلدهای متنی پاپ‌آپ PWA ---
        const popupStyleSelect = $('#pk_popup_style');
        const modalTexts = $('#pk-pwa-prompt-texts-modal');
        const bannerTexts = $('#pk-pwa-prompt-texts-banner');

        function togglePwaPromptFields() {
            const selectedStyle = popupStyleSelect.val();
            if (selectedStyle === 'modal') {
                modalTexts.show();
                bannerTexts.hide();
            } else {
                modalTexts.hide();
                bannerTexts.show();
            }
        }

        popupStyleSelect.on('change', togglePwaPromptFields);
        togglePwaPromptFields(); // اجرای اولیه

        // --- مدیریت نمایش فیلدهای پاپ‌آپ تایید ---
        const $welcomeToggle = $('#pk_welcome_notification_toggle');
        const $welcomeFields = $('.pk-welcome-notification-fields');

        function toggleWelcomeFields() {
            // اگر چک‌باکس فعال بود، فیلدها را با انیمیشن نمایش بده، در غیر این صورت مخفی کن
            $welcomeFields.toggle($welcomeToggle.is(':checked'));
        }

// اتصال رویداد change به چک‌باکس
        $welcomeToggle.on('change', toggleWelcomeFields);

// اجرای اولیه در زمان بارگذاری صفحه برای تنظیم وضعیت صحیح
        toggleWelcomeFields();
    });
</script>