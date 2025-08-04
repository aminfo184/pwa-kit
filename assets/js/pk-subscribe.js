(function () {
        "use strict";

        function initSubscribeMain() {

            localStorage.removeItem("pk_prompt_active");

            if (!("serviceWorker" in navigator) || typeof window.pk_subscribe_data === "undefined") {
                return;
            }

            const config = window.pk_subscribe_data;
            const modal = document.getElementById("pk-subscribe-modal-wrapper");
            const bellWrapper = document.getElementById("pk-subscribe-bell-wrapper");
            const unblockInstructionsModal = document.getElementById("pk-unblock-instructions");
            const confirmationModal = document.getElementById("pk-confirmation-modal");

            if (!modal || !bellWrapper || !unblockInstructionsModal) return;

            let acceptButton, denyButton, closeButton, overlay;
            const bellIcon = bellWrapper.querySelector(".pk-subscribe-bell");

            const lsKeys = {
                windowStart: "pk_notification_window_start",
                viewCount: "pk_notification_view_count",
                userDismissed: "pk_notification_user_dismissed",
            };

            const isIOS = () => /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

            function isEligibleToShow() {
                if (Notification.permission !== 'default') return false;

                if (isIOS() && !isInStandaloneMode()) return false;

                const now = new Date().getTime();
                let windowStart = parseInt(localStorage.getItem(lsKeys.windowStart) || "0");
                let viewCount = parseInt(localStorage.getItem(lsKeys.viewCount) || "0");

                if (now - windowStart > config.popup_delay * 60 * 1000) {
                    windowStart = now;
                    viewCount = 0;
                    localStorage.setItem(lsKeys.windowStart, windowStart);
                }

                if (viewCount < config.popup_count) {
                    localStorage.setItem(lsKeys.viewCount, viewCount + 1);
                    return true;
                }

                return false;
            }

            function showConfirmationModal() {
                if (!confirmationModal) return;
                confirmationModal.innerHTML = `
                <div class="pk-modal-overlay"></div>
                <div class="pk-modal-content">
                    <div class="pk-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path d="M396.138,85.295c-13.172-25.037-33.795-45.898-59.342-61.03C311.26,9.2,280.435,0.001,246.98,0.001   c-41.238-0.102-75.5,10.642-101.359,25.521c-25.962,14.826-37.156,32.088-37.156,32.088c-4.363,3.786-6.824,9.294-6.721,15.056   c0.118,5.77,2.775,11.186,7.273,14.784l35.933,28.78c7.324,5.864,17.806,5.644,24.875-0.518c0,0,4.414-7.978,18.247-15.88   c13.91-7.85,31.945-14.173,58.908-14.258c23.517-0.051,44.022,8.725,58.016,20.717c6.952,5.941,12.145,12.594,15.328,18.68   c3.208,6.136,4.379,11.5,4.363,15.574c-0.068,13.766-2.742,22.77-6.603,30.442c-2.945,5.729-6.789,10.813-11.738,15.744   c-7.384,7.384-17.398,14.207-28.634,20.479c-11.245,6.348-23.365,11.932-35.612,18.68c-13.978,7.74-28.77,18.858-39.701,35.544   c-5.449,8.249-9.71,17.686-12.416,27.641c-2.742,9.964-3.98,20.412-3.98,31.071c0,11.372,0,20.708,0,20.708   c0,10.719,8.69,19.41,19.41,19.41h46.762c10.719,0,19.41-8.691,19.41-19.41c0,0,0-9.336,0-20.708c0-4.107,0.467-6.755,0.917-8.436   c0.773-2.512,1.206-3.14,2.47-4.668c1.29-1.452,3.895-3.674,8.698-6.331c7.019-3.946,18.298-9.276,31.07-16.176   c19.121-10.456,42.367-24.646,61.972-48.062c9.752-11.686,18.374-25.758,24.323-41.968c6.001-16.21,9.242-34.431,9.226-53.96   C410.243,120.761,404.879,101.971,396.138,85.295z"/>
                            <path d="M228.809,406.44c-29.152,0-52.788,23.644-52.788,52.788c0,29.136,23.637,52.772,52.788,52.772   c29.136,0,52.763-23.636,52.763-52.772C281.572,430.084,257.945,406.44,228.809,406.44z"/>
                        </svg>
                    </div>
                    <h2>${config.confirmation_title}</h2>
                    <p>${config.confirmation_text}</p>
                    <div class="pk-modal-buttons">
                        <button id="pk-confirm-no" class="pk-modal-button-deny">${config.confirmation_deny_button}</button>
                        <button id="pk-confirm-yes" class="pk-modal-button-accept">${config.confirmation_accept_button}</button>        
                    </div>
                </div>
                <style>
                    #pk-confirmation-modal .pk-modal-content { background-color:${config.background_color} !important; }
                    #pk-confirmation-modal .pk-modal-content .pk-modal-icon { background-color:${config.theme_color}; }
                    #pk-confirmation-modal .pk-modal-content .pk-modal-icon svg { fill: ${config.accept_button_text_color}; }
                    #pk-confirmation-modal .pk-modal-content h2 { color:${config.title_color} !important; }
                    #pk-confirmation-modal .pk-modal-content p { color:${config.text_color} !important; }
                    #pk-confirmation-modal .pk-modal-content .pk-modal-buttons .pk-modal-button-deny { color:${config.deny_button_text_color}; border-color: ${config.deny_button_border_color}; }
                    #pk-confirmation-modal .pk-modal-content .pk-modal-buttons .pk-modal-button-accept { background-color:${config.theme_color}; border-color:${config.theme_color}; color:${config.accept_button_text_color}; }
                </style>`;
                confirmNoBtn = confirmationModal.querySelector("#pk-confirm-no");
                confirmYesBtn = confirmationModal.querySelector("#pk-confirm-yes");

                confirmYesBtn.addEventListener('click', () => {
                    hideConfirmationModal();
                });

                confirmNoBtn.addEventListener('click', async () => {
                    hideConfirmationModal();
                    await displayUnblockInstructions("os_blocked");
                });

                confirmationModal.classList.add('visible');
            }

            function hideConfirmationModal() {
                if (!confirmationModal) return;
                confirmationModal.classList.remove('visible');
            }

            function showModal() {
                if (Notification.permission !== "default") return;

                modal.innerHTML = `
                <div class="pk-modal-overlay"></div>
                <div class="pk-modal-content">
                    <button class="pk-modal-close">&times;</button>
                    <div class="pk-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M21,19V20H3V19L5,17V11C5,7.9 7.03,5.17 10,4.29V4A2,2 0 0,1 12,2A2,2 0 0,1 14,4V4.29C16.97,5.17 19,7.9 19,11V17L21,19M12,22A2,2 0 0,1 10,20H14A2,2 0 0,1 12,22Z"/>
                        </svg>
                    </div>
                    <h2>${config.title}</h2>
                    <p>${config.text}</p>
                    <div class="pk-modal-buttons">
                        <button class="pk-modal-button-deny">${config.deny_button}</button>
                        <button class="pk-modal-button-accept">${config.accept_button}</button>
                    </div>
                </div>
                <style>
                    #pk-subscribe-modal-wrapper .pk-modal-content { background-color:${config.background_color}; }
                    #pk-subscribe-modal-wrapper .pk-modal-content .pk-modal-close { color: ${config.text_color}; }
                    #pk-subscribe-modal-wrapper .pk-modal-content .pk-modal-icon { background-color:${config.theme_color}; }
                    #pk-subscribe-modal-wrapper .pk-modal-content .pk-modal-icon svg { fill: ${config.accept_button_text_color}; }
                    #pk-subscribe-modal-wrapper .pk-modal-content h2 { color:${config.title_color} !important; }
                    #pk-subscribe-modal-wrapper .pk-modal-content p { color:${config.text_color}; }
                    #pk-subscribe-modal-wrapper .pk-modal-content .pk-modal-buttons .pk-modal-button-deny { color:${config.deny_button_text_color}; border-color: ${config.deny_button_border_color}; }
                    #pk-subscribe-modal-wrapper .pk-modal-content .pk-modal-buttons .pk-modal-button-accept { background-color:${config.theme_color}; border-color:${config.theme_color}; color:${config.accept_button_text_color}; }
                </style>`;
                modal.style.display = "flex";
                localStorage.setItem("pk_prompt_active", "true");

                acceptButton = modal.querySelector(".pk-modal-button-accept");
                denyButton = modal.querySelector(".pk-modal-button-deny");
                closeButton = modal.querySelector(".pk-modal-close");
                overlay = modal.querySelector(".pk-modal-overlay");

                acceptButton.addEventListener("click", acceptPrompt);
                denyButton.addEventListener("click", handleDismiss);
                closeButton.addEventListener("click", handleDismiss);
                overlay.addEventListener("click", handleDismiss);

                setTimeout(() => {
                    modal.classList.add("visible")
                }, 50);
            }

            function hideModal() {
                localStorage.removeItem("pk_prompt_active");
                modal.classList.remove("visible");
                setTimeout(() => {
                    modal.style.display = "none";
                }, 300);
            }

            function handleDismiss() {
                localStorage.setItem(lsKeys.userDismissed, 'true');
                hideModal();
                showBellIcon();
            }

            function showBellIcon() {
                if (isIOS() && !isInStandaloneMode()) {
                    bellWrapper.style.display = 'none';
                    return;
                }
                const permission = Notification.permission;
                if (permission === 'granted') {
                    bellWrapper.style.display = 'none';
                } else if (permission === 'denied') {
                    bellWrapper.style.display = 'block';
                } else {
                    const dismissed = localStorage.getItem(lsKeys.userDismissed) === 'true';
                    const viewCount = parseInt(localStorage.getItem(lsKeys.viewCount) || "0");
                    const maxViews = config.popup_count; // حداکثر تعداد نمایش پاپ‌آپ

                    const seenUnsupported = localStorage.getItem('pk_seen_unsupported_prompt') === 'true';
                    if (dismissed || viewCount >= maxViews || seenUnsupported) {
                        bellWrapper.style.display = 'block';
                    } else {
                        bellWrapper.style.display = 'none';
                    }
                }

                bellWrapper.innerHTML = config.subscribe_bell_content;
            }

            /**
             * A utility to get the service worker registration with a timeout.
             * @param {number} timeout - The timeout in milliseconds.
             * @returns {Promise<ServiceWorkerRegistration>}
             */
            function getServiceWorkerReadyWithTimeout(timeout) {
                return new Promise((resolve, reject) => {
                    const timer = setTimeout(() => {
                        // --- منطق جدید در صورت عدم بارگذاری سرویس ورکر ---

                        // ۱. نمایش پیام خطا به کاربر
                        alert('فعالسازی سیستم نوتیفیکیشن با مشکل مواجه شد. لطفا اتصال اینترنت خود را بررسی کرده و صفحه را دوباره بارگذاری کنید.');

                        // ۲. پاک کردن لوکال استوریج‌های مربوط به نوتیفیکیشن تا کاربر بتواند دوباره تلاش کند
                        // این کد فرض می‌کند که آبجکت lsKeys در محدوده قابل دسترس است
                        for (const key in lsKeys) {
                            localStorage.removeItem(lsKeys[key]);
                        }
                        showBellIcon()

                        // --- پایان منطق جدید ---

                        // ۳. فرآیند را با خطا متوقف می‌کند تا کدهای بعدی اجرا نشوند
                        reject(new Error(`سرویس ورکر در مدت زمان ${timeout} میلی‌ثانیه آماده نشد.`));

                    }, timeout);

                    navigator.serviceWorker.ready
                        .then(registration => {
                            // اگر سرویس ورکر با موفقیت آماده شد، تایمر را متوقف می‌کنیم
                            clearTimeout(timer);
                            resolve(registration);
                        })
                        .catch(err => {
                            // اگر در فرآیند آماده‌سازی خطایی رخ داد، باز هم تایمر را متوقف می‌کنیم
                            clearTimeout(timer);
                            reject(err);
                        });
                });
            }

            async function acceptPrompt() {
                if (!isWebPushSupportedEnv()) {
                    localStorage.setItem('pk_seen_unsupported_prompt', 'true');
                    displayUnblockInstructions("push_notifications_unsupported");
                    hideModal();
                    showBellIcon()
                    return;
                }

                acceptButton.disabled = true;
                acceptButton.classList.add('is-loading');

                const resetButtonState = () => {
                    acceptButton.disabled = false;
                    acceptButton.classList.remove('is-loading');
                };

                try {
                    const permission = await Notification.requestPermission();

                    // مرحله ۲: نتیجه مجوز را مدیریت کن.
                    if (permission === "granted") {
                        await subscribeUser();

                    } else if (permission === "denied") {
                        // اگر کاربر درخواست را رد کرد
                        hideModal();
                        setTimeout(() => {
                            displayUnblockInstructions();
                        }, 300);
                        showBellIcon();

                    } else { // 'default' - یعنی کاربر پاپ‌آپ مرورگر را بسته است
                        handleDismiss();
                    }

                } catch (error) {
                    // در صورت بروز هرگونه خطا، به حالت اولیه برگرد
                    // console.error("خطا در درخواست مجوز یا اشتراک:", error);
                    handleDismiss();
                } finally {
                    // در هر صورت، دکمه را به حالت اولیه برگردان
                    resetButtonState();
                }
            }

            async function subscribeUser() {
                try {
                    bellWrapper.style.display = "none";
                    const swReg = await getServiceWorkerReadyWithTimeout(5000);
                    let subscription = await swReg.pushManager.getSubscription();
                    if (!subscription) {
                        subscription = await swReg.pushManager.subscribe({
                            userVisibleOnly: true, applicationServerKey: urlBase64ToUint8Array(config.public_key),
                        });
                    }
                    const serverResponse = await sendSubscriptionToServer(subscription);
                    hideModal();
                    if (serverResponse.success && config.welcome_notification_enabled) {
                        setTimeout(showConfirmationModal, 4000);
                    }
                } catch (error) {
                    //console.error("PWA Kit: Failed to subscribe user.", error);
                    showBellIcon();
                }
            }

            async function syncSubscriptionState() {
                if (Notification.permission !== 'granted' || document.cookie.includes('pk_subscription_synced=true')) {
                    return;
                }
                // console.log("PWA Kit: Sync check initiated. Permission is granted but sync cookie is missing.");
                try {
                    const swReg = await getServiceWorkerReadyWithTimeout(5000);
                    const subscription = await swReg.pushManager.getSubscription();
                    if (subscription) {
                        // حالت ۱: اشتراک زامبی - در مرورگر هست، به سرور می‌فرستیم
                        await sendSubscriptionToServer(subscription);
                    } else {
                        // حالت ۲: وضعیت متناقض - اجازه هست ولی اشتراک نیست. یک اشتراک جدید می‌سازیم.
                        //console.log("PWA Kit: Permission granted, but no subscription object. Re-subscribing automatically.");
                        const newSubscription = await swReg.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: urlBase64ToUint8Array(config.public_key),
                        });
                        if (newSubscription) {
                            await sendSubscriptionToServer(newSubscription);
                        }
                    }
                } catch (error) {
                    //console.error("PWA Kit: Error during subscription sync:", error);
                    showBellIcon();
                }
            }

            async function sendSubscriptionToServer(subscription) {
                const formData = new FormData();
                formData.append("action", "pk_save_subscription");
                formData.append("nonce", config.nonce);
                formData.append("subscription", JSON.stringify(subscription));
                try {
                    const response = await fetch(config.ajax_url, {
                        method: "POST", body: formData,
                    });
                    const data = await response.json();
                    if (data.success) {
                        //console.log("PWA Kit: Subscription saved/synced successfully.");
                        // --- اصلاحیه کلیدی: کوکی را در سمت کاربر نیز تنظیم می‌کنیم ---
                        document.cookie = `pk_subscription_synced=true;max-age=31536000;path=/;samesite=Lax`;
                        if (data.data && data.data.guest_token) {
                            document.cookie = `pk_guest_token=${data.data.guest_token};max-age=31536000;path=/;samesite=Lax`;
                        }
                        // console.log("PWA Kit: Subscription saved successfully.");
                    } else {
                        //console.error("PWA Kit: Failed to save subscription on server.", data.data.message);
                    }
                    return data;
                } catch (error) {
                    //console.error("PWA Kit: Error sending subscription to server.", error);
                    return {success: false};
                }
            }

            function urlBase64ToUint8Array(base64String) {
                if (!base64String) return new Uint8Array();
                const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
                const base64 = (base64String + padding)
                    .replace(/-/g, "+")
                    .replace(/_/g, "/");
                const rawData = window.atob(base64);
                const outputArray = new Uint8Array(rawData.length);
                for (let i = 0; i < rawData.length; ++i) {
                    outputArray[i] = rawData.charCodeAt(i);
                }
                return outputArray;
            }

            async function getPlatformInstructions(mode = "browser_blocked") {
                // This part of the code detects the user's device information like OS and browser.
                // It uses the 'ua-parser-js' library to get these details.
                const parser = new UAParser();
                const result = parser.getResult();
                const info = await (result.withClientHints?.() ?? Promise.resolve(result));

                const os = (info.os.name || "Unknown").toLowerCase();
                const osVersion = info.os.version || "0";
                const browser = (result.browser.name || "Unknown").toLowerCase();
                const browserVersion = result.browser.version || "0";
                const deviceModel = (info.device.model || "").toLowerCase();
                const ua = navigator.userAgent.toLowerCase();

                // This is the main object where we will store the title and steps for the user.
                const instructions = {title: "", steps: []};

                // This is the SVG code for the "quiet bell" icon shown in some browsers.
                const quietBellIcon =
                    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="${config.theme_color}" width="1.5rem" height="1.5rem" style="display: inline-block; vertical-align: middle; margin: 0 0.25rem;">
                        <path d="M12 2a1.5 1.5 0 0 0-1.5 1.5v.695A5.997 5.997 0 0 0 6 10v6l-2 2v1h6.27a2 2 0 0 0-.27 1 2 2 0 0 0 2 2 2 2 0 0 0 2-2 2 2 0 0 0-.271-1H20v-1l-2-2v-6a5.997 5.997 0 0 0-4.5-5.805V3.5A1.5 1.5 0 0 0 12 2zm0 4c2.206 0 4 1.794 4 4v6.828l.172.172H7.828L8 16.828V10c0-2.206 1.794-4 4-4z"/>
                    </svg>`;

                // A list of common browser names for display.
                const specialBrowsers = {
                    "samsung internet": "Samsung Internet",
                    "mi browser": "MI Browser",
                    "huawei browser": "Huawei Browser",
                    "oppo browser": "Oppo Browser",
                    "vivo browser": "Vivo Browser",
                    "xiaomi browser": "Xiaomi Browser",
                    "realme browser": "Realme Browser",
                    "safari": "Safari",
                    "chrome": "Chrome",
                    "firefox": "Firefox",
                    "edge": "Edge",
                    "opera": "Opera",
                    "brave": "Brave",
                };

                // This matrix defines the minimum browser version that supports push notifications on each OS.
                const supportMatrix = {
                    windows: {chrome: "42", firefox: "44", edge: "79", opera: "29", brave: "42"},
                    macos: {safari: "16", chrome: "42", firefox: "44", edge: "79", opera: "29", brave: "42"},
                    linux: {chrome: "42", firefox: "44", edge: "79", opera: "29", brave: "42"},
                    android: {
                        chrome: "50",
                        firefox: "48",
                        opera: "37",
                        brave: "50",
                        "samsung internet": "7",
                        "mi browser": "12",
                        "huawei browser": "12",
                        "oppo browser": "12",
                        "vivo browser": "12",
                        "xiaomi browser": "12",
                        "realme browser": "12",
                    },
                    ios: {safari: "16.4"},
                    chromeos: {chrome: "42", firefox: "44"},
                };

                // A helper function to capitalize the first letter of a string.
                const capitalize = (s) => s.charAt(0).toUpperCase() + s.slice(1);

                // A helper function to compare two version numbers (e.g., "16.4" vs "16").
                const versionCompare = (v1, v2) => {
                    const a = (v1 + "").split(".").map(Number);
                    const b = (v2 + "").split(".").map(Number);
                    for (let i = 0; i < Math.max(a.length, b.length); i++) {
                        if ((a[i] || 0) < (b[i] || 0)) return -1;
                        if ((a[i] || 0) > (b[i] || 0)) return 1;
                    }
                    return 0;
                };

                // Standardize browser and OS names for easier lookup.
                let browserKey = browser;
                if (browser.includes("samsung")) browserKey = "samsung internet";
                else if (browser.includes("mi ")) browserKey = "mi browser";
                else if (browser.includes("huawei")) browserKey = "huawei browser";
                else if (browser.includes("oppo")) browserKey = "oppo browser";
                else if (browser.includes("vivo")) browserKey = "vivo browser";
                else if (browser.includes("xiaomi")) browserKey = "xiaomi browser";
                else if (browser.includes("realme")) browserKey = "realme browser";

                let osKey = "unknown";
                if (os.includes("windows")) osKey = "windows";
                else if (os.includes("mac")) osKey = "macos";
                else if (os.includes("linux")) osKey = "linux";
                else if (os.includes("android")) osKey = "android";
                else if (os.includes("ios")) osKey = "ios";
                else if (os.includes("chrome os") || os.includes("chromeos")) osKey = "chromeos";

                const minSupportedVersion = supportMatrix[osKey]?.[browserKey] ?? null;
                const isInAppOrWebView = /wv|; fb|instagram|line|messenger|snapchat|tiktok|twitter/i.test(ua);
                const stepNumber = (num) => `<span class="pk-step-number">${num}</span>`;
                const userBrowserInfo = `مرورگر شما (<code>${specialBrowsers[browserKey] || capitalize(browser)}</code>) روی سیستم‌عامل (<code>${capitalize(os)}</code>)`;

                // --- Instructions for when the browser doesn't support notifications ---
                if (mode === "push_notifications_unsupported") {
                    instructions.title = `نوتیفیکیشن در ${userBrowserInfo} پشتیبانی نمی‌شود`;

                    if (isInAppOrWebView) {
                        instructions.steps.push(
                            "به نظر می‌رسد این صفحه در یک اپلیکیشن دیگر (مانند اینستاگرام یا یک برنامه خبری) باز شده است. این برنامه‌ها معمولاً اجازه ارسال نوتیفیکیشن را نمی‌دهند.",
                            "برای حل این مشکل، لطفاً لینک این صفحه را در مرورگر اصلی گوشی خود مانند <code>Chrome</code> یا <code>Safari</code> باز کنید."
                        );
                        return instructions;
                    }

                    if (minSupportedVersion && versionCompare(browserVersion, minSupportedVersion) < 0) {
                        instructions.steps.push(
                            `نسخه مرورگر شما (<code>${browserVersion}</code>) قدیمی است و به همین دلیل نوتیفیکیشن‌ها کار نمی‌کنند.`,
                            `برای دریافت نوتیفیکیشن، لطفاً مرورگر خود را حداقل به نسخه <b>${minSupportedVersion}</b> یا بالاتر به‌روزرسانی کنید.`
                        );
                        return instructions;
                    }

                    if (!minSupportedVersion) {
                        instructions.steps.push(
                            `متاسفانه ${userBrowserInfo} از نوتیفیکیشن پشتیبانی نمی‌کند.`,
                            "پیشنهاد می‌کنیم از یک مرورگر دیگر که از نوتیفیکیشن‌ها پشتیبانی می‌کند، مانند <code>Chrome</code>، <code>Firefox</code> یا <code>Safari</code> استفاده کنید."
                        );
                        return instructions;
                    }

                    instructions.steps.push(
                        "یک مشکل غیرمنتظره وجود دارد.",
                        "لطفاً مطمئن شوید که تنظیمات نوتیفیکیشن در مرورگر و سیستم‌عامل شما درست است."
                    );
                    return instructions;
                }

                // --- Instructions for when notifications are blocked by the Operating System ---
                if (mode === "os_blocked") {
                    instructions.title = "نوتیفیکیشن‌ها در تنظیمات اصلی دستگاه شما غیرفعال است";

                    if (osKey === "android") {
                        const androidSkins = [
                            {name: "MIUI/HyperOS", keywords: ["mi", "redmi", "poco", "xiaomi"]},
                            {name: "OneUI", keywords: ["samsung", "galaxy"]},
                            {name: "EMUI", keywords: ["huawei", "honor"]},
                            {name: "ColorOS", keywords: ["oppo"]},
                            {name: "Realme UI", keywords: ["realme"]},
                            {name: "FuntouchOS", keywords: ["vivo"]},
                        ];
                        const getAndroidSkin = () => {
                            const modelInfo = `${deviceModel} ${ua}`;
                            for (const skin of androidSkins) {
                                if (skin.keywords.some(k => modelInfo.includes(k))) return skin.name;
                            }
                            return "Stock Android";
                        };
                        const skin = getAndroidSkin();

                        instructions.steps.push(
                            `نوتیفیکیشن‌ها در تنظیمات اندروید شما (رابط کاربری <b>${skin}</b>) خاموش است. لطفاً مراحل زیر را دنبال کنید:`
                        );

                        switch (skin) {
                            case "MIUI/HyperOS":
                                instructions.steps.push(
                                    `${stepNumber(1)} وارد <b>«تنظیمات»</b> یا <code>Settings</code> شوید.`,
                                    `${stepNumber(2)} به بخش <b>«اعلان‌ها و مرکز کنترل»</b> یا <code>Notifications & Control center</code> بروید.`,
                                    `${stepNumber(3)} گزینه‌ی <b>«اعلان‌های برنامه»</b> یا <code>App notifications</code> را انتخاب کنید.`,
                                    `${stepNumber(4)} در لیست، مرورگر خود (<code>${specialBrowsers[browserKey] || capitalize(browser)}</code>) را پیدا کرده و کلید آن را روشن کنید.`
                                );
                                break;
                            case "OneUI":
                                instructions.steps.push(
                                    `${stepNumber(1)} وارد <b>«تنظیمات»</b> یا <code>Settings</code> شوید.`,
                                    `${stepNumber(2)} به بخش <b>«اعلان‌ها»</b> یا <code>Notifications</code> بروید.`,
                                    `${stepNumber(3)} گزینه‌ی <b>«اعلان‌های برنامه»</b> یا <code>App Notifications</code> را انتخاب کنید.`,
                                    `${stepNumber(4)} در لیست، مرورگر خود (<code>${specialBrowsers[browserKey] || capitalize(browser)}</code>) را پیدا کرده و آن را فعال کنید.`
                                );
                                break;
                            case "EMUI":
                                instructions.steps.push(
                                    `${stepNumber(1)} وارد <b>«تنظیمات»</b> یا <code>Settings</code> شوید.`,
                                    `${stepNumber(2)} به بخش <b>«اعلان‌ها»</b> یا <code>Notifications</code> بروید.`,
                                    `${stepNumber(3)} مرورگر خود (<code>${specialBrowsers[browserKey] || capitalize(browser)}</code>) را در لیست برنامه‌ها پیدا کنید.`,
                                    `${stepNumber(4)} گزینه‌ی <code>Allow notifications</code> را فعال کنید.`
                                );
                                break;
                            case "ColorOS":
                            case "Realme UI":
                                instructions.steps.push(
                                    `${stepNumber(1)} وارد <b>«تنظیمات»</b> یا <code>Settings</code> شوید.`,
                                    `${stepNumber(2)} به بخش <b>«نوار اعلان و وضعیت»</b> یا <code>Notification & status bar</code> بروید.`,
                                    `${stepNumber(3)} گزینه‌ی <b>«مدیریت اعلان‌ها»</b> یا <code>Manage notifications</code> را انتخاب کنید.`,
                                    `${stepNumber(4)} مرورگر خود (<code>${specialBrowsers[browserKey] || capitalize(browser)}</code>) را پیدا کرده و آن را فعال کنید.`
                                );
                                break;
                            case "FuntouchOS":
                                instructions.steps.push(
                                    `${stepNumber(1)} وارد <b>«تنظیمات»</b> یا <code>Settings</code> شوید.`,
                                    `${stepNumber(2)} به بخش <b>«نوار وضعیت و اعلان»</b> یا <code>Status bar and notification</code> بروید.`,
                                    `${stepNumber(3)} گزینه‌ی <b>«مدیریت اعلان‌ها»</b> یا <code>Manage notifications</code> را انتخاب کنید.`,
                                    `${stepNumber(4)} مرورگر خود (<code>${specialBrowsers[browserKey] || capitalize(browser)}</code>) را پیدا کرده و آن را فعال کنید.`
                                );
                                break;
                            default: // Stock Android and others
                                instructions.steps.push(
                                    `${stepNumber(1)} وارد <b>«تنظیمات»</b> یا <code>Settings</code> گوشی خود شوید.`,
                                    `${stepNumber(2)} گزینه‌ی <b>«برنامه‌ها»</b> یا <code>Apps</code> را پیدا کرده و آن را باز کنید.`,
                                    `${stepNumber(3)} روی <b>«مشاهده همه برنامه‌ها»</b> یا <code>See all apps</code> بزنید و در لیست، مرورگر خود (<code>${specialBrowsers[browserKey] || capitalize(browser)}</code>) را پیدا کنید.`,
                                    `${stepNumber(4)} وارد صفحه اطلاعات برنامه که شدید، گزینه‌ی <b>«اعلان‌ها»</b> یا <code>Notifications</code> را انتخاب کنید.`,
                                    `${stepNumber(5)} در این صفحه، کلید اصلی اعلان‌ها را روشن کنید تا همه نوتیفیکیشن‌ها فعال شوند.`
                                );
                        }
                        return instructions;
                    }

                    if (osKey === "ios") {
                        instructions.steps.push(
                            "نوتیفیکیشن‌ها در تنظیمات آیفون یا آیپد شما خاموش است. برای فعال کردن:",
                            `${stepNumber(1)} وارد <b>«تنظیمات»</b> یا <code>Settings</code> دستگاه خود شوید.`,
                            `${stepNumber(2)} گزینه‌ی <b>«اعلان‌ها»</b> یا <code>Notifications</code> را انتخاب کنید.`,
                            `${stepNumber(3)} در لیست برنامه‌ها، مرورگر <code>${specialBrowsers[browserKey] || capitalize(browser)}</code> را پیدا کنید.`,
                            `${stepNumber(4)} گزینه‌ی <code>Allow Notifications</code> را روشن کنید.`
                        );
                        return instructions;
                    }

                    if (osKey === "windows") {
                        instructions.steps.push(
                            `نوتیفیکیشن‌ها در تنظیمات ویندوز شما خاموش است. برای فعال کردن:`,
                            `${stepNumber(1)} وارد <b>«تنظیمات»</b> یا <code>Settings</code> کامپیوتر خود شوید (با فشردن کلیدهای <code>Win + I</code>).`,
                            `${stepNumber(2)} به بخش <b>«سیستم»</b> یا <code>System</code> و سپس <b>«اعلان‌ها»</b> یا <code>Notifications</code> بروید.`,
                            `${stepNumber(3)} مطمئن شوید که گزینه‌ی اصلی <code>Notifications</code> روشن است.`,
                            `${stepNumber(4)} سپس در لیست پایین صفحه، مرورگر خود (<code>${specialBrowsers[browserKey] || capitalize(browser)}</code>) را پیدا کرده و آن را هم فعال کنید.`
                        );
                        return instructions;
                    }

                    if (osKey === "macos") {
                        instructions.steps.push(
                            `نوتیفیکیشن‌ها در تنظیمات مک شما خاموش است. برای فعال کردن:`,
                            `${stepNumber(1)} روی لوگوی اپل در گوشه بالا سمت چپ کلیک کرده و وارد <b>«تنظیمات سیستم»</b> یا <code>System Settings</code> شوید.`,
                            `${stepNumber(2)} به بخش <b>«اعلان‌ها»</b> یا <code>Notifications</code> بروید.`,
                            `${stepNumber(3)} در لیست برنامه‌ها، مرورگر خود (<code>${specialBrowsers[browserKey] || capitalize(browser)}</code>) را پیدا کنید.`,
                            `${stepNumber(4)} گزینه‌ی <code>Allow Notifications</code> را روشن کنید.`
                        );
                        return instructions;
                    }

                    if (osKey === "linux") {
                        instructions.steps.push(
                            `نوتیفیکیشن‌ها در تنظیمات لینوکس شما خاموش است.`,
                            `در لینوکس، این تنظیمات به <b>محیط دسکتاپ (Desktop Environment)</b> شما بستگی دارد. لطفاً به بخش اعلان‌ها در تنظیمات سیستم خود بروید.`,
                            `برای مثال:`,
                            `<ul><li>در <b>GNOME</b>: به <code>Settings &gt; Notifications</code> بروید.</li><li>در <b>KDE Plasma</b>: به <code>System Settings &gt; Notifications</code> بروید.</li></ul>`,
                            `سپس مرورگر خود را در لیست پیدا کرده و اجازه‌ی ارسال نوتیفیکیشن را فعال کنید.`
                        );
                        return instructions;
                    }

                    instructions.steps.push(
                        "نوتیفیکیشن‌ها در تنظیمات اصلی دستگاه شما غیرفعال است.",
                        "لطفاً به تنظیمات سیستم‌عامل خود بروید و اجازه ارسال نوتیفیکیشن را برای مرورگرتان فعال کنید."
                    );
                    return instructions;
                }

                // --- Instructions for when notifications are blocked by the Browser ---
                if (mode === "browser_blocked") {
                    instructions.title = "نوتیفیکیشن در تنظیمات مرورگر شما مسدود شده است";

                    if (isInAppOrWebView) {
                        instructions.steps.push(
                            "به نظر می‌رسد این صفحه در یک اپلیکیشن دیگر باز شده است که اجازه ارسال نوتیفیکیشن را نمی‌دهد.",
                            "برای حل این مشکل، لطفاً لینک این صفحه را در مرورگر اصلی گوشی خود مانند <code>Chrome</code> یا <code>Safari</code> باز کنید."
                        );
                        return instructions;
                    }

                    instructions.steps.push("شما قبلاً درخواست نوتیفیکیشن را در مرورگر رد کرده‌اید. برای فعال کردن آن، لطفاً مراحل زیر را متناسب با مرورگر خود دنبال کنید:");

                    switch (browserKey) {
                        case 'firefox':
                            instructions.steps.push(
                                `${stepNumber(1)} منوی مرورگر (آیکون سه خط) را باز کرده و وارد <b>«تنظیمات»</b> یا <code>Settings</code> شوید.`,
                                `${stepNumber(2)} به بخش <b>«حریم خصوصی و امنیت»</b> یا <code>Privacy & Security</code> بروید.`,
                                `${stepNumber(3)} به پایین صفحه بروید تا به بخش <b>«مجوزها»</b> یا <code>Permissions</code> برسید.`,
                                `${stepNumber(4)} در مقابل <b>«اعلان‌ها»</b> یا <code>Notifications</code>، روی دکمه‌ی <code>Settings...</code> کلیک کنید.`,
                                `${stepNumber(5)} وب‌سایت ما را در لیست پیدا کرده، وضعیت آن را به <b><code>Allow</code></b> تغییر دهید و تغییرات را ذخیره کنید.`
                            );
                            break;
                        case 'safari':
                            if (osKey === 'macos') {
                                instructions.steps.push(
                                    `${stepNumber(1)} از نوار منوی بالای صفحه، روی <b><code>Safari</code></b> کلیک کرده و <b>«تنظیمات»</b> یا <code>Preferences...</code> را انتخاب کنید.`,
                                    `${stepNumber(2)} به تب <b>«وب‌سایت‌ها»</b> یا <code>Websites</code> بروید.`,
                                    `${stepNumber(3)} از منوی سمت چپ، <b>«اعلان‌ها»</b> یا <code>Notifications</code> را انتخاب کنید.`,
                                    `${stepNumber(4)} وب‌سایت ما را در لیست پیدا کرده و وضعیت آن را به <b><code>Allow</code></b> تغییر دهید.`
                                );
                            } else { // iOS Safari
                                instructions.steps.push(
                                    `${stepNumber(1)} وارد <b>«تنظیمات»</b> یا <code>Settings</code> آیفون/آیپد خود شوید.`,
                                    `${stepNumber(2)} به پایین اسکرول کنید و <b><code>Safari</code></b> را پیدا کنید.`,
                                    `${stepNumber(3)} دوباره به پایین اسکرول کرده و وارد <b>«تنظیمات برای وب‌سایت‌ها»</b> یا <code>Settings for Websites</code> شوید.`,
                                    `${stepNumber(4)} گزینه‌ی <b>«اعلان‌ها»</b> یا <code>Notifications</code> را انتخاب کنید.`,
                                    `${stepNumber(5)} وب‌سایت ما را در لیست پیدا کرده و وضعیت آن را به <b><code>Allow</code></b> تغییر دهید.`
                                );
                            }
                            break;
                        case 'samsung internet':
                            instructions.steps.push(
                                `<b>راهنمای مرورگر Samsung Internet:</b>`,
                                `${stepNumber(1)} منوی مرورگر (آیکون سه خط در پایین صفحه) را باز کرده و وارد <b>«تنظیمات»</b> یا <code>Settings</code> شوید.`,
                                `${stepNumber(2)} به بخش <b>«سایت‌ها و دانلودها»</b> یا <code>Sites and downloads</code> بروید.`,
                                `${stepNumber(3)} گزینه‌ی <b>«اعلان‌ها»</b> یا <code>Notifications</code> را انتخاب کنید.`,
                                `${stepNumber(4)} روی منوی سه نقطه در بالا سمت راست کلیک کرده و وارد <b>«مجاز کردن یا مسدود کردن سایت‌ها»</b> شوید.`,
                                `${stepNumber(5)} وب‌سایت ما را در لیست <code>Blocked</code> پیدا کرده و با انتخاب آن، گزینه‌ی <code>Allow</code> را بزنید.`
                            );
                            break;
                        case 'mi browser':
                        case 'xiaomi browser':
                            instructions.steps.push(
                                `<b>راهنمای مرورگر MI Browser:</b>`,
                                `${stepNumber(1)} منوی مرورگر (آیکون پروفایل یا سه خط) را باز کرده و وارد <b>«تنظیمات»</b> (آیکون چرخ‌دنده) شوید.`,
                                `${stepNumber(2)} به پایین اسکرول کرده و <b>«تنظیمات پیشرفته»</b> یا <code>Advanced</code> را انتخاب کنید.`,
                                `${stepNumber(3)} گزینه‌ی <b>«تنظیمات سایت»</b> یا <code>Site settings</code> را انتخاب کنید.`,
                                `${stepNumber(4)} روی <b>«اعلان‌ها»</b> یا <code>Notifications</code> کلیک کنید.`,
                                `${stepNumber(5)} وب‌سایت ما را در لیست <code>Blocked</code> پیدا کرده و با انتخاب آن، گزینه‌ی <code>Allow</code> را بزنید.`
                            );
                            break;
                        default: // For Chrome, Edge, Brave, Opera, and other OEM browsers
                            instructions.steps.push(
                                `<b>راهنمای مرورگرهای Chrome, Edge, Opera, Brave و موارد مشابه:</b>`,
                                `${stepNumber(1)} منوی مرورگر (آیکون سه نقطه) را باز کرده و وارد <b>«تنظیمات»</b> یا <code>Settings</code> شوید.`,
                                `${stepNumber(2)} به بخش <b>«حریم خصوصی و امنیت»</b> یا <code>Privacy and security</code> بروید.`,
                                `${stepNumber(3)} گزینه‌ی <b>«تنظیمات سایت»</b> یا <code>Site Settings</code> را انتخاب کنید.`,
                                `${stepNumber(4)} در داخل آن، روی <b>«اعلان‌ها»</b> یا <code>Notifications</code> کلیک کنید.`,
                                `${stepNumber(5)} وب‌سایت ما را در بخش <code>Not allowed to send notifications</code> پیدا کرده، روی آن کلیک کنید و اجازه را فعال کنید.`
                            );
                            break;
                    }
                    instructions.steps.push(`${stepNumber('!')} در نهایت، این صفحه را یک بار رفرش (تازه‌سازی) کنید.`);
                    return instructions;
                }

                // --- Default instructions for enabling notifications ---
                instructions.title = "چگونه نوتیفیکیشن‌ها را فعال کنیم؟";
                instructions.steps.push(
                    `وقتی مرورگر از شما پرسید که آیا اجازه ارسال نوتیفیکیشن را می‌دهید، لطفاً گزینه‌ی <b>«اجازه دادن»</b> یا <code>Allow</code> را انتخاب کنید.`,
                    `اگر به اشتباه گزینه‌ی دیگری را انتخاب کردید، صفحه را یک بار رفرش کنید و دوباره تلاش کنید.`,
                    `گاهی اوقات یک آیکون زنگوله ${quietBellIcon} در نوار آدرس مرورگر ظاهر می‌شود. می‌توانید روی آن کلیک کرده و نوتیفیکیشن را فعال کنید.`
                );

                return instructions;
            }

            async function displayUnblockInstructions(mode = "browser_blocked") {
                const instructions = await getPlatformInstructions(mode);
                unblockInstructionsModal.innerHTML = `
                <div class="pk-modal-overlay"></div>
                <div class="pk-modal-content">
                    <button class="pk-modal-close">&times;</button>
                    <h2>${instructions.title}</h2>
                    ${instructions.description ? `<p>${instructions.description}</p>` : ''}
                    <ol class="pk-instructions-list">
                        ${instructions.steps.map((step) => `
                            <li>${step}</li>
                        `).join("")}
                    </ol>
                </div>
                <style>
                    #pk-unblock-instructions .pk-modal-content { background-color: ${config.background_color}; }
                    #pk-unblock-instructions .pk-modal-content .pk-modal-close { color: ${config.text_color}; }
                    #pk-unblock-instructions .pk-modal-content h2 { color: ${config.title_color} !important; }
                    #pk-unblock-instructions .pk-modal-content .pk-instructions-list .pk-step-number { background-color: ${config.theme_color}; color: ${config.accept_button_text_color}; }
                    #pk-unblock-instructions .pk-modal-content .pk-instructions-list, .pk-instruction-modal p { color: ${config.text_color}; }
                    #pk-unblock-instructions .pk-modal-content .pk-instructions-list li code, #pk-unblock-instructions .pk-instructions-list li kbd { background-color: ${config.text_color}26; box-shadow: 1px 1px 0 ${config.text_color}4d; }
                </style>`;
                unblockInstructionsModal.querySelector(".pk-modal-close").addEventListener("click", () => {
                    unblockInstructionsModal.classList.remove("visible");
                });
                unblockInstructionsModal.classList.add("visible");
            }

            // ===== Helpers: version compare, standalone, webview/in-app =====
            function versionCmp(a = '0', b = '0') {
                const as = String(a).split('.').map(n => parseInt(n || '0', 10));
                const bs = String(b).split('.').map(n => parseInt(n || '0', 10));
                const len = Math.max(as.length, bs.length);
                for (let i = 0; i < len; i++) {
                    const av = as[i] || 0, bv = bs[i] || 0;
                    if (av > bv) return 1;
                    if (av < bv) return -1;
                }
                return 0;
            }

            function isInStandaloneMode() {
                return (
                    ('standalone' in window.navigator && window.navigator.standalone) ||
                    window.matchMedia('(display-mode: standalone)').matches ||
                    window.matchMedia('(display-mode: fullscreen)').matches
                );
            }

            function isInAppOrWebView(ua = navigator.userAgent) {
                const u = ua.toLowerCase();
                // In-app browsers
                if (u.includes('fbav') || u.includes('fban') || u.includes('instagram') || u.includes('line/') || u.includes('wechat')) return true;
                // Android WebView
                if (u.includes('; wv)') || (u.includes('version/4.0') && u.includes('chrome/'))) return true;
                return false;
            }

            function getUAInfo() {
                const parser = new UAParser();
                const r = parser.getResult();
                const os = (r.os.name || '').toLowerCase();               // ios, android, windows, mac os, linux
                const osVersion = (r.os.version || '').toLowerCase();     // 16.5, 14, 10, 13 ...
                const browser = (r.browser.name || '').toLowerCase();     // safari, chrome, edge, firefox, opera, samsung internet, brave, ...
                const browserVersion = (r.browser.version || '').toLowerCase();
                const deviceType = (r.device.type || 'desktop').toLowerCase();
                return {os, osVersion, browser, browserVersion, deviceType};
            }

// ===== Core detection: returns true IFF Web Push is realistically supported =====

            function isWebPushSupportedEnv() {
                // Base Web APIs must exist
                if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) {
                    return false;
                }

                const {os, osVersion, browser, browserVersion} = getUAInfo();
                const ua = navigator.userAgent || '';

                // In‑app browsers / WebView: not supported
                if (isInAppOrWebView(ua)) return false;

                // iOS/iPadOS: Safari 16.4+ and ONLY when launched from Home Screen (standalone)
                // نکته: ua-parser-js ممکن است iPadOS را به عنوان "iOS" شناسایی کند
                if (os === 'ios') {
                    // نکته: نام مرورگر سافاری در iOS معمولاً "Safari" است نه "Mobile Safari"
                    if (browser !== 'mobile safari') return false;
                    if (versionCmp(osVersion, '16.4') < 0) return false;
                    return isInStandaloneMode();
                }

                // Android
                if (os === 'android') {
                    const v = browserVersion;
                    // نکته: نام مرورگرهای موبایل کروم و اپرا به ترتیب "Chrome" و "Opera" است
                    if (browser === 'mobile chrome' || browser === 'edge' || browser === 'opera mobi' || browser === 'brave') {
                        return versionCmp(v, '50') >= 0;
                    }
                    if (browser === 'samsung internet') {
                        return versionCmp(v, '7') >= 0;
                    }
                    if (browser === 'mobile firefox') {
                        return versionCmp(v, '48') >= 0;
                    }
                    return false;
                }

                // Desktop: Windows / Linux
                if (os.includes('windows') || os.includes('linux')) {
                    if (browser === 'chrome' || browser === 'brave' || browser === 'opera') {
                        return versionCmp(browserVersion, '42') >= 0;
                    }
                    if (browser === 'edge') {
                        return versionCmp(browserVersion, '79') >= 0;  // Chromium Edge
                    }
                    if (browser === 'firefox') {
                        return versionCmp(browserVersion, '44') >= 0;
                    }
                    return false;
                }

                // macOS
                if (os.includes('mac')) {
                    if (browser === 'safari') {
                        return versionCmp(browserVersion, '16') >= 0;  // Safari 16+
                    }
                    if (browser === 'chrome' || browser === 'brave' || browser === 'opera') {
                        return versionCmp(browserVersion, '42') >= 0;
                    }
                    if (browser === 'edge') {
                        return versionCmp(browserVersion, '79') >= 0;
                    }
                    if (browser === 'firefox') {
                        return versionCmp(browserVersion, '44') >= 0;
                    }
                    return false;
                }

                // Others: conservative false
                return false;
            }

            function init() {
                syncSubscriptionState();
                showBellIcon();
                setTimeout(showBellIcon, 1500);

                if (localStorage.getItem("pk_prompt_active") === "true") return false;

                setTimeout(() => {
                    const runSubscriptionLogic = () => {
                        setTimeout(() => {
                            if (isEligibleToShow()) {
                                showModal();
                            }
                        }, 1500);
                    };

                    if (isInStandaloneMode()) {
                        runSubscriptionLogic();
                    } else {
                        document.addEventListener('pwaPromptHandled', runSubscriptionLogic);
                    }
                }, 1000);
            }

            init();

            bellWrapper.addEventListener("click", () => {
                const currentPermission = Notification.permission;
                if (currentPermission === 'denied') {
                    displayUnblockInstructions(); // browser_blocked
                    return;
                }
                if (!isWebPushSupportedEnv()) {
                    // ثبت یک فلگ تا دوباره و بیهوده پاپ‌آپ ندهید
                    localStorage.setItem('pk_seen_unsupported_prompt', 'true');
                    displayUnblockInstructions("push_notifications_unsupported");
                    return;
                }
                showModal();
            });

            let confirmNoBtn, confirmYesBtn;

            const unblockCloseButton = unblockInstructionsModal.querySelector(".pk-modal-close");
            if (unblockCloseButton) {
                unblockCloseButton.addEventListener("click", () => {
                    unblockInstructionsModal.classList.remove("visible");
                });
            }
        }

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", initSubscribeMain);
        } else {
            initSubscribeMain();
        }
    }
)
();