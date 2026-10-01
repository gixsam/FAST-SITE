<?php
session_start();
require_once __DIR__ . '/config.php';
$isUserLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseller Partner API | Fast Site</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #050508;
            --surface: #101016;
            --surface-light: #1a1a24;
            --text: #e8e8f0;
            --muted: #8888aa;
            --gold: #fcb900;
            --gold-glow: rgba(252, 185, 0, 0.2);
            --gradient: linear-gradient(135deg, #fcb900, #ffda6a);
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: var(--text); overflow-x: hidden; }

        /* Hero Section */
        .hero {
            padding: 8rem 2rem 4rem;
            text-align: center;
            background: radial-gradient(circle at 50% -20%, rgba(252, 185, 0, 0.15) 0%, var(--bg) 60%);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .tagline {
            display: inline-block;
            background: rgba(252, 185, 0, 0.1);
            color: var(--gold);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
            border: 1px solid var(--gold-glow);
        }
        .hero h1 {
            font-family: 'Oswald', sans-serif;
            font-size: 4.5rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero p {
            font-size: 1.2rem;
            color: var(--muted);
            max-width: 700px;
            margin: 0 auto 3rem;
            line-height: 1.6;
        }

        /* Flow Section */
        .flow-section {
            padding: 5rem 2rem;
            background: var(--surface);
        }
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-family: 'Oswald', sans-serif;
            margin-bottom: 4rem;
            color: #fff;
        }
        .flow-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .flow-card {
            background: var(--surface-light);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .flow-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border-color: var(--gold-glow);
        }
        .flow-num {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 40px;
            background: var(--gradient);
            color: #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            box-shadow: 0 0 20px var(--gold-glow);
        }
        .flow-card h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #fff;
        }
        .flow-card p {
            font-size: 0.95rem;
            color: var(--muted);
            line-height: 1.5;
        }

        /* Pricing Section */
        .pricing-section {
            padding: 6rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        .price-card {
            background: var(--surface);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 20px;
            padding: 3rem 2rem;
            position: relative;
            display: flex;
            flex-direction: column;
            transition: all 0.3s;
        }
        .price-card.popular {
            border-color: var(--gold);
            background: linear-gradient(180deg, rgba(252, 185, 0, 0.05) 0%, var(--surface) 100%);
            transform: scale(1.05);
            z-index: 2;
        }
        .popular-badge {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--gradient);
            color: #000;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .plan-name { font-size: 1.2rem; color: var(--muted); margin-bottom: 1rem; font-weight: 600; text-transform: uppercase; }
        .plan-price { font-size: 3rem; font-family: 'Oswald', sans-serif; font-weight: 700; color: #fff; margin-bottom: 0.5rem; }
        .plan-price span { font-size: 1rem; color: var(--muted); font-weight: 400; font-family: 'Inter', sans-serif; }
        .plan-desc { font-size: 0.9rem; color: var(--muted); margin-bottom: 2rem; }
        .features { list-style: none; margin-bottom: 3rem; flex: 1; }
        .features li { display: flex; align-items: center; gap: 0.8rem; margin-bottom: 1rem; font-size: 0.95rem; color: #e8e8f0; }
        .features li svg { color: var(--gold); width: 18px; }
        
        .btn-subscribe {
            display: block;
            width: 100%;
            padding: 1.2rem;
            text-align: center;
            background: rgba(255,255,255,0.05);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.2s;
        }
        .btn-subscribe:hover { background: rgba(255,255,255,0.1); }
        .price-card.popular .btn-subscribe { background: var(--gradient); color: #000; border: none; }
        .price-card.popular .btn-subscribe:hover { filter: brightness(1.1); transform: translateY(-2px); }

        @media (max-width: 768px) {
            .hero h1 { font-size: 3rem; }
            .price-card.popular { transform: none; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/nav_public.php'; ?>

    <section class="hero">
        <span class="tagline">The Headless Commerce Empire</span>
        <h1>BUILD YOUR OWN MARKETPLACE.<br>WE HANDLE THE REST.</h1>
        <p>Integrate the Fast Site Reseller Partner API into your own website. Automatically sync our vast inventory, set your own retail prices, keep 100% of your markup, and let us handle the fulfillment.</p>
    </section>

    <section class="flow-section">
        <h2 class="section-title">How The Exchange Works</h2>
        <div class="flow-grid">
            <div class="flow-card">
                <div class="flow-num">1</div>
                <h3>Connect The API</h3>
                <p>Subscribe below to generate your unique Master API Key. Paste it into your website's backend.</p>
            </div>
            <div class="flow-card">
                <div class="flow-num">2</div>
                <h3>Auto-Sync Products</h3>
                <p>Your website will automatically pull all our premium products, images, and descriptions. Your shop is instantly populated.</p>
            </div>
            <div class="flow-card">
                <div class="flow-num">3</div>
                <h3>Keep The Profit Margin</h3>
                <p>If our wholesale cost is $30, you sell it for $50. You keep the $20 profit directly from your customer.</p>
            </div>
            <div class="flow-card">
                <div class="flow-num">4</div>
                <h3>Zero-Touch Fulfillment</h3>
                <p>When an order is placed on your site, it routes to Fast Site. We deduct the $30 wholesale cost from your wallet and ship it directly to your customer.</p>
            </div>
        </div>
    </section>

    <section class="pricing-section">
        <h2 class="section-title">Choose Your API License</h2>
        <div class="pricing-grid">
            
            <div class="price-card">
                <div class="plan-name">Weekly Starter</div>
                <div class="plan-price">100 <span>Coins / Week</span></div>
                <div class="plan-desc">Perfect for testing the API on a development server or a new drop-shipping store.</div>
                <ul class="features">
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Full API Feed Access</li>
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Auto-Sync up to 500 Products</li>
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Standard Fulfillment Speed</li>
                </ul>
                <a href="<?= $isUserLoggedIn ? 'user/wallet.php' : 'login.php' ?>" class="btn-subscribe">Subscribe Now</a>
            </div>

            <div class="price-card popular">
                <div class="popular-badge">Most Popular</div>
                <div class="plan-name">Monthly Pro</div>
                <div class="plan-price">350 <span>Coins / Month</span></div>
                <div class="plan-desc">The standard license for serious entrepreneurs running live stores.</div>
                <ul class="features">
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Full API Feed Access</li>
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Unlimited Product Auto-Sync</li>
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> <strong>Priority Fast-Track Fulfillment</strong></li>
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Webhook Messenger Routing</li>
                </ul>
                <a href="<?= $isUserLoggedIn ? 'user/wallet.php' : 'login.php' ?>" class="btn-subscribe">Subscribe Now</a>
            </div>

            <div class="price-card">
                <div class="plan-name">Yearly Enterprise</div>
                <div class="plan-price">3500 <span>Coins / Year</span></div>
                <div class="plan-desc">Save massive amounts of coins with a long-term enterprise commitment.</div>
                <ul class="features">
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Everything in Monthly Pro</li>
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Save 700 Coins Annually</li>
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Dedicated API Developer Support</li>
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Custom Product Requests</li>
                </ul>
                <a href="<?= $isUserLoggedIn ? 'user/wallet.php' : 'login.php' ?>" class="btn-subscribe">Subscribe Now</a>
            </div>

        </div>
    </section>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
