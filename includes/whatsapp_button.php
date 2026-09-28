<!-- ==========================================
     FAST SITE — OFFICIAL WHATSAPP FLOATING BUTTON
     Direct Support: +8801337320544
     ========================================== -->
<div id="fastsite-whatsapp-widget">
  <a href="https://wa.me/8801337320544?text=Hello%20Fast%20Site%20Support%2C%20I%20need%20assistance." target="_blank" rel="noopener noreferrer" class="fsw-btn" title="Chat on WhatsApp (+8801337320544)" aria-label="Chat with Fast Site on WhatsApp">
    <!-- WhatsApp Official SVG -->
    <svg class="fsw-icon" viewBox="0 0 32 32" fill="currentColor">
      <path d="M16.02 2C8.29 2 2.01 8.27 2.01 16c0 2.65.74 5.12 2.02 7.24L2 30l7.01-1.98C11.05 29.17 13.46 30 16.02 30c7.73 0 14.01-6.27 14.01-14S23.75 2 16.02 2zm8.13 19.92c-.34.96-1.71 1.76-2.77 1.99-.73.16-1.68.29-4.88-1.04-4.09-1.69-6.72-5.83-6.93-6.1-.2-.28-1.67-2.22-1.67-4.24s1.05-3.01 1.43-3.42c.38-.41.83-.51 1.1-.51.28 0 .55.01.79.02.26.01.6-.1 1 .84.34.82 1.16 2.83 1.26 3.04.1.21.17.46.03.73-.13.28-.2.45-.4.68-.2.24-.43.53-.61.71-.21.21-.43.43-.19.84.25.41 1.09 1.79 2.34 2.91 1.61 1.43 2.96 1.88 3.38 2.09.42.21.66.18.91-.1.25-.29 1.07-1.25 1.36-1.68.28-.43.57-.36.96-.21.39.14 2.47 1.17 2.89 1.38.43.21.71.31.82.49.1.18.1 1.04-.24 2z"/>
    </svg>
    <span class="fsw-pulse"></span>
    <span class="fsw-tooltip">💬 Chat on WhatsApp<br><strong style="font-size:0.75rem; color:#fcb900;">+8801337320544</strong></span>
  </a>
</div>

<style>
#fastsite-whatsapp-widget {
  position: fixed;
  bottom: 25px;
  right: 25px;
  z-index: 999999;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

.fsw-btn {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 58px;
  height: 58px;
  background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
  color: #ffffff !important;
  border-radius: 50%;
  box-shadow: 0 8px 25px rgba(37, 211, 102, 0.45);
  text-decoration: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.fsw-btn:hover {
  transform: translateY(-4px) scale(1.08);
  box-shadow: 0 12px 35px rgba(37, 211, 102, 0.65);
}

.fsw-icon {
  width: 32px;
  height: 32px;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
}

.fsw-pulse {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  border: 2px solid #25D366;
  animation: fsw-pulse-animation 2s infinite cubic-bezier(0.4, 0, 0.2, 1);
  pointer-events: none;
}

@keyframes fsw-pulse-animation {
  0% {
    transform: scale(0.95);
    opacity: 0.9;
  }
  50% {
    transform: scale(1.35);
    opacity: 0;
  }
  100% {
    transform: scale(0.95);
    opacity: 0;
  }
}

.fsw-tooltip {
  position: absolute;
  right: 70px;
  background: rgba(15, 18, 28, 0.96);
  color: #fff;
  border: 1px solid rgba(37, 211, 102, 0.3);
  padding: 8px 14px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 700;
  white-space: nowrap;
  box-shadow: 0 10px 30px rgba(0,0,0,0.6);
  pointer-events: none;
  opacity: 0;
  transform: translateX(10px);
  transition: all 0.25s ease;
  line-height: 1.35;
  text-align: left;
  backdrop-filter: blur(8px);
}

.fsw-btn:hover .fsw-tooltip {
  opacity: 1;
  transform: translateX(0);
}

@media (max-width: 768px) {
  #fastsite-whatsapp-widget {
    display: none !important; /* WhatsApp is integrated inside the mobile bottom navigation bar */
  }
}
</style>
