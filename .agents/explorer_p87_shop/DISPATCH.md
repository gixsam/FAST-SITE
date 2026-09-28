# Dispatch for Explorer: Shop Panel Architecture & Switcher Survey

**Mission**: Investigate Shop / Partner Panel codebase (`partner/dashboard.php`, `partner/nav.php`, `partner/orders.php`, `partner/products.php`, etc.) to map out how to implement the 1-tap "Switch to Buyer Mode" button in both the top header and bottom dock, synchronize touch targets (44px+), badges, and back navigation under Google Stitch Nocturne Aurum standards.

## 2026-09-09T13:19:20Z
You are explorer_p87_shop.
Your Working Directory is: d:/TECH/WEBSITE/FAST SITE/fast site/.agents/explorer_p87_shop/
You MUST read the authoritative user request at: d:/TECH/WEBSITE/FAST SITE/fast site/.agents/ORIGINAL_REQUEST.md

Your mission:
Investigate Shop / Partner Panel codebase:
- partner/dashboard.php
- partner/nav.php
- partner/orders.php
- partner/products.php
- partner/product_add.php

Analyze:
1. Current top navbar, .partner-bottom-dock, and side drawer in partner/nav.php and partner/dashboard.php.
2. Where and how the 1-tap [ 👤 Switch to Buyer Mode ] mode toggle pill should be placed in BOTH the Top Header Bar and the Floating Bottom Navigation Dock.
3. How to synchronize the Shop Panel's bottom dock with the User Panel's bottom dock (aligned 5-slot structure, 44px+ touch targets, persistent back navigation, order/unread badges).
4. Session and auth state verification: ensure switching from Shop Mode back to Buyer Mode (/user/dashboard.php) works cleanly with 1 tap, 0ms friction, and zero session conflicts.
5. Exact file paths, line numbers, and concrete code recommendations.

Write your structured report to d:/TECH/WEBSITE/FAST SITE/fast site/.agents/explorer_p87_shop/handoff.md following the Handoff Protocol (Observation, Logic Chain, Caveats, Conclusion, Recommendations).
When complete, notify orchestrator via send_message.
