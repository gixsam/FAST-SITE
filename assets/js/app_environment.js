/**
 * FAST SITE — Native App & Mobile Environment Engine (Phase 94)
 * - Android WebView APK ([FAST SITE]AndroidApp) & PWA Standalone Detection
 * - Dynamic Viewport Height (100dvh fallback via CSS variable --dvh)
 * - Hardware Back Button & Drawer/Modal State Management
 * - Universal Table Auto-Collapse & Responsive Wrapping
 * - Non-blocking Glassmorphism Toast Notification Engine
 * - Mobile-First Inline Form Validation with Red Border & Helper Feedback
 */
(function() {
  'use strict';

  // ─── 1. Core Environment Detection ───
  function initAppEnvironment() {
    const ua = typeof navigator !== 'undefined' ? (navigator.userAgent || '') : '';
    
    // Android APK Detection
    const isApk = ua.includes('[FAST SITE]AndroidApp') || 
                  ua.includes('FastSiteAPK') || 
                  (typeof window !== 'undefined' && (window.AndroidApp !== undefined || window.FastSiteApp !== undefined));
    
    // Standalone PWA / WebApp Detection
    const isStandalone = typeof window !== 'undefined' && (
      (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) ||
      (window.navigator && window.navigator.standalone === true) ||
      document.referrer.includes('android-app://')
    );

    const docEl = document.documentElement;
    if (isApk) {
      docEl.classList.add('is-apk-app');
      window.isApkApp = true;
    }
    if (isStandalone) {
      docEl.classList.add('is-standalone-app');
      window.isStandaloneApp = true;
    }

    // ─── 2. Viewport Dynamic Unit Fallback (--dvh) ───
    function updateDvhUnit() {
      const dvh = window.innerHeight * 0.01;
      docEl.style.setProperty('--dvh', `${dvh}px`);
    }
    updateDvhUnit();
    window.addEventListener('resize', updateDvhUnit, { passive: true });
    window.addEventListener('orientationchange', updateDvhUnit, { passive: true });

    // ─── 3. DOM Ready Enhancements ───
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', onDomReady);
    } else {
      onDomReady();
    }

    function onDomReady() {
      // Hide redundant install/download prompts in native APK & PWA
      if (isApk) {
        document.querySelectorAll('.apk-download-card, .apk-download-btn, .hide-in-apk, #apkDownloadSection').forEach(el => {
          el.style.display = 'none';
        });
      }
      if (isStandalone) {
        document.querySelectorAll('.pwa-install-prompt, .hide-in-pwa').forEach(el => {
          el.style.display = 'none';
        });
      }

      // Auto-wrap bare tables in horizontal scroll containers on mobile
      autoWrapResponsiveTables();

      // Setup physical back-button navigation for drawers/modals
      initHardwareBackHandler();

      // Mobile Inline Form Validation
      initInlineFormValidation();
    }

    // Broadcast ready event
    window.dispatchEvent(new CustomEvent('app-environment-ready', {
      detail: { isApk, isStandalone }
    }));
  }

  // ─── 4. Auto-wrap Tables for Horizontal Scrolling on Small Screens ───
  function autoWrapResponsiveTables() {
    try {
      const tables = document.querySelectorAll('table:not(.no-auto-wrap)');
      tables.forEach(table => {
        const parent = table.parentElement;
        if (!parent) return;
        const parentStyle = window.getComputedStyle(parent);
        const alreadyScrollable = parent.classList.contains('table-responsive') || 
                                  parent.classList.contains('overflow-x-auto') ||
                                  parentStyle.overflowX === 'auto' || 
                                  parentStyle.overflowX === 'scroll';
        
        if (!alreadyScrollable) {
          const wrapper = document.createElement('div');
          wrapper.className = 'table-responsive overflow-x-auto';
          wrapper.style.width = '100%';
          wrapper.style.maxWidth = '100%';
          wrapper.style.overflowX = 'auto';
          wrapper.style.webkitOverflowScrolling = 'touch';
          wrapper.style.marginBottom = '1rem';
          parent.insertBefore(wrapper, table);
          wrapper.appendChild(table);
        }
      });
    } catch (e) {
      console.warn('[FAST SITE] Table auto-wrap notice:', e);
    }
  }

  // ─── 5. Android Physical Back Button & Drawer State Management ───
  function initHardwareBackHandler() {
    let hasModalHistory = false;

    // Observe changes to drawers / modals to manage history stack
    function getActiveDrawerOrModal() {
      return document.querySelector(
        '.drawer-menu.active, #drawerMenu.active, #categoryDrawer.active, .category-drawer.active, .modal.show, .modal.active, .bottom-sheet.active'
      );
    }

    // Hook popstate for back button
    window.addEventListener('popstate', function(e) {
      const activeModal = getActiveDrawerOrModal();
      if (activeModal) {
        // Close modal/drawer instead of leaving the application page
        if (typeof window.toggleDrawer === 'function' && document.getElementById('drawerMenu')?.classList.contains('active')) {
          window.toggleDrawer();
        } else if (typeof window.toggleCategoryDrawer === 'function' && document.getElementById('categoryDrawer')?.classList.contains('active')) {
          window.toggleCategoryDrawer();
        } else {
          activeModal.classList.remove('active', 'show');
          const overlay = document.querySelector('.drawer-overlay.active, #drawerOverlay.active, .modal-backdrop, .modal-overlay.active');
          if (overlay) overlay.classList.remove('active', 'show');
        }
        hasModalHistory = false;
      }
    });

    // Push state when drawer/modal opens so back button pops it
    window.pushModalBackState = function() {
      if (!hasModalHistory && window.history && window.history.pushState) {
        window.history.pushState({ fastSiteModal: true }, '');
        hasModalHistory = true;
      }
    };
  }

  // ─── 6. Modern Glassmorphic Toast Notification System ───
  window.showToast = function(message, type = 'info', duration = 3500) {
    let container = document.getElementById('fs-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'fs-toast-container';
      container.className = 'toast-container';
      container.style.cssText = `
        position: fixed;
        bottom: calc(var(--mobile-dock-height, 64px) + env(safe-area-inset-bottom, 16px) + 16px);
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        pointer-events: none;
        width: 90%;
        max-width: 420px;
      `;
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'alert-toast floating-snackbar';
    
    let borderColor = 'rgba(252, 185, 0, 0.4)';
    let icon = 'ℹ️';
    if (type === 'success') {
      borderColor = 'rgba(16, 185, 129, 0.5)';
      icon = '✅';
    } else if (type === 'error' || type === 'danger') {
      borderColor = 'rgba(239, 68, 68, 0.6)';
      icon = '⚠️';
    } else if (type === 'gold' || type === 'coin') {
      borderColor = 'rgba(252, 185, 0, 0.8)';
      icon = '🪙';
    }

    toast.style.cssText = `
      background: rgba(18, 22, 43, 0.94);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid ${borderColor};
      color: #f8fafc;
      padding: 10px 16px;
      border-radius: 12px;
      font-size: 0.86rem;
      font-weight: 600;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
      display: flex;
      align-items: center;
      gap: 10px;
      pointer-events: auto;
      opacity: 0;
      transform: translateY(20px) scale(0.95);
      transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
      width: 100%;
    `;

    toast.innerHTML = `<span style="font-size: 1.1rem; flex-shrink: 0;">${icon}</span><span style="flex: 1; word-break: break-word;">${message}</span>`;
    container.appendChild(toast);

    // Trigger animation in
    requestAnimationFrame(() => {
      toast.style.opacity = '1';
      toast.style.transform = 'translateY(0) scale(1)';
    });

    // Auto dismiss
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(12px) scale(0.95)';
      setTimeout(() => {
        toast.remove();
      }, 300);
    }, duration);
  };

  // ─── 7. Mobile-First Client-Side Inline Form Validation ───
  function initInlineFormValidation() {
    document.querySelectorAll('form').forEach(form => {
      if (form.getAttribute('data-skip-inline-val') === 'true' || form.classList.contains('no-inline-val')) {
        return;
      }

      const inputs = form.querySelectorAll('input, select, textarea');

      inputs.forEach(input => {
        const clearError = function() {
          if (input.classList.contains('is-invalid')) {
            input.classList.remove('is-invalid');
            const err = input.parentNode.querySelector('.form-inline-error');
            if (err) err.remove();
          }
        };
        input.addEventListener('input', clearError, { passive: true });
        input.addEventListener('change', clearError, { passive: true });
      });

      form.addEventListener('submit', function(e) {
        let firstInvalid = null;
        let hasError = false;

        inputs.forEach(input => {
          // Check required validation
          if (input.hasAttribute('required') && !input.value.trim()) {
            hasError = true;
            input.classList.add('is-invalid');

            let err = input.parentNode.querySelector('.form-inline-error');
            if (!err) {
              err = document.createElement('div');
              err.className = 'form-inline-error';
              err.style.color = '#ef4444';
              err.style.fontSize = '0.75rem';
              err.style.fontWeight = '600';
              err.style.marginTop = '4px';
              err.style.display = 'flex';
              err.style.alignItems = 'center';
              err.style.gap = '4px';
              err.innerHTML = '<span>⚠️</span> This field is required';
              input.parentNode.appendChild(err);
            }

            if (!firstInvalid) firstInvalid = input;
          }
        });

        if (hasError && firstInvalid) {
          e.preventDefault();
          firstInvalid.focus();
          firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
          if (typeof window.showToast === 'function') {
            window.showToast('Please fill out all required fields marked in red.', 'error', 3000);
          }
        }
      });
    });
  }

  // Execute immediate init
  initAppEnvironment();
})();
