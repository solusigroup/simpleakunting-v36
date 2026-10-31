/**
 * SimpleAkunting v3.6 - Progressive Web App (PWA) Manager
 * Handles Service Worker registration, deferred installation prompts,
 * update notifications, and online/offline status banners.
 */
(function () {
  'use strict';

  let deferredPrompt = null;
  const isIos = /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
  const isInStandaloneMode = () =>
    window.matchMedia('(display-mode: standalone)').matches ||
    window.navigator.standalone === true;

  // Add standalone class to body if running as installed app
  if (isInStandaloneMode()) {
    document.documentElement.classList.add('is-pwa-standalone');
  }

  // 1. Service Worker Registration & Update Detection
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      // Determine service worker URL from current host or script location
      const swUrl = (window.BASEURL ? window.BASEURL.replace(/\/$/, '') : '') + '/sw.js';

      navigator.serviceWorker
        .register(swUrl)
        .then((reg) => {
          console.log('[PWA] Service Worker registered successfully, scope:', reg.scope);

          // Check for worker updates
          reg.addEventListener('updatefound', () => {
            const newWorker = reg.installing;
            if (!newWorker) return;

            newWorker.addEventListener('statechange', () => {
              if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                // New content is available, show refresh prompt
                showUpdateToast(reg);
              }
            });
          });
        })
        .catch((err) => {
          console.warn('[PWA] Service Worker registration failed:', err);
        });

      // Reload page once new service worker takes over
      let refreshing = false;
      navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (!refreshing) {
          refreshing = true;
          window.location.reload();
        }
      });
    });
  }

  // 2. Handle beforeinstallprompt Event
  window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent default mini-infobar on mobile Chrome
    e.preventDefault();
    deferredPrompt = e;

    // Show install buttons
    document.querySelectorAll('.pwa-install-btn').forEach((btn) => {
      btn.style.setProperty('display', 'inline-flex', 'important');
      btn.classList.remove('d-none');
    });

    // Check if user dismissed floating banner recently (within 24 hours)
    const dismissedTime = localStorage.getItem('pwa_banner_dismissed_at');
    const isDismissed = dismissedTime && Date.now() - parseInt(dismissedTime, 10) < 24 * 60 * 60 * 1000;

    if (!isInStandaloneMode() && !isDismissed) {
      showInstallBanner();
    }
  });

  // 3. Handle appinstalled Event
  window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    hideInstallBanner();
    document.querySelectorAll('.pwa-install-btn').forEach((btn) => {
      btn.style.setProperty('display', 'none', 'important');
    });
    showToast('<i class="bi bi-check-circle-fill text-success me-2"></i>SimpleAkunting berhasil dipasang di perangkat Anda!');
  });

  // 4. Trigger Install Prompt
  window.promptPwaInstall = function () {
    if (deferredPrompt) {
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then((choiceResult) => {
        if (choiceResult.outcome === 'accepted') {
          console.log('[PWA] User accepted install prompt');
          hideInstallBanner();
        } else {
          console.log('[PWA] User dismissed install prompt');
        }
        deferredPrompt = null;
      });
    } else if (isIos && !isInStandaloneMode()) {
      showIosInstallModal();
    } else {
      showToast('<i class="bi bi-info-circle me-2"></i>Aplikasi sudah terpasang atau browser Anda telah mendukung instalasi melalui menu browser.');
    }
  };

  // 5. Floating Install Banner
  function showInstallBanner() {
    if (document.getElementById('pwa-install-banner') || isInStandaloneMode()) return;

    const banner = document.createElement('div');
    banner.id = 'pwa-install-banner';
    banner.className = 'pwa-floating-banner shadow-lg';
    banner.innerHTML = `
      <div class="d-flex align-items-center gap-3">
        <div class="pwa-banner-icon">
          <i class="bi bi-phone"></i>
        </div>
        <div class="flex-grow-1">
          <div class="fw-bold text-dark" style="font-size: 0.95rem;">Pasang SimpleAkunting</div>
          <div class="text-muted small" style="font-size: 0.8rem;">Akses cepat & hemat kuota langsung dari layar utama Anda.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-sm btn-success rounded-pill px-3 py-1 fw-bold" onclick="window.promptPwaInstall()">
            <i class="bi bi-download me-1"></i> Pasang
          </button>
          <button type="button" class="btn-close small ms-1" aria-label="Tutup" onclick="window.dismissPwaBanner()"></button>
        </div>
      </div>
    `;

    document.body.appendChild(banner);
    setTimeout(() => banner.classList.add('show'), 150);
  }

  window.dismissPwaBanner = function () {
    const banner = document.getElementById('pwa-install-banner');
    if (banner) {
      banner.classList.remove('show');
      setTimeout(() => banner.remove(), 300);
      localStorage.setItem('pwa_banner_dismissed_at', Date.now().toString());
    }
  };

  function hideInstallBanner() {
    const banner = document.getElementById('pwa-install-banner');
    if (banner) {
      banner.classList.remove('show');
      setTimeout(() => banner.remove(), 300);
    }
  }

  // 6. Update Notification Toast
  function showUpdateToast(reg) {
    if (document.getElementById('pwa-update-toast')) return;

    const toast = document.createElement('div');
    toast.id = 'pwa-update-toast';
    toast.className = 'pwa-update-toast shadow-lg';
    toast.innerHTML = `
      <div class="d-flex align-items-center gap-3">
        <i class="bi bi-arrow-repeat fs-4 text-primary animate-spin"></i>
        <div class="flex-grow-1">
          <div class="fw-bold" style="font-size: 0.9rem;">Pembaruan Tersedia</div>
          <div class="text-muted small" style="font-size: 0.78rem;">Versi baru SimpleAkunting telah diunduh.</div>
        </div>
        <button type="button" id="pwa-refresh-btn" class="btn btn-sm btn-primary rounded-pill px-3 fw-medium">
          Perbarui
        </button>
      </div>
    `;

    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 100);

    const refreshBtn = toast.querySelector('#pwa-refresh-btn');
    refreshBtn.addEventListener('click', () => {
      if (reg && reg.waiting) {
        reg.waiting.postMessage({ action: 'skipWaiting' });
      }
    });
  }

  // 7. General Toast Helper
  function showToast(messageHtml) {
    const toast = document.createElement('div');
    toast.className = 'pwa-generic-toast shadow';
    toast.innerHTML = messageHtml;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 50);
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  }

  // 8. iOS Safari Instructions Modal
  function showIosInstallModal() {
    if (document.getElementById('pwa-ios-modal')) return;

    const modal = document.createElement('div');
    modal.id = 'pwa-ios-modal';
    modal.className = 'pwa-ios-overlay';
    modal.innerHTML = `
      <div class="pwa-ios-card shadow-lg">
        <div class="text-center mb-3">
          <div class="pwa-banner-icon mx-auto mb-2" style="width: 50px; height: 50px; font-size: 1.5rem;">
            <i class="bi bi-apple"></i>
          </div>
          <h5 class="fw-bold mb-1">Pasang di iPhone / iPad</h5>
          <p class="text-muted small">Ikuti 2 langkah mudah di bawah ini:</p>
        </div>
        <ol class="text-start small ps-3 mb-4">
          <li class="mb-2">Ketuk tombol <strong>Bagikan / Share</strong> (<i class="bi bi-box-arrow-up text-primary"></i>) di bilah navigasi Safari.</li>
          <li>Gulir ke bawah dan pilih <strong>"Tambah ke Layar Utama" (Add to Home Screen)</strong> (<i class="bi bi-plus-square"></i>).</li>
        </ol>
        <button type="button" class="btn btn-outline-secondary w-100 rounded-pill" onclick="this.closest('.pwa-ios-overlay').remove()">
          Mengerti
        </button>
      </div>
    `;

    document.body.appendChild(modal);
  }

  // 9. Online / Offline Connectivity Detection
  function handleConnectivity(isOnline) {
    let indicator = document.getElementById('pwa-connectivity-badge');
    if (!indicator) {
      indicator = document.createElement('div');
      indicator.id = 'pwa-connectivity-badge';
      indicator.className = 'pwa-connectivity-badge';
      document.body.appendChild(indicator);
    }

    if (isOnline) {
      indicator.className = 'pwa-connectivity-badge online show';
      indicator.innerHTML = '<i class="bi bi-wifi me-1"></i> Koneksi Pulih (Online)';
      setTimeout(() => {
        indicator.classList.remove('show');
      }, 3000);
    } else {
      indicator.className = 'pwa-connectivity-badge offline show';
      indicator.innerHTML = '<i class="bi bi-wifi-off me-1"></i> Anda Sedang Offline';
    }
  }

  window.addEventListener('online', () => handleConnectivity(true));
  window.addEventListener('offline', () => handleConnectivity(false));
})();
