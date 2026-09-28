/**
 * =========================================================================
 * assets/js/universal_notifications.js — Real-Time Notification & Live Toast Engine
 * Delivers instant in-app toast banners, dynamic badge counting, and system
 * status-bar notifications for Android APK and Web users.
 * =========================================================================
 */
(function() {
  'use strict';

  if (window.__fastsite_notifications_initialized) return;
  window.__fastsite_notifications_initialized = true;

  const POLL_INTERVAL = 12000; // 12 seconds
  let lastAlertId = sessionStorage.getItem('fastsite_last_alert_id') || '';

  // Inject Live Toast Styles
  function injectToastStyles() {
    if (document.getElementById('fastsite-toast-style')) return;
    const style = document.createElement('style');
    style.id = 'fastsite-toast-style';
    style.textContent = `
      #fastsite-toast-container {
        position: fixed;
        top: -120px;
        left: 50%;
        transform: translateX(-50%);
        width: 92%;
        max-width: 420px;
        z-index: 9999999;
        background: rgba(14, 16, 26, 0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(252, 185, 0, 0.4);
        border-radius: 16px;
        padding: 12px 16px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.7), 0 0 20px rgba(252, 185, 0, 0.2);
        display: flex;
        align-items: center;
        gap: 12px;
        transition: top 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s ease;
        opacity: 0;
        pointer-events: auto;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      }
      #fastsite-toast-container.active {
        top: 16px;
        opacity: 1;
      }
      .toast-badge-dot {
        position: absolute;
        top: -2px;
        right: -2px;
        background: #ff5252;
        color: #fff;
        font-size: 0.6rem;
        font-weight: 800;
        min-width: 8px;
        height: 8px;
        border-radius: 50%;
        box-shadow: 0 0 8px #ff5252;
      }
      .toast-badge-pill {
        background: #ff5252;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 800;
        padding: 1px 6px;
        border-radius: 50px;
        margin-left: 4px;
      }
    `;
    document.head.appendChild(style);
  }

  function createToastElement() {
    if (document.getElementById('fastsite-toast-container')) return;
    const toast = document.createElement('div');
    toast.id = 'fastsite-toast-container';
    toast.innerHTML = `
      <div style="font-size: 1.6rem; flex-shrink: 0;" id="fs-toast-icon">🔔</div>
      <div style="flex: 1; min-width: 0; text-align: left;">
        <h4 id="fs-toast-title" style="margin: 0 0 2px 0; color: #fff; font-size: 0.9rem; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Fast Site Alert</h4>
        <p id="fs-toast-msg" style="margin: 0; color: #94a3b8; font-size: 0.78rem; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">You have a new update.</p>
      </div>
      <a href="javascript:void(0)" id="fs-toast-link" style="background: linear-gradient(135deg, #fcb900, #ff8a00); color: #000; font-size: 0.75rem; font-weight: 800; text-decoration: none; padding: 6px 12px; border-radius: 8px; flex-shrink: 0; white-space: nowrap;">View</a>
      <button type="button" onclick="window.dismissFastSiteToast()" style="background: none; border: none; color: #64748b; font-size: 1.2rem; cursor: pointer; padding: 0 4px; line-height: 1;">&times;</button>
    `;
    document.body.appendChild(toast);
  }

  window.dismissFastSiteToast = function() {
    const toast = document.getElementById('fastsite-toast-container');
    if (toast) {
      toast.classList.remove('active');
    }
  };

  function showInAppToast(alertData) {
    createToastElement();
    const toast = document.getElementById('fastsite-toast-container');
    const titleEl = document.getElementById('fs-toast-title');
    const msgEl = document.getElementById('fs-toast-msg');
    const linkEl = document.getElementById('fs-toast-link');

    if (toast && titleEl && msgEl && linkEl) {
      titleEl.textContent = alertData.title || 'Fast Site Notification';
      msgEl.textContent = alertData.message || '';
      linkEl.href = alertData.url || '/user/dashboard.php';
      
      toast.classList.add('active');

      // Auto dismiss after 6 seconds
      setTimeout(() => {
        toast.classList.remove('active');
      }, 6000);
    }

    // Forward to Android System Notification Bar if inside Native APK
    if (window.FastSiteNative && typeof window.FastSiteNative.showLocalNotification === 'function') {
      try {
        const notifId = Math.floor(Math.random() * 90000) + 10000;
        window.FastSiteNative.showLocalNotification(
          alertData.title || 'Fast Site Update',
          alertData.message || '',
          notifId,
          alertData.url || ''
        );
      } catch (e) {
        console.warn('Native notification error:', e);
      }
    }
  }

  function updateBadgeCounts(count) {
    // 1. Update Floating Bottom Nav Alerts Badge
    const bottomNavAlerts = document.querySelector('#user-floating-bottom-nav a[title="Notifications"]');
    if (bottomNavAlerts) {
      let badge = bottomNavAlerts.querySelector('.toast-badge-dot, .unread-dot');
      if (count > 0) {
        if (!badge) {
          const iconWrap = bottomNavAlerts.querySelector('div') || bottomNavAlerts;
          const newDot = document.createElement('span');
          newDot.className = 'toast-badge-dot';
          iconWrap.appendChild(newDot);
        }
      } else {
        if (badge) badge.remove();
      }
    }

    // 2. Update Top Nav Messages / Alerts Badge
    const topNavMsg = document.querySelector('.top-nav a[title="Messages"]');
    if (topNavMsg) {
      let badge = topNavMsg.querySelector('.toast-badge-pill');
      if (count > 0) {
        if (!badge) {
          badge = document.createElement('span');
          badge.className = 'toast-badge-pill';
          topNavMsg.appendChild(badge);
        }
        badge.textContent = count;
      } else {
        if (badge) badge.remove();
      }
    }
  }

  function pollNotifications() {
    fetch('/api/poll_notifications.php')
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          updateBadgeCounts(data.unread_count || 0);

          if (data.has_new_alert && data.latest_alert) {
            const alertId = String(data.latest_alert.id || '');
            if (alertId !== lastAlertId) {
              lastAlertId = alertId;
              sessionStorage.setItem('fastsite_last_alert_id', alertId);
              showInAppToast(data.latest_alert);
            }
          }
        }
      })
      .catch(() => {
        // Silently retry on next poll cycle
      });
  }

  function init() {
    injectToastStyles();
    createToastElement();
    // Immediate initial poll
    pollNotifications();
    // Background polling interval
    setInterval(pollNotifications, POLL_INTERVAL);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
