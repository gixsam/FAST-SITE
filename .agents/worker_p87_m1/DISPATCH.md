## 2026-09-09T13:24:28Z
You are worker_p87_m1.
Your Working Directory is: d:/TECH/WEBSITE/FAST SITE/fast site/.agents/worker_p87_m1/
You MUST read the authoritative user request at: d:/TECH/WEBSITE/FAST SITE/fast site/.agents/ORIGINAL_REQUEST.md
You MUST read the project scope at: d:/TECH/WEBSITE/FAST SITE/fast site/PROJECT.md
You MUST read the explorer findings at:
- d:/TECH/WEBSITE/FAST SITE/fast site/.agents/explorer_p87_user/handoff.md
- d:/TECH/WEBSITE/FAST SITE/fast site/.agents/explorer_p87_onboarding_stitch/handoff.md

Your Exclusive Write Ownership:
- includes/user_sidebar.php
- user/dashboard.php
- assets/css/user.css
Do NOT touch any other source files.

Task Description for Milestone 1 (M1):
1. In `includes/user_sidebar.php`:
   - Implement self-contained `getUserShopState($pdo, $userId, $userPhone)` helper function so every user page loading the sidebar receives accurate shop state ('approved', 'pending', 'none') even if $partnerInfo was not defined by the caller.
   - In Top Header Bar (`.top-nav`), replace the redundant Store link in the left slot with the 1-Tap Mode Toggle Pill (guaranteeing 44px+ touch targets):
     * If 'approved': render `[ 🏪 Switch to Shop Mode ]` (or mobile `[ 🏪 Shop ]`) linking to `/partner/dashboard.php`.
     * If 'pending': render `[ ⏳ Shop Under Review ]` (or mobile `[ ⏳ Review ]`) triggering `openShopReviewModal()`.
     * If 'none': render `[ ➕ Open Free Shop ]` (or mobile `[ ➕ Free Shop ]`) triggering `openQuickShopDrawer()`.
   - In Side Drawer, synchronize the shop navigation callout with $shopState.
   - At the bottom of `includes/user_sidebar.php`, append the Google Stitch Nocturne Aurum `#shopReviewModal` (informative bottom sheet for pending status with 3-step progress stepper, 24-48h SLA, and WhatsApp escalation link) and `#quickShopDrawer` (30-second frictionless 1-tap quick shop setup form with direct POST to `user/create_shop.php`).
   - Implement JavaScript helper functions: `openShopReviewModal()`, `closeShopReviewModal()`, `openQuickShopDrawer()`, `closeQuickShopDrawer()`.

2. In `user/dashboard.php`:
   - Update the floating bottom navigation dock (`#user-floating-bottom-nav`) to 5 synchronized slots:
     * Slot 1: Store (`/index.php`)
     * Slot 2: Orders (`/user/dashboard.php?tab=orders`) with dynamic active orders badge
     * Slot 3 (Center): 1-Tap Mode Switcher Action:
       - If 'approved': `[ 🏪 Shop Mode ]` linking to `/partner/dashboard.php` with elevated glowing center icon
       - If 'pending': `[ ⏳ In Review ]` triggering `openShopReviewModal()`
       - If 'none': `[ ➕ Free Shop ]` triggering `openQuickShopDrawer()`
     * Slot 4: Wallet (`/user/wallet.php`)
     * Slot 5: Profile (`/user/profile.php`)
   - Add active tab highlighting based on current tab.

3. In `assets/css/user.css`:
   - Add Google Stitch Nocturne Aurum styles for `.top-mode-pill`, `.dock-center-circle`, `.bottom-sheet-backdrop`, `.bottom-sheet`, and modal components.
   - Include active tap compression: `transform: scale(0.96)`.
   - Ensure all touch targets are at least 44px x 44px.

4. Run Verification Commands:
   - Run `php -l "includes/user_sidebar.php"`
   - Run `php -l "user/dashboard.php"`
   - Verify 0 syntax errors and pure UTF-8 formatting.
