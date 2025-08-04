<?php
// pwa-kit/includes/class-pk-rest-api-controller.php
namespace PwaKit\API;

if (!defined('ABSPATH')) exit;

/**
 * Class PK_REST_API_Controller
 * این کلاس تمام endpoint های سفارشی REST API برای سیستم نوتیفیکیشن را مدیریت می‌کند.
 * این نسخه برای توسعه‌پذیری، امنیت و ارائه بازخورد دقیق طراحی شده است.
 */
class PK_REST_API_Controller
{

    /**
     * ثبت تمام مسیرهای API در وردپرس.
     */
    public static function init(): void
    {
        add_action('rest_api_init', function () {
            register_rest_route('pwa-kit/v1', '/send/transactional', [
                'methods' => 'POST',
                'callback' => [__CLASS__, 'handle_send_request'],
            ]);
        });
    }

    /**
     * منطق اصلی و هوشمند برای پردازش درخواست ارسال آنی.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    /**
     * منطق نهایی و هوشمند برای پردازش تمام درخواست‌های ارسال آنی.
     */
    public static function handle_send_request(\WP_REST_Request $request): \WP_REST_Response
    {
        $params = $request->get_json_params();
        $recipients = $params['recipients'] ?? [];
        $content = $params['content'] ?? [];

        if (empty($recipients) || empty($content)) {
            return new \WP_REST_Response(['status' => 'error', 'message' => 'Parameters "recipients" and "content" are required.'], 400);
        }

        // بررسی نوع محتوا: مبتنی بر قالب یا سفارشی
        if (isset($content['template_name'])) {
            $template_id = \PwaKit\Core\TemplateManager::get_or_create($content['template_name']);
            if (!$template_id) {
                return new \WP_REST_Response(['status' => 'error', 'message' => 'Template not found.'], 404);
            }
            $success = pk_send_transactional_notification($recipients, $template_id);
        } elseif (isset($content['payload'])) {
            $payload = (array)$content['payload'];
            $success = pk_send_custom_transactional_notification($recipients, $payload);
        } else {
            return new \WP_REST_Response(['status' => 'error', 'message' => 'Content must contain either "template_name" or "payload".'], 400);
        }

        if ($success) {
            return new \WP_REST_Response(['status' => 'success', 'message' => 'Notification request accepted.'], 202);
        } else {
            return new \WP_REST_Response(['status' => 'error', 'message' => 'Failed to process notification request. Check if recipients are valid.'], 500);
        }
    }
}
