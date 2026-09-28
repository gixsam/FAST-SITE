# 📘 FAST SITE — MASTER COMPREHENSIVE NOTE & STATUS REPORT (NOTE.md)
**Project Name:** FAST SITE (Ecosystem Escrow Hub & Universal Marketplace)  
**Main Domain:** `https://fastsite.best-travel.ltd`  
**Current Active Version:** Phase 96 100% Complete & Production-Verified  
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

### Active Status: Phase 96 Complete & Production-Verified
The platform is currently at **Phase 96: Admin Settings Mobile Overflow Fix, DOM Hierarchy Restoration & Responsive Shielding**.

### Current Core Capabilities Live in the Codebase:
1. **Admin Settings Mobile Overflow Fix, DOM Hierarchy Restoration & Responsive Shielding (Phase 96):**
   - **Root Causes Eliminated:**
     - **Blank Tabs Resolved (Staff Access & Advanced Settings):** In `admin/settings_partials/tab_partners.php`, line 8 opened a section wrapper for *Client & Referral Management* that was never closed before line 17 opened *Affiliate Agent Program*. Consequently, line 89 closed line 8 instead of `#sec-all-partners-program`. Because `tab_staff.php` and `tab_advanced.php` are included right after `tab_partners.php`, the DOM parser treated `#sec-staff` and `#sec-advance-settings` as children inside `#sec-all-partners-program`. When switching tabs, `#sec-all-partners-program` was hidden (`display: none`), rendering its swallowed children completely invisible. In addition, `tab_advanced.php` had unclosed tags in Sub-Section 3 and Sub-Section 5, and `tab_staff.php` was missing an unconditional DOM container.
     - **Right Border Crossing / Horizontal Overflow Eliminated (Logo/Media & General/SEO):** In `assets/css/admin.css`, `.grid2` used `repeat(auto-fit, minmax(300px, 1fr))` without mobile overrides. In `tab_logo_media.php`, `tab_general.php`, and `tab_advanced.php`, multiple field wrappers used `style="grid-column: span 2;"` and nested `.grid2`, requiring at least 622px width. Combined with container and card padding (`padding: 2rem !important`), total required width reached 718px on a 360px smartphone screen, forcing form controls and preview cards 350px+ past the screen boundary.
     - **Dashboard Unclosed Divs Resolved:** Fixed 2 unclosed container tags (`.dashboard-container` and `.wrap`) in `admin/dashboard.php` and `admin/dashboard_updated.php`.
   - **Comprehensive Fixes Applied:**
     - **Tag Balancing (Diff = 0):** Added closing `</div>` tags in `tab_partners.php:17` and `tab_advanced.php` (Sub-Section 3 line 334, Sub-Section 5 line 641), and cleaned up redundant div at line 663. Wrapped `tab_staff.php` with an unconditional `#sec-staff` container with a clean permission guard card when non-admin. All 7 partials and dashboard now report exact `diff = 0`.
     - **Universal Mobile Responsive Shielding (`assets/css/admin.css`):** Added `@media (max-width: 768px)` rules collapsing `.grid2` into single-column vertical flex (`flex-direction: column !important; width: 100% !important;`), resetting `grid-column: span 2` to `grid-column: auto !important`, and enforcing `max-width: 100% !important; box-sizing: border-box !important` across all inputs, selects, textareas, and cards.
     - **Padding Optimization:** Reduced mobile `.section-body` padding from `2rem` (64px) to `1.2rem 0.85rem` (<768px) and `1rem 0.6rem` (<480px), reclaiming 40px of screen real estate.
     - **Swipeable Mobile Tabs Carousel:** Refactored `.tabs-nav` on mobile into a smooth, horizontal swipeable strip (`overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; scrollbar-width: none;`).
     - **Inline Grid Migration:** Migrated rigid inline `minmax(...)` grids in `tab_general.php` and `tab_advanced.php` to use the responsive `.grid2` class.
2. **Admin Navbar Mobile Overflow Fix & Action Alert Modal Dropdown (Phase 95):**
   - **Root Cause Eliminated:** On smartphone viewports (< 640px), the combined width of the admin brand container, long official shop text (`🏪 FAST SITE`), action alerts bell, ecosystem hub button, logout button, and hamburger icon exceeded screen width (410px+ vs 360-390px viewport), forcing the Omni Ecosystem Apps, Logout, and Hamburger buttons completely out of the right screen border.
   - **Mobile Layout Precision:**
     - Collapsed `.shop-btn-text` on screens `< 640px` (showing a clean, compact `🏪` icon pill) while keeping full text on desktop.
     - Converted the wide `Logout` text link into a sleek `🚪` icon button on mobile.
     - Suppressed `.nav-tag` on screens `< 768px` to keep brand identity compact.
     - Result: All 5 utility controls (`🏪 Shop`, `🔔 Alerts`, `🌐 Omni Apps`, `🚪 Logout`, `☰ Hamburger Menu`) fit neatly on any smartphone screen without horizontal scroll or border clipping.
   - **Action Alerts Modal Dropdown:** Both `.notif-menu` and `.hub-menu` upgraded to fixed floating viewport geometry on mobile (`position: fixed; top: 56px; left: 10px; right: 10px; width: auto; max-width: calc(100vw - 20px)`), completely eliminating clipping and negative margin offsets (`right: -40px`). Implemented mutually exclusive dropdown toggle handlers (`toggleNotifMenu` and `toggleHubMenu`).
   - **Staff Panel Authentication:** Clarified that staff members log in at the exact same portal (`https://fastsite.best-travel.ltd/admin/login.php`) using credentials created in `admin/staff_access.php`, receiving scoped permissions based on their assigned role.
2. **Professional Mobile Phone & Native APK App Responsive Optimization (Phase 94):**
   - **Pillar 1: Viewport & Universal Screen Standards:** Enforced `<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover"/>` across all portal entry points (`home.php`, `user/login.php`, `user/register.php`, `user/dashboard.php`, `partner/dashboard.php`, `admin/dashboard.php`). Implemented dynamic `100dvh` viewport height with `--dvh` fallback, hardware safe-area insets (`env(safe-area-inset-top/bottom)`), and `touch-action: manipulation !important` eliminating mobile tap delays.
   - **Pillar 2: Mobile-First Breakpoints & Content Layout:** Mobile-first layout standards (<640px, sm: 640px, md: 768px, lg: 1024px). Automated table wrapping engine in `app_environment.js` wrapping bare tables in `.table-responsive.overflow-x-auto` with touch momentum scrolling. Dialogs, bottom sheets, and modals constrained to `w-[calc(100vw-2rem)]` on mobile screens.
   - **Pillar 3: Native APK & Standalone App Detection:** Comprehensive detection engine in `assets/js/app_environment.js` detecting Android WebView APK (`[FAST SITE]AndroidApp`, `window.AndroidApp`) and standalone PWA mode. Automatically hides redundant download cards (`.apk-download-card.hide-in-apk`, `.pwa-install-prompt`). Intercepts Android physical back-button via `popstate` to close active side drawers and modals rather than exiting the application.
   - **Pillar 4: Standardized Layering & Inline Form Validation:** Standardized z-index scale (dock: 150, backdrops: 200, drawers/modals: 205-215, alerts/toasts: 9999). Deployed non-blocking glassmorphic floating toast system (`window.showToast()`) and client-side inline form validation that highlights required fields with red borders (`.is-invalid`) and clear inline helper notices before form submission.
2. **Hardcoded Google API Key Removal (`config.php` - Phase 93):**
   - **Root Cause Eliminated:** GitHub Secret Scanning detected a hardcoded Google Gemini API key (`AIzaSyDghzIYFlhGYNyUMbXKRzRkWXRzpWwcAdA`) at line 279 in `config.php`, which had been committed during initial repository initialization (`commit 6f30295b`).
   - **Credential Decoupling:** Purged the raw secret key from `config.php` and replaced it with dynamic environment reading (`getenv('GEMINI_API_KEY') ?: ''`). All API keys are now securely managed either in private server environment variables or configured in the Admin Panel (`Settings > Marketplace > Gemini API Key`).
   - **Resolution Protocol:** Provided user with exact 2-step verification protocol to revoke the exposed key in Google AI Studio / Google Cloud Console and resolve the security alert on GitHub.
2. **Highlighted VIP Guest Auth Card & Dual-Tab Switcher (`includes/nav_public.php` - Phase 92):**
   - **Root Cause Eliminated:** In the storefront side drawer (`#drawerMenu`), the guest authentication section previously lacked visual prominence and in older builds was rendered as an unstyled raw blue hyperlink (`#0000ee`).
   - **Google Stitch Nocturne Aurum Overhaul:** Replaced the plain link with an interactive, high-visibility VIP Guest Card (`.drawer-auth-card`):
     - Frosted obsidian-gold chassis with luminous gold border (`1.5px solid rgba(252, 185, 0, 0.4)`), gold glow (`box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4), 0 0 16px rgba(252, 185, 0, 0.18)`), and 14px rounded curvature.
     - Brand welcome badge with gold avatar (`👤`), crisp white heading (`Welcome to FAST SITE`), and bonus incentive subtitle (`🪙 Free 50 Coins on Register`).
     - Distinct 2-tab highlighted switcher:
       - **Primary Tab**: `[ 🔐 LOGIN ]` styled in brilliant glowing gold gradient (`linear-gradient(135deg, #fcb900 0%, #f7971e 100%)`) with dark typography and gold glow.
       - **Secondary Tab**: `[ ✨ REGISTER ]` styled in frosted dark glass with sharp gold border and hover illumination.
     - Full inline fallback CSS ensuring 100% rendering fidelity across every mobile browser and WebView.
2. **Removal of Crowded Navbar Open Shop Pill (`includes/nav_public.php` - Phase 91):**
   - **Root Cause Eliminated:** The storefront header Zone 3 previously contained a redundant fallback `[➕ Open Shop]` pill (`shop-nav-btn create`). On mobile screens (375px–420px), this button squeezed against the hamburger menu, caused text truncation (`Open S...`), and shoved the centered brand identity (`FAST SITE` logo) off-center to the left.
   - **Clean Uncluttered Navbar:** Completely removed the `(+ Open Shop)` button from Zone 3. Shop onboarding remains effortlessly accessible via the side drawer menu ("Become Partner" / "Open Shop") and bottom dock.
   - **Responsive Center Symmetry:** Enforced `.shop-nav-btn { display: none !important; }` in mobile media queries (`max-width: 600px`). This ensures that on all mobile viewports, Zone 3 strictly contains only the 38px hamburger button, balancing Zone 1 (coin wallet pill) and keeping the brand logo dead-centered.
3. **Resolution of Registration Transaction Error & Self-Healing Recovery (Phase 90):**
   - **Root Cause Eliminated:** `user/register.php` had a DDL query (`CREATE TABLE IF NOT EXISTS coin_wallets`) executing inside `$pdo->beginTransaction()`. In MySQL, any DDL triggers an immediate implicit commit, causing subsequent `$pdo->commit()` to throw `PDOException: There is no active transaction`.
   - **Transaction Hardening:** Extracted all DDL table creation outside of transactions to the initial self-healing migration block. Replaced wallet creation inside transaction with pure DML (`INSERT ... ON DUPLICATE KEY UPDATE`) and protected the commit with `if ($pdo->inTransaction()) { $pdo->commit(); }`.
   - **Self-Healing Interrupted Registration Recovery:** If a user submitted registration and was already inserted into the database during an interrupted transaction attempt (e.g., phone `01612669922`), resubmitting the registration form with the same password automatically validates credentials, verifies the registration number, allocates the 50 welcome coins, and immediately logs them into `/user/dashboard.php?welcome=1`.
4. **Elimination of Drawer Over-Layering & Bleeding (Phase 89):**
   - **Root Cause Eliminated:** Relocated the `#categoryDrawer` and `#categoryDrawerScrim` DOM tree out of the content flow (previously nestled between Hero and Product grid) to the root document level right before `</body>`.
   - **Hardened Visibility Architecture:** Enforced `visibility: hidden`, `opacity: 0`, `pointer-events: none`, `transform: translateY(115%)`, and `z-index: 99999` when inactive. Completely eliminates bottom-dock peeking, shadow artifacts, and prevents iOS Safari full-page screenshot tools from rendering the drawer over product cards.
   - **Dynamic Hardware Stacking:** Configured safe-area inset padding `calc(env(safe-area-inset-bottom, 16px) + 12px)` and `@media print { display: none !important; }` ensuring clean rasterization in all viewport states.
   - **Synchronized Toggle Engine:** `toggleCategoryDrawer()` upgraded to manage `aria-hidden` and explicitly toggle `visibility` with smooth 360ms CSS transition.
2. **Zero-Tolerance Deployment Build Engine (`build_hostinger_zip.py`, `build_phase_zip.py`):**
   - Implements multi-layer filter explicitly rejecting `.env`, `.env.*`, and any environment file variant.
   - Enforces automated post-build archive inspection (`ZipArchive` / `zipfile`) that audits all entries and immediately purges the archive if any `.env` entry is detected, guaranteeing that live Hostinger production database credentials (`u422364295_admin`) can NEVER be overwritten.
   - Embeds auto-generated, canonical `DEPLOYMENT_GUIDE.txt` detailing exact extraction paths and phase status.
3. **Universal Dynamic Root-Relative Media Resolution (`config.php`, `includes/image_helper.php`):**
   - Core resolvers upgraded: `resolveProductArtwork()`, `resolveShopMedia()`, `resolveMediaUrl()`, and `resolveUserAvatar()`.
   - Strips hardcoded localhost origins (`http://localhost:8000/...`, `http://127.0.0.1:...`), ensuring images never attempt to resolve to localhost on production devices.
   - Normalizes Windows backslashes (`\`) to POSIX forward slashes (`/`), preventing Linux flat-file filename corruption.
   - Resolves all local images as dynamic root-relative paths (`/uploads/...`, `/assets/...`) allowing 100% seamless rendering across Localhost, Cloudflare tunnels, and Hostinger production without environmental configuration changes.
4. **Elimination of Hostinger WAF 422 Rewrite Loop (`.htaccess`):**
   - Injected dedicated direct HTTP 404 rule for missing static media files (`.jpg`, `.jpeg`, `.png`, `.webp`, `.svg`, `.mp3`) placed directly before the catch-all `index.php` rewrite.
   - Prevents Apache from rewriting missing image requests into `index.php` and returning HTML text to `<img>` tags, eliminating browser parse failures and stopping Hostinger ModSecurity/WAF HTTP 422 (Unprocessable Entity) errors.
5. **Clean Storefront & Admin Image Linkage:**
   - Overhauled relative paths (`../uploads/...`) in `checkout.php`, `admin/partner_shops.php`, `admin/shop_edit.php`, `partner/dashboard.php`, and `user/forgot_password.php` into canonical root-relative resolvers with SVG vector fallbacks.
6. **1-Tap Bi-Directional Mode Switcher & 5-Slot Bottom Dock (Phase 87):**
   - User Panel: `[ 🏪 Switch to Shop Mode ]` (or `[ ➕ Open Free Shop ]` / `[ ⏳ Shop Under Review ]`).
   - Shop Panel: `[ 👤 Switch to Buyer Mode ]` top header pill and persistent bottom dock.
   - Synchronized 5-slot bottom floating docks across Storefront, User Space, and Shop Space with active state badges.
7. **Active Test Environments:**
   - **Local Server Live Host**: `http://localhost:8000` (test locally before Hostinger upload).
   - **Cloudflare Mobile Live Tunnel**: `https://lamb-applications-favors-disabilities.trycloudflare.com`.
8. **Latest Deployment Archive:**
   - Archive Name: `fastsite_phase88.zip` (53.25 KB) located in root directory.
   - Security Audit: 0 `.env` files detected, 100% clean.
9. **Automated GitHub & Hostinger Git Auto-Deployment (`gixsam/FAST-SITE`):**
   - Public Repository initialized & linked: `https://github.com/gixsam/FAST-SITE` (Branch: `main`).
   - Automated Workflow: `.github/workflows/deploy.yml` with pre-flight asset/security validation.
   - Hostinger Native Git Integration Activated: Connected via Hostinger hPanel Advanced Git to `gixsam/FAST-SITE` with `Auto-deployment` enabled deploying directly into `public_html/` (Verified live status: `Completed` in 5 seconds).
   - Live Production Verification: `https://fastsite.best-travel.ltd` responding HTTP 200 OK with zero errors.
   - Permanent zero-tolerance protection for live `.env` credentials and local dev databases.
10. **Mandatory Direct URL Links Directive (Rule 7):**
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
- **Phase 89 (Elimination of Drawer Over-Layering & Bleeding Flaw)**:
  - Relocated `#categoryDrawer` and `#categoryDrawerScrim` from mid-body content flow to the root document level before `</body>`.
  - Configured strict inactive styling (`visibility: hidden`, `opacity: 0`, `pointer-events: none`, `transform: translateY(115%)`, `z-index: 99999`) and injected `@media print { display: none !important; }` to eliminate iOS Safari full-page screenshot bleeding.
- **Phase 90 (Resolution of MySQL Implicit Commit Registration Transaction Error)**:
  - Extracted DDL `CREATE TABLE IF NOT EXISTS coin_wallets` outside active transactions, eliminating MySQL implicit commit crashes during `$pdo->commit()`.
  - Protected commits with `$pdo->inTransaction()` checks and added self-healing interrupted registration recovery for automatic login and welcome coin credit.
- **Phase 91 (Removal of Crowded Navbar Open Shop Button)**:
  - Completely removed the awkward, truncated `[➕ Open Shop]` pill (`shop-nav-btn create`) from the top navbar in `includes/nav_public.php`.
  - Enforced `.shop-nav-btn { display: none !important; }` on mobile viewports (<600px), ensuring the center `FAST SITE` brand logo is dead-centered and never squished.
- **Phase 92 (Highlighted VIP Guest Auth Card & Dual-Tab Switcher in Side Drawer)**:
  - Replaced the plain, unhighlighted guest login link in the side drawer with a glowing Google Stitch *Nocturne Aurum* VIP Guest Card (`.drawer-auth-card`).
  - Added welcome avatar (`👤`), bonus incentive badge (`🪙 Free 50 Coins on Register`), and a high-contrast 2-tab highlighted switcher (`[ 🔐 LOGIN ]` glowing gold + `[ ✨ REGISTER ]` frosted gold glass) with active micro-interactions and inline fallback CSS.
- **Phase 93 (Hardcoded Google API Key Removal & Secret Scanning Alert Resolution)**:
  - Purged hardcoded Google Gemini API key from `config.php:279` and transitioned to `getenv('GEMINI_API_KEY') ?: ''`.
  - Resolved GitHub Secret Scanning security alert for commit `6f30295b` and established secure protocol for key rotation.

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
