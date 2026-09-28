## 2026-09-09T13:36:11Z
Task Description for Milestone 2 (M2):
1. In `partner/nav.php`:
   - Top Header Bar (`.top-nav`): In `.nav-right`, embed a high-visibility 1-Tap Mode Switcher Pill `[ 👤 Switch to Buyer Mode ]` linking directly to `/user/dashboard.php`. Style with sky blue/cyan accents (#38bdf8, rgba(56, 189, 248, 0.15)), active tap scaling `transform: scale(0.96)`, 44px+ touch target, and responsive text collapse (desktop: "Switch to Buyer Mode", mobile <=600px: "Buyer").
   - Top Header Left (`.nav-left`): When `$current_page !== 'dashboard.php'`, render a dedicated 44px `[ ← ]` back chevron button linking to `dashboard.php` for persistent subpage back navigation.
   - Mobile Bottom Navigation Dock (`.partner-bottom-dock`):
     * Re-architect to 5 synchronized slots:
       1. Slot 1: Hub (dashboard.php)
       2. Slot 2: Orders (orders.php) with live order count badge ($partner_pending_orders_count)
       3. Slot 3: Center FAB - Add Product (product_add.php)
       4. Slot 4: Catalog (products.php)
       5. Slot 5: 1-Tap Buyer Mode Switcher (/user/dashboard.php) with icon 👤 and label "Buyer Mode"
     * Replace redundant Slot 5 "Menu" with the 1-Tap Buyer Mode Switcher.
     * Incorporate `env(safe-area-inset-bottom)` into dock height and body bottom padding.
     * Ensure all touch targets are >= 44px x 44px with active tap compression `transform: scale(0.96)`.
   - Side Drawer: Update the return link to `/user/dashboard.php` with Google Stitch Buyer Mode styling.
2. In `partner/dashboard.php`:
   - Add direct `👤 Switch to Buyer Mode` button to the executive hero card quick action bar.
3. Fix the 7 dead-end redirects to `/partner/login.php`:
   - Change `header('Location: /partner/login.php');` to `header('Location: /user/login.php');` in:
     * `partner/index.php`
     * `partner/logout.php`
     * `partner/product_add.php`
     * `partner/product_edit.php`
     * `partner/product_delete.php`
     * `partner/profile.php`
     * `partner/api_docs.php`
4. Run verification commands:
   - Run `php -l` on all modified files and verify 0 syntax errors.
   - Verify pure UTF-8 encoding without BOM.

Exclusive Write Ownership:
- partner/nav.php
- partner/dashboard.php
- partner/index.php
- partner/logout.php
- partner/product_add.php
- partner/product_edit.php
- partner/product_delete.php
- partner/profile.php
- partner/api_docs.php
Do NOT touch user/*.php files.
