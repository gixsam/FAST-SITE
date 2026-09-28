# Worker M1 Fix Workspace
Target: Remediate M1 gate feedback
Target files: user/dashboard.php, includes/user_sidebar.php
Reviewer feedback:
1. Remove duplicate `toggleSidebar()` in user/dashboard.php:461-465 so the master implementation in user_sidebar.php is preserved.
2. Fix variable mismatch at line 1617: change `$totalActiveOrders` to `$activeOrders` for bottom nav order badge.
3. Fix `#reflink-agent` URL at line 1240: clean up duplicate ref path.
4. Guard `switchUserTab` so `history.pushState` only runs when valid target tab element exists in DOM.
5. In includes/user_sidebar.php, ensure touch targets meet 44x44px.
