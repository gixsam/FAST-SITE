# Project: Fast Site Phase 87 — Frictionless 1-Tap Buyer Mode ⇄ Shop Mode Switcher & Navigation Dock Synchronization

## Architecture
- Module/package boundaries, data flow, shared interfaces
- Unified Shop State Engine (`getUserShopState($pdo, $userId, $phone)`)
- User Panel: `user/dashboard.php`, `includes/user_sidebar.php`, `assets/css/user.css`, `assets/css/native_mobile.css`
- Shop Panel: `partner/nav.php`, `partner/dashboard.php`, `partner/orders.php`, `partner/products.php`
- Onboarding & Modals: `includes/user_sidebar.php`, `user/create_shop.php`, `#shopReviewModal`, `#quickShopDrawer`
- Design Tokens: Google Stitch *Nocturne Aurum* (`#0A0D1A`, `rgba(18, 22, 43, 0.85)`, `backdrop-filter: blur(16px)`, `#F59E0B`, `transform: scale(0.96)`)

## Feature Inventory
Every feature from the Survey phase appears here with its assigned milestone.
| # | Feature | Description | Milestone | Source |
|---|---------|-------------|-----------|--------|
| 1 | Unified Shop State Resolution | Single cached `getUserShopState()` helper resolving 'approved', 'pending', 'none' across all user pages | M1 | explorer_p87_onboarding_stitch, explorer_p87_user |
| 2 | User Top Header Mode Switcher Pill | 1-tap pill in `includes/user_sidebar.php` left slot with Approved (`[ 🏪 Switch to Shop Mode ]`), Pending (`[ ⏳ Shop Under Review ]`), or Shopless (`[ ➕ Open Free Shop ]`) | M1 | explorer_p87_user |
| 3 | User Floating Bottom Dock Sync | 5-slot synchronized dock in `user/dashboard.php` with Mode Switcher slot (center/FAB), 44px+ touch targets, active state styling | M1 | explorer_p87_user |
| 4 | Frictionless 1-Tap Shop Onboarding Drawer | Google Stitch bottom sheet `#quickShopDrawer` for shopless users with 30s setup and instant transition to review state | M1 | explorer_p87_onboarding_stitch |
| 5 | Interactive Shop Under Review Modal | Informative bottom sheet `#shopReviewModal` with tracking ID, 3-step progress stepper, 24-48h SLA, and WhatsApp escalation | M1 | explorer_p87_onboarding_stitch, explorer_p87_user |
| 6 | Shop Top Header Mode Switcher Pill | 1-tap pill `[ 👤 Switch to Buyer Mode ]` in `partner/nav.php` top bar with 44px touch target and responsive text collapse | M2 | explorer_p87_shop |
| 7 | Shop Bottom Dock 5-Slot Sync | Synchronized 5-slot dock in `partner/nav.php` (Hub, Orders + Badge, Add FAB, Catalog, Buyer Mode) with safe-area padding | M2 | explorer_p87_shop |
| 8 | Persistent Back Navigation & Auth Fix | 44px back chevron on partner subpages and fix 7 broken dead-end redirects to `/user/login.php` | M2 | explorer_p87_shop |
| 9 | Google Stitch Nocturne Aurum Tokens & Visual Polish | Universal dark luxury tokens (`#0A0D1A`, `rgba(18, 22, 43, 0.85)`, `#F59E0B`, `transform: scale(0.96)`) across panels | M3 | explorer_p87_onboarding_stitch, explorer_p87_shop |
| 10 | Cross-Module Testing, Live Verification & Hostinger Zip | Full PHP linting, live local host verification, tunnel test, `DEPLOYMENT_GUIDE.txt`, and `fastsite_phase87.zip` | M3 | orchestrator_3 |

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| 1 | M1: User Panel Mode Switcher, Dock Sync & Onboarding Modals | `includes/user_sidebar.php`, `user/dashboard.php`, `assets/css/user.css` | none | DONE |
| 2 | M2: Shop Panel Mode Switcher, Dock Sync & Back Navigation | `partner/nav.php`, `partner/dashboard.php`, partner login redirects | none | IN_PROGRESS |
| 3 | M3: Google Stitch Polish, Verification & Deployment Packaging | `assets/css/native_mobile.css`, linting, local server test, `fastsite_phase87.zip` | M1, M2 | PLANNED |

## Interface Contracts
### `getUserShopState(PDO $pdo, int $userId, string $userPhone): array`
- Returns:
  ```php
  [
    'state'     => 'approved'|'pending'|'none',
    'shop'      => ?array, // raw row from partners table
    'request'   => ?array, // raw row from partner_requests table
    'shop_name' => string,
    'shop_id'   => int
  ]
  ```
- Guaranteed non-null, self-healing, safe against missing columns.

### Mode Switcher Pill Contract:
- In User Panel:
  - When `'approved'`: `href="/partner/dashboard.php"`, label "Switch to Shop Mode", icon 🏪, gold/amber theme.
  - When `'pending'`: `onclick="openShopReviewModal()"`, label "Shop Under Review", icon ⏳, pulsating border.
  - When `'none'`: `onclick="openQuickShopDrawer()"` (or `href="/user/create_shop.php"`), label "Open Free Shop", icon ➕, emerald/green theme.
- In Shop Panel:
  - `href="/user/dashboard.php"`, label "Switch to Buyer Mode", icon 👤, sky-blue/cyan theme (`#38bdf8`).
- Touch Targets: Min 44px × 44px bounding box on all viewports.
- Tap feedback: `transform: scale(0.96)` active transition.

## Code Layout
- `includes/user_sidebar.php` (User Top Bar, Side Drawer, Onboarding Drawers, `getUserShopState` helper)
- `user/dashboard.php` (User Dashboard & Floating Bottom Navigation Dock)
- `assets/css/user.css` (User styling & mode switcher pills)
- `partner/nav.php` (Shop Top Bar, Side Drawer, Partner Bottom Dock, Back Navigation, CSS tokens)
- `partner/dashboard.php` (Shop Dashboard Hero & Quick Action Bar)
- `assets/css/native_mobile.css` (Universal Google Stitch Nocturne Aurum tokens)
- `DEPLOYMENT_GUIDE.txt` & `fastsite_phase87.zip` (Hostinger Deployment Bundle)
