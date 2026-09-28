## 2026-09-09T15:09:36Z
You are worker_p87_m2_exec, working on Phase 87 Milestone 2: Shop Panel Mode Switcher, Dock Sync & Back Navigation.

## Working Directory
Your working directory is: `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/worker_p87_m2_exec/`.
You own this directory exclusively for your metadata and progress files.

## Mandatory Prerequisites
Read these authoritative files before starting implementation:
- Authoritative User Request: `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/ORIGINAL_REQUEST.md`
- Master Project Specification: `d:/TECH/WEBSITE/FAST SITE/fast site/PROJECT.md`
- Project State: `d:/TECH/WEBSITE/FAST SITE/fast site/PROJECT_STATE.md`
- Shop Survey Findings: `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/explorer_p87_shop/handoff.md`

## MANDATORY INTEGRITY WARNING
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_preview_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

## Milestone 2 Tasks & Requirements

### 1. `partner/nav.php` Enhancements:
- **Top Header 1-Tap Mode Switcher Pill**:
  - In `<nav class="top-nav">` right side (`.nav-right`), add a prominent 1-tap pill:
    `[ 👤 Switch to Buyer Mode ]` linking directly to `/user/dashboard.php`.
  - Ensure minimum 44px × 44px touch target.
  - Implement responsive text collapse:
    - Desktop (`> 600px`): `[ 👤 Switch to Buyer Mode ]` (`.mode-pill-text-desktop`)
    - Mobile (`<= 600px`): `[ 👤 Buyer ]` (`.mode-pill-text-mobile`)
  - Google Stitch *Nocturne Aurum* tokens:
    - Sky blue / cyan accent for Buyer Mode: `#38bdf8`, `rgba(33, 150, 243, 0.12)` background, `border: 1px solid rgba(56, 189, 248, 0.35)`.
    - Active tap compression: `transform: scale(0.96)`.
    - Pill shape: `border-radius: 50px`, `box-shadow: 0 2px 10px rgba(33, 150, 243, 0.15)`.
- **Top Header Persistent Back Navigation**:
  - In `<nav class="top-nav">` left side (`.nav-left`), if `$current_page !== 'dashboard.php'`, render a 44px × 44px back button (`nav-back-btn`) linking to `dashboard.php` with a clean back chevron SVG.
  - Ensure `.hamburger-btn` and `.app-hub-dropdown button` have explicit 44px touch targets.
- **Mobile Bottom Navigation Dock 5-Slot Synchronization**:
  - In `<nav class="partner-bottom-dock">`:
    - Slot 1: 📊 `Hub` (`dashboard.php`)
    - Slot 2: 📦 `Orders` (`orders.php`) + live pending orders counter badge (`.dock-badge-counter`). Execute a lightweight query to count pending/active orders if `$partner_pending_orders_count` is not already defined.
    - Slot 3: ➕ `Add` (`product_add.php` center FAB `dock-item-primary`)
    - Slot 4: 🛍️ `Catalog` (`products.php`)
    - Slot 5: 👤 `Buyer Mode` (`/user/dashboard.php` with `.dock-item-buyer`)
  - Remove obsolete Slot 5 "Menu" button (which duplicated top hamburger).
  - Hardware safe-area insets:
    - `padding: 6px 8px calc(env(safe-area-inset-bottom, 0px) + 6px)`
    - `height: calc(64px + env(safe-area-inset-bottom, 0px))`
    - Ensure `body` bottom padding accounts for safe area.
  - Apply Google Stitch *Nocturne Aurum* frosted glass styling (`rgba(10, 13, 26, 0.94)`, `backdrop-filter: blur(24px)`, `border-top: 1px solid rgba(245, 158, 11, 0.2)`).

### 2. `partner/dashboard.php` Enhancements:
- In the executive hero quick action bar (`.hero-actions` or similar around line 707), add a direct `👤 Switch to Buyer Mode` action button linking to `/user/dashboard.php` with Google Stitch styling.

### 3. Fix 7 Dead-End 404 Redirects to `/partner/login.php`:
Replace `header('Location: /partner/login.php');` with `header('Location: /user/login.php');` in:
1. `partner/index.php`
2. `partner/logout.php`
3. `partner/product_add.php`
4. `partner/product_edit.php`
5. `partner/product_delete.php`
6. `partner/profile.php`
7. `partner/api_docs.php`

### 4. Verification & Testing:
- Run PHP syntax validation (`php -l`) on every modified file.
- Verify pure UTF-8 encoding (no BOM, no mojibake).
- Verify with curl on Local Server Live Host (`http://localhost:8000`) that `/partner/index.php` redirects to `/user/login.php` (HTTP 302), not 404.
- Verify that `/partner/dashboard.php` renders cleanly.
- Document all changes and test outputs in `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/worker_p87_m2_exec/handoff.md`.
- Send completion message to orchestrator.
