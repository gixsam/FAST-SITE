<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin System Guide - Fast Site</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<div class="container">
    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    <button onclick="window.print()" style="float:right; padding: 0.8rem 1.5rem; background: var(--brand); color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">🖨️ Save as PDF / Print</button>
    
    <h1>FAST SITE SYSTEM ARCHITECTURE & ADMIN GUIDE</h1>
    
    <div class="feature-box">
        <p><strong>System Name:</strong> Fast Site Marketplace & Service Platform</p>
        <p><strong>Version:</strong> 2.5</p>
        <p><strong>Architecture:</strong> Monolithic PHP with SQLite & MySQL Support (Hybrid)</p>
    </div>

    <h2>1. Directory Structure Overview</h2>
    <ul>
        <li><strong>/</strong> - Root folder containing main user-facing files (index.php, marketplace.php, product_detail.php) and system config (config.php, router.php, .htaccess).</li>
        <li><strong>/admin</strong> - Super-admin dashboard, managing all global settings, users, and overall health.</li>
        <li><strong>/user</strong> - Unified customer dashboard (wallet, orders, messages) AND Partner dashboard (manage products, partner analytics).</li>
        <li><strong>/api</strong> - Contains backend API endpoints (AJAX handlers) for handling checkouts, status updates, and fee calculations.</li>
        <li><strong>/assets</strong> & <strong>/uploads</strong> - Static assets and user-generated content (images, screenshots, message attachments).</li>
        <li><strong>/whatsapp-bot</strong> - Node.js powered Baileys + Gemini AI chatbot.</li>
    </ul>

    <h2>2. Unified User & Partner System</h2>
    <p>Fast Site employs a unified account system. Every user logs in via <code>user/login.php</code>. From their unified dashboard (<code>user/dashboard.php</code>):</p>
    <ul>
        <li><strong>Standard Users:</strong> Can view their wallet, purchase points, view their orders from partners, and chat with partner shops.</li>
        <li><strong>Partners:</strong> If a user applies and is approved as a partner, their dashboard dynamically unlocks the "Partner Mode" tab. They can switch seamlessly between acting as a Customer and acting as a Shop.</li>
    </ul>

    <h2>3. Payment & Coin System (Fast Points)</h2>
    <p>To enforce a "Safe Trade" ecosystem, all internal marketplace transactions MUST occur using Fast Points.</p>
    <ul>
        <li><strong>Deposits:</strong> Users send fiat (bKash/Nagad) to the Admin number and submit a request in <code>user/deposit.php</code>. Admins approve this in the Admin Panel, crediting the user's Fast Points wallet.</li>
        <li><strong>Purchasing:</strong> Users buy products/services using their Wallet Balance.</li>
        <li><strong>Partner Earnings:</strong> When a user buys a product, the points are placed in escrow (User Pending Cash/Hold). Once the order is completed, the points transfer to the Partner's Wallet.</li>
        <li><strong>Withdrawals:</strong> Partners withdraw their Fast Points for fiat via the Partner Dashboard. Admins process the payout.</li>
    </ul>

    <h2>4. Shipping Regions & Digital Goods</h2>
    <p>The checkout process supports dynamic shipping fees based on the buyer's region.</p>
    <ul>
        <li><strong>Inside Dhaka:</strong> Flat rate (e.g. 60 BDT).</li>
        <li><strong>Outside Dhaka:</strong> Flat rate (e.g. 120 BDT).</li>
        <li><strong>Soft / Digital Products:</strong> Bypasses physical shipping (0 BDT fee).</li>
    </ul>
    <p><em>Note: Delivery charges are calculated and deducted dynamically alongside the product price.</em></p>

    <h2>5. Marketplace In-Built Messaging & Handoff</h2>
    <p>To prevent off-platform scams, Fast Site provides an in-built chat system.</p>
    <ul>
        <li><strong>Messaging (user/messages.php):</strong> Real-time AJAX-powered chat for Users and Partners to discuss orders and upload attachments.</li>
        <li><strong>God-Mode Monitoring:</strong> Admins can view ALL chats via <code>admin/messages.php</code> to monitor for fraud or policy violations.</li>
        <li><strong>WhatsApp AI Handoff:</strong> The AI chatbot handles tier-1 support. If a user gets frustrated or explicitly asks for an admin, the bot replies with an `[ESCALATE]` tag. The system then pauses AI responses for that user and sends a direct WhatsApp alert to the Admin's phone to take over manually.</li>
    </ul>

    <h2>6. Security Protocols</h2>
    <div class="feature-box" style="border-color: var(--red);">
        <h3 style="color: var(--red); margin-top:0;">Strict Policies</h3>
        <ul>
            <li>Never share database credentials or `.db` files publicly.</li>
            <li>All file uploads (images/attachments) are validated against extensions to prevent shell uploads.</li>
            <li>The "Safe Trade Warning" is prominently displayed to prevent users from sharing direct bKash numbers in the chat. All payments must go through Fast Points.</li>
        </ul>
    </div>
</div>

</body>
</html>
