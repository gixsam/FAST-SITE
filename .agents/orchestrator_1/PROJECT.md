# Fast Site — Phase 85: Focused UX/UI & Navigation Overhaul

## Architecture
Fast Site platform UX and navigation layer:
- **User Space**: `user/dashboard.php`, `includes/user_sidebar.php`, `assets/css/user.css`, `assets/css/mobile_responsive.css`
- **Partner / Shop Space**: `partner/dashboard.php`, `partner/orders.php`, `partner/products.php`, `partner/product_add.php`, `partner/nav.php`
- **Marketplace Space**: `home.php`, `includes/nav_public.php`
- **Stitch Design Engine**: "Nocturne Aurum" dark luxury theme (`#080911` obsidian, `#fcb900` gold accents, `#00e676` emerald accents, frosted backdrop blur, Oswald/Sora/Inter typography).

## Feature Inventory
| # | Feature | Description | Milestone | Source |
|---|---------|-------------|-----------|--------|
| 1 | Plain English Terminology (User) | Replace developer jargon and MLM phrasing with everyday consumer words (Member ID, Invite Code, Available Balance ৳, Cash Out, Earn Bonus Points, Service Orders, Store Orders). | M1 | ORIGINAL_REQUEST §R1, Explorer 1 |
| 2 | Dead Tab Routes Resolution | Fix missing `tab-social` (route to `profile.php` or valid settings tab) and `tab-orders` (create unified orders tab) in `user/dashboard.php`. | M1 | Explorer 1 |
| 3 | Mobile Viewport Clearance & Zero Overlap (User) | Fix `.dashboard-container` and `body` padding in `mobile_responsive.css` and `user.css` to prevent top navbar overlap and bottom navigation bar occlusion. | M1 | Explorer 1 |
| 4 | Dual Drawer Decoupling | Separate `#sidebarOverlay` handlers so tapping the notification drawer backdrop does not trigger the sidebar menu drawer. | M1 | Explorer 1 |
| 5 | Ergonomic 5-Slot User Bottom Nav | Implement 44px touch targets for Store (`/index.php`), Orders, Wallet, Alerts, Profile. | M1 | Explorer 1 |
| 6 | Shop Portal Section Titles & Jargon Cleanup | Replace Fiverr/developer terms with everyday merchant words (`• Add New Product / Gig` -> `➕ Add New Product`, `In Escrow Queue` -> `Orders to Fulfill`, `Gross Delivered Volume` -> `Total Sales Earned`). | M2 | ORIGINAL_REQUEST §R2, Explorer 2 |
| 7 | Shop Navigation & Responsive Mobile Dock | Remove duplicate dashboard links in mobile drawer, implement 62px blurred dark glassmorphism bottom navigation dock in `partner/nav.php`. | M2 | ORIGINAL_REQUEST §R2, Explorer 2 |
| 8 | Orders Pipeline Mojibake & Stepper Clarification | Fix encoding mojibake (`à§³` -> `৳`), clarify order status tabs, and convert fulfillment upload note into a warm advisory banner in `partner/orders.php`. | M2 | Explorer 2 |
| 9 | Product Catalog & Product Add Streamlining | Add live storefront preview link `👁️ View` in `partner/products.php`, format FREE products cleanly, simplify buyer requirement submission box in `partner/product_add.php`. | M2 | Explorer 2 |
| 10 | Centered Logo Branding Header | Re-architect `includes/nav_public.php` with 3-zone symmetrical flex/grid layout (`1fr auto 1fr`) ensuring mathematical centering with 0px collision risk on all viewports. | M3 | ORIGINAL_REQUEST §R3, Explorer 3 |
| 11 | Prominent Search Command Bar | Streamline hero padding and integrate a prominent search bar with Stitch glassmorphism and an integrated "Filters" trigger button in `home.php`. | M3 | ORIGINAL_REQUEST §R3, Explorer 3 |
| 12 | Modern Slide-Up Category Drawer | Replace centered popup with a smooth slide-up bottom sheet on mobile (with drag handle, 85vh max-height) and elevated sheet on desktop in `home.php`. | M3 | ORIGINAL_REQUEST §R3, Explorer 3 |
| 13 | Pull-to-Refresh Non-Interference | Ensure all new navigation and drawer components conform to `pull_to_refresh.js` exclusion rules to prevent accidental refreshes. | M3 | Explorer 3 |
| 14 | Google Stitch Visual Hierarchy & Component Quality | Apply "Nocturne Aurum" design tokens, verified typography, and glassmorphism styling across Mobile APK and Desktop. | M4 | ORIGINAL_REQUEST §R4, StitchMCP |
| 15 | System Syntax Linting, Local Live Host & Packaging | Perform PHP syntax linting on all modified files, update `PROJECT_STATE.md`, package `fastsite_phase85.zip` with `DEPLOYMENT_GUIDE.txt`. | M4 | Mandatory Directives |

## Milestones
| # | Name | Target Files | Dependencies | Status |
|---|------|--------------|--------------|--------|
| M1 | User Dashboard & Navigation Overhaul | `user/dashboard.php`, `includes/user_sidebar.php`, `assets/css/user.css`, `assets/css/mobile_responsive.css` | none | DONE |
| M2 | Shop / Partner Portal Simplification | `partner/dashboard.php`, `partner/orders.php`, `partner/products.php`, `partner/product_add.php`, `partner/nav.php` | none | IN_PROGRESS |
| M3 | Marketplace Header, Filter & Drawer | `home.php`, `includes/nav_public.php` | none | PLANNED |
| M4 | Stitch UI Polish, System Verification & Packaging | `PROJECT_STATE.md`, `DEPLOYMENT_GUIDE.txt`, `fastsite_phase85.zip` | M1, M2, M3 | PLANNED |

## Code Layout & Ownership Boundaries
- Worker M1 owns: `user/dashboard.php`, `includes/user_sidebar.php`, `assets/css/user.css`, `assets/css/mobile_responsive.css` (M1 DONE)
- Worker M2 owns: `partner/dashboard.php`, `partner/orders.php`, `partner/products.php`, `partner/product_add.php`, `partner/nav.php`
- Worker M3 owns: `home.php`, `includes/nav_public.php`
- Worker M4 owns: `PROJECT_STATE.md`, `DEPLOYMENT_GUIDE.txt`, and builds `fastsite_phase85.zip`
