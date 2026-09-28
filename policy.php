<?php
session_start();
require_once __DIR__ . '/config.php';

$site_name = $settings['site_name'] ?? 'Fast Site';
$logo_url = $settings['logo_url'] ?? '';
$chatbot_whatsapp = $settings['marketplace_whatsapp'] ?? '01963601472';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Policies | <?= htmlspecialchars($site_name) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0d0d14;
            --dark2: #14141e;
            --dark3: #1c1c28;
            --gold: #fcb900;
            --gold-hover: #ffda6a;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.08);
            --brand: #3b82f6;
            --green: #10b981;
        }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1rem;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 1rem;
        }
        h1, h2, h3 {
            font-family: 'Oswald', sans-serif;
            color: var(--gold);
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        h2 {
            font-size: 1.5rem;
            margin-top: 2rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.5rem;
        }
        p, li {
            color: var(--muted);
            font-size: 0.95rem;
        }
        ul {
            padding-left: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .back-btn {
            display: inline-block;
            background: rgba(255,255,255,0.05);
            color: #fff;
            padding: 0.6rem 1.2rem;
            text-decoration: none;
            border-radius: 8px;
            border: 1px solid var(--border);
            margin-bottom: 1rem;
            transition: all 0.2s;
        }
        .back-btn:hover {
            background: rgba(255,255,255,0.1);
            color: var(--gold);
        }
        .help-box {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 3rem;
            text-align: center;
        }
        .help-box a {
            display: inline-block;
            margin-top: 1rem;
            background: var(--brand);
            color: #fff;
            padding: 0.8rem 1.5rem;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="javascript:history.back()" class="back-btn">← Back</a>
    
    <div class="header">
        <?php if($logo_url): ?>
            <img src="<?= htmlspecialchars($logo_url) ?>" alt="<?= htmlspecialchars($site_name) ?>" class="logo">
        <?php else: ?>
            <h1 style="color:#fff;"><?= htmlspecialchars($site_name) ?></h1>
        <?php endif; ?>
        <h1>Terms & Policies</h1>
        <p>Last Updated: <?= date('F d, Y') ?></p>
    </div>

    <h2>1. Introduction</h2>
    <p>Welcome to <?= htmlspecialchars($site_name) ?>. By accessing our website, you agree to these terms and conditions in full. Do not continue to use <?= htmlspecialchars($site_name) ?> if you do not accept all of the terms and conditions stated on this page.</p>

    <h2>2. User Accounts & Registration</h2>
    <ul>
        <li>You must be at least 18 years of age to register for a Partner Shop or Affiliate account.</li>
        <li>You are responsible for maintaining the confidentiality of your account password.</li>
        <li>We reserve the right to suspend or terminate accounts that engage in fraudulent activity.</li>
    </ul>

    <h2>3. Marketplace & Escrow System</h2>
    <p>Our marketplace uses a secure Escrow system to protect both buyers and sellers:</p>
    <ul>
        <li><strong>For Buyers:</strong> Payment is held in escrow (Fast Coins) until the seller uploads proof of delivery and you confirm receipt.</li>
        <li><strong>For Sellers:</strong> You must deliver the service or product as described and upload proof of delivery. Funds will be released upon buyer confirmation or admin dispute resolution.</li>
        <li><strong>Disputes:</strong> If a buyer does not receive the service, they may file a dispute. Our Admin team (and AI Support) will review the chat logs and evidence to make a final ruling.</li>
    </ul>

    <h2>4. Digital Currency (Fast Coins)</h2>
    <p>Transactions within the <?= htmlspecialchars($site_name) ?> ecosystem are conducted using our internal digital currency. These coins can be deposited via mobile banking or withdrawn to your local bank account, subject to standard processing fees and times.</p>

    <h2>5. Refund Policy</h2>
    <p>Refunds are only issued if a service or product is not delivered as described and a dispute is ruled in the buyer's favor. Once funds have been transferred to the seller's withdrawal wallet, refunds are no longer possible.</p>

    <div class="help-box">
        <h3>Need Help or Have Questions?</h3>
        <p>Our Smart AI Assistant is available 24/7 to guide you through any issues or disputes.</p>
        <a href="https://wa.me/<?= htmlspecialchars($chatbot_whatsapp) ?>" target="_blank">Chat with Support</a>
    </div>

</div>

</body>
</html>
