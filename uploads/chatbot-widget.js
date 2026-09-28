// chatbot-widget.js - Premium AI Chatbot Widget Loader
(function() {
  const currentScript = document.currentScript || (function() {
    const scripts = document.getElementsByTagName('script');
    return scripts[scripts.length - 1];
  })();

  const brand = currentScript.getAttribute('data-brand') || 'fast-site';
  const serverUrl = 'https://chatbot.best-travel.ltd';
  
  const botNames = {
    'fast-site': 'Fast Site Assistant',
    'best-travel': 'Best Travel Guide',
    'ayra-mart': 'Ayra Mart Fashion Bot',
    'affi-bangla': 'Affi Bangla Deal Finder',
    'enzor': 'Enzor Motors Advisor'
  };
  const botName = botNames[brand] || 'AI Assistant';

  let sessionId = localStorage.getItem(`fs_chat_session_${brand}`);
  if (!sessionId) {
    sessionId = 'sess_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
    localStorage.setItem(`fs_chat_session_${brand}`, sessionId);
  }

  const socketScript = document.createElement('script');
  socketScript.src = "https://cdn.socket.io/4.7.5/socket.io.min.js";
  socketScript.onload = initializeWidget;
  document.head.appendChild(socketScript);

  function initializeWidget() {
    const style = document.createElement('style');
    style.innerHTML = `
      :root {
        --fs-chat-primary: #6366f1;
        --fs-chat-bg: rgba(16, 16, 26, 0.95);
        --fs-chat-text: #f3f4f6;
        --fs-chat-gold: #fbc02d;
        --fs-chat-border: rgba(251, 192, 45, 0.25);
      }
      .fs-chat-launcher {
        position: fixed;
        bottom: 90px;
        right: 24px;
        width: 60px;
        height: 60px;
        background: var(--fs-chat-bg);
        border: 2px solid var(--fs-chat-gold);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        z-index: 999999;
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      }
      .fs-chat-launcher:hover {
        transform: scale(1.1) rotate(5deg);
      }
      .fs-chat-launcher svg {
        width: 28px;
        height: 28px;
        fill: var(--fs-chat-gold);
      }
      .fs-chat-container {
        position: fixed;
        bottom: 96px;
        right: 24px;
        width: 370px;
        height: 520px;
        background: var(--fs-chat-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1.5px solid var(--fs-chat-border);
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
        z-index: 999999;
        opacity: 0;
        transform: translateY(20px) scale(0.95);
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      }
      .fs-chat-container.open {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: auto;
      }
      .fs-chat-header {
        background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%);
        border-bottom: 1.5px solid var(--fs-chat-border);
        padding: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
      }
      .fs-chat-header-info {
        display: flex;
        align-items: center;
        gap: 10px;
      }
      .fs-chat-avatar {
        width: 36px;
        height: 36px;
        background: rgba(251, 192, 45, 0.1);
        border: 1px solid var(--fs-chat-gold);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: var(--fs-chat-gold);
      }
      .fs-chat-title-group {
        display: flex;
        flex-direction: column;
      }
      .fs-chat-title {
        font-family: 'Inter', sans-serif;
        font-weight: 600;
        font-size: 14px;
        color: var(--fs-chat-text);
      }
      .fs-chat-subtitle {
        font-size: 11px;
        color: #10b981;
        display: flex;
        align-items: center;
        gap: 4px;
      }
      .fs-chat-subtitle::before {
        content: '';
        width: 6px;
        height: 6px;
        background: #10b981;
        border-radius: 50%;
        display: inline-block;
      }
      .fs-chat-close {
        background: transparent;
        border: none;
        cursor: pointer;
        color: #9ca3af;
        transition: color 0.2s;
      }
      .fs-chat-close:hover {
        color: #f3f4f6;
      }
      .fs-chat-messages {
        flex: 1;
        padding: 16px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
      }
      .fs-chat-msg {
        max-width: 80%;
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 13.5px;
        line-height: 1.4;
        word-wrap: break-word;
      }
      .fs-chat-msg.bot {
        align-self: flex-start;
        background: rgba(255, 255, 255, 0.06);
        color: var(--fs-chat-text);
        border: 1px solid rgba(255, 255, 255, 0.05);
      }
      .fs-chat-msg.user {
        align-self: flex-end;
        background: var(--fs-chat-primary);
        color: #fff;
      }
      .fs-chat-typing {
        align-self: flex-start;
        background: rgba(255, 255, 255, 0.04);
        padding: 10px 14px;
        border-radius: 12px;
        display: none;
        align-items: center;
        gap: 4px;
      }
      .fs-chat-typing span {
        width: 6px;
        height: 6px;
        background: #9ca3af;
        border-radius: 50%;
        animation: fs-bounce 1.4s infinite both;
      }
      .fs-chat-typing span:nth-child(2) { animation-delay: .2s; }
      .fs-chat-typing span:nth-child(3) { animation-delay: .4s; }
      
      @keyframes fs-bounce {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1.0); }
      }
      
      .fs-chat-input-area {
        padding: 12px;
        border-top: 1.5px solid var(--fs-chat-border);
        display: flex;
        gap: 8px;
        background: rgba(0, 0, 0, 0.2);
      }
      .fs-chat-input {
        flex: 1;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 10px 12px;
        color: #fff;
        font-size: 13.5px;
        outline: none;
        transition: border-color 0.2s;
      }
      .fs-chat-input:focus {
        border-color: var(--fs-chat-gold);
      }
      .fs-chat-send {
        background: var(--fs-chat-gold);
        border: none;
        border-radius: 8px;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.2s;
      }
      .fs-chat-send:hover {
        transform: scale(1.05);
      }
      .fs-chat-send svg {
        width: 18px;
        height: 18px;
        fill: #000;
        transform: rotate(45deg);
      }
      
      @media (max-width: 480px) {
        .fs-chat-container {
          bottom: 0; 
          right: 0;
          width: 100%; 
          height: 100dvh;
          border-radius: 0;
          border: none;
        }
        .fs-chat-launcher {
          bottom: 90px; right: 24px;
        }
      }
    `;
    document.head.appendChild(style);

    const container = document.createElement('div');
    container.className = 'fs-chat-container';
    container.innerHTML = `
      <div class="fs-chat-header">
        <div class="fs-chat-header-info">
          <div class="fs-chat-avatar">${botName[0]}</div>
          <div class="fs-chat-title-group">
            <span class="fs-chat-title">${botName}</span>
            <span class="fs-chat-subtitle">Online</span>
          </div>
        </div>
        <button class="fs-chat-close">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
      </div>
      <div class="fs-chat-messages" id="fs-chat-messages-box">
        <div class="fs-chat-typing" id="fs-chat-typing-indicator">
          <span></span><span></span><span></span>
        </div>
      </div>
      <div class="fs-chat-input-area">
        <input type="text" class="fs-chat-input" id="fs-chat-text-input" placeholder="Type a message..." autocomplete="off" />
        <button class="fs-chat-send" id="fs-chat-send-btn">
          <svg viewBox="0 0 24 24"><path d="M2,21L23,12L2,3V10L17,12L2,14V21Z"/></svg>
        </button>
      </div>
    `;
    document.body.appendChild(container);

    const launcher = document.createElement('div');
    launcher.className = 'fs-chat-launcher';
    launcher.innerHTML = `<svg viewBox="0 0 24 24"><path d="M20,2H4C2.9,2,2,2.9,2,4v18l4-4h14c1.1,0,2-0.9,2-2V4C22,2.9,21.1,2,20,2z M20,14H6l-2,2V4h16V14z"/></svg>`;
    document.body.appendChild(launcher);

    const socket = io(serverUrl);
    const messagesBox = document.getElementById('fs-chat-messages-box');
    const textInput = document.getElementById('fs-chat-text-input');
    const sendBtn = document.getElementById('fs-chat-send-btn');
    const typingIndicator = document.getElementById('fs-chat-typing-indicator');

    let isInitialized = false;

    launcher.addEventListener('click', () => {
      container.classList.add('open');
      launcher.style.display = 'none'; // HIDE LAUNCHER SO IT DOES NOT OVERLAP!
      textInput.focus();
      
      if (!isInitialized) {
        socket.emit('init', { sessionId, brand });
        isInitialized = true;
      }
    });

    container.querySelector('.fs-chat-close').addEventListener('click', () => {
      container.classList.remove('open');
      launcher.style.display = 'flex'; // BRING LAUNCHER BACK!
    });

    function addMessage(sender, text) {
      const msg = document.createElement('div');
      msg.className = `fs-chat-msg ${sender}`;
      msg.innerText = text;
      messagesBox.insertBefore(msg, typingIndicator);
      messagesBox.scrollTop = messagesBox.scrollHeight;
    }

    function sendMessage() {
      const text = textInput.value.trim();
      if (!text) return;
      
      addMessage('user', text);
      textInput.value = '';
      
      socket.emit('message', { sessionId, brand, text });
    }

    sendBtn.addEventListener('click', sendMessage);
    textInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') sendMessage();
    });

    socket.on('message', (data) => {
      addMessage('bot', data.text);
    });

    socket.on('typing', (isTyping) => {
      if (isTyping) {
        typingIndicator.style.display = 'flex';
        messagesBox.scrollTop = messagesBox.scrollHeight;
      } else {
        typingIndicator.style.display = 'none';
      }
    });
  }
})();
