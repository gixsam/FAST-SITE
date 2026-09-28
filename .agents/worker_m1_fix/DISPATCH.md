# DISPATCH

## 2026-09-09T05:56:20Z

### Caller
- Agent: parent
- ID: bee31ca9-af9f-4ea9-b602-c7247f534ed9

### Task Assignment
Milestone M1 Remediation for Fast Site:
1. Critical Defect (JS Function Shadowing): In `user/dashboard.php` (around lines 461-465), delete the obsolete duplicate declaration of `function toggleSidebar() { ... }`.
2. Order Badge Variable Mismatch: In `user/dashboard.php` (around line 1617), update the bottom navigation active orders badge check from `($totalActiveOrders ?? 0) > 0` to `($activeOrders ?? 0) > 0`.
3. Referral URL String Cleanup: In `user/dashboard.php` (around line 1240), ensure `#reflink-agent` contains a clean, valid URL without duplicated path segments.
4. Tab State Guard: In `switchUserTab(tabName)` in `user/dashboard.php`, ensure `window.history.pushState` only runs if `target` exists in the DOM.
5. Touch Targets: In `includes/user_sidebar.php`, ensure top nav icons (hamburger, bell, messages) have min 44x44px touch targets.

### Exclusive File Write Ownership
- `user/dashboard.php`
- `includes/user_sidebar.php`
- Local `.agents/worker_m1_fix/` files
- `PROJECT_STATE.md`
