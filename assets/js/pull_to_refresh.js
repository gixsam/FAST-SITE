/**
 * =========================================================================
 * assets/js/pull_to_refresh.js — Native Pull-to-Refresh Engine for Fast Site
 * Provides ultra-smooth, native-app swipe down to refresh for mobile APKs
 * and mobile browsers with haptic feedback & glassmorphism HUD.
 * Strictly requires a deliberate downward pull (>110px) at the very top of
 * the page, and never interferes with buttons, links, or normal taps.
 * =========================================================================
 */
(function() {
  'use strict';

  // Prevent multiple initializations
  if (window.__fastsite_ptr_initialized) return;
  window.__fastsite_ptr_initialized = true;

  let startX = 0;
  let startY = 0;
  let currentX = 0;
  let currentY = 0;
  let isPulling = false;
  let isRefreshing = false;
  let hasMovedDownward = false;
  const THRESHOLD = 110; // Explicit px distance required to trigger refresh
  const MAX_PULL = 160;  // Max visual HUD translation

  // Create & Inject HUD elements into DOM
  function createPtrHUD() {
    if (document.getElementById('fastsite-ptr-container')) return;
    const style = document.createElement('style');
    style.id = 'fastsite-ptr-style';
    style.textContent = `
      #fastsite-ptr-container {
        position: fixed;
        top: -90px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 999999;
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(13, 16, 28, 0.96);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(252, 185, 0, 0.4);
        border-radius: 50px;
        padding: 8px 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7), 0 0 15px rgba(252, 185, 0, 0.25);
        transition: top 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.2s ease;
        opacity: 0;
        pointer-events: none;
        user-select: none;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      }
      #fastsite-ptr-icon {
        width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fcb900;
        transition: transform 0.2s ease;
      }
      #fastsite-ptr-text {
        color: #f1f5f9;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.02em;
      }
      .ptr-spinning {
        animation: fastsite-ptr-spin 0.8s linear infinite !important;
      }
      @keyframes fastsite-ptr-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
    `;
    document.head.appendChild(style);

    const hud = document.createElement('div');
    hud.id = 'fastsite-ptr-container';
    hud.innerHTML = `
      <div id="fastsite-ptr-icon">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="#fcb900" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <polyline points="19 12 12 19 5 12"></polyline>
        </svg>
      </div>
      <span id="fastsite-ptr-text">Pull down to refresh</span>
    `;
    document.body.appendChild(hud);
  }

  let hudElem = null;
  let iconElem = null;
  let textElem = null;
  let rafId = null;

  function getScrollTop() {
    return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
  }

  function isInteractiveElement(target) {
    if (!target) return false;
    return !!target.closest('a, button, input, textarea, select, label, .btn, .b-nav-item, .segment-btn, .cat-pill, .pcard, .pcard-share-btn, .shop-card, .trending-card, .fs-switch, .modal-box, .drawer-menu, [onclick]');
  }

  function onTouchStart(e) {
    if (isRefreshing) return;

    // Only activate if at the very top of the page (0px) and single touch
    if (getScrollTop() <= 0 && e.touches && e.touches.length === 1) {
      const target = e.target;
      // Do not initiate PTR on interactive elements
      if (isInteractiveElement(target)) {
        isPulling = false;
        return;
      }

      startX = e.touches[0].clientX;
      startY = e.touches[0].clientY;
      currentX = startX;
      currentY = startY;
      hasMovedDownward = false;
      isPulling = true;

      document.addEventListener('touchmove', onTouchMove, { passive: true });
      document.addEventListener('touchend', onTouchEnd, { passive: true });
      document.addEventListener('touchcancel', onTouchCancel, { passive: true });
    } else {
      isPulling = false;
    }
  }

  function onTouchMove(e) {
    if (!isPulling || isRefreshing || !e.touches || e.touches.length !== 1) return;

    currentX = e.touches[0].clientX;
    currentY = e.touches[0].clientY;
    const diffX = currentX - startX;
    const diffY = currentY - startY;

    // Check if horizontal scroll or upward scroll
    if (diffY <= 0 || Math.abs(diffX) > diffY * 0.8 || getScrollTop() > 0) {
      resetPtrState();
      return;
    }

    if (diffY > 15) {
      hasMovedDownward = true;
      if (rafId) cancelAnimationFrame(rafId);
      rafId = requestAnimationFrame(() => {
        const pullDistance = Math.min(MAX_PULL, diffY * 0.45);
        if (!hudElem) {
          hudElem = document.getElementById('fastsite-ptr-container');
          iconElem = document.getElementById('fastsite-ptr-icon');
          textElem = document.getElementById('fastsite-ptr-text');
        }

        if (hudElem && iconElem && textElem) {
          const topNav = document.querySelector('.top-nav, .public-nav');
          const navOffset = topNav ? topNav.offsetHeight : 60;
          hudElem.style.top = `${navOffset + Math.max(10, pullDistance * 0.6)}px`;
          hudElem.style.opacity = Math.min(1, pullDistance / 40);

          if (pullDistance >= THRESHOLD * 0.45) {
            iconElem.style.transform = 'rotate(180deg)';
            textElem.textContent = 'Release to refresh';
            textElem.style.color = '#00e676';
          } else {
            iconElem.style.transform = 'rotate(0deg)';
            textElem.textContent = 'Pull down to refresh';
            textElem.style.color = '#f1f5f9';
          }
        }
      });
    }
  }

  function onTouchEnd(e) {
    cleanupListeners();
    if (!isPulling || isRefreshing || !hasMovedDownward) {
      resetPtrState();
      return;
    }
    isPulling = false;

    const diffY = currentY - startY;
    const diffX = currentX - startX;
    const pullDistance = diffY * 0.45;

    // Strictly enforce minimum pull distance (>110px raw drag) and vertical direction
    if (diffY >= THRESHOLD && pullDistance >= (THRESHOLD * 0.45) && Math.abs(diffX) < diffY * 0.6 && getScrollTop() <= 0) {
      isRefreshing = true;
      if (hudElem && iconElem && textElem) {
        const topNav = document.querySelector('.top-nav, .public-nav');
        const navOffset = topNav ? topNav.offsetHeight : 60;
        hudElem.style.top = `${navOffset + 15}px`;
        hudElem.style.opacity = '1';
        iconElem.innerHTML = `
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="#00e676" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path d="M23 4v6h-6"></path>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
          </svg>
        `;
        iconElem.classList.add('ptr-spinning');
        textElem.textContent = 'Refreshing Fast Site...';
        textElem.style.color = '#00e676';
      }

      setTimeout(() => {
        window.location.reload();
      }, 350);
    } else {
      resetPtrState();
    }
  }

  function onTouchCancel() {
    cleanupListeners();
    resetPtrState();
  }

  function cleanupListeners() {
    document.removeEventListener('touchmove', onTouchMove);
    document.removeEventListener('touchend', onTouchEnd);
    document.removeEventListener('touchcancel', onTouchCancel);
  }

  function resetPtrState() {
    isPulling = false;
    hasMovedDownward = false;
    startY = 0;
    currentY = 0;
    if (hudElem && !isRefreshing) {
      hudElem.style.top = '-90px';
      hudElem.style.opacity = '0';
      if (iconElem) iconElem.style.transform = 'rotate(0deg)';
    }
  }

  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    createPtrHUD();
    hudElem = document.getElementById('fastsite-ptr-container');
    iconElem = document.getElementById('fastsite-ptr-icon');
    textElem = document.getElementById('fastsite-ptr-text');
    document.addEventListener('touchstart', onTouchStart, { passive: true });
  }
})();
