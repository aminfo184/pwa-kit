(function () {
        "use strict";

        function initPwaInstallMain() {
            if (
                typeof window.pwa_install_data === "undefined" ||
                !("serviceWorker" in navigator)
            ) {
                document.dispatchEvent(new CustomEvent('pwaPromptHandled'));
                return;
            }

            navigator.serviceWorker.register(window.pwa_install_data.sw_url, {
                scope: window.pwa_install_data.scope,
            });

            // auto update code
            // navigator.serviceWorker.addEventListener('controllerchange', () => {
            //     console.log('pwa controller change called');
            //     window.location.reload();
            // });
            //
            // navigator.serviceWorker.ready.then(reg => {
            //     reg.addEventListener('updatefound', () => {
            //         console.log('pwa update found', reg.installing);
            //         reg.installing;
            //     });
            // });

            // document.addEventListener('DOMContentLoaded', async () => {
            // فقط در حالت PWA یا fullscreen اجرا کن
            // const isStandalone = window.matchMedia('(display-mode: fullscreen)').matches
            // window.matchMedia('(display-mode: standalone)').matches
            // window.navigator.standalone === true;
            //
            // if (isStandalone && 'orientation' in screen && typeof screen.orientation.lock === 'function') {
            //     alert('you are standalone and ready for auto rotate')
            //     try {
            //         // تلاش برای قفل کردن در حالت portrait
            //         await screen.orientation.lock('portrait');
            //     } catch (e) {
            //         // اگر شکست خورد، احتمالاً کاربر قفل چرخش فعال کرده یا اجازه نداده
            //         console.warn('Orientation lock failed:', e.message);
            //     }
            // }
            // });

            const data = window.pwa_install_data;
            const lsKeys = {
                windowStart: "pk_pwa_window_start",
                viewCount: "pk_pwa_view_count",
                attemptsFinished: "pk_pwa_attempts_finished",
            };

            const isIOS = () =>
                /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            const isAndroid = () => /Android/i.test(navigator.userAgent);
            const isInStandaloneMode = () =>
                ("standalone" in window.navigator && window.navigator.standalone) ||
                window.matchMedia("(display-mode: standalone)").matches;

            if (isInStandaloneMode()) {
                document.dispatchEvent(new CustomEvent('pwaPromptHandled'));
                return;
            }

            const popupManager = {
                mainWrapper: document.getElementById("pk-pwa-popup-wrapper"),
                dynamicInstructionWrapper: document.getElementById("pk-pwa-dynamic-instructions"),

                show(element) {
                    if (element) {
                        localStorage.setItem("pk_prompt_active", "true");
                        element.style.display = "flex";
                        setTimeout(() => element.classList.add("visible"), 50);
                    }
                },
                hideAll() {
                    localStorage.removeItem("pk_prompt_active");
                    [
                        this.mainWrapper,
                        this.dynamicInstructionWrapper
                    ].forEach((el) => {
                        if (el) el.classList.remove("visible");
                    });
                },
                buildInitialPrompt(wrapperElement, isManual = false) {
                    if (!wrapperElement || wrapperElement.innerHTML !== "") return;
                    let contentHTML = "";
                    if (data.popup_style === "modal") {
                        const modalTitle = data.modal_title;
                        const modalText = data.modal_text;
                        const modalButtonText = isManual ? 'راهنمای نصب' : data.modal_button;
                        contentHTML = `
                    <div class="pwa-popup-overlay"></div>
                    <div class="pwa-popup-content">
                        <button class="close-button">&times;</button>
                        <img src="${data.icon_192}" alt="App Icon" class="modal-icon">
                        <h2>${modalTitle}</h2>
                        <p>${modalText}</p>
                        <button class="install-button">${modalButtonText}</button>
                    </div>
                    <style> 
                        #pk-pwa-popup-wrapper .close-button { color: ${data.text_color}; }
                        #pk-pwa-popup-wrapper .pwa-popup-content { background-color: ${data.background_color}; }
                        #pk-pwa-popup-wrapper h2 { color: ${data.title_color} !important; }
                        #pk-pwa-popup-wrapper p { color: ${data.text_color}; }
                        #pk-pwa-popup-wrapper .install-button { background-color: ${data.theme_color}; color: ${data.button_text_color}; } 
                    </style>`;
                    } else {
                        const bannerTitle = data.banner_title;
                        const bannerText = data.banner_text;
                        const bannerButtonText = isManual ? "راهنمای نصب" : data.banner_button;
                        contentHTML = `
                    <div class="pwa-popup-overlay"></div>
                    <div class="pwa-popup-content">
                        <img src="${data.icon_192}" alt="App Icon" class="banner-icon">
                        <div class="banner-text">
                            <strong>${bannerTitle}</strong>
                            <small>${bannerText}</small>
                        </div>
                        <button class="install-button">${bannerButtonText}</button>
                    </div>
                    <style> 
                        #pk-pwa-popup-wrapper .close-button { color: ${data.text_color}; }
                        #pk-pwa-popup-wrapper .pwa-popup-content { background-color: ${data.background_color}; }
                        #pk-pwa-popup-wrapper strong { color: ${data.title_color} !important; }
                        #pk-pwa-popup-wrapper small { color: ${data.text_color}; }
                        #pk-pwa-popup-wrapper .install-button { background-color: ${data.theme_color}; color: ${data.button_text_color}; } 
                    </style>`;
                    }
                    wrapperElement.className = "pk-pwa-popup-wrapper style-" + data.popup_style;
                    wrapperElement.innerHTML = contentHTML;
                },
                showDynamicInstructions(platform) {
                    const content = getPlatformInstallationInstructions(platform);
                    this.dynamicInstructionWrapper.innerHTML = `
                <div class="pwa-popup-overlay"></div>
                <div class="pwa-popup-content">
                    <button class="close-button">&times;</button>
                    <h2>${content.title}</h2>
                    <ol class="pwa-dynamic-instructions-list">
                        ${content.steps.map(step => `<li>${step}</li>`).join("")}
                    </ol>
                </div>
                <style>
                #pk-pwa-dynamic-instructions .pwa-popup-content { background-color: ${data.background_color}; }
                #pk-pwa-dynamic-instructions h2 { color: ${data.title_color} !important; }
                #pk-pwa-dynamic-instructions .pwa-dynamic-instructions-list .pk-step-number { background-color: ${data.theme_color}; color: ${data.button_text_color}; }
                #pk-pwa-dynamic-instructions .pwa-dynamic-instructions-list { color: ${data.text_color}; }
                </style>`;
                    this.show(this.dynamicInstructionWrapper);
                }
            };

            function getPlatformInstallationInstructions(platform) {
                const instructions = {title: "", steps: []};
                const iosShareIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="${data.theme_color}" viewBox="0 0 52 52" width="2rem" height="2rem" style="display: inline; margin-inline: -3px;"><path d="M30.3 13.7 25 8.4l-5.3 5.3-1.4-1.4L25 5.6l6.7 6.7z"/><path d="M24 7h2v21h-2z"/><path d="M35 40H15c-1.7 0-3-1.3-3-3V19c0-1.7 1.3-3 3-3h7v2h-7c-.6 0-1 .4-1 1v18c0 .6.4 1 1 1h20c.6 0 1-.4 1-1V19c0-.6-.4-1-1-1h-7v-2h7c1.7 0 3 1.3 3 3v18c0 1.7-1.3 3-3 3z"/></svg>`;
                const iosAddToHomeScreenIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="${data.theme_color}" viewBox="0 0 52 52" width="1.75rem" height="1.75rem" style="display: inline; margin-inline-start: -4px;"><path d="M37 6c2.689 0 5 2.18 5 5v26c0 2.689-2.17 5-5 5H11c-2.689 0-5-2.212-5-5V11c0-2.689 2.17-5 5-5zM11 8c-1.598 0-3 1.276-3 3v26c0 1.598 1.289 3 3 3h26c1.598 0 3-1.262 3-3V11c0-1.598-1.282-3-3-3zm13 7c.513 0 1 .404 1 1v7h7a1 1 0 0 1 1 1c0 .513-.439 1-1 1h-7v7a1 1 0 0 1-2 0v-7h-7a1 1 0 0 1-1-1c0-.513.426-1 1-1h7v-7a1 1 0 0 1 1-1z"/></svg>`;
                const androidMenuIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="${data.theme_color}" viewBox="0 0 16 16" width="1.5rem" height="1.5rem" style="display: inline; margin-inline: -3px;"><path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/></svg>`;

                switch (platform) {
                    case 'iOS':
                        instructions.title = "راهنمای نصب در iOS";
                        instructions.steps.push(
                            `<span class="pk-step-number">۱</span> روی آیکون ${iosShareIcon} (Share) پایین صفحه کلیک کنید.`,
                            `<span class="pk-step-number">۲</span> گزینه "${iosAddToHomeScreenIcon} Add to Home Screen" را انتخاب نمایید.`,
                            `<span class="pk-step-number">۳</span> دکمه "Add" را بزنید.`
                        );
                        break;
                    case 'Android':
                        instructions.title = "راهنمای نصب در Android";
                        instructions.steps.push(
                            `<span class="pk-step-number">۱</span> روی منو سه نقطه ${androidMenuIcon} کلیک کنید.`,
                            `<span class="pk-step-number">۲</span> گزینه "Install app" یا "Add to Home screen" را انتخاب نمایید.`,
                            `<span class="pk-step-number">۳</span> دکمه "Install" را بزنید.`
                        );
                        break;
                    default:
                        instructions.title = "نصب اپلیکیشن در دستگاه شما";
                        instructions.steps.push(
                            'دستگاه شما ممکن است از نصب PWA پشتیبانی نکند.',
                            'لطفاً از مرورگرهای مدرن مانند Chrome یا Safari استفاده کنید.'
                        );
                }

                return instructions;
            }

            let deferredPrompt = null;

            function isEligibleToShow() {
                const now = new Date().getTime();
                let windowStart = parseInt(localStorage.getItem(lsKeys.windowStart) || "0");
                let viewCount = parseInt(localStorage.getItem(lsKeys.viewCount) || "0");

                if (now - windowStart > data.popup_delay * 60 * 1000) {
                    windowStart = now;
                    viewCount = 0;
                    localStorage.setItem(lsKeys.windowStart, windowStart);
                    localStorage.removeItem(lsKeys.attemptsFinished);
                }

                if (viewCount < data.popup_count) {
                    localStorage.setItem(lsKeys.viewCount, viewCount + 1);
                    return true;
                } else {
                    localStorage.setItem(lsKeys.attemptsFinished, 'true');
                    return false;
                }
            }

            // *** FIX: New function to signal the end of the entire PWA interaction ***
            function finishPwaInteraction() {
                popupManager.hideAll();
                document.dispatchEvent(new CustomEvent('pwaPromptHandled'));
            }

            function init() {
                if (!isEligibleToShow()) {
                    document.dispatchEvent(new CustomEvent('pwaPromptHandled'));
                    return;
                }

                let eventFired = false;
                window.addEventListener("beforeinstallprompt", (e) => {
                    //console.log('beforeinstallprompt')
                    eventFired = true;
                    e.preventDefault();
                    deferredPrompt = e;
                    popupManager.buildInitialPrompt(popupManager.mainWrapper, false);
                    popupManager.show(popupManager.mainWrapper);
                });

                setTimeout(() => {
                    if (eventFired) return;

                    if (isIOS() || (isAndroid() && !("BeforeInstallPromptEvent" in window))) {
                        popupManager.buildInitialPrompt(popupManager.mainWrapper, true);
                        popupManager.show(popupManager.mainWrapper);
                    } else {
                        document.dispatchEvent(new CustomEvent('pwaPromptHandled'));
                    }
                }, 1000);
            }

            init();

            // *** FIX: Re-written click handler for proper flow control ***
            document.body.addEventListener("click", async (e) => {
                // 1. User clicks the main "Install" or "Installation Guide" button
                if (e.target.matches(".install-button")) {
                    popupManager.hideAll();
                    if (deferredPrompt) {
                        deferredPrompt.prompt();
                        await deferredPrompt.userChoice;
                        finishPwaInteraction();
                        deferredPrompt = null;
                    } else {
                        const platform = isIOS() ? 'iOS' : isAndroid() ? 'Android' : 'Other';
                        popupManager.showDynamicInstructions(platform);
                    }
                }

                // 2. User closes the FIRST prompt using overlay or its close button
                else if (e.target.matches(".pwa-popup-overlay") || (e.target.matches(".close-button") && e.target.closest('#pk-pwa-popup-wrapper'))) {
                    finishPwaInteraction();
                }

                // 3. User closes the INSTRUCTION modal (for iOS or Android)
                else if (e.target.matches(".close-button") && (e.target.closest('#pk-pwa-dynamic-instructions') || e.target.closest('#pk-pwa-dynamic-instructions'))) {
                    finishPwaInteraction();
                }
            });
        }

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", initPwaInstallMain);
        } else {
            initPwaInstallMain();
        }
    }
)
();