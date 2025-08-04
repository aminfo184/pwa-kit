<?php
if (!defined('ABSPATH')) exit;

/**
 * @var PwaKit\Admin\Subscribers_List_Table $list_table
 * این متغیر از فایل class-admin-manager.php پاس داده شده است.
 */
?>
<div class="wrap">
    <h1 class="wp-heading-inline">مدیریت مشترکین</h1>

    <hr class="wp-header-end">

    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
        <?php
        // این تابع استاندارد وردپرس، جعبه جستجو را نمایش می‌دهد
        $list_table->search_box('جستجوی مشترکین', 'subscriber-search-input');
        ?>
    </form>

    <form method="post">
        <?php
        // این تابع، جدول کامل مشترکین را با تمام قابلیت‌ها (صفحه‌بندی، لینک‌ها و...) نمایش می‌دهد.
        $list_table->display();
        ?>
    </form>
</div>
<style>
    /* استایل‌های کوچک برای خوانایی بهتر وضعیت */
    mark.success {
        background-color: #c8e6c9;
        color: #255d28;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
    }

    mark.error {
        background-color: #ffcdd2;
        color: #c62828;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
</style>