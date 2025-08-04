<div class="wrap pk-campaign-wrap">
    <h1>ارسال کمپین جدید</h1>
    <p>در این صفحه می‌توانید یک نوتیفیکیشن جدید برای مشترکین خود ارسال یا زمان‌بندی کنید.</p>

    <div id="pk-ajax-message" style="display:none; margin-top: 15px;"></div>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">

            <div id="post-body-content">
                <form id="pk-campaign-form">
                    <?php \wp_nonce_field('pk_campaign_nonce', 'nonce'); ?>

                    <div class="postbox">
                        <h2 class="hndle"><span>۱. محتوای نوتیفیکیشن</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="pk-template-selector">انتخاب قالب *</label></th>
                                    <td><select id="pk-template-selector" name="template_id" style="width: 100%;" required>
                                            <option></option>
                                        </select></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle"><span>۲. مخاطبان هدف</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">ارسال به</th>
                                    <td>
                                        <fieldset id="pk-target-type-fieldset">
                                            <label><input type="radio" name="target_type" value="all" checked> همه مشترکین فعال</label><br>
                                            <label><input type="radio" name="target_type" value="registered"> فقط کاربران عضو</label><br>
                                            <label><input type="radio" name="target_type" value="guests"> فقط کاربران مهمان</label><br>
                                            <label><input type="radio" name="target_type" value="specific_users"> کاربران خاص</label>
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr id="pk-specific-users-row" style="display: none;">
                                    <th scope="row"><label for="pk-users-selector">انتخاب کاربران</label></th>
                                    <td>
                                        <select id="pk-users-selector" name="target_users[]" multiple="multiple" style="width: 100%;"></select>
                                        <p class="description">نام، نام کاربری یا ایمیل کاربر مورد نظر را جستجو کنید.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">یا ارسال با کوئری سفارشی</th>
                                    <td>
                                        <select id="pk-custom-query-selector" name="custom_query_key" style="width:100%; max-width:350px;">
                                            <option value="">-- انتخاب کوئری --</option>
                                            <?php $custom_queries = get_option('pk_custom_queries', []);
                                            foreach ($custom_queries as $key => $data) {
                                                printf('<option value="%s">%s</option>', esc_attr($key), esc_html($data['name']));
                                            } ?>
                                        </select>
                                        <p class="description">با انتخاب یک کوئری، گزینه‌های بالا نادیده گرفته می‌شوند.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle"><span>۳. زمان‌بندی</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="pk-schedule-time">زمان ارسال</label></th>
                                    <td><input type="datetime-local" id="pk-schedule-time" name="scheduled_for">
                                        <p class="description">اگر خالی بماند، بلافاصله ارسال می‌شود.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </form>
            </div>

            <div id="postbox-container-1" class="postbox-container">
                <div class="postbox" id="pk-submit-metabox">
                    <h2 class="hndle"><span>عملیات ارسال</span></h2>
                    <div class="inside">
                        <p>پس از اطمینان از تنظیمات، عملیات را شروع کنید. این فرآیند در پس‌زمینه انجام می‌شود و می‌توانید این صفحه را ببندید.</p>
                        <button type="button" id="pk-start-queuing-btn" class="button button-primary button-large">شروع عملیات صف‌بندی</button>

                        <div id="pk-progress-wrapper" style="display: none; margin-top: 20px;">
                            <strong id="pk-progress-status">در حال آماده‌سازی...</strong>
                            <div class="pk-progress-bar">
                                <div id="pk-progress-bar-inner" style="width: 0%;"></div>
                            </div>
                            <p id="pk-progress-text">0 / 0</p>
                        </div>
                    </div>
                </div>
                <div class="postbox">
                    <h2 class="hndle"><span>مدیریت کوئری‌های سفارشی</span></h2>
                    <div class="inside">
                        <p>کوئری‌های خود را برای هدف‌گذاری‌های پیچیده بسازید.</p>
                        <div id="pk-custom-queries-list-ui"></div>
                        <hr>
                        <div id="pk-custom-query-builder">
                            <h4 id="pk-query-builder-title">افزودن کوئری جدید</h4>
                            <input type="hidden" id="pk-custom-query-key" value="">
                            <p><label for="pk-custom-query-name">نام کوئری:</label><input type="text" id="pk-custom-query-name" class="widefat"></p>
                            <p><label for="pk-custom-query-sql">کوئری SQL:</label><textarea id="pk-custom-query-sql" class="widefat" rows="5"></textarea></p>
                            <p class="description">کوئری شما باید ستون `user_id` یا `ID` را برگرداند.</p>
                            <div id="pk-query-builder-actions">
                                <button type="button" id="pk-save-query-btn" class="button button-primary">اعتبارسنجی و ذخیره</button>
                                <button type="button" id="pk-cancel-edit-btn" class="button button-secondary" style="display: none;">لغو ویرایش</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    /* استایل‌های جدید و بهبود یافته */
    .pk-settings-section,
    .postbox {
        margin-bottom: 20px;
    }

    #pk-custom-queries-list-ui ul {
        margin: 0;
        padding: 0;
        list-style: none;
        border: 1px solid #ddd;
    }

    #pk-custom-queries-list-ui li {
        padding: 8px 12px;
        background: #fff;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    #pk-custom-queries-list-ui li:last-child {
        border-bottom: none;
    }

    #pk-custom-queries-list-ui .query-actions {
        white-space: nowrap;
    }

    #pk-custom-queries-list-ui .query-actions a {
        margin-right: 8px;
        color: #0073aa;
        cursor: pointer;
        text-decoration: none;
    }

    #pk-custom-queries-list-ui .query-actions a.delete {
        color: #a00;
    }

    #pk-query-builder-sql-wrapper .CodeMirror {
        border: 1px solid #ddd;
    }

    .pk-progress-bar {
        width: 100%;
        background-color: #ddd;
        border-radius: 4px;
        overflow: hidden;
        margin: 10px 0;
    }

    #pk-progress-bar-inner {
        width: 0;
        height: 20px;
        background-color: #0073aa;
        transition: width 0.3s ease;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // --- متغیر ویرایشگر کد را در بالاترین سطح تعریف می‌کنیم ---
        let sqlEditor = null;

        // --- Initialize Select2 & Code Editor ---
        $('#pk-template-selector').select2({
            placeholder: 'جستجو و انتخاب قالب...',
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: p => ({
                    action: 'pk_search_notification_templates',
                    nonce: $('#nonce').val(),
                    q: p.term
                }),
                processResults: d => ({
                    results: d.results
                })
            }
        });

        const $startButton = $('#pk-start-queuing-btn');
        const $progressWrapper = $('#pk-progress-wrapper');
        const $progressStatus = $('#pk-progress-status');
        const $progressBarInner = $('#pk-progress-bar-inner');
        const $progressText = $('#pk-progress-text');
        const $ajaxMessage = $('#pk-ajax-message');

        function updateProgress(processed, total) {
            const percentage = total > 0 ? (processed / total) * 100 : 0;
            $progressBarInner.css('width', percentage + '%');
            $progressText.text(`${processed.toLocaleString()} / ${total.toLocaleString()}`);
        }

        function processBatch() {
            $progressStatus.text('در حال افزودن به صف (لطفاً منتظر بمانید)...');

            $.post(ajaxurl, {
                    action: 'pk_process_queue_batch',
                    nonce: $('#nonce').val()
                })
                .done(function(response) {
                    if (!response.success) {
                        $progressStatus.text('خطا!');
                        $ajaxMessage.html(`<div class="notice notice-error is-dismissible"><p>${response.data.message}</p></div>`).show();
                        $startButton.prop('disabled', false);
                        return;
                    }

                    updateProgress(response.data.processed, response.data.total);

                    if (response.data.status === 'processing') {
                        // اگر کار تمام نشده، دسته بعدی را فراخوانی کن
                        processBatch();
                    } else {
                        // اگر کار تمام شده
                        $progressStatus.text('عملیات با موفقیت به پایان رسید!');
                        $ajaxMessage.html(`<div class="notice notice-success is-dismissible"><p>تمام نوتیفیکیشن‌ها با موفقیت در صف قرار گرفتند.</p></div>`).show();
                        $startButton.prop('disabled', false);
                    }
                })
                .fail(function() {
                    $progressStatus.text('خطای ارتباط با سرور!');
                    $startButton.prop('disabled', false);
                });
        }

        $startButton.on('click', function() {
            if (!$('#pk-template-selector').val()) {
                alert('لطفاً یک قالب نوتیفیکیشن انتخاب کنید.');
                return;
            }

            $startButton.prop('disabled', true);
            $ajaxMessage.hide();
            $progressWrapper.show();
            $progressStatus.text('در حال آماده‌سازی...');
            updateProgress(0, 0);

            // **اصلاحیه اصلی: ارسال تمام داده‌های هدف‌گذاری**
            const postData = {
                action: 'pk_initiate_queue_job',
                nonce: $('#nonce').val(),
                template_id: $('#pk-template-selector').val(),
                scheduled_for: $('#pk-schedule-time').val(),
                target_type: $('input[name="target_type"]:checked').val(),
                custom_query_key: $('#pk-custom-query-selector').val(),
                target_users: $('#pk-users-selector').val()
            };

            $.post(ajaxurl, postData)
                .done(function(response) {
                    if (response.success) {
                        $ajaxMessage.html(`<div class="notice notice-info is-dismissible"><p>${response.data.message}</p></div>`).show();
                        updateProgress(0, response.data.total);
                        // شروع پردازش اولین دسته
                        processBatch();
                    } else {
                        $ajaxMessage.html(`<div class="notice notice-error is-dismissible"><p>${response.data.message}</p></div>`).show();
                        $startButton.prop('disabled', false);
                    }
                })
                .fail(function() {
                    alert('خطا در ارتباط با سرور.');
                    $startButton.prop('disabled', false);
                });
        });

        // --- Initialize User Search Select2 ---
        $('#pk-users-selector').select2({
            placeholder: 'جستجوی کاربر...',
            minimumInputLength: 2,
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        action: 'pk_search_users_with_subscription',
                        nonce: $('#nonce').val(),
                        q: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.data.results
                    };
                }
            }
        });

        // --- UI Logic to show/hide user selector ---
        $('input[name="target_type"]').on('change', function() {
            $('#pk-specific-users-row').toggle($(this).val() === 'specific_users');
        });

        // **اصلاحیه اصلی:** ویرایشگر را به درستی مقداردهی اولیه می‌کنیم
        if (typeof wp.codeEditor !== 'undefined' && typeof pk_code_editor_settings !== 'undefined') {
            sqlEditor = wp.codeEditor.initialize($('#pk-custom-query-sql'), pk_code_editor_settings).codemirror;
        } else {
            //console.warn('PWA Kit: Code editor scripts not loaded. Falling back to simple textarea.');
        }

        // --- Custom Query Management (Full AJAX) ---
        const queriesListUI = $('#pk-custom-queries-list-ui');
        const queryBuilderTitle = $('#pk-query-builder-title');
        const queryKeyInput = $('#pk-custom-query-key');
        const queryNameInput = $('#pk-custom-query-name');
        const querySqlTextarea = $('#pk-custom-query-sql');
        const cancelBtn = $('#pk-cancel-edit-btn');

        function renderQueries(queries) {
            let html = '<ul>';
            if ($.isEmptyObject(queries)) {
                html += '<li>هیچ کوئری ذخیره نشده است.</li>';
            } else {
                $.each(queries, function(key, data) {
                    html += `<li data-key="${key}" data-query="${escape(data.query)}">
                            <span>${data.name}</span>
                            <span class="query-actions">
                                <a class="view">مشاهده</a> | <a class="edit">ویرایش</a> | <a class="delete">حذف</a>
                            </span>
                         </li>`;
                });
            }
            html += '</ul>';
            queriesListUI.html(html);
            $('#pk-custom-query-selector').html('<option value="">-- انتخاب کوئری --</option>' + Object.keys(queries).map(k => `<option value="${k}">${queries[k].name}</option>`).join(''));
        }

        function resetQueryBuilder() {
            queryBuilderTitle.text('افزودن کوئری جدید');
            queryKeyInput.val('');
            queryNameInput.val('');
            sqlEditor ? sqlEditor.setValue('') : querySqlTextarea.val('');
            cancelBtn.hide();
        }

        function manageQuery(data) {
            const button = $('#pk-save-query-btn');
            button.prop('disabled', true).text('در حال پردازش...');

            $.post(ajaxurl, {
                    ...data,
                    action: 'pk_manage_custom_query',
                    nonce: $('#nonce').val()
                })
                .done(function(res) {
                    if (res.success) {
                        renderQueries(res.data.queries);
                        resetQueryBuilder();
                    }
                    alert(res.data.message);
                }).fail(() => alert('خطا در ارتباط با سرور.'))
                .always(() => button.prop('disabled', false).text('اعتبارسنجی و ذخیره'));
        }

        $('#pk-save-query-btn').on('click', function() {
            // **اصلاحیه اصلی:** قبل از استفاده، از وجود ویرایشگر مطمئن می‌شویم
            const queryValue = sqlEditor ? sqlEditor.getValue() : querySqlTextarea.val();
            manageQuery({
                query_action: 'save',
                key: queryKeyInput.val(),
                name: queryNameInput.val(),
                query: queryValue
            });
        });

        cancelBtn.on('click', resetQueryBuilder);

        queriesListUI.on('click', '.edit', function() {
            const item = $(this).closest('li');
            queryBuilderTitle.text('ویرایش کوئری');
            queryKeyInput.val(item.data('key'));
            queryNameInput.val(item.find('span:first').text());
            sqlEditor ? sqlEditor.setValue(unescape(item.data('query'))) : querySqlTextarea.val(unescape(item.data('query')));
            cancelBtn.show();
            queryNameInput.focus();
        });

        queriesListUI.on('click', '.delete', function() {
            if (confirm('آیا از حذف این کوئری اطمینان دارید؟')) {
                manageQuery({
                    query_action: 'delete',
                    key: $(this).closest('li').data('key')
                });
            }
        });

        queriesListUI.on('click', '.view', function() {
            alert(unescape($(this).closest('li').data('query')));
        });

        renderQueries(<?php echo json_encode($custom_queries); ?>);

        // --- Form Submission (FINAL REWRITTEN LOGIC) ---
        $('#pk-submit-campaign').on('click', function() {
            const $form = $('#pk-campaign-form');
            const $btn = $(this),
                $spinner = $btn.siblings('.spinner');

            if (!$('#pk-template-selector').val()) {
                alert('لطفاً یک قالب نوتیفیکیشن انتخاب کنید.');
                return;
            }
            if (!confirm('آیا از افزودن این کمپین به صف ارسال اطمینان دارید؟')) return;

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');

            // داده‌های فرم را به صورت دستی جمع‌آوری می‌کنیم تا کنترل کامل داشته باشیم
            const postData = {
                action: 'pk_queue_campaign',
                nonce: $('#nonce').val(),
                template_id: $('#pk-template-selector').val(),
                scheduled_for: $('#pk-schedule-time').val(),
                target_type: $('input[name="target_type"]:checked').val(),
                custom_query_key: $('#pk-custom-query-selector').val(),
                target_users: $('#pk-users-selector').val()
            };

            $.post(ajaxurl, postData).done(function(response) {
                    let message = response.data.message;
                    if (response.data.debug_query) {
                        message += '<br><code style="display:block; direction:ltr; text-align:left; margin-top:10px; padding: 5px; background: #f0f0f1;">' + response.data.debug_query + '</code>';
                    }
                    const messageType = response.success ? 'success' : 'error';
                    $('#pk-ajax-message').html(`<div class="notice notice-${messageType} is-dismissible"><p>${message}</p></div>`).show();
                    if (response.success) {
                        $form[0].reset();
                        $('#pk-template-selector').val(null).trigger('change');
                        $('#pk-custom-query-selector').val('').trigger('change');
                    }
                    $('html, body').animate({
                        scrollTop: 0
                    }, 'slow');
                }).fail(() => alert('خطا در ارتباط با سرور.'))
                .always(() => {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
        });
    });
</script>