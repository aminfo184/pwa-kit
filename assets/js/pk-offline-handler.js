(function () {
    'use strict';

    const config = window.pk_offline_data;
    if (!config) return;

    let isOverlayVisible = false;

    function showOfflineUI() {
        if (isOverlayVisible) return;
        isOverlayVisible = true;

        const placeholder = document.getElementById('pk-offline-placeholder');
        if (placeholder) {
            document.body.innerHTML = '';
        }

        const overlayHTML = `
            <div id="pk-offline-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: ${config.background_color}; z-index: 2000000010; display: flex; align-items: center; justify-content: center; text-align: center; opacity: 0; transition: opacity 0.3s ease;">
                <div class="pk-offline-content-wrapper" style="color: ${config.offline_text_color};">
                    <div class="pk-user-content" style="color: ${config.offline_title_color};">
                        ${config.content_html}
                    </div>
                    <div class="pk-loader-wrapper" style="margin-top: 30px;">
                        <div class="pk-loader" style="border: 4px solid rgba(0,0,0,0.1); border-radius: 50%; border-top-color: ${config.offline_loader_color}; width: 40px; height: 40px; animation: pk-spin 1s linear infinite; margin: auto;"></div>
                        <p id="pk-status-message" style="color: ${config.offline_status_text_color}; margin: 15px 0;">در تلاش برای اتصال مجدد...</p>
                        <button id="pk-reload-btn" style="background-color: ${config.offline_button_bg_color}; color: ${config.offline_button_text_color}; border: 1px solid ${config.offline_button_border_color}; padding: 10px 25px; border-radius: 5px; cursor: pointer; font-size: 1em;">تلاش مجدد</button>
                    </div>
                </div>
            </div>
            <style id="pk-offline-styles">
                @keyframes pk-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                body {
                  margin: 0;
                  font-family: ${config.main_font_family};
                  background-color: ${config.background_color};
                  color: ${config.offline_title_color};
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  text-align: center;
                  min-height: 100vh;
                }
                .pk-offline-content-wrapper {padding: 0 20px;}
                .pk-user-content {display: flex;flex-direction: column;justify-content: center;align-items: center;gap: 0.5rem}
                .pk-user-content img { width: 100px; height: auto;}
                .pk-user-content .offline-logo-wrapper {
                    position: relative;
                    display: inline-block;
                    line-height: 0;
                }

                .pk-user-content .offline-logo-wrapper img {
                    display: block;
                    max-width: 100%;
                    filter: grayscale(1);
                }

                .pk-user-content .offline-logo-wrapper::after {
                    content: '';
                    position: absolute;
                    bottom: -40px;
                    left: 40px;
                    width: 100%;
                    height: 100%;
                    background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxNiAxNiI+PGcgZmlsbD0iI2JjMmQyMiI+PHBhdGggZD0iTTggMS45OTJjLTIuNjE3IDAtNS4yMzguOTM0LTcuMTk1IDIuODA5bC0uNDk2LjQ4YS45OTkuOTk5IDAgMCAwLS4wMzIgMS40MSAxIDEgMCAwIDAgMS40MTQuMDI4bC41LS40NzdjMy4wODYtMi45NTMgOC41MzItMi45NTMgMTEuNjE4IDBsLjUuNDc3YTEgMSAwIDAgMCAxLjQxNC0uMDI4Ljk5OS45OTkgMCAwIDAtLjAzMi0xLjQxbC0uNDk2LS40OEMxMy4yMzggMi45MjYgMTAuNjE3IDEuOTkyIDggMS45OTJ6TTcuOTY5IDZjLTEuNTcuMDEyLTMuMTMuNjI5LTQuMjA3IDEuODEzbC0uNS41NWEuOTk3Ljk5NyAwIDEgMCAxLjQ3NiAxLjM0NGwuNS0uNTQzYzEuMjQyLTEuMzYzIDMuOTkyLTEuNDkyIDUuMzk5LS4xMjlhMS45OTkgMS45OTkgMCAwIDEgMS43NzcuNTVsLjIyMy4yMjRjLjAxMS0uMDEyLjAyMy0uMDIuMDM5LS4wMzJhMSAxIDAgMCAwIC4wNjItMS40MTRsLS41LS41NUMxMS4xMTMgNi41ODIgOS41MzUgNS45ODggNy45NjggNnpNOCAxMGEyIDIgMCAxIDAgMS44NzEgMi43bC0uMjg1LS4yODZhMi4wMTQgMi4wMTQgMCAwIDEtLjQ2OS0yLjA3QTIuMDAxIDIuMDAxIDAgMCAwIDggMTB6TTExIDEwYTEgMSAwIDAgMC0uNzA3IDEuNzA3TDExLjU4NiAxM2wtMS4yOTMgMS4yOTNhMSAxIDAgMSAwIDEuNDE0IDEuNDE0TDEzIDE0LjQxNGwxLjI5MyAxLjI5M2ExIDEgMCAxIDAgMS40MTQtMS40MTRMMTQuNDE0IDEzbDEuMjkzLTEuMjkzYTEgMSAwIDEgMC0xLjQxNC0xLjQxNEwxMyAxMS41ODZsLTEuMjkzLTEuMjkzQTEgMSAwIDAgMCAxMSAxMHptMCAwIi8+PC9nPjwvc3ZnPg==');
                    background-repeat: no-repeat;
                    background-position: center;
                    background-size: 50%;
                }.pk-user-content p { color: ${config.offline_text_color}; }
                #pk-reload-btn { font-family: inherit; }
            </style>`;

        document.body.insertAdjacentHTML('beforeend', overlayHTML);
        const overlay = document.getElementById('pk-offline-overlay');

        setTimeout(() => {
            if (overlay) overlay.style.opacity = '1';
        }, 10);

        document.getElementById('pk-reload-btn').addEventListener('click', () => window.location.reload());
    }

    function hideOfflineUI() {
        const overlay = document.getElementById('pk-offline-overlay');
        if (!overlay) return;

        const statusMessage = document.getElementById('pk-status-message');
        if (statusMessage) {
            statusMessage.textContent = 'اتصال برقرار شد! در حال بارگذاری مجدد...';
        }

        // setTimeout(() => {
        //     overlay.style.opacity = '0';
        //     setTimeout(() => {
        //         overlay.remove();
        //         const offlineStyles = document.getElementById('pk-offline-styles');
        //         if (offlineStyles) {
        //             offlineStyles.remove();
        //         }
        //         isOverlayVisible = false;
        //     }, 300);
        // }, 1500);

        const reloadBtn = document.getElementById('pk-reload-btn');
        const loader = document.querySelector('.pk-loader');
        if (reloadBtn) reloadBtn.style.display = 'none';
        if (loader) loader.style.animation = 'none';

        setTimeout(() => {
            window.location.reload();
        }, 1500);
    }

    window.addEventListener('offline', showOfflineUI);
    window.addEventListener('online', hideOfflineUI);

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('pk-offline-placeholder')) {
            showOfflineUI();
        } else if (!navigator.onLine) {
            showOfflineUI();
        }
    });

})();