<div class="wrap">
    <h1 class="wp-heading-inline">قالب‌های نوتیفیکیشن</h1>
    <a href="#" id="pk-add-new-template" class="page-title-action">افزودن قالب جدید</a>
    <hr class="wp-header-end">
    <div id="pk-ajax-message"></div>

    <form id="pk-templates-list-form" method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>"/>
        <?php
        $list_table->views();
        $list_table->search_box('جستجوی قالب‌ها', 'template-search-input');
        $list_table->display();
        ?>
    </form>
</div>

<div id="pk-template-modal" class="pk-modal hidden">
    <div class="pk-modal-backdrop"></div>
    <div class="pk-modal-content">
        <form id="pk-template-form">
            <div class="pk-modal-header">
                <h2 id="pk-modal-title">افزودن قالب جدید</h2>
                <button type="button" class="pk-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
            </div>
            <div class="pk-modal-body">
                <input type="hidden" id="pk-template-id" name="template_id" value="0">
                <?php \wp_nonce_field('pk_template_nonce', 'nonce'); ?>
                <div class="form-field"><label for="pk-internal-name">نام داخلی *</label><input type="text"
                                                                                                id="pk-internal-name"
                                                                                                name="template[internal_name]"
                                                                                                required></div>
                <div class="form-field"><label for="pk-title">عنوان نوتیفیکیشن *</label><input type="text" id="pk-title"
                                                                                               name="template[title]"
                                                                                               required maxlength="50">
                </div>
                <div class="form-field">
                    <label for="pk-message">متن پیام *</label>
                    <textarea id="pk-message" name="template[message]" rows="4" required maxlength="150"></textarea>
                </div>
                <div class="form-field" id="pk-guest-fallback-wrapper" style="display: none;">
                    <label for="pk-guest-fallback">متن پیش‌فرض برای مهمان *</label>
                    <input type="text" id="pk-guest-fallback" name="template[guest_fallback]" placeholder="مثال: دوست">
                    <p class="description">این متن به جای الگوهایی مانند {first_name} برای کاربرانی که عضو نیستند، نمایش
                        داده می‌شود.</p>
                </div>
                <div class="pk-patterns-guide">
                    <p><strong>الگوهای در دسترس (برای شخصی‌سازی):</strong></p>
                    <code>{first_name}</code>, <code>{last_name}</code>
                    <!-- , <code>{display_name}</code>, <code>{user_email}</code> -->
                </div>
                <div class="form-field"><label for="pk-url">آدرس URL (اختیاری)</label><input type="url" id="pk-url"
                                                                                             name="template[url]"
                                                                                             placeholder="<?php echo esc_url(\home_url()); ?>">
                </div>
                <div class="form-field">
                    <label for="pk-image">تصویر بزرگ (اختیاری)</label>
                    <div class="pk-image-uploader" style="display: flex; gap: 0.5rem;">
                        <input type="text" id="pk-image" name="template[image]" class="pk-image-url regular-text">
                        <button type="button" class="button button-secondary pk-upload-button" data-target="pk-image">
                            انتخاب
                        </button>
                    </div>
                    <div class="pk-image-preview-wrapper">
                        <img src="" alt="Image Preview" style="display: none;">
                        <a href="#" class="pk-remove-image-btn" style="display: none;">حذف تصویر</a>
                    </div>
                </div>
            </div>
            <div class="pk-modal-footer">
                <span class="spinner"></span>
                <button type="button" class="button button-secondary pk-modal-close">انصراف</button>
                <button type="submit" class="button button-primary">ذخیره قالب</button>
            </div>
        </form>
    </div>
</div>

<style>
    .pk-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 160;
        display: flex;
        align-items: center;
        justify-content: center
    }

    .pk-modal.hidden {
        display: none
    }

    .pk-modal-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, .7)
    }

    .pk-modal-content {
        position: relative;
        background: #fff;
        width: 90%;
        max-width: 600px;
        border-radius: 4px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, .3)
    }

    .pk-modal-header {
        padding: 15px 20px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center
    }

    .pk-modal-header h2 {
        margin: 0
    }

    .pk-modal-body {
        padding: 20px;
        max-height: 70vh;
        overflow-y: auto
    }

    .pk-modal-body .form-field {
        margin-bottom: 15px
    }

    .pk-modal-body label {
        font-weight: 600;
        display: block;
        margin-bottom: 5px
    }

    .pk-modal-body input[type=text],
    .pk-modal-body input[type=url],
    .pk-modal-body textarea {
        width: 100%
    }

    .pk-modal-footer {
        padding: 15px 20px;
        border-top: 1px solid #ddd;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px
    }

    .pk-modal-footer .spinner {
        order: -1;
        margin: 0
    }

    .pk-modal-close {
        border: none;
        background: none;
        cursor: pointer;
        padding: 5px;
        line-height: 1;
        color: #666
    }

    .pk-modal-close:hover {
        color: #000
    }

    .pk-template-thumbnail {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
        vertical-align: middle;
    }

    .pk-image-preview-wrapper {
        margin-top: 10px;
    }

    .pk-image-preview-wrapper img {
        max-width: 100%;
        height: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .pk-image-preview-wrapper .pk-remove-image-btn {
        color: #a00;
        text-decoration: none;
        display: block;
        margin-top: 5px;
    }

    .pk-template-thumbnail.pk-placeholder-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background-color: #f0f0f1;
        color: #a0a5aa;
        font-size: 24px;
    }

    .pk-patterns-guide {
        background-color: #f0f0f1;
        border: 1px solid #ddd;
        padding: 10px 15px;
        border-radius: 4px;
        font-size: 13px;
        margin-top: -15px;
    }

    .pk-patterns-guide p {
        margin-top: 0;
    }

    .pk-patterns-guide code {
        background: rgba(0, 0, 0, 0.07);
        padding: 2px 5px;
        border-radius: 3px;
    }

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

    mark.warning {
        background-color: #ffefcdff;
        color: #a98303ff;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
    }

    mark.info {
        background-color: #cdfdff;
        color: #0390a9;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
    }

    #pk-guest-fallback-wrapper .description {
        padding-bottom: 15px;
    }
</style>
<script>
    jQuery(document).ready(function ($) {
        const $modal = $('#pk-template-modal');
        const $form = $('#pk-template-form');

        function showMessage(message, type = 'success') {
            const className = type === 'success' ? 'notice-success' : 'notice-error';
            $('#pk-ajax-message').html(`<div class="notice ${className} is-dismissible"><p>${message}</p></div>`).show();
        }

        function updateImagePreview(container, url) {
            const $previewWrapper = $(container);
            const $img = $previewWrapper.find('img');
            const $removeBtn = $previewWrapper.find('.pk-remove-image-btn');
            if (url) {
                $img.attr('src', url).show();
                $removeBtn.show();
            } else {
                $img.hide().attr('src', '');
                $removeBtn.hide();
            }
        }

        function resetForm() {
            $form[0].reset();
            $('#pk-template-id').val('0');
            $('#pk-modal-title').text('افزودن قالب جدید');
            updateImagePreview($form.find('.pk-image-preview-wrapper'), '');
        }

        // --- Event Handlers ---
        $('#pk-add-new-template').on('click', function (e) {
            e.preventDefault();
            resetForm();
            $modal.removeClass('hidden');
        });
        $('.pk-modal-close, .pk-modal-backdrop').on('click', function () {
            $modal.addClass('hidden');
        });

        $('body').on('click', '.edit-template', function (e) {
            e.preventDefault();
            resetForm();
            const templateId = $(this).data('id');
            $.post(ajaxurl, {
                action: 'pk_handle_template_action',
                sub_action: 'get_template',
                template_id: templateId,
                nonce: $('#pk-template-form [name="nonce"]').val() // Get nonce from the form
            }).done(function (response) {
                if (response.success) {
                    const data = response.data;
                    $('#pk-modal-title').text('ویرایش قالب');
                    $('#pk-template-id').val(data.id);
                    $('#pk-internal-name').val(data.internal_name);
                    $('#pk-title').val(data.title);
                    $('#pk-message').val(data.message);
                    $('#pk-url').val(data.url);
                    $('#pk-image').val(data.image);
                    $('#pk-guest-fallback').val(data.guest_fallback);
                    checkPatterns();
                    updateImagePreview($form.find('.pk-image-preview-wrapper'), data.image);
                    $modal.removeClass('hidden');
                } else {
                    showMessage(response.data.message, 'error');
                }
            });
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            const $btn = $(this).find('button[type="submit"]'),
                $spinner = $(this).find('.spinner');
            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            const data = $(this).serialize() + '&action=pk_handle_template_action&sub_action=save';
            $.post(ajaxurl, data).done(function (response) {
                if (response.success) {
                    location.reload(); // Reload to see the new/updated template in the list
                } else {
                    alert(response.data.message);
                }
            }).fail(function () {
                alert('خطا در ارتباط با سرور.');
            }).always(function () {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            });
        });

        $('body').on('click', '.delete-template', function (e) {
            e.preventDefault();
            if (!confirm('آیا از حذف این قالب اطمینان دارید؟')) return;
            const templateId = $(this).data('id'),
                nonce = $(this).data('nonce');
            const $row = $(this).closest('tr');
            $.post(ajaxurl, {
                action: 'pk_handle_template_action',
                sub_action: 'delete',
                template_id: templateId,
                nonce: nonce
            })
                .done(function (response) {
                    if (response.success) {
                        $row.fadeOut(300, function () {
                            $(this).remove();
                        });
                        showMessage(response.data.message, 'success');
                    } else {
                        showMessage(response.data.message, 'error');
                    }
                });
        });

        // --- Universal Media Uploader (Corrected Logic) ---
        let mediaUploader;
        $('body').on('click', '.pk-upload-button', function (e) {
            e.preventDefault();
            const button = $(this);
            const targetInput = $('#' + button.data('target')); // Target input based on data-target attribute

            if (!targetInput.length) {
                //console.error('Uploader Error: Target input not found for button', button);
                return;
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
                }).open();
        });

        // Update preview when input value changes
        $('body').on('input', '.pk-image-url', function () {
            const url = $(this).val();
            const previewContainer = $(this).closest('.form-field').find('.pk-image-preview-wrapper');
            updateImagePreview(previewContainer, url);
        });

        // Remove image button functionality
        $('body').on('click', '.pk-remove-image-btn', function (e) {
            e.preventDefault();
            const previewContainer = $(this).closest('.pk-image-preview-wrapper');
            // Find the related input field and clear its value
            previewContainer.closest('.form-field').find('.pk-image-url').val('').trigger('input');
        });

        // --- مدیریت داینامیک فیلد متن جایگزین ---
        const $titleInput = $('#pk-title');
        const $messageInput = $('#pk-message');
        const $fallbackWrapper = $('#pk-guest-fallback-wrapper');
        const $fallbackInput = $('#pk-guest-fallback');

        function checkPatterns() {
            const titleText = $titleInput.val();
            const messageText = $messageInput.val();
            const hasPattern = /\{[a-zA-Z_]+\}/.test(titleText) || /\{[a-zA-Z_]+\}/.test(messageText);

            if (hasPattern) {
                $fallbackWrapper.slideDown();
                $fallbackInput.prop('required', true);
            } else {
                $fallbackWrapper.slideUp();
                $fallbackInput.prop('required', false);
            }
        }

        // بررسی در زمان ویرایش و همچنین در زمان تایپ
        $('body').on('input', '#pk-title, #pk-message', checkPatterns);

        // وقتی مودال ویرایش باز می‌شود نیز این تابع را فراخوانی می‌کنیم
        $('body').on('click', '.edit-template', function () {
            setTimeout(checkPatterns, 200); // با کمی تاخیر برای اطمینان از پر شدن فیلدها
        });
        $('#pk-add-new-template').on('click', checkPatterns);
    });
</script>