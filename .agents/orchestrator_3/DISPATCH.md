# Dispatch Log

## 2026-09-09T13:18:23Z

Execute a comprehensive UX/UI and structural overhaul to provide a frictionless, 1-tap mode switcher between the User Panel (Buyer / Customer Mode) and Shop Panel (Merchant / Seller Mode), engineered with Google Stitch design standards across both mobile and desktop.

### Requirements:
1. **R1. Prominent 1-Tap "Buyer Mode ⇄ Shop Mode" Switcher:**
   - Implement a high-visibility, 1-tap mode toggle pill (similar to Airbnb / Fiverr) in BOTH the Top Header Bar and the Floating Bottom Navigation Dock across the User Panel (`user/dashboard.php`, `includes/user_sidebar.php`) and Shop Panel (`partner/dashboard.php`, `partner/nav.php`).
   - In User Panel: Display `[ 🏪 Switch to Shop Mode ]` (or `[ ➕ Open Free Shop ]` if no shop exists).
   - In Shop Panel: Display `[ 👤 Switch to Buyer Mode ]` with instant 1-tap navigation back to the customer dashboard.
2. **R2. Structural Design & Navigation Dock Synchronization:**
   - Standardize the navigation architecture across both portals so shifting between Customer and Merchant feels like a fluid tab transition in a native mobile application.
   - Ensure bottom docks, top headers, and side drawers share aligned 44px+ touch targets, real-time unread/order badges, and persistent back navigation.
3. **R3. Google Stitch UI, Transitions & Nocturne Aurum Standards:**
   - Refactor visual styling to adhere strictly to Google Stitch design principles (*Nocturne Aurum* tokens): deep obsidian background (`#0A0D1A`), frosted glass elevation layers (`rgba(18, 22, 43, 0.85)` with `backdrop-filter: blur(16px)`), luminous gold highlights (`#F59E0B`), and fluid micro-interactions with active tap scale feedback (`transform: scale(0.96)`).
4. **R4. Shopless User Onboarding & Approval Flow:**
   - For users without an approved shop, mode switcher serves as an interactive onboarding trigger:
     - If no shop: opens clean bottom sheet or directs to `user/create_shop.php` with 1-tap shop setup flow.
     - If shop application is pending: displays informative `[ ⏳ Shop Under Review ]` status pill with live status details.

## Mandatory User Directives (from .agents/AGENTS.md):
1. **Context Preservation**: Read `PROJECT_STATE.md` before planning/executing.
2. **Documentation**: Update `PROJECT_STATE.md` on plan creation, plan edits, and milestone completions.
3. **Hostinger Zip Upload**: Build `fastsite_phase87.zip` with `DEPLOYMENT_GUIDE.txt` containing folder paths, workflow step, active phase, and local server reminder.
4. **Complex Architecture**: Read `HIGH CODING BY CLAUDE SONNET.md` if executing backend logic/DB migrations.
5. **Local Server Live Host**: Remind user to turn on Local Server Live Host (`http://localhost:8000`) and test before deployment. Verify on `http://localhost:8000` and live tunnel `https://zoo-dubai-hopefully-note.trycloudflare.com`.
6. **Google Stitch UI Directive**: Strictly adhere to Google Stitch (StitchMCP) design standards and Nocturne Aurum design tokens.
