# Original User Request

## 2026-09-09T05:28:37Z

Execute a focused, module-by-module UX/UI and navigation overhaul for Fast Site, starting with the User Dashboard & Navigation, progressing through the Shop Panel and Marketplace, using simple everyday wording, streamlined menus, and Google Stitch design standards.

Working directory: d:/TECH/WEBSITE/FAST SITE/fast site
Integrity mode: development

## Requirements

### R1. User Dashboard & Navigation Overhaul (Module 1 Priority)
Streamline the User Dashboard (`user/dashboard.php`, `includes/user_sidebar.php`, `assets/css/user.css`) by replacing confusing jargon with intuitive, plain English words (e.g., simple names for stats, wallet, orders, daily streak, and referral missions). Reorganize top navigation headers, sidebar drawers, and floating bottom navigation to ensure 1-tap accessibility with zero element overlap.

### R2. Shop / Partner Portal Simplification (Module 2)
Restructure the merchant control center (`partner/dashboard.php`, `partner/orders.php`, `partner/products.php`, `partner/product_add.php`) with straightforward section titles (e.g., Shop Overview, My Products, Customer Orders, Store Settings) and responsive mobile/desktop menu bars.

### R3. Marketplace Header, Filter & Drawer Streamlining (Module 3)
Refine the public storefront (`home.php`, `includes/nav_public.php`) with a centered logo, prominent search bar, an easy-to-use Filter button, and a modern slide-up Category Drawer that presents categories and filters clearly on mobile and desktop.

### R4. Google Stitch UI, Graphics & Component Quality
Ensure all renovated headers, menus, cards, and drawers adhere to Google Stitch visual hierarchy, balanced responsive CSS/Tailwind layouts, and modern aesthetics across both Mobile APK and Desktop.

## Acceptance Criteria

### Plain Terminology & Menu Simplicity
- [ ] Section titles, stat cards, and navigation items across User, Shop, and Marketplace use clear, natural wording.
- [ ] User and Shop menus are organized into clean, intuitive action groups with zero cognitive clutter.

### Navigation Flow & Visual Harmony
- [ ] Top navbar, side drawer, and bottom navigation bar have cleanly separated, uncluttered touch targets.
- [ ] Marketplace header features centered logo branding with an effortless Filter and Category drawer.

### Technical Integrity & Mobile Responsiveness
- [ ] All updated links, tabs, and endpoints return HTTP 200 with zero broken routing or PHP errors.
- [ ] Interfaces render cleanly on both Mobile APK (WebView) and Desktop browsers without text wrapping glitches.

## 2026-09-09T13:16:32Z

Execute a comprehensive UX/UI and structural overhaul to provide a frictionless, 1-tap mode switcher between the User Panel (Buyer / Customer Mode) and Shop Panel (Merchant / Seller Mode), engineered with Google Stitch design standards across both mobile and desktop.

Working directory: d:/TECH/WEBSITE/FAST SITE/fast site
Integrity mode: development

## Requirements

### R1. Prominent 1-Tap "Buyer Mode ⇄ Shop Mode" Switcher
Implement a high-visibility, 1-tap mode toggle pill (similar to Airbnb / Fiverr) in BOTH the Top Header Bar and the Floating Bottom Navigation Dock across the User Panel (`user/dashboard.php`, `includes/user_sidebar.php`) and Shop Panel (`partner/dashboard.php`, `partner/nav.php`).
- In User Panel: Display `[ 🏪 Switch to Shop Mode ]` (or `[ ➕ Open Free Shop ]` if no shop exists).
- In Shop Panel: Display `[ 👤 Switch to Buyer Mode ]` with instant 1-tap navigation back to the customer dashboard.

### R2. Structural Design & Navigation Dock Synchronization
Standardize the navigation architecture across both portals so shifting between Customer and Merchant feels like a fluid tab transition in a native mobile application. Ensure bottom docks, top headers, and side drawers share aligned 44px+ touch targets, real-time unread/order badges, and persistent back navigation.

### R3. Google Stitch UI, Transitions & Nocturne Aurum Standards
Refactor the visual styling of both panels to adhere strictly to Google Stitch design principles (*Nocturne Aurum* tokens): deep obsidian background (`#0A0D1A`), frosted glass elevation layers (`rgba(18, 22, 43, 0.85)` with `backdrop-filter: blur(16px)`), luminous gold highlights (`#F59E0B`), and fluid micro-interactions with active tap scale feedback (`transform: scale(0.96)`).

### R4. Shopless User Onboarding & Approval Flow
For users without an approved shop, the mode switcher serves as an interactive onboarding trigger:
- If no shop: opens a clean bottom sheet or directs to `user/create_shop.php` with a 1-tap shop setup flow.
- If shop application is pending: displays an informative `[ ⏳ Shop Under Review ]` status pill with live status details.

## Acceptance Criteria

### Seamless Mode Shifting & Usability
- [ ] 1-tap mode toggle pill is prominently visible in both the top header and bottom dock on mobile and desktop viewports.
- [ ] Tapping the switcher teleports between User Dashboard and Shop Dashboard with zero friction, zero auth confusion, and 0ms layout jumping.
- [ ] Pending and shopless states provide clear visual feedback without dead ends.

### Google Stitch Visual Design & Layout Precision
- [ ] Both User Panel and Shop Panel feature identical luxury glassmorphism cards, typography, and color tokens.
- [ ] Zero horizontal overflow, zero visual clipping, and full hardware safe-area clearance (`env(safe-area-inset-top)`, `env(safe-area-inset-bottom)`).

### Technical Integrity & Live Verification
- [ ] All modified endpoints, routes, and switch links return HTTP 200 with zero PHP syntax or runtime errors.
- [ ] Verified on Local Server Live Host (`http://localhost:8000`) and Cloudflare mobile live tunnel (`https://zoo-dubai-hopefully-note.trycloudflare.com`).
