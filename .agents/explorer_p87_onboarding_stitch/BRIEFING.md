# BRIEFING — 2026-09-09T13:40:00Z

## Mission
Investigate Shopless User Onboarding Flow & Google Stitch Design Standards for Phase 87 1-Tap Mode Switcher & Navigation Sync.

## 🔒 My Identity
- Archetype: explorer
- Roles: investigation, synthesis
- Working directory: d:/TECH/WEBSITE/FAST SITE/fast site/.agents/explorer_p87_onboarding_stitch/
- Original parent: e6094591-5da3-4b70-ac01-55cdfa61ebbf
- Milestone: Phase 87 - Shopless Onboarding & Google Stitch Standards

## 🔒 Key Constraints
- Read-only investigation — do NOT implement
- Base findings strictly on code inspection and schema verification
- Follow 5-Component Handoff Protocol in handoff.md

## Current Parent
- Conversation ID: e6094591-5da3-4b70-ac01-55cdfa61ebbf
- Updated: 2026-09-09T13:40:00Z

## Investigation State
- **Explored paths**:
  - `user/create_shop.php` (full inspection of registration form, validation, file uploads, redirect logic)
  - `partner/dashboard.php` & `partner/nav.php` (partner detection, in-memory dummy partner, top nav, bottom dock)
  - `user/dashboard.php` & `includes/user_sidebar.php` (shop detection, `$isPartner`, `$partnerInfo`, bottom nav, drawer)
  - `includes/nav_public.php` & `includes/footer.php` (existing shop status pill, 3 states, public dock)
  - `admin/partner_shops.php` & `admin/partner_requests.php` (approval workflow, status transitions, notifications)
  - Database schema: `partners`, `partner_requests`, `users` (verified across `config.php` and `fast_site_local.db`)
  - `assets/css/native_mobile.css` & `assets/css/user.css` (Google Stitch Nocturne Aurum tokens, touch scaling, bottom sheet)
- **Key findings**:
  1. Three user shop states exist: `NO_SHOP`, `SHOP_PENDING`, `SHOP_APPROVED`.
  2. Currently, detection is fractured: `user_sidebar.php` only checks `$isPartner` if caller defines it; `user/dashboard.php` treats `pending` as an active partner; `partner/dashboard.php` shows full controls with no pending banner; `partner/nav.php` hides the pending badge on mobile.
  3. `native_mobile.css` already defines the complete Nocturne Aurum design tokens (`#0A0D1A`, `rgba(18, 22, 43, 0.85)`, `#F59E0B`, `transform: scale(0.96)`, `.bottom-sheet`) but is not yet loaded in user or partner panels.
  4. Onboarding can be streamlined into a 30-second 1-tap Google Stitch bottom sheet asking only for Business Name and Category (pre-filling owner name, phone, email, and bKash from profile).
  5. Pending state should render `[ ⏳ Shop Under Review ]` with an amber dashed border, pulse animation, and tap trigger opening an informative Live Status Review bottom sheet with progress stepper and direct WhatsApp escalation.
- **Unexplored areas**: None. Core investigation complete.

## Key Decisions Made
- Architected unified `getUserShopStatus()` state helper specification.
- Defined 1-tap quick setup bottom sheet and live review bottom sheet specs.
- Mapped out top header and 5-slot synchronized bottom dock layouts for both panels.
- Documented exact file changes, line numbers, CSS tokens, and verification methods.

## Artifact Index
- d:/TECH/WEBSITE/FAST SITE/fast site/.agents/explorer_p87_onboarding_stitch/DISPATCH.md
- d:/TECH/WEBSITE/FAST SITE/fast site/.agents/explorer_p87_onboarding_stitch/progress.md
- d:/TECH/WEBSITE/FAST SITE/fast site/.agents/explorer_p87_onboarding_stitch/BRIEFING.md
- d:/TECH/WEBSITE/FAST SITE/fast site/.agents/explorer_p87_onboarding_stitch/handoff.md
