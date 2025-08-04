/**
 * PWA Kit Service Worker (نسخه نهایی و خودکفا)
 * این نسخه شامل تمام منطق برای ساخت و نمایش صفحه آفلاین داینامیک است.
 */
'use strict';

const CACHE_NAME = '{{CACHE_NAME}}';
/*{{CONFIG_JSON}}*/ // این بخش توسط PHP با آبجکت تنظیمات جایگزین می‌شود

function buildOfflinePage() {
    // اعمال رنگ پیش‌فرض برای پاراگراف‌های داخل محتوای سفارشی
    const userContent = CONFIG.content_html.replace(/<p>/g, `<p style="color: ${CONFIG.text_color};">`);
    //console.log(CONFIG)
    return `
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="${CONFIG.main_font_url}" >
      <title>آفلاین</title>
      <style>
        @keyframes pk-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        body {
          margin: 0;
          font-family: ${CONFIG.main_font_family};
          background-color: ${CONFIG.background_color};
          color: ${CONFIG.title_color};
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
        }.pk-user-content p { color: ${CONFIG.text_color}; }
        #pk-reload-btn { font-family: inherit; }
      </style>
    </head>
    <body>
      <div class="pk-offline-content-wrapper">
        <div class="pk-user-content">
          ${userContent}
        </div>
        <div class="pk-loader-wrapper" style="margin-top: 30px;">
          <div class="pk-loader" style="border: 4px solid rgba(0,0,0,0.1); border-radius: 50%; border-top-color: ${CONFIG.loader_color}; width: 40px; height: 40px; animation: pk-spin 1s linear infinite; margin: auto;"></div>
          <p id="pk-status-message" style="color: ${CONFIG.status_text_color}; margin: 15px 0;">شما آفلاین هستید. در حال تلاش برای اتصال مجدد...</p>
          <button id="pk-reload-btn" onclick="window.location.reload()" style="background-color: ${CONFIG.button_bg_color}; color: ${CONFIG.button_text_color}; border: 1px solid ${CONFIG.button_border_color}; padding: 10px 25px; border-radius: 5px; cursor: pointer; font-size: 1em;">تلاش مجدد</button>
        </div>
      </div>
      
       <script>
  // راه حل اول (سریع): به محض آنلاین شدن، سعی در رفرش می‌کند.
  // این ممکن است در بار اول کار نکند، اما اگر کار کند سریعترین راه است.
  window.addEventListener('online', () => {
    window.location.reload();
  });

  // راه حل دوم (قابل اعتماد): به عنوان پشتیبان، هر چند ثانیه اتصال را چک می‌کند.
  // این روش قطعا کار خواهد کرد.
  const checkConnectivity = setInterval(() => {
    // یک درخواست سبک به یک فایل کوچک می‌زنیم تا از اتصال مطمئن شویم.
    // پارامتر زمان برای جلوگیری از کش شدن درخواست است.
    fetch('/favicon.ico?t=' + new Date().getTime(), {
      method: 'HEAD', // متد HEAD فقط هدرها را می‌گیرد و بهینه‌تر است.
      cache: 'no-store' // به هیچ وجه از کش استفاده نکن.
    })
    .then(response => {
      // اگر هر نوع پاسخی دریافت کنیم (حتی 404)، یعنی آنلاین هستیم.
      if (response.ok || response.status === 404) {
        // اگر آنلاین شدیم، دیگر نیازی به چک کردن نیست.
        clearInterval(checkConnectivity);
        
        const statusMessage = document.getElementById('pk-status-message');
        if (statusMessage) {
          statusMessage.textContent = 'اتصال برقرار شد! در حال بارگذاری مجدد...';
        }
        // با یک تاخیر کوتاه رفرش می‌کنیم تا کاربر پیام را ببیند.
        setTimeout(() => window.location.reload(), 500);
      }
    })
    .catch(() => {
      // اگر درخواست ناموفق بود، یعنی هنوز آفلاین هستیم. هیچ کاری نکن.
    });
  }, 5000); // هر 5 ثانیه یک بار چک کن.
</script>
      
    </body>
    </html>`;
}

self.addEventListener('install', event => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName.startsWith('pwa-kit-cache-') && cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    // اگر درخواست برای ناوبری یک صفحه است
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => {
                // در صورت قطعی شبکه، صفحه آفلاین را نمایش بده
                const offlinePage = buildOfflinePage();
                return new Response(offlinePage, {
                    headers: {'Content-Type': 'text/html; charset=utf-8'}
                });
            })
        );
        return; // از ادامه اجرای کد برای این درخواست جلوگیری کن
    }

    // // برای سایر درخواست‌ها (CSS, JS, Fonts, Images) از استراتژی Stale-While-Revalidate استفاده کن
    // event.respondWith(
    //     caches.open(CACHE_NAME).then(cache => {
    //         return cache.match(event.request).then(response => {
    //             // یک درخواست به شبکه برای آپدیت کش می‌فرستیم
    //             const fetchPromise = fetch(event.request).then(networkResponse => {
    //                 // اگر پاسخ معتبر بود، کش را آپدیت کن
    //                 if (networkResponse && networkResponse.status === 200) {
    //                     cache.put(event.request, networkResponse.clone());
    //                 }
    //                 return networkResponse;
    //             });
    //
    //             // اگر فایل در کش موجود بود، آن را برگردان (برای سرعت)
    //             // در غیر این صورت، منتظر پاسخ شبکه بمان
    //             return response || fetchPromise;
    //         });
    //     })
    // );
});

self.addEventListener('push', event => {
    try {
        const payload = event.data.json();
        if ('title' in payload && 'body' in payload) {
            const title = payload.title;
            const options = {body: payload.body, icon: payload.icon, image: payload.image, data: {url: payload.url}};
            event.waitUntil(self.registration.showNotification(title, options));
        }
    } catch (e) {
        // Push received, but not in our format.
    }
});

self.addEventListener('notificationclick', event => {
    if (event.notification.data && 'url' in event.notification.data) {
        event.notification.close();
        const urlToOpen = event.notification.data.url;
        if (urlToOpen) {
            event.waitUntil(clients.openWindow(urlToOpen));
        }
    }
});