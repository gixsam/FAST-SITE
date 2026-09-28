# 📘 FAST SITE — MASTER COMPREHENSIVE NOTE & STATUS REPORT (NOTE.md)
**Project Name:** FAST SITE (Ecosystem Escrow Hub & Universal Marketplace)  
**Main Domain:** `https://fastsite.best-travel.ltd`  
**Current Active Version:** Phase 88 100% Complete & Independently Audited  
**Document Classification:** Canonical Website State, Historical Log & Future Roadmap  
**Target Audience:** AI Agents (Google Antigravity, Claude Sonnet, Gemini, GPT) and Human Developers / Project Managers  

---

## 🧭 PURPOSE OF THIS DOCUMENT
This document (`NOTE.md`) is the **authoritative single source of truth** for the Fast Site platform. Whenever any AI agent or human developer opens this project, this file provides an instant, unambiguous understanding of:
1. **Current Operational Status**: What works right now, active servers, active endpoints, and live test configurations.
2. **Historical Updates Log**: A complete chronicle of all past updates from Phase 1 through Phase 88.
3. **Future Updates Plan**: The strategic roadmap of upcoming features, migrations, and enhancements.
4. **Architecture & Guidelines**: Critical rules for database failover, Hostinger deployments, Google Stitch design standards, and file maintenance.

> 🚨 **MANDATORY DUAL-SAVE RULE:**  
> Whenever this `NOTE.md` file is modified, it **MUST ALWAYS** be saved simultaneously in both:
> 1. `D:\TECH\WEBSITE\FAST SITE\fast site\NOTE.md` (Local Project Root)
> 2. `G:\My Drive\ALL WEBSITE WORKPLACE\FAST SITE WORKPLACE\NOTE.md` (Google Drive Backup)

---

## 🟢 SECTION 1: WHAT WE ARE DOING NOW (CURRENT ACTIVE STATE)

### Active Status: Phase 88 Complete & Production-Verified
The platform is currently at **Phase 88: Strict .env Exclusion & Dynamic Root-Relative Image Architecture (Zero-Tolerance Deployment Audit & Hostinger WAF 422 Direct 404 Fix)**.

### Current Core Capabilities Live in the Codebase:
1. **Zero-Tolerance Deployment Build Engine (`build_hostinger_zip.py`, `build_phase_zip.py`):**
   - Implements multi-layer filter explicitly rejecting `.env`, `.env.*`, and any environment file variant.
   - Enforces automated post-build archive inspection (`ZipArchive` / `zipfile`) that audits all entries and immediately purges the archive if any `.env` entry is detected, guaranteeing that live Hostinger production database credentials (`u422364295_admin`) can NEVER be overwritten.
   - Embeds auto-generated, canonical `DEPLOYMENT_GUIDE.txt` detailing exact extraction paths and phase status.
2. **Universal Dynamic Root-Relative Media Resolution (`config.php`, `includes/image_helper.php`):**
   - Core resolvers upgraded: `resolveProductArtwork()`, `resolveShopMedia()`, `resolveMediaUrl()`, and `resolveUserAvatar()`.
   - Strips hardcoded localhost origins (`http://localhost:8000/...`, `http://127.0.0.1:...`), ensuring images never attempt to resolve to localhost on production devices.
   - Normalizes Windows backslashes (`\`) to POSIX forward slashes (`/`), preventing Linux flat-file filename corruption.
   - Resolves all local images as dynamic root-relative paths (`/uploads/...`, `/assets/...`) allowing 100% seamless rendering across Localhost, Cloudflare tunnels, and Hostinger production without environmental configuration changes.
3. **Elimination of Hostinger WAF 422 Rewrite Loop (`.htaccess`):**
   - Injected dedicated direct HTTP 404 rule for missing static media files (`.jpg`, `.jpeg`, `.png`, `.webp`, `.svg`, `.mp3`) placed directly before the catch-all `index.php` rewrite.
   - Prevents Apache from rewriting missing image requests into `index.php` and returning HTML text to `<img>` tags, eliminating browser parse failures and stopping Hostinger ModSecurity/WAF HTTP 422 (Unprocessable Entity) errors.
4. **Clean Storefront & Admin Image Linkage:**
   - Overhauled relative paths (`../uploads/...`) in `checkout.php`, `admin/partner_shops.php`, `admin/shop_edit.php`, `partner/dashboard.php`, and `user/forgot_password.php` into canonical root-relative resolvers with SVG vector fallbacks.
5. **1-Tap Bi-Directional Mode Switcher & 5-Slot Bottom Dock (Phase 87):**
   - User Panel: `[ 🏪 Switch to Shop Mode ]` (or `[ ➕ Open Free Shop ]` / `[ ⏳ Shop Under Review ]`).
   - Shop Panel: `[ 👤 Switch to Buyer Mode ]` top header pill and persistent bottom dock.
   - Synchronized 5-slot bottom floating docks across Storefront, User Space, and Shop Space with active state badges.
6. **Active Test Environments:**
   - **Local Server Live Host**: `http://localhost:8000` (test locally before Hostinger upload).
   - **Cloudflare Mobile Live Tunnel**: `https://lamb-applications-favors-disabilities.trycloudflare.com`.
7. **Latest Deployment Archive:**
   - Archive Name: `fastsite_phase88.zip` (53.25 KB) located in root directory.
   - Security Audit: 0 `.env` files detected, 100% clean.
8. **Automated GitHub & Hostinger Git Auto-Deployment (`gixsam/FAST-SITE`):**
   - Public Repository initialized & linked: `https://github.com/gixsam/FAST-SITE` (Branch: `main`).
   - Automated Workflow: `.github/workflows/deploy.yml` with pre-flight asset/security validation.
   - Hostinger Native Git Integration Activated: Connected via Hostinger hPanel Advanced Git to `gixsam/FAST-SITE` with `Auto-deployment` enabled deploying directly into `public_html/` (Verified live status: `Completed` in 5 seconds).
   - Live Production Verification: `https://fastsite.best-travel.ltd` responding HTTP 200 OK with zero errors.
   - Permanent zero-tolerance protection for live `.env` credentials and local dev databases.
9. **Mandatory Direct URL Links Directive (Rule 7):**
   - Enforced in `.agents/AGENTS.md`: Every AI model and developer must provide direct clickable URL links to both the User Panel and Admin Panel (Local & Live Hostinger) at the conclusion of every update response for immediate verification.

---

## 📜 SECTION 2: HISTORICAL UPDATES LOG (EVERY SINGLE UPDATE DONE IN PAST)

### Era 1: Core Foundation & Marketplace Architecture (Phases 1–38)
- **Phases 1–10**: Central PHP routing engine via `index.php`, MySQL database schema creation, multi-portal authentication (admin, partner, user), and initial glassmorphic storefront.
- **Phases 11–20**: Implementation of Escrow transaction vault, 72-hour timer tracking, digital asset streaming via `download.php`, and multi-tier coin system (*Fast Points*).
- **Phases 21–28**: Omni-Search engine (`api/omni_search.php`), real-time live product search (`api/live_search.php`), and automated escrow auto-release cron (`api/cron_escrow_autorelease.php`).
- **Phases 29–35**: Mobile-first Admin Command Center, staff role-based permission system (`admin/staff_access.php`), and financial transaction audit ledger (`admin/wallet.php`, `admin/coin_deposits.php`, `admin/user_withdrawals.php`).
- **Phases 36–38**: Consolidated Admin navigation flow (`DASHBOARD ➔ ADMIN PANEL ➔ USER PANEL ➔ SHOPS ➔ ALL PARTNER ➔ WALLET ➔ ADVANCE SETTING`), and elimination of redundant service hubs.

### Era 2: Media Resolution & Auth Routing Overhaul (Phases 39–43)
- **Phase 39**: Universal Product Artwork & Image Resolution Engine (`resolveProductArtwork`) generating vector fallback artwork for official government services (NID, Driving License, Passport, Birth Certificate).
- **Phase 40**: User registration HTTP 500 error diagnosis and resolution; PDO transaction safety added to user onboarding.
- **Phase 41**: Registration POST handling overhaul, broken link cleanup across 15+ subpages, and universal fallback routing.
- **Phase 42**: Global redirect fix across 19 files; platform-wide session persistence and auth routing overhaul.
- **Phase 43**: Static asset routing hardening via `.htaccess` to prevent Hostinger WAF from executing image files as PHP scripts.

### Era 3: HD Product Assets, Seeder & Seller Tools (Phases 44–58)
- **Phase 44**: High-definition real product photography asset production (`uploads/products/`, `assets/images/services/`) and Universal Marketplace Seeder Engine (`admin/seed_marketplace_products.php`) populating all 7 ecosystem partner shops with 30+ direct escrow items.
- **Phase 45**: Unified all-in-one Hostinger deployment archive bundling backend code with compiled Android APK binaries.
- **Phase 46–50**: Verified buyer purchase review engine (`product_detail.php`, `user/partner_orders.php`), seller sales analytics dashboard (`partner/dashboard.php`), and 1-click buyer dispute & refund mechanism (`admin/partner_disputes.php`).
- **Phase 51–53**: Full codebase character encoding audit eliminating corrupt Mojibake characters across all directories; files saved with pure UTF-8 encoding.
- **Phase 54–55**: Shop analytics date filter via AJAX, user wishlist engine (`user_wishlist`), real-time notification drawer, and 30-day revenue analytics chart (`admin/analytics.php`).
- **Phase 56–58**: Interactive audio player for voiceover and song previews (`product_detail.php`), removal of 30-second audio cutoff, and KYC document vault auto-clear queue in admin.

### Era 4: Gamification, Admin Upgrades & SuperApp (Phases 59–65)
- **Phase 59**: Dedicated Trending Curation Engine (`home.php`) reserving the top spotlight strictly for genuine direct Escrow items and excluding external affiliate links.
- **Phase 60**: Gamified Affiliate Missions & Tier Engine (`user/missions.php`, `affiliate/dashboard.php`) with Bronze, Silver, Gold, Platinum ranks and instant commission drops.
- **Phase 61**: Live server database failover restoration, Hostinger MySQL connection recovery, and WAF 422 image fix.
- **Phase 62**: Centralized customer loyalty gamification engine (`updateUserMissionProgress`) tracking purchases, reviews, and rewarding coins automatically.
- **Phase 63**: Admin Master Wallet upgrade (`admin/wallet.php`) with 1-click SSO teleport button into the Fast Site Official Shop (`impersonate_official.php`).
- **Phase 64**: User panel withdrawal interface overhaul (`user/withdraw_coins.php`) with glassmorphism banking forms (bKash/Nagad/Bank).
- **Phase 65**: Modularization of monolithic `admin/settings.php` into clean partials (`admin/settings_partials/tab_*.php`) with zero database overhead on page load.

### Era 5: Escrow Stepper, Social Generator & Heatmap (Phases 66–78)
- **Phase 66**: 5-step visual order stepper in `user/partner_orders.php` and `partner/orders.php`, courier tracking modal (Pathao, Steadfast, Paperfly, RedX), and 48–72h escrow auto-release countdown linked to `delivered_at`.
- **Phase 67**: Storefront coupon engine (`partner/coupons.php`, `shop.php`, `checkout.php`) with percentage/fixed discounts and shop announcement banners.
- **Phase 68**: Prepared multi-store unified cart & split escrow architecture design (`cart.php`).
- **Phase 69**: Daily check-in flame streak counter and post-purchase scratch card popup rewarding 5–25 bonus coins.
- **Phase 70**: Manual MFS TrxID verification engine with 1-click admin approval (direct webhooks deferred).
- **Phase 71**: 1-Click Social Media Card Generator (`api/generate_social_card.php`) creating branded promo cards for WhatsApp/Facebook stories using PHP GD & Canvas.
- **Phase 72**: Admin Master Escrow Heatmap & Fraud Detection Engine (`admin/escrow_heatmap.php`, `admin/fraud_detector.php`) identifying circulating vs locked coins and multi-account abuse.
- **Phase 73**: Task proof approval white screen fix and modal task editor (`admin/tasks.php`).
- **Phase 74**: Admin "See Shop & Edit" trigger on all shop rows and pending partner shop request pipeline synchronization.
- **Phase 75**: Master wallet manual coin debit/penalty tool and defensive SQL schema writing on withdrawals.
- **Phase 76**: Admin dashboard KPI metric cards renaming into operational queues (`TOTAL SHOPS`, `SHOPS PENDING REQUEST`, `ESCROW DISPUTES`).
- **Phase 77**: Universal media resolver linking `profile_pic`, `logo_url`, `cover_pic`, and `banner_url` across `uploads/partners/`.
- **Phase 78**: User profile edit link routing directly to `user/dashboard.php?tab=settings` and trending deduplication from marketplace feeds.

### Era 6: Unified Hubs, Android APK v1.2–v1.8 & Recovery (Phases 79–84)
- **Phase 79**: Unified Customer & Shop Management Hub in `admin/users.php` via master `LEFT JOIN` unified table with 4 modular action modals (KYC Trade License, Adjust Coins, Delete Choice, Profile Edit).
- **Phases 80–81**: Admin dashboard animated emoji watermarks and KPI card filter scrolling.
- **Phase 82**: Product upload PDO transaction hotfix (`partner/product_add.php`) separating DDL migrations outside transaction blocks to prevent implicit MySQL commits.
- **Phase 83**: Android hardware BiometricPrompt fingerprint/face login (`api/biometric_login.php`), pull-to-refresh gesture engine (`assets/js/pull_to_refresh.js`) with 110px threshold, and universal polling notification system (`assets/js/universal_notifications.js`).
- **Phase 84**: Emergency Hostinger Recovery & Full Platform Archive Build (`fastsite_full_restore.zip`) packaging 100% of all files, uploads, and databases.
- **Android APK Evolution (v1.2–v1.8)**:
  - v1.2: Added runtime media/camera permissions and Android `onShowFileChooser` handler.
  - v1.3: Branded launcher icons across all screen densities and bottom gesture back pill.
  - v1.4: Decoupled permissions from file chooser to fix system file picker cancellation.
  - v1.5: Resolved flexbox navbar collisions and hardware layer GPU acceleration.
  - v1.6: Dark notch status bar enforcement (`#08080C`) and shop messaging fix.
  - v1.7: Rate-limited update loop with `SharedPreferences` dismissal caching.
  - v1.8: Disabled heavy GPU blur filters on mobile viewports (<768px) for fluid 60–120 FPS scrolling.

### Era 7: Recent Major Overhauls & Security (Phases 85–88)
- **Phase 85 (UX/UI & Navigation Overhaul)**:
  - Streamlined User Dashboard (`user/dashboard.php`, `includes/user_sidebar.php`) with plain everyday consumer English (Member ID, Invite Code, Available Balance ৳, Cash Out / Withdraw Money).
  - Streamlined Partner Shop Portal (`partner/dashboard.php`, `partner/orders.php`, `partner/products.php`) with everyday merchant terminology (Add New Product, Orders to Fulfill, Total Sales Earned).
  - Streamlined Marketplace Header (`home.php`, `includes/nav_public.php`) with 3-zone centered logo grid, prominent search bar, and slide-up Category Drawer.
- **Phase 86 (Natively Mobile Fast Site Architecture)**:
  - Engineered Google Stitch *Nocturne Aurum* stylesheet (`assets/css/native_mobile.css`).
  - Viewport configured with `viewport-fit=cover` and Apple mobile web app capable headers.
  - Strict 2x2 responsive product grid with 1:1 square media ratio and price pill overlays.
  - Persistent 5-slot mobile bottom navigation dock with safe-area padding.
  - Database fallback to SQLite (`fast_site_local.db`) with 7 seeded partner shops.
- **Phase 87 (Frictionless 1-Tap Buyer Mode ⇄ Shop Mode Switcher)**:
  - Bi-directional 1-tap mode switchers in top headers and bottom docks across User and Shop spaces.
  - Aligned 5-slot bottom docks with dynamic active states and order badge counters.
  - Google Stitch onboarding bottom sheets: `#quickShopDrawer` (30s setup) and `#shopReviewModal` (live tracking + WhatsApp support).
  - Cleaned 7 legacy 404 redirects into seamless HTTP 302 redirects to `/user/login.php`.
  - Independent Victory Audit confirmed with 145/145 passing checks.
- **Phase 88 (Strict .env Exclusion & Dynamic Root-Relative Image Architecture)**:
  - Configured zero-tolerance deployment build engine (`build_hostinger_zip.py`, `build_phase_zip.py`) with automated post-build security verification, permanently guaranteeing that `.env` files are never bundled and Hostinger MySQL credentials (`u422364295_admin`) are never overwritten.
  - Implemented dynamic root-relative paths for all images across `config.php`, `includes/image_helper.php`, `checkout.php`, `partner/dashboard.php`, `admin/partner_shops.php`, `admin/shop_edit.php`, and `user/forgot_password.php`.
  - Injected intelligent resolvers (`resolveProductArtwork`, `resolveShopMedia`, `resolveMediaUrl`, `resolveUserAvatar`) stripping localhost leaks, normalizing Windows backslashes to POSIX slashes, and anchoring all local media to `/uploads/...` and `/assets/...`.
  - Added dedicated static media direct 404 rewrite guard in `.htaccess` to eliminate the Apache rewrite-to-index.php loop and prevent Hostinger WAF 422 (Unprocessable Entity) errors.
  - Verified 7/7 automated assertions with 100% pass rate and packaged `fastsite_phase88.zip` (53.25 KB).

---

## 🚀 SECTION 3: FUTURE UPDATES PLAN (ROADMAP FOR UPCOMING PHASES)

### 🔮 Phase 89: Smart Multi-Store Unified Cart & Split Escrow Checkout
- **Files Affected:** `cart.php`, `checkout.php`, `includes/escrow_engine.php`
- **Objective:** Enable buyers to add products from multiple partner shops (e.g., Ayra Mart + Enzor Motor + Fast Site Official) into a single unified shopping cart.
- **Mechanism:** Single checkout transaction generating a parent `order_group_id`, with automatic backend splitting into individual shop orders and separate escrow holding vaults.

### 🔮 Phase 90: Universal Shop Cover Photo & Profile Studio Upgrade
- **Files Affected:** `partner/dashboard.php`, `partner/profile.php`, `shop.php`, `includes/image_helper.php`
- **Objective:** Multi-directory avatar and banner image resolver scanning `uploads/partners/`, `uploads/profiles/`, and `uploads/shops/`.
- **Mechanism:** Live interactive cropping studio allowing shop owners to upload cinematic banners (16:9) and store avatars (1:1) with instant CDN/local synchronization.

### 🔮 Phase 91: Automated Direct bKash & Nagad MFS Webhooks
- **Files Affected:** `api/mfs_webhook.php`, `admin/payouts.php`, `user/wallet.php`
- **Objective:** Transition from manual TrxID verification to instant automated payment gateway callbacks.
- **Mechanism:** Direct integration with official bKash Merchant API / Nagad API for instant coin balance crediting upon payment and 1-click batch disbursement for user withdrawals.

### 🔮 Phase 92: Advanced Storefront Customization Studio
- **Files Affected:** `shop.php`, `partner/dashboard.php`, `assets/css/shop_themes.css`
- **Objective:** Allow verified partner shops to customize their store theme color, hero banner layout, featured listings carousel, and promotional announcement banners.

### 🔮 Phase 93: Batch Catalog Feed Synchronization for Meta & Google
- **Files Affected:** `api/catalog_feed_facebook.php`, `api/catalog_feed_google.php`
- **Objective:** Automated real-time CSV/XML product catalog feeds for Facebook Dynamic Ads, Instagram Shopping, and Google Merchant Center.

### 🔮 Phase 94: Gamified Buyer Loyalty Hub & Tiered Buyer Rewards
- **Files Affected:** `user/loyalty.php`, `user/dashboard.php`, `config.php`
- **Objective:** Expand the gamified mission engine to everyday buyers (e.g., "Make 3 purchases to unlock 5% cashback", "Leave 2 verified reviews to earn 50 bonus coins").

### 🔮 Phase 95: Progressive Web App (PWA) Offline Engine & Web Push Notifications
- **Files Affected:** `manifest.json`, `sw.js`, `api/push_subscribe.php`
- **Objective:** Transform the web storefront into an installable Progressive Web App with offline caching and native browser push notifications for new orders, messages, and escrow status changes.

---

## 🏢 SECTION 4: THE 7-CONNECTED ECOSYSTEM DIRECTORY

| # | Website | Domain | Platform / Engine | Role in Ecosystem |
|---|---------|---------|-------------------|-------------------|
| 1 | **Fast Site (HUB)** | `https://fastsite.best-travel.ltd` | Custom PHP Backend (No Framework) | Central Escrow Marketplace, API Hub & User Authentication |
| 2 | **Affi Bangla** | `https://affibangla.best-travel.ltd` | WordPress Affiliate Engine | External Affiliate Traffic Aggregator |
| 3 | **Best Travel** | `https://best-travel.ltd` | Travel Agency Booking Engine | Visa, Air Tickets & Tour Packages Partner Store |
| 4 | **Enzor Motor** | `https://enzor.best-travel.ltd` | Automobile E-Commerce | Auto Parts, Motorcycle Gear & Hardware Partner Store |
| 5 | **Ayra Mart** | `https://atayramart.com` | Fashion Retail E-Commerce | Clothing, Apparel & Lifestyle Partner Store |
| 6 | **Manza** | `https://manza.best-travel.ltd` | News & Content Media | Content Publisher & Advertising Portal |
| 7 | **GixSam** | `https://gixsam.best-travel.ltd` | Personal Portfolio Engine | Executive Portfolio & Web Tech Services |

---

## 🛠️ SECTION 5: INSTRUCTIONS FOR AI AGENTS & HUMAN DEVELOPERS

Whenever an AI model (Antigravity, Claude, Gemini, GPT) or human developer works on Fast Site, you **MUST** follow these protocols:

1. **Context First:** Always read this `NOTE.md` and `PROJECT_STATE.md` before making changes.
2. **Local Server Live Host Rule:** Remind the user to keep `http://localhost:8000` running (`php -S localhost:8000`) to test changes before live deployment.
3. **Google Stitch Standards:** All visual components must use the *Nocturne Aurum* tokens (`#0A0D1A`, frosted glass, `#F59E0B` gold, 44px+ touch targets).
4. **Hostinger Zip Upload Rule:**
   - Always name deployment archives `fastsite_phaseX.zip` (where X is the phase number).
   - Always include a `DEPLOYMENT_GUIDE.txt` in the archive specifying exact Hostinger `public_html/` paths.
   - Use strict Unix forward slashes (`/`) for all paths inside the zip archive.
   - **Strictly exclude all `.env` files** and run the post-build automated security audit before finalizing the zip.
5. **Database Safety:** Ensure all SQL changes are compatible with both Hostinger MySQL and the local SQLite failover engine (`config.php`).
6. **Dual Documentation Synchronization:** Every time this `NOTE.md` file is edited, write the exact contents to **BOTH**:
   - `D:\TECH\WEBSITE\FAST SITE\fast site\NOTE.md`
   - `G:\My Drive\ALL WEBSITE WORKPLACE\FAST SITE WORKPLACE\NOTE.md`

---
*End of Report — Document actively maintained across all development sessions.*
