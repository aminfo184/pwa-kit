<?php
// Helper function to find count from stats array
function pk_get_stat_count($stats, $status_key)
{
    foreach ($stats as $stat) {
        if ($stat->status === $status_key) {
            return number_format($stat->count);
        }
    }
    return 0;
}
?>
<div class="wrap">
    <h1>داشبورد وب اپلیکیشن</h1>

    <div id="dashboard-widgets-wrap">
        <div id="dashboard-widgets" class="metabox-holder columns-2">

            <div class="postbox-container">
                <div class="postbox">
                    <h2 class="hndle"><span>آمار مشترکین</span></h2>
                    <div class="inside">
                        <p><strong>فعال:</strong> <?php echo pk_get_stat_count($sub_stats, 'active'); ?></p>
                        <p><strong>منقضی شده:</strong> <?php echo pk_get_stat_count($sub_stats, 'expired'); ?></p>
                    </div>
                </div>
            </div>

            <div class="postbox-container">
                <div class="postbox">
                    <h2 class="hndle"><span>وضعیت صف ارسال</span></h2>
                    <div class="inside">
                        <p><strong>در انتظار:</strong> <?php echo pk_get_stat_count($queue_stats, 'queued'); ?></p>
                        <p><strong>در حال پردازش:</strong> <?php echo pk_get_stat_count($queue_stats, 'processing'); ?></p>
                        <p><strong>ارسال موفق:</strong> <?php echo pk_get_stat_count($queue_stats, 'sent'); ?></p>
                        <p><strong>ارسال ناموفق:</strong> <?php echo pk_get_stat_count($queue_stats, 'failed'); ?></p>
                    </div>
                </div>
            </div>

            <a href="<?php echo esc_url(pk_get_simple_manual_trigger_url()); ?>" target="_blank" class="button">
                اجرای دستی پردازشگر (مرورگر قفل می‌شود)
            </a>

            <a href="<?php echo esc_url(pk_get_manual_reset_url()); ?>" class="button button-secondary">
                ریست کردن قفل‌ها و مدارشکن سیستم ارسال
            </a>
        </div>
    </div>
</div>