<?php
if (!isset($settings)) {
    try { $settings = $pdo->query("SELECT setting_key, setting_value FROM homepage_settings")->fetchAll(PDO::FETCH_KEY_PAIR); } catch(Exception $e) { $settings = []; }
}
?>
<style>
    /* Force the Action Button to be gold (Bypass all cache/dark mode) */
    .dynamic-upload-btn, #action-btn-gold {
        background: #fcb900 !important;
        background-color: #fcb900 !important;
        background-image: none !important;
        color: #000 !important;
    }
    
    .dynamic-upload-btn *, #action-btn-gold * {
        color: #000 !important;
        text-shadow: none !important;
    }
</style>
<footer style="margin-top: 4rem; padding: 3rem 1.5rem; background: var(--surface); border-top: 1px solid rgba(255,255,255,0.05); color: var(--muted); text-align: center; font-size: 0.9rem;">
    <div style="max-width: 1000px; margin: 0 auto; display: flex; flex-wrap: wrap; gap: 2rem; justify-content: space-between; text-align: left;">
        
        <div style="flex: 1; min-width: 250px;">
            <h3 style="color: #fff; margin-bottom: 1rem; font-weight: 800; font-family: 'Oswald', sans-serif; letter-spacing: 1px;"> FAST SITE</h3>
            <p style="line-height: 1.6; margin-bottom: 1rem;">
                The ultimate verified escrow marketplace and digital services hub. Secure transactions powered by <?= htmlspecialchars($settings['coin_name'] ?? 'Fast Coins') ?>.
            </p>
            <div style="display: flex; gap: 1rem;">
                <!-- Social placeholders -->
                <a href="<?= htmlspecialchars($settings['facebook_url'] ?? '#') ?>" style="color: var(--brand); text-decoration: none;">Facebook</a>
                <a href="<?= htmlspecialchars($settings['youtube_url'] ?? '#') ?>" style="color: var(--brand); text-decoration: none;">YouTube</a>
            </div>
        </div>

        <div style="flex: 1; min-width: 200px;">
            <h4 style="color: #fff; margin-bottom: 1rem;">Quick Links</h4>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <a href="/index.php" style="color: var(--muted); text-decoration: none;">Marketplace</a>
                <a href="/about.php" style="color: var(--muted); text-decoration: none;">Company Profile</a>
                <a href="/policy.php" style="color: var(--muted); text-decoration: none;">Terms & Conditions</a>
                <a href="/policy.php" style="color: var(--muted); text-decoration: none;">Privacy Policy</a>
            </div>
        </div>

        <div style="flex: 1; min-width: 200px;">
            <h4 style="color: #fff; margin-bottom: 1rem;">Contact Us</h4>
            <p style="margin-bottom: 0.5rem;">
                <strong>Address:</strong><br>
                Fast Site Headquarters<br>
                Dhaka, Bangladesh
            </p>
            <p style="margin-bottom: 0.5rem;">
                <strong>Phone:</strong><br>
                <?= htmlspecialchars($settings['whatsapp_number'] ?? '+880 1963 601472') ?>
            </p>
        </div>

        <div style="flex: 1; min-width: 220px;">
            <h4 style="color: #fff; margin-bottom: 1rem; font-family:'Oswald',sans-serif;">📱 FAST SITE WORLD APP</h4>
            <div style="display:flex; align-items:center; gap:12px; background:rgba(16,18,28,0.95); border:1px solid rgba(252,185,0,0.3); padding:12px; border-radius:14px; box-shadow:0 4px 15px rgba(0,0,0,0.4);">
                <img src="/assets/images/fast_site_world_app_icon.jpg" style="width:50px; height:50px; border-radius:12px; object-fit:cover; border:1px solid var(--gold,#fcb900);" alt="FAST SITE WORLD Icon" onerror="this.onerror=null; this.src='/assets/images/logo.png';"/>
                <div>
                    <strong style="color:#fff; font-size:0.9rem; display:block; font-weight:800;"><?= htmlspecialchars($settings['apk_app_name'] ?? 'FAST SITE WORLD') ?></strong>
                    <span style="font-size:0.7rem; color:var(--muted); display:block; margin-bottom:4px;"><?= htmlspecialchars($settings['apk_app_version'] ?? 'v2.0.4-world') ?></span>
                    <a href="/<?= htmlspecialchars($settings['apk_download_url'] ?? 'fastsite_storefront.apk') ?>" download style="color:#000; background:linear-gradient(135deg, #fcb900 0%, #f59e0b 100%); font-weight:800; font-size:0.75rem; text-decoration:none; padding:4px 10px; border-radius:6px; display:inline-block;">⬇️ Download APK</a>
                </div>
            </div>
        </div>
    </div>
    
    <div style="border-top: 1px solid rgba(255,255,255,0.05); margin-top: 3rem; padding-top: 1.5rem;">
        &copy; <?= date('Y') ?> <?= htmlspecialchars($settings['site_name'] ?? 'Fast Site') ?>. All rights reserved.
    </div>
</footer>

<!-- ==========================================
     PHASE 22: GEMINI AI CHATBOT WIDGET
     ========================================== -->
<div id="geminiChatWidget" style="display: none !important; position: fixed; bottom: 30px; right: 30px; z-index: 99999; font-family: 'Inter', sans-serif;">
    <!-- Chat Window -->
    <div id="geminiChatWindow" style="display: none; width: 350px; height: 500px; background: rgba(10, 10, 15, 0.95); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); flex-direction: column; overflow: hidden; margin-bottom: 15px; transform-origin: bottom right; transition: all 0.3s ease;">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #fcb900 0%, #ff9800 100%); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.5rem;">✨</span>
                <div>
                    <h4 style="margin: 0; color: #000; font-weight: 800; font-size: 1rem;">Gemini AI Assistant</h4>
                    <span style="font-size: 0.75rem; color: rgba(0,0,0,0.7); font-weight: 600;">Fast Site Escrow Guide</span>
                </div>
            </div>
            <button onclick="toggleGeminiChat()" style="background: rgba(0,0,0,0.1); border: none; color: #000; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-weight: bold;">✕</button>
        </div>

        <!-- Messages Area -->
        <div id="geminiMessages" style="flex: 1; padding: 15px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px;">
            <div style="align-self: flex-start; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 10px 15px; border-radius: 15px; border-bottom-left-radius: 4px; color: #fff; font-size: 0.9rem; max-width: 85%; line-height: 1.4;">
                Hi there! 👋 I'm Gemini, the AI assistant for Fast Site. How can I help you with Escrow or buying products today?
            </div>
        </div>

        <!-- Input Area -->
        <div style="padding: 15px; background: rgba(0,0,0,0.3); border-top: 1px solid rgba(255,255,255,0.05); display: flex; gap: 10px;">
            <input type="text" id="geminiInput" placeholder="Ask me anything..." onkeypress="handleGeminiEnter(event)" style="flex: 1; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 10px 15px; color: #fff; outline: none; font-size: 0.9rem;" autocomplete="off" />
            <button onclick="sendGeminiMessage()" id="geminiSendBtn" style="background: var(--brand, #fcb900); color: #000; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; transition: 0.2s;">
                ➤
            </button>
        </div>
    </div>

    <!-- Floating Toggle Button -->
    <button id="geminiToggleBtn" onclick="toggleGeminiChat()" style="width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #fcb900 0%, #ff9800 100%); border: none; color: #000; font-size: 1.8rem; cursor: pointer; box-shadow: 0 4px 20px rgba(252, 185, 0, 0.4); display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; float: right;">
        ✨
    </button>
</div>

<script>
let isGeminiOpen = false;

function toggleGeminiChat() {
    const chatWin = document.getElementById('geminiChatWindow');
    const toggleBtn = document.getElementById('geminiToggleBtn');
    isGeminiOpen = !isGeminiOpen;
    
    if (isGeminiOpen) {
        chatWin.style.display = 'flex';
        toggleBtn.style.transform = 'scale(0.8)';
    } else {
        chatWin.style.display = 'none';
        toggleBtn.style.transform = 'scale(1)';
    }
}

function handleGeminiEnter(e) {
    if (e.key === 'Enter') {
        sendGeminiMessage();
    }
}

async function sendGeminiMessage() {
    const input = document.getElementById('geminiInput');
    const msg = input.value.trim();
    if (!msg) return;

    input.value = '';
    appendGeminiMessage(msg, 'user');
    
    const sendBtn = document.getElementById('geminiSendBtn');
    sendBtn.innerHTML = '⌛';
    sendBtn.disabled = true;

    try {
        const res = await fetch('/api/gemini_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: msg })
        });
        const data = await res.json();
        
        if (data.status === 'success') {
            appendGeminiMessage(data.reply, 'bot');
        } else {
            appendGeminiMessage('Error: ' + data.message, 'bot');
        }
    } catch (e) {
        appendGeminiMessage('Connection error. Please try again.', 'bot');
    }

    sendBtn.innerHTML = '➤';
    sendBtn.disabled = false;
}

function appendGeminiMessage(text, sender) {
    const container = document.getElementById('geminiMessages');
    const div = document.createElement('div');
    div.style.padding = '10px 15px';
    div.style.borderRadius = '15px';
    div.style.fontSize = '0.9rem';
    div.style.maxWidth = '85%';
    div.style.lineHeight = '1.4';
    div.style.wordWrap = 'break-word';

    // Parse bold text (markdown **text**)
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    
    // Parse line breaks
    text = text.replace(/\n/g, '<br>');

    div.innerHTML = text;

    if (sender === 'user') {
        div.style.alignSelf = 'flex-end';
        div.style.background = 'var(--brand, #fcb900)';
        div.style.color = '#000';
        div.style.borderBottomRightRadius = '4px';
        div.style.fontWeight = '500';
    } else {
        div.style.alignSelf = 'flex-start';
        div.style.background = 'rgba(255,255,255,0.05)';
        div.style.border = '1px solid rgba(255,255,255,0.1)';
        div.style.color = '#fff';
        div.style.borderBottomLeftRadius = '4px';
    }

    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}
</script>

<script src="/assets/js/universal_notifications.js"></script>
<?php include_once __DIR__ . '/whatsapp_button.php'; ?>

<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$isUserDashboard = ($currentPage === 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'user') !== false);
$isPartnerPanel  = (strpos($_SERVER['PHP_SELF'], 'partner') !== false);
$isAdminPanel    = (strpos($_SERVER['PHP_SELF'], 'admin') !== false);

// Only render global public mobile dock on public & storefront pages
if (!$isUserDashboard && !$isPartnerPanel && !$isAdminPanel):
    $isHomeActive     = in_array($currentPage, ['index.php', 'home.php']) && (!isset($_GET['type']) || $_GET['type'] === 'all' || empty($_GET['type']));
    $isShopActive     = (isset($_GET['type']) && $_GET['type'] === 'shops') || $currentPage === 'shop.php';
    $isMessagesActive = ($currentPage === 'messages.php');
    $isAccountActive  = in_array($currentPage, ['dashboard.php', 'profile.php', 'login.php', 'register.php', 'wallet.php', 'tasks.php', 'missions.php']);
    $accountUrl       = isset($_SESSION['user_id']) ? '/user/dashboard.php' : '/user/login.php';
    $shopUrl          = (isset($has_shop) && $has_shop) ? '/partner/dashboard.php' : '/index.php?type=shops';
?>
<!-- Universal 5-Slot Natively Mobile Bottom Dock -->
<nav class="mobile-bottom-dock" aria-label="Mobile Bottom Navigation">
    <!-- 1. Home -->
    <a href="/index.php" class="dock-item <?= $isHomeActive ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        <span>Home</span>
    </a>

    <!-- 2. Discover / Filter -->
    <a href="javascript:void(0)" onclick="if(typeof toggleCategoryDrawer === 'function'){ toggleCategoryDrawer(true); } else { window.location.href='/index.php'; }" class="dock-item">
        <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        <span>Discover</span>
    </a>

    <!-- 3. Shop -->
    <a href="<?= htmlspecialchars($shopUrl) ?>" class="dock-item <?= $isShopActive ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M20 4H4v2h16V4zm1 10v-2l-1-5H4l-1 5v2h1v6h10v-6h4v6h2v-6h1zm-9 4H6v-4h6v4z"/></svg>
        <span>Shop</span>
    </a>

    <!-- 4. Messages -->
    <a href="/user/messages.php" class="dock-item <?= $isMessagesActive ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
        <span>Messages</span>
    </a>

    <!-- 5. Account -->
    <a href="<?= htmlspecialchars($accountUrl) ?>" class="dock-item <?= $isAccountActive ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        <span>Account</span>
    </a>
</nav>
<?php endif; ?>
