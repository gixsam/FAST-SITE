<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gemini AI Assistant - Fast Site</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
    <?php include 'nav.php'; ?>
    <div class="main-content">
        <div class="header">
            <h1>✨ Gemini AI Assistant</h1>
            <div style="font-size: 0.85rem; color: var(--muted); background: rgba(255,255,255,0.05); padding: 0.4rem 0.8rem; border-radius: 50px;">Strictly adheres to Fast Site templates</div>
            <div style="font-size: 0.85rem; color: #fcb900; background: rgba(252,185,0,0.1); padding: 0.4rem 0.8rem; border-radius: 6px; margin-top: 10px; border: 1px solid rgba(252,185,0,0.3);">
                <strong>Note:</strong> A Google One AI Premium subscription does NOT provide developer API access. You must create a FREE Developer API Key at <a href="https://aistudio.google.com/app/apikey" target="_blank" style="color: #fff; text-decoration: underline;">Google AI Studio</a> and save it in Settings.
            </div>
        </div>
        
        <div class="card">
            <div class="ai-controls">
                <button class="ai-btn active" onclick="setMode('product_desc', this)">🛍️ Generate Product Description</button>
                <button class="ai-btn" onclick="setMode('service_desc', this)">🪪 Generate Service Description</button>
                <button class="ai-btn" onclick="setMode('custom', this)">💬 Custom AI Request</button>
            </div>
            
            <div class="chat-area" id="chatArea">
                <div class="msg bot">
                    <strong>System:</strong> Hello Admin! I am hooked up to Google Gemini 1.5 Pro and tuned specifically to the Fast Site branding. What do you need help with today?
                </div>
            </div>
            
            <div class="input-area">
                <textarea id="aiInput" placeholder="Describe the product/service or give me a prompt..."></textarea>
                <button onclick="sendPrompt()">Send ✨</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        let currentMode = 'product_desc';

        function setMode(mode, btnElement) {
            currentMode = mode;
            document.querySelectorAll('.ai-btn').forEach(b => b.classList.remove('active'));
            btnElement.classList.add('active');
        }

        async function sendPrompt() {
            const inputEl = document.getElementById('aiInput');
            const chatArea = document.getElementById('chatArea');
            const text = inputEl.value.trim();
            if (!text) return;

            // Add User msg
            const userMsg = document.createElement('div');
            userMsg.className = 'msg user';
            userMsg.textContent = text;
            chatArea.appendChild(userMsg);
            
            inputEl.value = '';
            chatArea.scrollTop = chatArea.scrollHeight;

            // Add Loading bot msg
            const botMsg = document.createElement('div');
            botMsg.className = 'msg bot';
            botMsg.innerHTML = '<span style="opacity:0.5;">Generating response... ⏳</span>';
            chatArea.appendChild(botMsg);
            chatArea.scrollTop = chatArea.scrollHeight;

            try {
                const res = await fetch('api_gemini.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: currentMode, input: text })
                });
                const data = await res.json();
                
                if (data.success) {
                    botMsg.innerHTML = marked.parse(data.output);
                } else {
                    botMsg.innerHTML = `<span style="color: #ef4444;">❌ Error: ${data.message}</span>`;
                }
            } catch (err) {
                botMsg.innerHTML = `<span style="color: #ef4444;">❌ Request failed. Make sure the API key is set in Settings.</span>`;
            }
            chatArea.scrollTop = chatArea.scrollHeight;
        }
    </script>
</body>
</html>
