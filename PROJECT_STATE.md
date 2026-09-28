# 🚀 FAST SITE — PROJECT STATE & WORKFLOW
**Last Updated:** Phase 92 100% COMPLETE — Highlighted VIP Guest Auth Card & Dual-Tab Switcher in Side Drawer

---

## 🏢 THE ECOSYSTEM (7 Connected Websites)

| # | Website | Domain | Role |
|---|---------|---------|------|
| 1 | **Fast Site (HUB)** | https://fastsite.best-travel.ltd | Core Escrow Marketplace & API Hub |
| 2 | **Affi Bangla** | https://affibangla.best-travel.ltd | Affiliate Aggregator (WordPress) |
| 3 | **Best Travel** | https://best-travel.ltd | Travel Agency (Visa, Tickets, Tours) |
| 4 | **Enzor Motor** | https://enzor.best-travel.ltd | Automobile Parts E-Commerce |
| 5 | **Ayra Mart** | https://atayramart.com | Fashion & Retail E-Commerce |
| 6 | **Manza** | https://manza.best-travel.ltd | News & Content (Placeholder added) |
| 7 | **GixSam** | https://gixsam.best-travel.ltd | Personal Portfolio (Live Editor Built) |

---

## 🏛️ ARCHITECTURE

- **Platform:** Custom PHP Backend (No Framework)
- **Database:** MySQL (Hostinger) — credentials from `.env`
- **Routing:** Centralized through `index.php`
- **Admin Panel:** Mobile-first card UI / Desktop table UI
- **Security:** `.htaccess` blocks direct `.env` access; all admin pages require `admin_logged_in` session; deployment build engine enforces zero-tolerance `.env` exclusion to permanently safeguard production database credentials.
- **Escrow Auto-Release:** 72-Hour automated release engine (`api/cron_escrow_autorelease.php`)
- **Digital Auto-Fulfillment:** Instant secure file streaming (`download.php?order_id=X`)
- **Dynamic Root-Relative Media Engine:** Intelligent cross-environment image & media resolution (`/uploads/...`, `/assets/...`) stripping localhost leaks, normalizing POSIX paths, and eliminating Hostinger WAF 422 errors via direct 404 rewrite handling (`resolveProductArtwork`, `resolveShopMedia`, `resolveMediaUrl`, `resolveUserAvatar`).
- **UI / CSS / Graphics Design Engine:** Google Stitch (StitchMCP) integration for high-fidelity component generation, Tailwind design systems, and responsive layout styling.
- **Automated CI/CD & Hostinger Git Engine:** Connected official GitHub repository [gixsam/FAST-SITE](https://github.com/gixsam/FAST-SITE) directly to Hostinger's native Git deployment engine (`Advanced > GIT`) with `Auto-deployment` enabled. Pushes to `main` automatically deploy to `public_html/` within 5 seconds.
- **Canonical Status & Note Engine (`NOTE.md`):** Dual-synchronized master note file maintained simultaneously in local root and Google Drive (`G:\My Drive\ALL WEBSITE WORKPLACE\FAST SITE WORKPLACE\NOTE.md`).
- **Direct Panel Links Standard:** Mandatory direct links rendered at the end of each session for User Panel, Admin Panel, Shop Panel, and Storefront.

---

## 🔗 QUICK DIRECTORY & VERIFICATION LINKS

| Portal | Local Server URL | Live Production URL |
| :--- | :--- | :--- |
| **User Panel (Dashboard)** | [http://localhost:8000/user/dashboard.php](http://localhost:8000/user/dashboard.php) | [https://fastsite.best-travel.ltd/user/dashboard.php](https://fastsite.best-travel.ltd/user/dashboard.php) |
| **Admin Panel (Command Center)** | [http://localhost:8000/admin/dashboard.php](http://localhost:8000/admin/dashboard.php) | [https://fastsite.best-travel.ltd/admin/dashboard.php](https://fastsite.best-travel.ltd/admin/dashboard.php) |
| **Partner / Shop Portal** | [http://localhost:8000/partner/dashboard.php](http://localhost:8000/partner/dashboard.php) | [https://fastsite.best-travel.ltd/partner/dashboard.php](https://fastsite.best-travel.ltd/partner/dashboard.php) |
| **Public Storefront** | [http://localhost:8000/](http://localhost:8000/) | [https://fastsite.best-travel.ltd/](https://fastsite.best-travel.ltd/) |

---

## 📤 HOSTINGER DEPLOYMENT INSTRUCTIONS

### Method 1: Automated GitHub Deployment (Native Hostinger Git Webhook)
1. Push any update: `git push origin main`
2. Hostinger immediately receives the GitHub push webhook and pulls the updated files directly into `public_html/` within ~5 seconds.
3. Production `.env` credentials are permanently excluded and 100% safe.

### Method 2: Manual ZIP Archive Deployment (File Manager)
1. Take the latest `.zip` (`fastsite_phase88.zip`) from `D:\TECH\WEBSITE\FAST SITE\fast site\`
2. Upload to `public_html` on Hostinger File Manager
3. Extract and overwrite existing files (production `.env` credentials are safe and excluded)
4. Visit `https://fastsite.best-travel.ltd/admin/seed_ecosystem_shops.php` once to seed shops (if needed)
5. **RULE:** Always delete old zip and replace with newest only

---

## ✅ COMPLETED PHASES

| Phase | What Was Done |
|-------|--------------|
| 1–38 | Core marketplace build, ecosystem connectivity, UI overhauls & performance upgrades |
| 39 | Universal Product Artwork & Image Resolution Engine (Vector Cards for Official Services) |
| 40 | User Registration 500 Error Fix & Auth Overhaul |
| 41 | Registration POST Fix, Broken Links Overhaul & Universal Routing |
| 42 | Global Redirect Fix (19 Files) & Platform-Wide Auth Routing Overhaul |
| 44–84 | HD Product Photos, Seeder, Mobile APK v1.2–v1.8, Push Notifications, 2x2 Grid, Full Codebase Recovery |
| 85 | Focused Module-by-Module UX/UI & Navigation Overhaul (User, Shop, Marketplace, Google Stitch) |
| 86 | Natively Mobile Fast Site Architecture (Google Stitch Tokens, 2x2 Grid, 5-Slot Dock, Live Tunnel) |
| 87 | Frictionless 1-Tap Buyer Mode ⇄ Shop Mode Switcher & Navigation Dock Sync (Google Stitch Nocturne Aurum Standards) |
| 88 | Strict .env Exclusion & Dynamic Root-Relative Image Architecture (Zero-Tolerance Deployment Audit, Hostinger WAF 422 Direct 404 Fix) |
| 89 | Elimination of Categories & Filter Drawer Over-Layering Flaw (Root-Level DOM Relocation, Visibility Hardening & Print Exclusion) |
| 90 | Resolution of MySQL Implicit Commit Registration Transaction Error & Self-Healing Account Recovery |
| 91 | Removal of Crowded Navbar Open Shop Button |
| 92 | Highlighted VIP Guest Auth Card & Dual-Tab Switcher in Side Drawer |

### Phase 92 Details — Highlighted VIP Guest Auth Card & Dual-Tab Switcher in Side Drawer
- **1. Highlighted VIP Guest Card Architecture (`includes/nav_public.php`):** Transformed the unhighlighted drawer login link into an eye-catching, glassmorphic VIP Guest Card (`.drawer-auth-card`) with a 1.5px luminous gold border, ambient gold glow (`rgba(252, 185, 0, 0.18)`), and 14px rounded curvature.
- **2. High-Converting Visual Hierarchy:** Added a welcoming gold avatar (`👤`), crisp white heading (`Welcome to FAST SITE`), and an incentive subtitle (`🪙 Free 50 Coins on Register`).
- **3. Dual-Tab Highlighted Switcher (`.drawer-auth-tabs`):** Positioned side-by-side action tabs: `[ 🔐 LOGIN ]` (solid glowing gold gradient) and `[ ✨ REGISTER ]` (frosted gold glass), complete with active scale feedback and comprehensive inline CSS fallbacks.

### Phase 91 Details — Removal of Crowded Navbar Open Shop Button
- **1. Removal of Crowded Navbar Open Shop Button (`includes/nav_public.php`):** Completely removed the redundant and cramped `[➕ Open Shop]` pill from top navigation Zone 3, preventing mobile text truncation (`Open S...`) and uncluttering the header.
- **2. Responsive Viewport Center Symmetry (`includes/nav_public.php`):** Enforced `.shop-nav-btn { display: none !important; }` on mobile (<600px). Zone 3 strictly contains the 38px hamburger button, balancing Zone 1 (coin pill) and guaranteeing the `FAST SITE` brand logo remains dead-centered with zero squeeze.

### Phase 90 Details — Resolution of MySQL Implicit Commit Registration Transaction Error
- **1. Elimination of DDL Transaction Collision (`user/register.php`):** Extracted `CREATE TABLE IF NOT EXISTS coin_wallets` out of the active transaction `$pdo->beginTransaction()` to the initial script migration block, preventing MySQL from triggering an implicit commit that caused `$pdo->commit()` to throw `PDOException: There is no active transaction`.
- **2. Defensive Transaction Guard (`user/register.php`):** Injected `if ($pdo->inTransaction()) { $pdo->commit(); }` and replaced wallet seeding with pure DML (`INSERT ... ON DUPLICATE KEY UPDATE balance = balance`).
- **3. Self-Healing Interrupted Registration Recovery (`user/register.php`):** Enabled seamless login and credential reconciliation for accounts created during an interrupted registration attempt (such as Sayam's phone `01612669922`), automatically allocating welcome coins and directing the user to `/user/dashboard.php?welcome=1`.

### Phase 89 Details — Elimination of Categories & Filter Drawer Over-Layering Flaw
- **1. Root-Level DOM Relocation (`home.php`):** Extracted the `#categoryDrawer` and `#categoryDrawerScrim` markup and scripts from the document content flow (previously nestled between Hero and Products grid) to the root document level immediately before `</body>`.
- **2. Hardware-Accelerated Visibility Hardening (`home.php`):** Configured strict inactive styling (`visibility: hidden`, `opacity: 0`, `pointer-events: none`, `transform: translateY(115%)`, `z-index: 99999`) preventing bottom dock collision, shadow artifacts, and unwanted rendering during page load.
- **3. Print & Full-Page Screenshot Protection (`home.php`):** Added `@media print { .category-drawer, .drawer-scrim, .modal-overlay { display: none !important; } }` ensuring iOS Safari full-page screenshot tools and rasterizers never capture hidden drawers over product cards.
- **4. Synchronized Toggle Engine (`home.php`):** Enhanced `toggleCategoryDrawer()` to synchronously manage `aria-hidden` attributes and transition visibility smoothly with 360ms CSS cleanup.

### Phase 88 Details — Strict .env Exclusion & Dynamic Root-Relative Image Architecture
- **1. Zero-Tolerance Deployment Build Engine (`build_hostinger_zip.py` / `build_phase_zip.py`):** Multi-layer filter excluding any `.env` file variant, coupled with an automated post-build archive inspection that immediately aborts and purges the archive if any `.env` entry is detected.
- **2. Universal Dynamic Root-Relative Media Resolution (`config.php` & `includes/image_helper.php`):** Upgraded `resolveProductArtwork()`, `resolveShopMedia()`, `resolveMediaUrl()`, and `resolveUserAvatar()`. Dynamically strips `http://localhost[:port]` and `http://127.0.0.1[:port]`, normalizes Windows backslashes `\\` to POSIX `/`, and anchors all local media to root-relative paths (`/uploads/...`, `/assets/...`).
- **3. Elimination of Hostinger WAF 422 Rewrite Loop (`.htaccess`):** Injected direct HTTP 404 rule for missing static media assets before the catch-all `index.php` rewrite, preventing Apache from returning HTML text for broken images.
- **4. Storefront & Admin Image Linkage Overhaul (`checkout.php`, `admin/partner_shops.php`, `admin/shop_edit.php`, `partner/dashboard.php`, `user/forgot_password.php`):** Converted relative paths (`../uploads/...`) and unparsed URLs to use canonical root-relative resolver functions.
- **5. Packaged Deployment Archive:** Assembled and verified `fastsite_phase88.zip` (53.25 KB) with complete `DEPLOYMENT_GUIDE.txt`.


### Phase 44 Details — High-Definition Real Product Photos & Universal Marketplace Seeder Engine

- [ ] **1. HD Product Photo Asset Production** (`assets/images/services/` & `uploads/products/`) — Generate/prepare photorealistic, high-converting product photography assets for all 18+ official services (NID, Driving License, Passport, Birth Certificate, Land Registry, etc.) and all ecosystem categories (Fashion, Auto Parts, Travel Packages, Gaming, Tech SaaS).
- [ ] **2. Universal Marketplace Seeder Engine** (`admin/seed_marketplace_products.php`) — Automated 1-click seeder for Hostinger and local host that populates all 7 partner shops, 30+ products, explicit `partner_product_images` entries, and links `services.logo_url`.
- [ ] **3. Universal Artwork Resolver Upgrade** (`config.php` & `includes/image_helper.php`) — Enhanced resolution to ensure every product card loads real photorealistic images across homepage, trending slider, live search, and detail views.
- [ ] **4. Packaged Deployment Archive** — Create `fastsite_phase44.zip` with updated `DEPLOYMENT_GUIDE.txt`.

### 🔍 Image Debug Investigation (2026-08-18)
- Created `admin/debug_images.php` — a diagnostic tool to run on Hostinger that shows exactly which image files are missing from disk vs DB records.
- **Root Cause Identified:** Products either have (A) no record in `partner_product_images` table, OR (B) the `image_url` stored points to a local file that was never uploaded to Hostinger's `uploads/products/` folder.
- SVG vector fallbacks (`/assets/images/services/*.svg`) ARE working correctly — the NID/Driving License cards in the screenshot ARE displaying them as designed.
- **Next Step:** Upload `debug_images.php` to Hostinger and visit it as admin to get exact list of missing files.





- [x] **2. Instant Digital Auto-Fulfillment** (`download.php`) — Secure tokenized file streaming for purchased digital products, with fallback to seller messages.
- [x] **3. Verified Purchase Reviews System** (`product_detail.php` & `user/partner_orders.php`) — Restricts review submissions exclusively to buyers with completed order IDs for that product/shop.
- [x] **4. Seller Sales Analytics Engine** (`partner/dashboard.php`) — Visual analytics card displaying Completed Sales Revenue, Locked Escrow, Order Fulfillment Rate %, and Top Performing Listings.
- [x] **5. 1-Click Order Dispute & Refund System** (`user/partner_orders.php` & `admin/partner_disputes.php`) — Buyer evidence upload, 3-way dispute thread, and 1-click Admin Refund Customer / Release Escrow buttons.

---

## ✅ Phase 60 Complete - Gamified Affiliate Missions & Tiers
- Built an interactive Affiliate Gamification Dashboard ( ffiliate/dashboard.php).
- Implemented a Tier Engine (Bronze, Silver, Gold, Platinum) with percentage-based commission bonuses.
- Introduced a Missions system (e.g., "Refer Friends", "Make Sales") where affiliates earn direct Coin Rewards for reaching specific milestones.

## 📋 MASTER EXECUTION ROADMAP (PHASES 66 TO 78)

### 🟣 Group 1: Backend Logic, Multi-Table SQL, Escrow Safety & Moderation (Claude Agent Specialization)
- [x] **[Phase 66] Visual Order Lifecycle & Escrow Release Timeline** (`user/partner_orders.php`, `partner/orders.php`, `api/cron_escrow_autorelease.php`) -> **100% COMPLETE**. 5-step visual order stepper, courier tracking modal (Pathao, Steadfast, Paperfly, RedX), 48-72h auto-release countdown linked to `delivered_at`.
- [x] **[Phase 67] Storefront Coupon Engine & Announcement Banners** (`partner/dashboard.php`, `shop.php`, `checkout.php`) -> **100% COMPLETE**. Percentage/Fixed discount coupon generator, top shop announcements from `partners.announcement_text`, live coupon calculation at checkout.
- [ ] **[Phase 68] Smart Multi-Store Unified Cart & Split Escrow Checkout** (`cart.php`, `checkout.php`, `includes/escrow_engine.php`) -> Multi-vendor unified session cart, single checkout submission with parent `order_group_id`, automated escrow vault vendor split.
- [x] **[Phase 73] Task Proofs White Screen Fix & Task Editing Modal** (`admin/tasks.php`) -> **100% COMPLETE**. Safe `try...catch` redirect on proof approval/rejection, interactive modal task editor (Title, Category, FP Reward, Link).
- [x] **[Phase 74] Admin "See Shop & Edit" & Shop Request Pipeline Fix** (`admin/partner_shops.php`, `admin/shop_edit.php`, `admin/nav.php`) -> **100% COMPLETE**. "See Shop & Edit" trigger on all shop rows, admin shop profile/banner/product manager, pending shop requests badge & pipeline sync.
- [x] **[Phase 75] Master Wallet Manual Coin Debit & Withdrawal SQL Fix** (`user/wallet.php`, `admin/wallet.php`) -> **100% COMPLETE**. Defensive `reference`/`description` schema writing on withdrawals, Admin manual coin penalty/debit tool, live transaction audit link.
- [ ] **[Phase 77] Shop Profile & Cover Photo Resolution Engine** (`partner/dashboard.php`, `shop.php`, `includes/image_helper.php`) -> Multi-directory avatar/banner resolution (`uploads/partners/`, `uploads/profiles/`, `uploads/shops/`), reliable storefront rendering without fallback shield.

### 🔵 Group 2: UI Gamification, Social Cards, Admin Dashboards & Live Search (Gemini Agent Specialization)
- [x] **[Phase 69] Gamified Daily Streaks & Post-Order Reward Drops** (`user/missions.php`, `config.php`, `checkout.php`) -> **100% COMPLETE**. Daily check-in button with flame streak counter, `claimDailyStreakReward($pdo, $user_id)`, post-purchase scratch card popup (5-25 bonus coins).
- [x] **[Phase 71] 1-Click Social Media Card Generator & OpenGraph Suite** (`api/generate_social_card.php`, `partner/dashboard.php`, `shop.php`) -> **100% COMPLETE**. PHP GD/HTML5 canvas branded image generator with BDT price/shop link for WhatsApp/FB stories, 1-click generator button in shop hub, dynamic OpenGraph meta tags.
- [x] **[Phase 72] Admin Master Escrow Heatmap & Fraud Detection Engine** (`admin/escrow_heatmap.php`, `admin/fraud_detector.php`, `admin/nav.php`) -> **100% COMPLETE**. Visual escrow economy heatmap (circulating vs locked coins vs fee revenue), multi-rule fraud scanner (duplicate payout numbers, self-referral loops), security alerts bell.
- [x] **[Phase 76] Admin Dashboard Metric Cards Renaming & Hub Embedding** (`admin/dashboard.php`) -> **100% COMPLETE**. Rename KPI stat cards to direct operational queues (`TOTAL SHOPS`, `SHOPS PENDING REQUEST`, `SHOP DEPOSIT/WITHDRAWAL REQUEST`, `ALL TASKS`, `ESCROW DISPUTES`), embed quick management card below applications.
- [x] **[Phase 77] Shop Profile & Cover Photo Resolution Engine** (`config.php`, `partner/dashboard.php`, `shop.php`) -> **100% COMPLETE**. Universal media resolver to unify 'profile_pic', 'logo_url' and 'cover_pic', 'banner_url'. Settings tab to handle image uploads cleanly to uploads/partners/.
- [x] **[Phase 78] User Profile Edit Link & Trending Deduplication** (`user/dashboard.php`, `includes/user_sidebar.php`, `home.php`) -> **100% COMPLETE**. Route "Edit Profile ✏️" to `user/dashboard.php?tab=settings`, deduplicate trending items from main marketplace listing grid.

- [x] **[Phase 79] Unified User & Shop Management Hub** (`admin/users.php`, `user/dashboard.php`) -> **100% COMPLETE**. Master `LEFT JOIN` unified table, 4 modular admin action modals (KYC Trade License, Adjust Coins, Delete Choice, Profile Edit), custom short referral code engine.

- [x] **[Phases 80 & 81] Dashboard Watermarks & Unified Hub** (`admin/dashboard.php`, `admin/users.php`) -> **100% COMPLETE**. Added animated emoji watermarks to admin stat cards and finalized the unified LEFT JOIN user+shop master table.
- [x] **[Phase 82] Product Upload Transaction Hotfix** (`partner/product_add.php`) -> **100% COMPLETE**. Safe directory creation, nested PDO transaction safety, and clean JSON upload error handling.
- [x] **[Phase 83] Marketplace Mobile Navigation, 2x2 Grid, Android Biometrics & Universal Notifications** (`home.php`, `includes/nav_public.php`, `includes/user_sidebar.php`, `user/dashboard.php`, `user/login.php`, `api/biometric_login.php`, `api/poll_notifications.php`, `assets/js/pull_to_refresh.js`, `assets/js/universal_notifications.js`, Android APK) -> **100% COMPLETE**. Fixed accidental mobile pull-to-refresh with 110px threshold & element exclusion; implemented 2x2 mobile product grid; consolidated mobile categories/types into bottom drawer filter modal; centered mobile logo branding; centered streak & wallet profile cards; implemented real Android hardware BiometricPrompt fingerprint/face login; and built universal real-time polling notification engine with top toast banners and APK system alerts.
- [x] **[Phase 84] Emergency Hostinger Recovery & Full Platform Archive Build** (`fastsite_full_restore.zip`) -> **100% COMPLETE**. Packaged 100% of the entire website codebase into a single comprehensive restore zip (admin, affiliate, api, assets, includes, partner, user, uploads, downloads, partner logos, index.php, config.php, etc.) with simple 1-click extraction instructions for Hostinger public_html.

### 🟢 Group 3: Catalog Feeds & Copywriting (GPT Tasks)
- [ ] **Batch Catalog Feed Synchronization** -> Meta dynamic ads CSV/XLSX export formats (`catalog_products_facebook.csv`, `catalog_products_facebook.xlsx`).
- [ ] **Offline Documentation & Copy Standardization** -> `policy.php` and `DEPLOYMENT_GUIDE.txt`.

### ⏳ Deferred Milestone
- **[Phase 70] Automated Direct bKash & Nagad MFS Webhooks** -> On hold pending official API proposals from gateways. Operational with manual TrxID + 1-click verification.

## 💡 FUTURE ENHANCEMENTS FOR NEXT STEPS

1. **Gamified Customer Loyalty Hub** — Expand the missions concept to everyday buyers (e.g., "Leave 3 verified reviews to earn a discount").
2. **Push Notifications** — Implement browser push notifications to instantly alert shop owners of new orders or messages.
3. **Advanced Storefront Customization** — Allow top-tier shop owners to customize their store banner, color theme, and featured items layout.
 
 # #   =  P h a s e   5 4   U p d a t e   ( C u r r e n t   S t a t e )  
 -   R e s o l v e d   2 3   m a j o r   b u g s   a c r o s s   f r o n t e n d ,   b a c k e n d ,   a n d   p a r t n e r   p o r t a l s .  
 -   E x e c u t e d   m a j o r   U I   r e p o s i t i o n i n g   o f   t h e   U s e r   D a s h b o a r d   S t o r e f r o n t   ( H e r o   A n a l y t i c s ,   M y   S h o p   U I ,   A c t i v e   A p p l i c a t i o n s ,   A g e n t   W a l l e t ) .  
 -   F i x e d   c r i t i c a l   T y p e E r r o r   b u g   c a u s i n g   b l a n k   s c r e e n   o n   P a r t n e r   H u b .  
  
 
## ✅ Phase 55 Complete - Analytics & Ratings
- Implemented Shop Analytics Date Filter with dynamic earnings/orders via AJAX.
- Implemented user_wishlist table and "Save for Later" toggle across the marketplace.
- Created Verified Shop Ratings & Reviews system for customers to rate completed orders.
- Built a Real-Time Notification Drawer in the user sidebar (user_notifications table).
- Updated Admin Analytics Line Chart to show 30-day Revenue and Active Users.

## 📝 Next Steps / Recommendations for the Platform:
1. **Withdraw Your Rewards / Wallet Improvements:** The withdrawal section can be enhanced with an automated payout gateway (like bulk bKash disbursement) to reduce admin manual workload. 
2. **Affiliate & Tasks / Missions:** Implement a gamified "Missions" dashboard where users unlock badges and higher commission rates (e.g., Bronze, Silver, Gold tiers) by completing specific milestones (e.g., "Refer 5 friends", "Make 10 sales").
3. **Want Higher Commissions?:** Create an upsell subscription (e.g., "Fast Site Pro") where partners pay a monthly coin fee to reduce platform transaction fees and boost their products to the top of the marketplace.

## 📋 NOTES & FUTURE PHASES
- **Database Architecture Check Needed**: Make sure all future `config.php` changes maintain compatibility with Hostinger MySQL environment vs Local Fallback SQLite.
- **Claude Sonnet 4.1 Audit File**: An audit of the Admin Panel, SQLite schema, and complex files has been saved to `HIGH CODING BY CLAUDE SONNET.md` (also backed up to Google Drive). This is intended for Claude Sonnet 4.1 to execute complex refactoring (e.g. `settings.php`, `nav.php`, and MySQL migration).
- Ensure Hostinger WAF rules aren't blocking static assets by verifying `.htaccess` settings.
- We need to continue standardizing all dashboards to the unified glassmorphism premium theme (as done in `user/wallet.php` and `admin/wallet.php`).

## ✅ Phase 61 Complete - Live Server Critical Bug Fixes (Hostinger)
- **Database Restoration**: Diagnosed and fixed the local fallback trigger in config.php. Verified correct .env database credentials and restored the Hostinger MySQL connection. Re-seeded products via seed_ecosystem_shops.php.
- **Image / WAF 422 Fix**: Discovered a critical .htaccess bug where static images were being routed into index.php and executed as PHP. This triggered Hostinger WAF (Web Application Firewall) to return 422 / Invalid source image. Fixed .htaccess to properly serve static files.
- **UI Tweaks**: Enlarged the main navigation logo across nav_public.php and admin/nav.php for better visibility on the live site.

## ✅ Phase 62 Complete - Gamified Customer Loyalty Hub
- **Gamification Engine (`config.php`)**: Engineered a centralized `updateUserMissionProgress` engine to dynamically track user behaviors (purchases, reviews) and auto-credit `coins_balance` upon mission completion.
- **Missions Frontend (`user/missions.php`)**: Built a flagship, glassmorphism UI for users to track their progress towards milestones (e.g. "Active Reviewer", "First Purchase", "Big Spender") with animated progress bars.
- **Dynamic Hooks**: Integrated mission progress tracking natively into the `place_order.php` checkout flow and the `partner_orders.php` review submission flow.
- **Dashboard Quick Access**: Added a premium promotional card to `user/dashboard.php` driving traffic directly to the Loyalty Hub.

## ✅ Phase 63 Complete - Admin Workspace Enhancements
- **Admin Master Wallet (`admin/wallet.php`)**: Engineered a premium, glassmorphism UI for the Admin's Master Wallet. Shows global economy metrics, Official Fast Site Shop revenue, total coins in circulation, and recent global transactions.
- **Fast Site Shop SSO**: Added a one-click SSO teleport button into the `admin/nav.php` allowing the admin to instantly impersonate and manage the "Fast Site Official" partner shop without separate logins.
- **Navigation Menu Overhaul**: Updated the Admin Sidebar (`admin/nav.php`) to dynamically inject and show the "Fast Site Shop" and "My Wallet" icons natively in the routing definitions.

## ✅ Work Project 1 Complete - Admin & Wallet Upgrades
- **Admin Coin Giveaways**: Added the ability for admins to instantly give/send Fast Site Coins directly to any user from the Admin Users Hub.
- **Payout SQL Fixes**: Fixed a database syntax error (NOW() vs CURRENT_TIMESTAMP) that was breaking admin payout processing in `admin/payouts.php`.
- **Checkout Instructions**: Updated the checkout gateway instructions to exactly match requested string: "Please send exact {AMOUNT} to {METHOD} our official wallet below".
- **Premium User Wallet**: Completely redesigned `user/wallet.php` using rich aesthetics, glassmorphism UI, a gradient dark theme, and micro-animations to create a flagship banking app experience.

## ✅ Phase 64 Complete - User Panel Polish & UI Upgrades (Gemini Execution)
- **Withdraw Coins UI**: Upgraded `user/withdraw_coins.php` from a raw POST endpoint into a beautiful, premium glassmorphism form for requesting payouts (bKash/Nagad/Bank).
- **Navigation Cleanup**: Restructured `includes/user_sidebar.php` into logical categories (Marketplace, Earning Tools, Financial Tools) and injected the new Withdraw link.
- **Link Corrections**: Fixed the broken 404 "Claim & Cash Out" link in `user/dashboard.php` and the Admin Guide link that wrongly redirected users to admin login.
- **Debt Removal**: Deleted placeholder files (`admin/give_task_affiliates.php`, `admin/give_task_agents.php`, `user/buy_coins.php`) and removed them from the navigation arrays to streamline the codebase.
- **HTML Validation**: Removed orphaned `</nav>` tags breaking the layout in `user/deposit.php` and `user/wallet.php`.

## ✅ Phase 65 & 66 Complete — Refactoring, Character Encoding Fix & Full Codebase Mojibake Scan
- **Admin Settings Modularization**: Refactored monolithic `admin/settings.php` into clean partials (`admin/settings_partials/tab_*.php`) with zero database overhead on page load.
- **Admin Nav Architecture & Styling**: Extracted inline CSS to `assets/css/admin-nav.css`, optimized permissions and dropdown menus.
- **Comprehensive Mojibake Elimination**: Scanned and cleaned corrupted characters (Mojibake) across the entire codebase (`admin/`, `user/`, `partner/`, `affiliate/`, `includes/`).
- **Official Shop SSO Bug Fix**: Fixed schema mismatch in `admin/impersonate_official.php` (`ref_code` vs `registration_number`, removed invalid `user_id` query on `partners` table) allowing seamless 1-click teleport to Official Shop.
- **Pure UTF-8 Clean Files**: All files saved with pure UTF-8 encoding (no BOM) for 100% compatibility across both local and Hostinger production servers.

## 🚧 CURRENT WORK (WORK PROJECT 1) - ADMIN & USER PANEL UPDATES
- [x] Fix unclickable 'Pending' links in Admin dashboard.
- [x] Update Admin 'manage_services.php' to allow Affiliate Product Image + URL uploads.
- [x] Add explicit 'Fast Site Official Shop' navigation to Admin Panel.
- [x] Fix 'Add New Product' URL bug in User Panel Dashboard (partner/dashboard.php).
- [x] Diagnosed local missing products and successfully ran database fix to auto-publish all 55+ products.
- [x] Fixed character encoding corruption (Mojibake) across entire admin & user panel (including `Add New Product` title).
- [x] Fixed `admin/impersonate_official.php` fatal error on column query.
- [x] Fixed `user/messages.php` crash on missing `user_id` column.
- [x] Fixed `partner/coupons.php` missing `shop_coupons` table.
- [x] Enhanced Shipping/Delivery dropdown visual contrast with explicit high-contrast option styling.
- [x] Fixed `user/dashboard.php` Affiliate Milestone Badges fatal error on `referred_by` column.
- [x] Added `user_missions` table creation to database and `config.php`.
- [x] Added explicit high-contrast option styling to `partner/profile.php` Shop Settings dropdowns.
- [x] Upgraded 'Share & Earn Real Cash' referral card with automatic `ref_code` generator, glassmorphism UI, interactive copy feedback, and branded social share pills.
- [x] Removed deprecated orphaned `tab_partner.php` include warning from `user/dashboard.php`.
- [x] Fixed `registration_number` undefined array key warning in `user/dashboard.php`.
- [x] Restructured user dashboard: merged 'My Wishlist' and conditional 'My Active Applications' into 'My Shop', removed redundant cashout blocks from homepage (redirecting hero rewards to `withdraw_coins.php`), and unified all earning tools under 'Affiliate & Tasks'.
- [x] Added official Fast Site logo image and branding to User Panel top navbar and sidebar header (`includes/user_sidebar.php`).
- [x] Upgraded User Messaging system (`user/messages.php`) with interactive "Start New Chat" hub and shop selector modal.
- [x] Streamlined User Panel sidebar navigation into clear, intuitive categories (`Marketplace & Shop`, `Earning & Tasks`, `Financial Tools`, `Support & Settings`).
- [x] Overhauled `user/profile.php` into a centered, premium 2-column glassmorphism layout with real-time avatar preview and KYC verification vault.
- [x] Fixed `user/missions.php` undefined array key `coins` warning and made all Available Missions interactive with real-time progress, claim buttons, and direct action links.
- [x] Fixed `user/create_shop.php` fatal error on missing `user_id` column with defensive self-healing migration and unified dashboard redirection.
- [x] Fixed unclickable 'Affiliate & Referral Hub' and 'Help & Support' buttons by establishing proper anchor IDs (`#tab-affiliate`, `#tab-support`) and dedicated Support Hub.
- [x] Enlarged official top-nav logo to 38px and upgraded branding typography to ultra-premium gradient look, removing "Fast Sitee" typos from database seed settings.
- [x] Verified and verified all photo upload directories (`uploads/kyc`, `uploads/partners`, `uploads/products`, `uploads/messages`).
- [x] Redesigned `user/wallet.php`: built interactive inline "Deposit Funds / Buy Points" dropdown with bKash/Nagad details and quick-select chips, removed redundant sub-bar, integrated "My Shop Orders" directly into the wallet view with live status and payment release actions, and updated sidebar.
- [x] Upgraded `user/wallet.php` with dynamic inline "Withdrawal Form Dropdown", changed button text to 'Deposit' and 'Withdraw', added direct withdrawal processing, and renamed sidebar item to '🪙 My Wallet'.
- [x] Connected Admin Panel Mobile APK app logo upload settings (`FAST SITE HQ` admin app icon & `FAST SITE WORLD` user app icon) with dynamic live previews and immediate synchronization across headers.
- [x] Fixed `admin/wallet.php` database query fatal error (`pending_balance` column not found) with defensive SQL, updated master economy cards, and added instant coin gifting tool.
- [x] Removed redundant `Admin Services Hub` / `manage_services.php` from admin navbar, unified product creation into the single Official Shop Manager (`impersonate_official.php`), and linked the dashboard `➕ Add Product` button directly to `partner/product_add.php`.
- [x] Renamed Admin Panel navigation item from 'PARTNER SETTING' to "SHOPPER'S SETTING" in `admin/nav.php`.
- [x] Rebuilt `admin/wallet.php` and `user/wallet.php` layouts with standard responsive containers, fixing the flexbox squeezing bug on desktop and optimizing all cards, grids, and dropdown drawers for mobile APK screens.
- [x] Fully implemented Phase 36 Consolidated Admin Navigation Flow: `DASHBOARD` ➔ `ADMIN PANEL` ➔ `USER PANEL` ➔ `SHOPS` ➔ `ALL PARTNER` (including Best Travel, GixSam, Affi Bangla, Ayra Mart, Manza, Enzor Motor) ➔ `WALLET` ➔ `ADVANCE SETTING` with right-hand alerts, App Hub, Upload to Official Shop, and Logout.
- [x] Unified `admin/users.php` into an all-in-one Customer Hub with 4 responsive tabs: All Customers, KYC Verification Vault, Broadcast Notifications, and Loyalty Streaks.
- [x] Started Local Server Live Host on `http://localhost:8000` with session preservation and synced the `admin` user password to `FastSitee2026`.
- [x] Upgraded Admin Dashboard KPI stat cards (Total Orders, Pending, Processing, Approved, Cancelled) into interactive filter buttons that smoothly scroll to the applications table and filter records in real-time.
- [x] Redesigned and fixed `admin/analytics.php` (Data & Analytics Hub): removed broken `.dashboard-layout` flex container that squeezed the navbar into the bottom-left, integrated standard `.dashboard-container` and `.admin-hero`, added 4 high-level KPI cards (Inflow Revenue, Total GMV, Active Storefronts, Registered Users), multi-timeframe filter switcher (7d/30d/90d/365d), dual Revenue vs GMV trendlines, user acquisition bar chart, category doughnut chart, and Top Performing Ecosystem Shops table.
- [x] Completely redesigned `admin/staff_access.php` (Staff & Role Access Control): fixed "STUFF" typo to "STAFF", created glassmorphism UI with interactive toggle switches, 1-click permission presets (All Access, Customer Support, Finance, Shop Moderator), modal for adding new staff members, direct password resets, staff deletion, and updated permissions parser in `admin/nav.php`.
- [x] Redesigned `admin/give_task_staff.php` (Staff Task Delegation Hub) into a responsive 2-column glassmorphism layout with task status badges and direct completion triggers.
- [x] Fixed `admin/agents.php` HTTP 500 fatal error: removed invalid `a.user_id` JOIN on `users` table, overhauled page with glassmorphism KPI counters (Total Agents, Active, Pending, Total Paid Out), and 1-click status actions (Approve, Suspend, Reactivate, Delete).
- [x] Fixed `admin/wallet.php` Coin Gifting SQL error (`unknown column 'description'`): implemented defensive fallback to `reference` / `description` columns in `coin_transactions`.
- [x] Upgraded `admin/wallet.php` Recent Global Financial Transactions: joined customer names, phone numbers, and transaction IDs, transformed all transaction entries into rich clickable links directly opening `coin_deposits.php` and `user_withdrawals.php` for instant 1-click verification.
- [x] Completely redesigned `admin/coin_deposits.php` and `admin/user_withdrawals.php` with unified glassmorphism dashboard layout, KPI metrics, and verified approval/rejection workflows.
- [x] Completely overhauled `admin/payouts.php` (Master Payouts & Gateways Hub): upgraded with high-level KPI cards (Pending Agent Payouts, Pending Invoices, Settled Payouts, Official Disbursement Sender), multi-tab layout (Agent Payouts Ledger, Gateway & Shipping Controls, Gateway Portals Directory), iOS switch cards for bKash/Nagad/Cards/COD, delivery charges config, and 1-click batch bKash CSV mass export.
- [x] Implemented Full End-to-End Customer KYC Identity Verification in `admin/users.php` and `user/profile.php`: added 1-click `🪪 KYC` interactive modal in 'All Customers' table with attached documents preview (NID, Passport, eTIN, Driving License), direct `✓ Approve KYC (Verified)` / `✕ Reject` triggers, live status badges, upgraded `🪪 KYC Vault` tab query, and user-side auto-submission with status banner.
- [x] Fixed Product Upload Failure in `partner/product_add.php`: resolved MySQL column mismatch errors (`affiliate_url`, `affiliate_action`, `require_submission`, `submission_type`, `submission_required`, `submission_prompt`), and added self-healing database migrations in `config.php`.
- [x] Fixed Photo Upload Unclickable Bug: fixed `includes/cropper_modal.php` DOM initialization where `originalInput.style.display = 'none'` blocked file picker trigger, added transparent full-coverage input overlay, and explicit '📁 Choose Photos from Device' action button.
- [x] Implemented Customer Document & Information Submission System (Summation Box) across Partner Panel, Product View & Checkout: created optional/mandatory submission toggle in `partner/product_add.php` and `partner/product_edit.php`, dynamic buyer prompt and file upload in `product_detail.php`, multi-file upload and storage in `user/place_order.php`, and document viewer in `partner/orders.php`.
- [x] Upgraded Partner / Shopper Panel to Ultra-Premium Dark Glassmorphism Theme: overhauled `partner/product_add.php` and `partner/product_edit.php` with glowing gold/emerald accents, interactive pill switcher, iOS toggle switches, and responsive form layout.
- [x] Form State Preservation & Field Highlight Feedback: prevented form clearing on errors in `partner/product_add.php` and `partner/product_edit.php`, retaining all user input, added instant client-side validation with glowing red `.field-error` outlines, tooltips, and smooth auto-scroll to missing blocks.
- [x] Optional / Free Price Support: made Price optional (defaults to 0.00) in product add/edit forms and storefront checkout, supporting free promotional items with a `🎁 FREE / PROMOTIONAL` badge.
- [x] Completely Overhauled Shop Dashboard (`partner/dashboard.php`): removed clunky duplicate sidebar, built unified executive command center with 6 KPI glassmorphism cards, Quick Actions bar, 4-tab hub (Command Overview, Storefront Inventory, Escrow Orders, Viral Marketing), and shareable store URL with 1-click social buttons.
- [x] Fixed Promo Code Page HTTP 500 Error & Overhauled `partner/coupons.php`: added missing `shop_coupons` table creation and migrations in `config.php` and `partner/coupons.php`, built luxury 2-column coupon management board with KPI metrics, discount presets, copy button, and status indicators.
- [x] Added Floating WhatsApp Support Widget: created `includes/whatsapp_button.php` positioned in bottom-right corner with direct chat number `+8801337320544`, pulsating glow animation, and integrated across all public, partner, and admin views.
- [x] Fixed Post-Upload HTTP 500 & Added Executive Animated 1-to-100% HUD Upload Score: transformed `partner/product_add.php` and `partner/product_edit.php` to use seamless AJAX upload and built a circular neon HUD progress dial counting 1% to 100% with live multi-stage status text (Initializing -> Processing HD Photos -> Registering on Escrow -> Published) and auto-redirect.
- [x] Overhauled Shop Profile Settings (`partner/profile.php`): transformed into an Executive Brand & Profile Studio with live Cinema Banner/Logo upload preview, Verified Partner & Registration badges, 2-column layout (Brand Details & Contact, District discovery, Payout settlement info, Security password toggle vault, and Escrow Shield status).
- [x] Implemented Direct Portal Clean URLs (`/admin`, `/partner`, `/user`): added `admin/index.php`, `partner/index.php`, `user/index.php`, and updated `.htaccess` so visiting `https://fastsite.best-travel.ltd/admin` or `http://localhost:8000/admin` seamlessly opens the admin dashboard (if logged in) or login portal.
- [x] Added Admin Dashboard Quick Product / Service Upload System: created `admin/ajax_quick_product_add.php` and upgraded `admin/dashboard.php` with an interactive Quick Upload Modal, optional price support (0 = FREE), 1:1 HD image preview, and animated 1-to-100% progress score.
- [x] Overhauled Admin Panel Navigation Bar (`admin/nav.php` & `assets/css/admin-nav.css`): redesigned into an Executive Enterprise navbar with high-gloss glassmorphism, glowing gold logo, clean hub icons, Quick Upload & Official Shop action pills, pulsating alert badges, and multi-platform bridge.
- [x] Unified Official Shop Registration Identifier (`FS-OFFICIAL-1`): unified `partners.registration_number` and `users.ref_code` to consistently show `FS-OFFICIAL-1` across all admin, partner, and user profile views.
- [x] Overhauled User Forgot Password Page (`user/forgot_password.php`): eliminated session notice, added self-healing `password_resets` table creation, and redesigned with luxury dark glassmorphism card, `+880` phone input formatting, and direct WhatsApp 1-click recovery support.
- [x] Fixed Admin Navbar Overflow & Responsive Precision (`assets/css/admin-nav.css`): resolved navigation items extending past right viewport border, applied precision flex-wrap boundaries, compact typography, auto-scaling media queries, and single-row alignment across all screen sizes.
- [x] Turned on Local Server Live Host (`http://localhost:8000`) with built-in PHP daemon process and verified HTTP 200 responses for root marketplace and admin diagnostic tools.
- [x] Built Universal Hostinger Linux Path & Backslash Normalizer (`fix_backslashes.php` & `fix_paths.php`) and generated `fastsite_fix_backslashes.zip` with `DEPLOYMENT_GUIDE.txt` to eliminate Windows `\` extracted filenames on Hostinger.
- [x] Built `fastsite_phase44.zip` with 100% strictly normalized Unix forward slash (`/`) paths and `DEPLOYMENT_GUIDE.txt`, guaranteeing all files extract directly into their proper subdirectories on Hostinger without flat backslash files.
- [x] Created instant auto-executing `fix.php` (and `fix_hostinger.zip`) that automatically moves all `user\*.php`, `partner\*.php`, `admin\*.php` files directly inside their respective subfolders and removes the flat files from `public_html`.
- [x] Upgraded `shop.php` & `config.php` Universal Image Resolver: fixed partner slug matching (for `dark_wolf` and other partners), fixed shop logo and cover banner display (`profile_pic`, `cover_pic`), joined `partner_product_images` to show all uploaded product photos, and created `fastsite_shop_image_fix.zip` and updated `fastsite_phase44.zip`.
- [x] Fixed `shop.php` 0 Listings bug: added multi-strategy defensive queries to match shop by `business_name`, `partner_products` JOIN, and self-healing schema migration for `shop_slug` on MySQL.
- [x] Created `admin/sync_partner_photos.php` to scan and auto-link all physical photos stored in `uploads/partners/` directly into products, shop logos, and cover banners.
- [x] Overhauled `home.php` Shops Directory & Ecosystem Cards: pre-populates all registered and approved partner shops directly from `partners` table (Fast Site Official, DARK WOLF, Best Travel, Ayra Mart, Enzor Motor, Affi Bangla, Manza, GixSam) so all shops appear in the `🏪 SHOPS` tab with their portfolios and storefront links.
- [x] Auto-Approved Past Registered Shopper Shops: added a one-time migration in `config.php` (`system_migrations`) to approve existing historical shopper shops so they appear live on the marketplace, while strictly setting all future shop registrations in `user/create_shop.php` and `partner/dashboard.php` to `status = 'pending'` for Admin review.
- [x] Mobile APK Visual Precision across Admin, User, and Partner panels (`assets/css/mobile_responsive.css`): enforced `max-width: 100vw`, zero horizontal overflow, responsive table containers with touch momentum scrolling, compact KPI stat grids, and flex-wrap boundaries.
- [x] Native Swipe-Down-To-Refresh Engine (`assets/js/pull_to_refresh.js`): implemented ultra-smooth pull-down gesture with glassmorphism HUD spinner, dynamic state transitions (Pull / Release / Refreshing), haptic vibration feedback, and integrated across all Admin, User, Partner, and Storefront pages.
- [x] Bulletproofed Shop Loading & Schema Defenses: added defensive `ALTER TABLE` migrations for all partner columns (`is_hidden`, `is_official`, `seller_level`, `tags`, etc.) in `config.php`, updated `home.php` to never drop shops on missing columns, and unified `user_id` and phone matching in `partner/nav.php` and `partner/dashboard.php`.
- [x] Full Codebase & File Integrity Verification: Ran full-codebase PHP syntax linting with 0 errors across all 170+ files, verified all 52 core database tables, synchronized `check_files.php` to 100% (186/186 files present and verified), confirmed directory structures and permissions, and validated all zip deployment packages.
- [x] Local Server Live Host Launched: Started PHP built-in live server daemon on `http://localhost:8000` for live local testing.
- [x] Fixed User KYC Approval & Shop Acceptance HTTP 500 Fatal Error:
  - **Root Cause Identified:** Missing `kyc_status` column in `users` and missing `type` column in `user_notifications` table on MySQL caused an uncaught PDOException and HTTP 500 error when clicking "Approve (Mark Verified)" or "Reject KYC" in `admin/users.php`. Additionally, `admin/partner_shops.php` and `admin/partner_requests.php` had mismatched request ID parameters and lacked self-healing `status`, `registration_number`, and `is_hidden` column migrations.
  - **Resolution Executed:** 
    1. Added automatic self-healing schema migrations for `kyc_status`, `nid_front_photo`, `nid_back_photo`, and `user_notifications.type` across both MySQL and SQLite in `config.php`.
    2. Overhauled `admin/users.php` with defensive try-catch and 1-click Account Status toggling (Activate / Suspend) + 1-click KYC Verification.
    3. Overhauled `admin/partner_shops.php` and `admin/partner_requests.php` to synchronize shop approvals, generate registration numbers (`FS-SHOP-XXXXX`), set `role = 'partner'`, and send instant user notifications.
    4. Packaged updated files into `fastsite_phase44.zip` (2.68 MB) and `fastsite_admin_only.zip` (221 KB) with `DEPLOYMENT_GUIDE.txt`.
- [x] Fixed Mobile App APK File, Photo & Audio Picker Issue:
  - **Root Cause Identified:** Android's native `WebView` component does NOT open file pickers for `<input type="file">` unless the `WebChromeClient` explicitly overrides `onShowFileChooser(webView, filePathCallback, fileChooserParams)` and delegates to an Android `ActivityResultLauncher`. Additionally, Android 13+ (API 33+) requires runtime permission prompts (`READ_MEDIA_IMAGES`, `READ_MEDIA_AUDIO`, `CAMERA`) from the phone owner.
  - **Resolution Executed:**
    1. Implemented runtime storage and camera permission requester (`requestStoragePermissions()`) using `ActivityResultContracts.RequestMultiplePermissions()` on app launch and file chooser tap.
    2. Implemented `Intent.createChooser` universal intent with multi-mime-type support and full clipData URI array extraction.
    3. Added `READ_MEDIA_IMAGES`, `READ_MEDIA_AUDIO`, `READ_MEDIA_VIDEO`, `READ_EXTERNAL_STORAGE`, `WRITE_EXTERNAL_STORAGE`, and `CAMERA` permissions to `AndroidManifest.xml`.
    4. Enabled `allowFileAccess` and `allowContentAccess` on `WebSettings`.
    5. Bumped `versionCode` to 3 and `versionName` to 1.2 in `build.gradle.kts` and `api/app_version.php`.
    6. Successfully compiled updated `fastsite_storefront.apk` (11.25 MB) and synchronized to `uploads/fastsite_superapp.apk` and root.
- [x] Fixed Mobile App APK Display Layout Overflow (Dashboard, Escrow Orders, Wallet):
  - **Root Cause Identified:** `partner/dashboard.php`, `partner/orders.php`, and `user/wallet.php` had fixed grid minimums (`minmax(190px, 1fr)`), unconstrained flex action buttons, and non-scrollable horizontal tab bars that exceeded mobile screen boundaries (320px–390px). Additionally, the pull-to-refresh badge overlapped with the fixed top navigation bar.
  - **Resolution Executed:**
    1. **Partner Dashboard (`partner/dashboard.php`):** Bound `.hub-container` to `max-width: 100vw`, transformed `.executive-hero` to vertical flex with 100% action buttons, constrained `.kpi-grid` strictly to 2 balanced mobile columns with compacted value fonts, and enabled momentum touch scrolling (`-webkit-overflow-scrolling: touch`) on `.hub-tabs-bar`.
    2. **Partner Orders Queue (`partner/orders.php`):** Added smooth touch scrolling to `.tabs-bar` with hidden scrollbars, made `.order-card` full width, and made order action buttons stretch to full container width.
    3. **User Wallet (`user/wallet.php`):** Compressed `.premium-wallet-card` padding, turned deposit/withdraw action buttons into full-width vertical stack, enabled horizontal touch scrolling on `.wallet-tabs`, and adjusted font scaling on `.pw-balance`.
    4. **Pull-to-Refresh HUD (`assets/js/pull_to_refresh.js`):** Adjusted HUD top offset to position below the fixed top navbar (`top: 70px+`), eliminating overlapping on header brand and balance pill.
    5. Rebuilt deployment package `fastsite_phase44.zip` (2.68 MB).
- [x] APK Version 1.3 Update (Logo, Gesture Back, Direct Photo & Audio Upload):
  - **Root Cause Identified for Upload Button:** Synthetic click bubbling in `cropper_modal.php` caused double intent dispatch, triggering Android OS callback cancellation. Furthermore, specific comma-delimited accept strings filtered out some Android photo galleries.
  - **Resolution Executed:**
    1. **Upload Target Engine:** Removed synthetic click listeners in `includes/cropper_modal.php`, applied `pointer-events: none` on visual children in `partner/product_add.php`, and switched file inputs to universal standard `accept="image/*"` and `accept="audio/*"`.
    2. **APK Official Logo:** Generated high-resolution branded launcher icons across all Android screen densities (`mdpi`, `hdpi`, `xhdpi`, `xxhdpi`, `xxxhdpi`) and placed `fastsite_logo.png` into `res/drawable/`.
    3. **Gesture Navigation in APK (`MainActivity.kt`):** Built a native animated floating `[ ← Back ]` gesture pill at the bottom-left with gold accents and haptics, and enabled smooth edge-swipe gesture navigation (swipe right from left edge to navigate back in webview history).
    4. Bumped `versionCode` to 4 and `versionName` to 1.3 in `build.gradle.kts` and `api/app_version.php`.
    5. Compiled fresh `fastsite_storefront.apk` (11.18 MB) and synchronized to `uploads/fastsite_superapp.apk` and root.
- [x] APK Version 1.4 & Partner Navigation Overhaul (Root Cause Diagnostics & Resolution):
  - **Technical Root Causes Identified:**
    1. **File Picker Cancellation:** In `MainActivity.kt`, calling `requestStoragePermissions()` concurrently inside `onShowFileChooser` simultaneously fired two Android Activity Result launchers. The Android OS permission dialog immediately canceled the file chooser with `RESULT_CANCELED`, executing `callback.onReceiveValue(null)` before the gallery could open.
    2. **Back Button Visibility:** In `MainActivity.kt`, the floating back button was gated by `AnimatedVisibility(visible = backEnabled)`. When `webView.canGoBack()` was false (such as opening the shop directly), the button was completely hidden.
    3. **Missing User Dashboard Link in Shop Menu:** In `partner/nav.php`, the side drawer and header lacked any link to `/user/dashboard.php`, trapping shop owners inside the partner hub.
  - **Resolution Executed:**
    1. **APK File Chooser Engine (`MainActivity.kt`):** Decoupled `requestStoragePermissions()` from `onShowFileChooser` (permissions are requested once on startup), letting Android's standard System File Picker / SAF intent execute without activity interruptions.
    2. **Universal Floating Navigation Bar (`MainActivity.kt`):** Replaced conditional back button with a permanent, glassmorphism floating `[ ← Back | 🏠 Home ]` control pill that is always visible and functional.
    3. **Shop Menu & Header Overhaul (`partner/nav.php` & `partner/dashboard.php`):** Kept the clean `🏠 User Dashboard` button inside the shop side drawer, removed the duplicate top headers and top breadcrumb bar for a clean, distraction-free view, and relied on the APK's bottom floating `[ ← Back | 🏠 Home ]` control.
    4. **Fast Mobile Scrolling:** Added GPU-accelerated scrolling (`transform: translateZ(0)`) and disabled heavy rasterization blur filters during mobile touch scrolling for smooth 60–120 FPS performance.
    5. Recompiled `fastsite_storefront.apk` (11.19 MB) and rebuilt clean lightweight code deployment package `fastsite_phase44.zip` (2.68 MB).
- [x] APK Version 1.5 & Universal Header Overlap Fix:
  - **Issues Identified:**
    1. **Logo & Shop Pill Overlap:** In `includes/nav_public.php`, `.nav-center` used `position: absolute; left: 50%` which collided directly with the dynamic partner shop pill and coin badge on mobile viewport widths (<390px).
    2. **APK Scrolling Drag Lag:** In `MainActivity.kt`, Jetpack Compose's root Box had a `pointerInput(Unit) { detectHorizontalDragGestures }` modifier which intercepted all touch streams before forwarding to the WebView, causing touch delay and sluggish scrolling.
  - **Resolution Executed:**
    1. **Navbar Flexbox Architecture (`includes/nav_public.php`):** Completely removed absolute positioning. Replaced with pure flexbox with `justify-content: space-between`, placing brand logo & coins on the left, and dynamic shop pill (with auto-ellipsis text capping) + hamburger button on the right, guaranteeing zero collisions.
    2. **APK Hardware Acceleration (`MainActivity.kt`):** Removed the Compose touch interception modifier, enabled `View.LAYER_TYPE_HARDWARE` on the WebView, enabled `useWideViewPort` & `loadWithOverviewMode`, and set overScrollMode to direct hardware compositor for fluid 60–120 FPS scrolling.
    3. Bumped APK version to 1.5 (versionCode = 6) in `build.gradle.kts` and `api/app_version.php`.
    4. Compiled `fastsite_storefront.apk` (11.18 MB, v1.5) and rebuilt `fastsite_phase44.zip` (2.68 MB).
- [x] High-Speed Fluid Scrolling Engine (Elimination of Micro-Stutters & GPU Bottlenecks):
  - **Issues Identified:**
    1. **Heavy CSS GPU Blur Filters:** In `home.php`, two large glowing background orbs used `filter: blur(80px)` combined with infinite keyframe animations (`orbFloat1`, `orbFloat2`, and `drift`). In mobile WebViews, animating a large 80px Gaussian blur forces continuous GPU rasterization on every frame, dropping FPS from 60fps down to 15fps during touch scrolling.
    2. **Touch Event Main-Thread Blocking:** In `assets/js/pull_to_refresh.js`, `onTouchMove` executed un-throttled DOM queries on every touch pixel, causing touch event contention.
  - **Resolution Executed:**
    1. **CSS Layer Optimization (`home.php`):** Replaced heavy 80px blur filters and animated orbs with clean, GPU-efficient radial gradients with zero rasterization overhead.
    2. **Throttled Pull-to-Refresh (`assets/js/pull_to_refresh.js`):** Cached DOM element references and throttled HUD transforms via `requestAnimationFrame`. Upward scrolls immediately de-activate the listener to let native hardware scrolling take over with 0ms delay.
    3. Rebuilt clean lightweight code deployment package `fastsite_phase44.zip` (2.68 MB).
- [x] Android File Selection Filter & User Dashboard UI Refinement:
  - **Issues Identified:**
    1. **Android Gallery Filter Rejection:** In `includes/cropper_modal.php`, `f.type.startsWith('image/')` filtered out files when Android Content Providers sent empty (`""`) or generic `application/octet-stream` MIME types, silently aborting the cropper modal.
    2. **Top Navbar Logo Distortion:** In `includes/user_sidebar.php`, giant gradient text `FAST SITE` overflowed the 60px fixed top navbar on mobile devices, pushing the logo out of view.
    3. **Squished Shop Label in User Dashboard:** In `user/dashboard.php`, the label `🏪 My Shop: TEST SHOP` caused aggressive word-wrapping into single letters on small screens.
    4. **Missing Back Navigation in User Dashboard:** The mobile bottom navbar lacked a functional, highlighted Back button with fallback navigation.
  - **Resolution Executed:**
    1. **Robust Extension Fallback (`includes/cropper_modal.php`):** Updated `handleFilePick` to evaluate image file extensions (`.jpg`, `.jpeg`, `.png`, `.webp`, `.gif`, `.heic`, `.jfif`) and fallback to raw selected files if MIME type is omitted by Android OS.
    2. **Clean Navbar Logo (`includes/user_sidebar.php`):** Hidden large text on mobile screens to display the crystal-clear Fast Site shield logo at a fixed height of 36px without collisions.
    3. **Clean Shop Branding (`user/dashboard.php` & `includes/user_sidebar.php`):** Replaced `🏪 My Shop: TEST SHOP` with `⚡ TEST SHOP` with flexible wrapping, eliminating multi-line word breaks.
    4. **Universal Back Button (`user/dashboard.php` & `includes/user_sidebar.php`):** Added a top `← Store` button and a dedicated `[ ⬅️ Back ]` item with history fallback in the bottom navigation bar.
    5. Rebuilt clean lightweight code deployment package `fastsite_phase44.zip` (2.68 MB).
- [x] User Dashboard Hub Overhaul & Android APK Version 1.6:
  - **Requirements & Problems Addressed:**
    1. **User Dashboard Hub Sidebar (`includes/user_sidebar.php`):**
       - Top Profile section: Tapping avatar or user name redirects straight to `/user/profile.php` or profile settings (`tab-social`), removing redundant bottom duplicate links.
       - Marketplace & Shop: Renamed `Shop Fast Site (Home)` to `🏪 Marketplace`. Hidden `User Dashboard` link when already viewing `dashboard.php`. `My Application Orders` only appears if the user has requested applications. `Open a Free Shop` only appears for users who haven't opened a shop yet. `Customer SHOP ORDER` only appears when orders exist for the shopkeeper with active order badge count.
       - Removed `Job Board SOON` completely across the platform.
       - Removed duplicate `Affiliate & Referral Hub` from drawer (moved as a central feature to User Dashboard).
       - Repositioned Messages into `Support & Settings`.
       - Renamed Help & Support to `💬 Support Center` and added official admin email (`info.fastsite@gmail.com`).
       - Sized up and lowered the sidebar header logo ($40–44\text{px}$) with drop shadow.
    2. **User Dashboard Page (`user/dashboard.php`):**
       - Dynamic Loyalty Missions Tier header: Automatically computes user referral count and loyalty bracket (`Bronze`, `Silver`, `Gold`, `Platinum`, `Diamond`), dynamic gradient background matching the badge tier, and renders the badge right beside the user's name with the tagline `"⚡ Complete Mission to Earn Real Cash"`.
       - Integrated **`🎯 Missions & Real Cash Hub`** card directly into the User Dashboard view, featuring 20% instant commission link copy, and quick links to Daily Tasks, Loyalty Tiers, and Cash Withdrawal.
       - Fixed settings sliders (`.fs-switch`) with smooth toggle animations and persistent `localStorage` synchronization for Marketplace Shopper Storefront and Biometric Login (with device dependency notice).
       - Fixed Change Password button width to prevent text wrap on mobile.
       - Updated Support Center with `info.fastsite@gmail.com` direct mailto action.
    3. **Android Status Bar & Notch Dark Theme (`MainActivity.kt` & `Theme.kt`):**
       - Fixed white notch in Mobile APK: Enforced `#08080C` dark theme, dark status bar, dark navigation bar, and `isAppearanceLightStatusBars = false` so network, net speed, clock, and battery icons are crisp white on dark background.
       - Bumped APK version to 1.6 (`versionCode = 7`) in `build.gradle.kts` and `api/app_version.php`.
    4. **Messages / Shop Chat Fix (`user/messages.php`):**
       - Removed restrictive partner check when initiating shop chat, allowing all accounts to open chat modal with seller/admin.
       - Universal parameterized timestamp handling (`:cat`) replacing SQLite-specific `datetime('now')`.
    5. Rebuilt deployment package `fastsite_phase44.zip` (2.69 MB) and recompiled `fastsite_storefront.apk` (v1.6).
- [x] Affiliate Link Product Discovery & Trending Curation Engine:
  - **Rules Applied:**
    1. **"🔥 Trending Right Now" Curation (`home.php`):** Strictly excludes affiliate link products (`p.affiliate_url IS NULL OR p.affiliate_url = ''`) so the spotlight is reserved 100% for genuine Direct Escrow products, official services, and digital downloads.
    2. **Default Homepage Curation (`home.php` & `api/live_search.php`):** Default homepage feed shows only direct escrow items. Added `⚡ PARTNER DEALS` segment button allowing buyers to optionally filter and browse partner affiliate deals.
    3. **Intent-Based Search Engine (`home.php` & `api/live_search.php`):** When a user searches for keywords in the Search Bar, matching affiliate products are returned with `[ ⚡ PARTNER OFFER ]` badge and `[ Visit Partner ↗ ]` action button.
    4. **Dedicated Shop Storefronts (`shop.php`):** When visiting any specific shopkeeper's store (`/shop/:slug` or `shop.php?id=X`), ALL products uploaded by that shop owner (including affiliate deals) are displayed.
    5. Shop Owner Hub (`partner/dashboard.php`): Shop owners retain full visibility and management over all their products.
    6. Rebuilt `fastsite_phase44.zip` (2.69 MB).
- [x] Admin Products Command Center & Enhanced Notification Engine:
  - **Features Implemented:**
    1. **Admin Product Management Hub (`admin/products.php`):**
       - Complete product oversight across all shops: Direct Products, Official Services, Partner Affiliate Offers.
       - 1-click **Add to Trending / Cancel Trending** (`is_trending` toggle).
       - 1-click **Put on Hold / Activate** (`is_published` toggle).
       - 1-click **Permanent Deletion** with image cleanup.
       - **Automated Shop Owner Notifications:** Automatically notifies shopkeeper's account when any product is held, unheld, deleted, or featured in Trending.
    2. **Admin Navbar Upgrade (`admin/nav.php`):**
       - Added `🛍️ PRODUCTS` navigation button tab and dropdown right next to `SHOPS` in both desktop header and mobile drawer.
       - Enhanced Notification Bell Icon: monitors 7 core pending queues (Shop Registrations/Requests, KYC Submissions, Service Orders, Disputes, Withdrawals, Deposits, Task Proofs) on both Mobile APK and Desktop.
    3. **Resilient Shop Request Approvals (`admin/partner_shops.php`):**
       - Fixed shop approval logic to auto-create missing partner profile records, link `user_id` by phone/registration, promote `users.role = 'partner'`, and trigger instant onboarding notifications.
    4. Rebuilt `fastsite_phase44.zip` (2.70 MB).
- [x] Android Mobile App APK Version 1.7 (Update Loop Fix & Unified Direct Downloads):
  - **Root Cause of Loop Diagnosed:** When the app was downloaded from old paths, it ran with an older version code (e.g. 5/6), whereas `api/app_version.php` served code 7. The update popup had no dismissal cache and kept re-triggering upon launch.
  - **Fixes Applied:**
    1. Built & signed release APK **`fastsite_storefront.apk`** with `versionCode = 8`, `versionName = "1.7"`.
    2. Updated `api/app_version.php` to version code 8 ('1.7').
    3. Implemented `SharedPreferences` dismissal caching in `MainActivity.kt` so tapping "LATER" caches dismissal and prevents repetitive prompts.
    4. Unified all download buttons across `nav_public.php`, `download.php`, and `footer.php` to point to `/fastsite_storefront.apk`.
    5. Copied the 1.7 release APK to `fastsite_storefront.apk`, `fastsite_superapp.apk`, and `uploads/fastsite_superapp.apk`.
    6. Rebuilt `fastsite_phase44.zip` (2.70 MB).
- [x] Phase 45: All-In-One Unified Hostinger Deployment Bundle (`fastsite_phase45.zip` — 31.44 MB):
  - **Reason:** Previous zip archives excluded `.apk` files to keep the zip small (~2.7 MB). Consequently, Hostinger remained serving old APK files, causing the phone to download outdated builds and trigger the update alert loop.
  - **Solution:** `build_hostinger_zip.py` now bundles both the web application code AND the newly compiled signed Android APK binaries (`fastsite_storefront.apk`, `fastsite_superapp.apk`, and `uploads/fastsite_superapp.apk`).
  - **Result:** Extracting `fastsite_phase45.zip` on Hostinger `public_html/` instantly updates website backend, Admin products hub, notification engine, and mobile APK binaries in one single step.
- [x] Interactive Audio Player & KYC Verification Vault Auto-Clear:
  - **Audio Player Engine:**
    1. Built a modern Glassmorphism HTML5 Audio Player for `product_detail.php` with animated sound waves, rotating vinyl disc, scrubbable progress bar, rewind/forward 10s, loop toggle, and volume control.
    2. Completely removed the artificial 30s cutoff so customers and buyers can hear the full audio track / singer sample before ordering.
    3. Added audio track upload & management support in `partner/product_add.php` and `partner/product_edit.php` (MP3, WAV, FLAC up to 50MB) with auto-play prevention and error handling.
    4. Enhanced marketplace cards and trending picks in `home.php` and `shop.php` with `🎵 Audio Track` / `🎵 Voice / Audio` badging.
    5. Full compatibility across Desktop browsers and Android Mobile APK WebView.
  - **KYC Vault Queue Auto-Clear:**
    1. In `admin/users.php`, updated the active KYC Queue to strictly display users with pending document submissions.
    2. Once an admin clicks "✓ Approve KYC (Verified)" or "Reject", that user is instantly cleared from the active queue.
    3. Added a collapsible "✅ Verified Accounts Archive" below the queue for past verification review and status resetting.
    4. Rebuilt `fastsite_phase45.zip` (31.44 MB).
- [x] Local Server Live Host Launched & Verified: Started PHP built-in web server daemon on `http://localhost:8000` (and `http://127.0.0.1:8000`), verified HTTP 200 OK across root storefront, system check, authentication portals, and admin routes.
- [x] Android Mobile APK Version 1.8 (Build 9) & 120 FPS High-Speed Scrolling Engine:
  - **Infinite Update Loop Elimination:** Added persistent dismissal caching (`last_dismissed_version_code`) on both "UPDATE NOW" and "LATER", plus a 24-hour rate limiter in `MainActivity.kt`.
  - **Buttery 60–120 FPS Fluid Scrolling:** Disabled GPU raster-heavy `backdrop-filter` on mobile viewports (<768px) in `assets/css/mobile_responsive.css`, optimized dynamic touch listener lifecycle in `assets/js/pull_to_refresh.js` with zero main-thread contention, and enabled hardware layer rendering and `offscreenPreRaster` in WebView.
  - **Compiled & Synchronized v1.8 APK:** Compiled fresh release APK (`fastsite_storefront.apk`, 7.65 MB) with `versionCode = 9` and `versionName = "1.8"`, synchronized across root, `uploads/`, and `APK FILE USER/`.
  - **1-Click Hostinger Auto-Extractor (`extract_update.php`):** Created a browser-accessible web tool so uploading and extracting `fastsite_phase45.zip` or `fastsite_apk_update.zip` on Hostinger takes 1 click.
  - **Fresh Date-Stamped Archives:** Rebuilt `fastsite_phase45.zip` (32.96 MB) and `fastsite_apk_update.zip` (22.60 MB) with updated timestamp (`8/22/2026 8:51 AM`).
- [x] **[Hotfix] Resolve "No Active Transaction" Upload Error in Product Add / Edit** (`partner/product_add.php`, `partner/product_edit.php`, `admin/ajax_quick_product_add.php`) -> **100% COMPLETE**:
  - Moved defensive schema DDL queries (`ALTER TABLE` and `CREATE TABLE`) completely outside the active transaction blocks to prevent MySQL's automatic implicit commit from breaking PDO transactions.
  - Added robust directory creation check for `uploads/products/`, `uploads/partners/`, and `uploads/audio/`.
  - Protected `beginTransaction()`, `commit()`, and `rollBack()` with `$pdo->inTransaction()` checks.
  - Upgraded AJAX/XHR client-side upload handler with real-time byte progress, live status HUD, and response parsing.
  - Unified JSON response format and error handling across both AJAX and standard POST flows.
  - Packaged deployment bundle `fastsite_upload_fix.zip`.
- [x] **[UI/UX Polish] User Dashboard Mobile APK Navigation Bar & Profile Card Polish** (`includes/user_sidebar.php`, `user/dashboard.php`, `includes/nav_public.php`) -> **100% COMPLETE**:
  - Aligned the Store navigation button and back arrow with clean inline SVG styling.
  - Centered and scaled the Fast Site logo icon (44px) right in the exact horizontal center of the top navigation bar.
  - Removed duplicate wallet coin badge from top nav to highlight the dedicated "MY WALLET" profile card.
  - Renamed "MY REWARD" card to "MY WALLET" and fixed digit wrapping so full coin balance renders cleanly on one line.
  - Enlarged user profile photo to 80px and tightened name styling to a single, compact sentence.
  - Removed redundant "Complete Mission to Earn Real Cash" text & mission button from profile header.
  - Unified FAST SITE WORLD APK download card, branding, and icon across marketplace, sidebars, and user dashboard.
- [x] **[Mobile APK & Web App] Universal 5-Position Floating Bottom Bar, Menu Hub Profile/Shop Fix, & Settings Layout Fix** (`includes/nav_public.php`, `includes/user_sidebar.php`, `user/dashboard.php`, `includes/whatsapp_button.php`, `MainActivity.kt`) -> **100% COMPLETE**:
  - **Menu Hub Profile Photo & Shop Name:** In `includes/nav_public.php`, included `profile_pic` in the query and linked `partners` table by `user_id` and `phone` to display user profile photo and the user's actual shop name (`🏪 Shop: {shop_name}`).
  - **Dashboard Visuals Storefront Toggle:** Linked `#marketplace-shopper-view` card ID to toggle function and persisted state with `localStorage.getItem('fastsite_storefront_view')`.
  - **Settings Layout & Vertical Text Bug:** Re-engineered flex containers for `Change Password` and `Biometric Login` in `user/dashboard.php` with `flex: 1; min-width: 0;` and fixed button sizing to prevent vertical single-character text collapse.
  - **Removed Conflicting Floating Overlays:** Removed native Jetpack Compose floating pill overlay (`[ ← Back | 🏠 Home ]`) from `MainActivity.kt` and hid standalone floating WhatsApp widget on screens < 768px in `includes/whatsapp_button.php`.
  - **Universal 5-Position Floating Navigation Bar:** Built integrated fixed glassmorphic bottom bar in `user/dashboard.php`:
    1. Position 1: 💬 **WhatsApp** Direct Support Link
    2. Position 2: 🏪 **My Shop / Open Shop** Direct Link
    3. Position 3 (Middle): 👤 **Profile Hub** with User Profile Photo & Gold Glow
    4. Position 4: 🔔 **Alerts / Notification Bell** with Unread Indicator Badge
    5. Position 5: ⬅️ **Back Button** with History Traversal
  - **Rebuilt & Synchronized Release APK:** Recompiled `fastsite_storefront.apk` (8.00 MB) and synchronized across distribution paths.
- [x] **[Phase 83] Marketplace Header Revamp, Mobile Filter Modal Hub, 2x2 Grid, Auto-Refresh Bugfix, Android Biometrics & Universal Notifications** -> **100% COMPLETE**.
- [x] **[Phase 85] Focused Module-by-Module UX/UI & Navigation Overhaul (User Dashboard, Shop Panel, Marketplace, Google Stitch Standards)** -> **100% COMPLETE**:
  - [x] **Module 1: User Dashboard & Navigation Overhaul** (`user/dashboard.php`, `includes/user_sidebar.php`, `assets/css/user.css`, `assets/css/mobile_responsive.css`) -> **100% COMPLETE**:
    - [x] UX exploration and architecture audit completed (`explorer_survey_user/handoff.md`).
    - [x] 1. Plain Everyday English Terminology: Replaced MLM jargon with consumer-friendly labels ("Member ID", "Invite Code", "Available Balance ৳", "Cash Out / Withdraw Money", "Earn Bonus Points", "Daily Check-in", "My Service Orders", "Store Orders", "Rewards & Invites", "Affiliate Partner").
    - [x] 2. Dead Tab Routes Resolution: Completely eliminated non-existent `tab-social`, routed avatar & Edit Profile directly to `/user/profile.php`; created dedicated `tab-orders` consolidating both service applications and partner store purchases with clean consumer tracking steps (`Pending` -> `Processing` -> `Completed`).
    - [x] 3. Zero-Overlap Navigation: Overhauled 5-slot bottom floating bar with 44px+ touch targets (`Store (/index.php) | Orders (?tab=orders) | Wallet (/user/wallet.php) | Alerts (toggleNotificationDrawer) | Profile (/user/profile.php)`); centered top-nav logo; added 44px touch targets for Notification Bell & Messages; decoupled `#sidebarOverlay` with `closeAllDrawers()` to prevent drawer collisions.
    - [x] 4. Responsive Viewport Clearance: Added `calc(var(--top-nav-height, 60px) + 15px)` top clearance and `calc(var(--bottom-nav-height, 65px) + 35px)` bottom clearance in `assets/css/user.css` and `assets/css/mobile_responsive.css`, decoupling `.dashboard-container` horizontal margins so content is never obscured.
    - [x] 5. DOM ID Uniqueness & Verification: Replaced duplicate `id="reflink"` with distinct `reflink-quick`, `reflink-share`, and `reflink-agent`; upgraded `copyLink(id, btn)` helper with animated feedback; verified with `php -l` (0 errors).
    - [x] 6. Milestone M1 Remediation:
      - Deleted obsolete duplicate `function toggleSidebar()` in `user/dashboard.php` to eliminate JS shadowing and restore drawer mutual exclusion & desktop body shift.
      - Fixed active orders badge variable mismatch in `user/dashboard.php` from `$totalActiveOrders` to `$activeOrders`.
      - Cleaned `#reflink-agent` input URL in `user/dashboard.php` to output clean `$baseUrl` without duplicated path segments.
      - Guarded `window.history.pushState` in `switchUserTab()` in `user/dashboard.php` to only execute when target tab element exists in DOM.
      - Upgraded top navigation and drawer buttons in `includes/user_sidebar.php` (hamburger, notification bell, messages, close buttons) to full 44x44px minimum touch targets.
      - Verified with `php -l` on all affected files (0 errors).
  - [x] **Module 2: Shop / Partner Portal Simplification** (`partner/dashboard.php`, `partner/orders.php`, `partner/products.php`, `partner/product_add.php`, `partner/nav.php`) -> **100% COMPLETE**:
    - [x] 1. Plain Everyday English Terminology: Replaced Fiverr and technical jargon with everyday merchant phrasing ("➕ Add New Product", "Orders to Fulfill", "Total Sales Earned", "Customer Orders", "Instructions & Requirements for Buyer", "External Affiliate Link").
    - [x] 2. Modern Action Bar & Catalog Visibility: Added live storefront preview link (`👁️ View`) and vibrant emerald `FREE` badge in `partner/products.php`.
    - [x] 3. Mobile Navigation & Dock: Built 62px frosted glass bottom dock (`.partner-bottom-dock`) with 5 primary thumb actions (`Hub`, `Orders`, `+ Add`, `Catalog`, `Menu`).
    - [x] 4. Milestone M2 Remediation:
      - [x] Fixed stray `<` in `partner/orders.php` line 398 (`<<div class="content-wrapper">` -> `<div class="content-wrapper">`).
      - [x] Added `.side-drawer { bottom: 62px; }` to `@media (max-width: 900px)` in `partner/nav.php` so drawer terminates cleanly above the 62px dock and Logout button is never obscured.
      - [x] Verified with `php -l "partner/orders.php"` and `php -l "partner/nav.php"` (0 errors detected).
  - [x] **Module 3: Marketplace Header, Filter & Drawer Streamlining** (`home.php`, `includes/nav_public.php`) -> **100% COMPLETE (worker_m3)**:
    - [x] 1. 3-Zone Symmetrical Top Navbar (`includes/nav_public.php`): Built mathematical centering grid (`1fr auto 1fr`) with zero element collision risk across all viewports. Zone 1 left coin wallet pill (`.coin-badge-pill`), Zone 2 centered brand identity (`.nav-brand-centered` with logo icon & responsive text collapse for <=375px), Zone 3 right dynamic shop pill + hamburger drawer. Applied Google Stitch "Nocturne Aurum" dark luxury theme with frosted glass (`rgba(8, 8, 12, 0.75)`, blur 16px, `#fcb900` amber glow) and mobile APK safe-area top padding (`env(safe-area-inset-top)`).
    - [x] 2. Streamlined Hero & Prominent Search Command Hub (`home.php`): Reduced hero padding to compact 2.2rem on mobile / 3rem on desktop to bring the search hub into the primary fold. Engineered glassmorphism search bar with search lens icon, clear trigger, live search integration, and integrated `.btn-filter-trigger` button with active state indicator. Fixed JS hoisting order for `omniInput`.
    - [x] 3. Horizontal Quick-Types Ribbon (`home.php`): Implemented smooth horizontal touch chips (`📦 All`, `🛍️ Products`, `🤝 Services`, `🏪 Shops`, `⚡ Deals`, and `📁 All Categories ➔`) with hidden scrollbar and momentum scrolling.
    - [x] 4. Universal Slide-Up Category Drawer (`#categoryDrawer` + `#categoryDrawerScrim`): Built mobile bottom sheet (<768px: rounded top 24px, grab handle, max-height 85vh) and desktop centered elevated dialog (>=768px: max-width 580px, max-height 80vh, border-radius 20px). Features listing type radio cards, 2-column visual category grid with rich emoji iconography and active checkmarks, regional filter, sticky footer with Reset All and Apply Filters, ESC key listener, and pull-to-refresh exclusion tags (`.category-drawer`, `drawer-menu`, `modal-box`).
    - [x] 5. Technical Verification & Linting: Verified `php -l "home.php"` and `php -l "includes/nav_public.php"` with 0 errors, pristine UTF-8 encoding without mojibake.
  - [x] **Module 4: Google Stitch UI, Graphics, Cross-Module Verification & Packaging** (`PROJECT_STATE.md`, `DEPLOYMENT_GUIDE.txt`, `fastsite_phase85.zip`) -> **100% COMPLETE**:
    - [x] 1. Google Stitch Visual Polish & Nocturne Aurum Theme: Applied dark luxury aesthetics (`#080911` obsidian background, `#fcb900` amber gold accents, `#00e676` emerald accents, frosted backdrop blur 16px, balanced touch targets >= 44px) across User, Shop, and Marketplace spaces.
    - [x] 2. Full PHP Syntax Linting: Successfully linted all Phase 85 modified PHP files (`user/dashboard.php`, `includes/user_sidebar.php`, `partner/dashboard.php`, `partner/orders.php`, `partner/products.php`, `partner/product_add.php`, `partner/nav.php`, `home.php`, `includes/nav_public.php`) with 0 syntax errors.
    - [x] 3. Comprehensive Character Encoding Audit: Verified all modified PHP and CSS files (`assets/css/user.css`, `assets/css/mobile_responsive.css`) have 100% pure UTF-8 encoding with zero mojibake corruption.
    - [x] 4. Local Server Live Host Verification: Verified live server running on `http://localhost:8000` (`http://[::1]:8000`), confirming HTTP 200 OK on marketplace root, clean HTTP 302 auth redirects for protected panels, and zero server crashes.
    - [x] 5. Deployment Guide Creation: Produced comprehensive `DEPLOYMENT_GUIDE.txt` satisfying all mandatory Hostinger Zip Upload Directives (folder paths, workflow step, Phase 85 active status, file manifest, extraction instructions, Local Server Live Host reminder).
    - [x] 6. Packaged Archive Build: Assembled and verified `fastsite_phase85.zip` containing all Phase 85 modified files preserving exact `public_html/` relative paths.
- [x] **[Phase 86] Natively Mobile Fast Site Architecture (Google Stitch Tokens, 2x2 Grid, 5-Slot Dock, Live Tunnel)** -> **100% COMPLETE**:
  - [x] 1. Google Stitch *Nocturne Aurum* Native Mobile Stylesheet (`assets/css/native_mobile.css`): Enforced `#0A0D1A` obsidian void, `rgba(18, 22, 43, 0.85)` frosted glass elevation, `#F59E0B` amber/gold highlights, active tap compression (`transform: scale(0.96)`), safe-area insets (`env(safe-area-inset-top)`, `env(safe-area-inset-bottom)`), and smooth momentum scrolling.
  - [x] 2. Strict 2x2 Mobile Card Grid & 1:1 Image Ratio: Integrated `.product-grid-4` and `.pcard` into native 2x2 mobile grid with square 1:1 media containers, price pill overlays, and verified partner badges.
  - [x] 3. Edge-to-Edge Mobile Viewport & App Headers (`home.php`): Configured `viewport-fit=cover`, Apple mobile web app capable meta tags, `#0A0D1A` theme-color, and direct linkage to `native_mobile.css`.
  - [x] 4. Universal 5-Slot Mobile Bottom Dock (`includes/footer.php`): Standardized persistent 5-tab mobile dock (`[ 🏠 Home | 🔍 Discover | 🏪 Shop | 💬 Messages | 👤 Account ]`) with contextual active indicator states and safe-area padding for all public and storefront pages.
  - [x] 5. Resilient Database Fallback Engine (`config.php`): Seamless SQLite fallback (`fast_site_local.db`) with pre-seeded ecosystem partner shops for 100% offline & local server reliability.
  - [x] 6. Live Verification & Packaging: Verified HTTP 200 responses on local server (`http://localhost:8000`) and Cloudflare live tunnel (`https://lamb-applications-favors-disabilities.trycloudflare.com`), updated `DEPLOYMENT_GUIDE.txt`, and generated production archive `fastsite_phase86.zip`.
- [x] **[Phase 87] Frictionless 1-Tap Buyer Mode ⇄ Shop Mode Switcher & Navigation Dock Synchronization (Google Stitch Nocturne Aurum Standards)** -> **100% COMPLETE**:
  - [x] **Milestone 1: User Panel Mode Switcher, Dock Sync & Onboarding Modals (`worker_p87_m1`)** -> **100% COMPLETE**:
    - Built self-contained `getUserShopState($pdo, $userId, $userPhone)` helper function resolving accurate partner shop states ('approved', 'pending', 'none') for all user sub-pages even when `$partnerInfo` was undefined by caller.
    - Integrated high-visibility 1-Tap Mode Toggle Pill in Top Header Bar (`includes/user_sidebar.php`) with 44px+ touch targets and responsive text collapse for Approved (`[ 🏪 Switch to Shop Mode ]`), Pending (`[ ⏳ Shop Under Review ]`), and Shopless (`[ ➕ Open Free Shop ]`).
    - Synchronized side drawer shop navigation callouts with `$shopState`.
    - Implemented Google Stitch Nocturne Aurum `#shopReviewModal` bottom sheet (with 3-step progress stepper, 24-48h SLA, and WhatsApp priority escalation) and `#quickShopDrawer` (30s frictionless 1-tap shop setup form with direct POST to `user/create_shop.php`).
    - Overhauled mobile floating bottom navigation dock (`#user-floating-bottom-nav`) in `user/dashboard.php` to 5 synchronized slots (Store, Orders with active count badge, Mode Switcher center action, Wallet, Profile) with dynamic active tab highlighting.
    - Added comprehensive Google Stitch Nocturne Aurum styles in `assets/css/user.css` with active tap compression (`transform: scale(0.96)`), pulsating gold borders, frosted glass elevations, and guaranteed 44px x 44px touch targets.
    - Verified with `php -l` (0 errors) and validated pure UTF-8 formatting without BOM.
  - [x] **Milestone 2: Shop Panel Mode Switcher, Dock Sync & Back Navigation (`worker_p87_m2`)** -> **100% COMPLETE**:
    - Built prominent 1-Tap Mode Switcher Pill in Top Header Bar (`partner/nav.php`) linking directly to `/user/dashboard.php` (`[ 👤 Switch to Buyer Mode ]` on desktop >600px, `[ 👤 Buyer ]` on mobile <=600px, 44px+ touch target, Nocturne Aurum sky-blue `#38bdf8`, active tap compression `transform: scale(0.96)`).
    - Added Persistent Back Navigation Button (`nav-back-btn`) with clean chevron SVG linking to `dashboard.php` on all subpages (`$current_page !== 'dashboard.php'`) and upgraded `.hamburger-btn` & `.app-hub-dropdown button` to explicit 44px x 44px touch targets.
    - Synchronized Mobile Bottom Navigation Dock (`.partner-bottom-dock`) to 5 standardized thumb slots (`[ 📊 Hub | 📦 Orders + Badge | ➕ Add | 🛍️ Catalog | 👤 Buyer Mode ]`), removed redundant Menu button, integrated live pending orders counter query, and added hardware safe-area insets (`padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 6px)`, `height: calc(64px + env(safe-area-inset-bottom, 0px))`).
    - Added direct `[ 👤 Switch to Buyer Mode ]` action button in the Executive Hero Quick Action Bar (`partner/dashboard.php`) with Google Stitch sky-blue styling (`.btn-action-buyer`).
    - Eliminated all 7 dead-end 404 redirects to `/partner/login.php` across `partner/index.php`, `partner/logout.php`, `partner/product_add.php`, `partner/product_edit.php`, `partner/product_delete.php`, `partner/profile.php`, and `partner/api_docs.php`, rerouting unauthenticated and logged-out users cleanly to `/user/login.php`.
    - Validated all 9 touched files with `php -l` (0 errors), 100% pure UTF-8 without BOM, and verified live Local Server Live Host HTTP 302 redirects and rendering.
    - **Remediation (`worker_p87_m2_fix`):** Resolved PHP boolean stringification defect in `partner/nav.php`. Upgraded `isActive($page, $current_page)` to safely accept an array or single string and guarded with `!function_exists('isActive')`. Replaced chained boolean OR calls with array invocations across drawer Products (`['products.php', 'product_add.php', 'product_edit.php']`), drawer Earnings (`['earnings.php', 'withdraw.php']`), and dock Catalog (`['products.php', 'product_edit.php']`). Validated with 70/70 checks passing on Challenger 2 test harness (`tests/test_p87_m2_dom_stress.php`) and 40/40 checks passing on Auditor suite (`.agents/auditor_p87_m2/forensic_suite.php`).
  - [x] **Milestone 3: Google Stitch Polish, Verification & Deployment Packaging (`worker_p87_m3`)** -> **100% COMPLETE**:
    - Performed comprehensive Google Stitch *Nocturne Aurum* tokens and ergonomics audit across User and Shop panels: deep obsidian `#0A0D1A`, frosted glass elevations (`rgba(18, 22, 43, 0.85)` / `rgba(10, 13, 26, 0.94)`, blur 16-25px), amber/gold `#F59E0B` / `#fcb900` highlights, sky blue `#38bdf8` Buyer Mode accents, fluid active tap scale compression (`transform: scale(0.96)`), guaranteed >=44px x 44px touch targets across all buttons and dock items, and hardware safe-area clearance (`env(safe-area-inset-top)`, `env(safe-area-inset-bottom)`).
    - Polished safe-area inset top and bottom paddings across `includes/user_sidebar.php`, `assets/css/user.css`, and `partner/nav.php`.
    - Executed `php -l` syntax audit on all 11 modified PHP files with 0 syntax errors detected.
    - Audited 100% pure UTF-8 character encoding across all 12 Phase 87 files with 0 BOM markers (`3c3f70` leading bytes on PHP) and 0 mojibake characters.
    - Completed Local Server Live Host testing on `http://localhost:8000`: verified HTTP 200 on `/index.php` and `/user/login.php`, clean HTTP 302 authentication redirects on `/partner/dashboard.php`, `/partner/index.php` (zero 404s), and `/user/dashboard.php`.
    - Verified Cloudflare mobile live tunnel (`https://lamb-applications-favors-disabilities.trycloudflare.com/index.php`) with HTTP 200 response.
    - Generated comprehensive `DEPLOYMENT_GUIDE.txt` adhering to `.agents/AGENTS.md` directives with exact Hostinger paths (`public_html/`), workflow step, active phase, file manifest with SHA256 hashes and byte sizes, and Local Server Live Host reminders.
    - Assembled production archive `fastsite_phase87.zip` with normalized Unix forward slash (`/`) paths containing all 12 modified files plus `DEPLOYMENT_GUIDE.txt`, verified with CRC integrity check passing.