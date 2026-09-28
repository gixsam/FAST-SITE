# Dispatch for Explorer: User Panel Architecture & Switcher Survey

**Mission**: Investigate User Panel codebase (`user/dashboard.php`, `includes/user_sidebar.php`, `assets/css/user.css`, `assets/css/native_mobile.css`) to map out how to implement the 1-tap "Buyer Mode ⇄ Shop Mode" switcher in both the top header and bottom dock, synchronize touch targets (44px+), badges, and back navigation under Google Stitch Nocturne Aurum standards.

## 2026-09-09T13:19:20Z
You are explorer_p87_user.
Your Working Directory is: d:/TECH/WEBSITE/FAST SITE/fast site/.agents/explorer_p87_user/
You MUST read the authoritative user request at: d:/TECH/WEBSITE/FAST SITE/fast site/.agents/ORIGINAL_REQUEST.md

Your mission:
Investigate User Panel codebase:
- user/dashboard.php
- includes/user_sidebar.php
- assets/css/user.css
- assets/css/native_mobile.css
- any relevant navigation partials/headers

Analyze:
1. Current top navbar and floating bottom navigation dock in User Panel.
2. Where and how user shop/partner status is currently fetched and checked (e.g. $partner, active shop, pending request, or no shop).
3. How to implement the 1-tap mode toggle pill [ 🏪 Switch to Shop Mode ] (or [ ➕ Open Free Shop ] / [ ⏳ Shop Under Review ]) in BOTH the Top Header Bar and the Floating Bottom Navigation Dock.
4. Touch target compliance (>=44px), spacing, active tab states, badges, and smooth transition.
5. Exact file paths, line numbers, and concrete code recommendations.

Write your structured report to d:/TECH/WEBSITE/FAST SITE/fast site/.agents/explorer_p87_user/handoff.md following the Handoff Protocol (Observation, Logic Chain, Caveats, Conclusion, Recommendations).
When complete, notify orchestrator via send_message.
