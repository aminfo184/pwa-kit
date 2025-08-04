(function () {
        'use strict';

        function initMediaSession() {

            // بررسی می‌کنیم که آیا مرورگر از این قابلیت پشتیبانی می‌کند یا خیر.
            if (!('mediaSession' in navigator)) {
                //console.log('PWA Kit: Media Session API is not supported by this browser.');
                return;
            }

            // تمام تگ‌های <audio> و <video> موجود در صفحه را پیدا می‌کنیم.
            const mediaElements = document.querySelectorAll('audio, video');

            if (mediaElements.length === 0) {
                return; // اگر هیچ مدیایی در صفحه نبود، کاری انجام نده.
            }

            // برای هر مدیا در صفحه، یک شنونده رویداد 'play' اضافه می‌کنیم.
            mediaElements.forEach(player => {
                player.addEventListener('play', () => {
                    //console.log('PWA Kit: Media playback started. Setting up Media Session.');

                    // از روی تگ مدیا، اطلاعات را می‌خوانیم (به مرحله ۴ مراجعه کنید)
                    const title = player.getAttribute('data-title') || document.title;
                    const artist = player.getAttribute('data-artist') || window.location.hostname;
                    const album = player.getAttribute('data-album') || '';
                    const artworkSrc = player.getAttribute('data-artwork') || 'path/to/default-artwork.png';

                    // متادیتای مدیا را تنظیم می‌کنیم. این اطلاعات در صفحه قفل نمایش داده می‌شود.
                    navigator.mediaSession.metadata = new MediaMetadata({
                        title: title,
                        artist: artist,
                        album: album,
                        artwork: [
                            {src: artworkSrc, sizes: '512x512', type: 'image/png'},
                        ]
                    });

                    // کنترل‌کننده‌ها را برای دکمه‌های صفحه قفل/هدفون تنظیم می‌کنیم.
                    navigator.mediaSession.setActionHandler('play', () => player.play());
                    navigator.mediaSession.setActionHandler('pause', () => player.pause());

                    // می‌توانید کنترل‌های دیگر را نیز در صورت نیاز اضافه کنید:
                    // navigator.mediaSession.setActionHandler('seekbackward', (details) => { player.currentTime = Math.max(player.currentTime - (details.seekOffset || 10), 0); });
                    // navigator.mediaSession.setActionHandler('seekforward', (details) => { player.currentTime = Math.min(player.currentTime + (details.seekOffset || 10), player.duration); });
                    // navigator.mediaSession.setActionHandler('nexttrack', () => { /* منطق آهنگ بعدی */ });
                    // navigator.mediaSession.setActionHandler('previoustrack', () => { /* منطق آهنگ قبلی */ });
                });
            });
        }

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", initMediaSession);
        } else {
            initMediaSession();
        }
    }

)
();