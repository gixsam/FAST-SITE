## 2026-09-09T05:42:33Z

You are a Worker agent for the Fast Site project.
Your working directory is: d:/TECH/WEBSITE/FAST SITE/fast site/.agents/worker_m1/

MANDATORY INPUTS:
- Authoritative User Request: d:/TECH/WEBSITE/FAST SITE/fast site/.agents/ORIGINAL_REQUEST.md
- Project Scope: d:/TECH/WEBSITE/FAST SITE/fast site/.agents/orchestrator_1/PROJECT.md
- Detailed Survey & Blueprint: d:/TECH/WEBSITE/FAST SITE/fast site/.agents/explorer_survey_user/handoff.md

EXCLUSIVE FILE WRITE OWNERSHIP:
- user/dashboard.php
- includes/user_sidebar.php
- assets/css/user.css
- assets/css/mobile_responsive.css (only the user/dashboard container & bottom nav padding rules)

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_preview_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

YOUR ASSIGNMENT (Milestone M1: User Dashboard & Navigation Overhaul):
Implement the complete, verified recommendations from `explorer_survey_user/handoff.md`:
1. Plain Everyday English Terminology:
   - Apply the Master Terminology Replacement Dictionary across `user/dashboard.php` and `includes/user_sidebar.php`.
   - Replace developer/MLM terms with clear consumer phrases: Member ID, Invite Code, Available Balance ৳, Cash Out / Withdraw Money, Earn Bonus Points, Daily Check-in, My Service Orders, Store Orders, Rewards & Invites, Affiliate Partner.
   - Clean up duplicate `id="reflink"` to unique IDs so clipboard copying works reliably everywhere.
2. Dead Tab Routes Resolution:
   - Eliminate dead `tab-social` references: route profile clicks cleanly to `/user/profile.php` or `tab-settings`.
   - Ensure all bottom navigation, sidebar drawer, and hero links point to valid, existing tabs or endpoints.
   - Wire orders link to a clean orders section or `tab-orders` with tracking steps (`Pending` -> `Processing` -> `Completed`).
3. Zero-Overlap Navigation & Responsive Clearance:
   - In `includes/user_sidebar.php`, center `.nav-logo`, add clean 44px touch targets for Notification Bell and Messages.
   - Implement the streamlined 5-slot bottom navigation:
     [ 🏪 Store (/index.php) | 📦 Orders (?tab=orders) | 🪙 Wallet (/user/wallet.php) | 🔔 Alerts (toggleNotificationDrawer) | 👤 Profile (/user/profile.php) ]
   - Fix `.dashboard-container` and `body` padding in `assets/css/user.css` and `assets/css/mobile_responsive.css`: ensure the top 60px navbar never covers hero avatar/name and the bottom 80px floating nav never obscures page footer, buttons, or modals on mobile (<768px).
   - Decouple `#sidebarOverlay`: ensure closing the notification drawer does NOT trigger the sidebar drawer.
4. Google Stitch UI Standards:
   - Apply clean visual hierarchy, sleek glassmorphism accents, readable typography, and balanced card layouts.

VERIFICATION REQUIREMENTS:
- Run PHP syntax linting (`php -l`) on every modified PHP file.
- Verify that every tab trigger matches an existing DOM ID.
- Document all modified lines, commands run, and verification results in your handoff report:
  `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/worker_m1/handoff.md`
- Notify the orchestrator (Recipient: "bee31ca9-af9f-4ea9-b602-c7247f534ed9") via `send_message` when complete.
