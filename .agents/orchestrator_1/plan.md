# Execution Plan: Phase 85 UX/UI & Navigation Overhaul

## Scope & Architecture Overview
Overhaul the Fast Site User, Partner (Shop), and Marketplace frontend views to provide intuitive everyday English wording, eliminate visual overlap, streamline navigation controls (top bar, side drawer, bottom mobile nav), and elevate UI components to modern Google Stitch / Tailwind standards.

## Feature Inventory
| # | Feature | Description | Milestone | Source |
|---|---------|-------------|-----------|--------|
| 1 | Plain English Terminology (User) | Replace complex/jargon terms with clear everyday labels across stats, wallet, orders, daily streak, and referral missions in User Dashboard. | M1 | ORIGINAL_REQUEST §R1 |
| 2 | Zero-Overlap User Navigation | Align top navbar, sidebar drawer, and floating bottom navigation with clean touch targets and zero clipping/overlap on mobile APK and desktop. | M1 | ORIGINAL_REQUEST §R1 |
| 3 | Shop Portal Section Titles | Streamline merchant control center into clear, natural sections: Shop Overview, My Products, Customer Orders, Store Settings. | M2 | ORIGINAL_REQUEST §R2 |
| 4 | Responsive Shop Menu Bars | Implement responsive, touch-friendly navigation bars and tab switchers across partner dashboard, orders, products, and product_add. | M2 | ORIGINAL_REQUEST §R2 |
| 5 | Centered Marketplace Header Branding | Reorganize home.php and includes/nav_public.php to feature a centered logo branding, prominent search bar, and clean layout. | M3 | ORIGINAL_REQUEST §R3 |
| 6 | Slide-Up Category & Filter Drawer | Refactor filter button to open a modern, slide-up category drawer presenting categories and filters cleanly on mobile and desktop. | M3 | ORIGINAL_REQUEST §R3 |
| 7 | Google Stitch Component Quality & Styling | Ensure modern visual hierarchy, cohesive CSS/Tailwind design system, glassmorphism accents, and responsive aesthetics across all devices. | M4 | ORIGINAL_REQUEST §R4 |
| 8 | Linting, Testing & Archive Deployment | PHP syntax validation across all modified files, local server live verification, PROJECT_STATE.md updates, and fastsite_phase85.zip packaging with DEPLOYMENT_GUIDE.txt. | M4 | Mandatory Directives |

## Milestones
| # | Name | Target Files | Status |
|---|------|--------------|--------|
| M1 | User Dashboard & Navigation Overhaul | `user/dashboard.php`, `includes/user_sidebar.php`, `assets/css/user.css` | PLANNED |
| M2 | Shop / Partner Portal Simplification | `partner/dashboard.php`, `partner/orders.php`, `partner/products.php`, `partner/product_add.php`, `partner/nav.php` | PLANNED |
| M3 | Marketplace Header, Filter & Drawer | `home.php`, `includes/nav_public.php`, `assets/css/style.css` (or relevant) | PLANNED |
| M4 | Stitch UI Polish, System Verification & Packaging | `PROJECT_STATE.md`, `DEPLOYMENT_GUIDE.txt`, `fastsite_phase85.zip` | PLANNED |

## Orchestrator Execution Workflow
1. **Survey (3 Explorers in parallel)**:
   - Explorer 1: Deep dive into User Dashboard & Navigation (`user/dashboard.php`, `includes/user_sidebar.php`, `assets/css/user.css`).
   - Explorer 2: Deep dive into Shop/Partner Portal (`partner/dashboard.php`, `partner/orders.php`, `partner/products.php`, `partner/product_add.php`, `partner/nav.php`).
   - Explorer 3: Deep dive into Marketplace Header, Filter & Drawer (`home.php`, `includes/nav_public.php`).
2. **Sequential Milestone Execution (M1 -> M2 -> M3 -> M4)**:
   - Worker implements planned changes according to Explorer findings and Stitch design standards.
   - Reviewer independently checks correctness, usability, and absence of visual overlap.
   - Challenger runs syntax checks and verification commands.
   - Forensic Auditor confirms zero cheating/dummy facades.
   - Gate evaluation before proceeding to next milestone.
3. **Packaging & Verification**:
   - Worker updates `PROJECT_STATE.md`.
   - Worker packages `fastsite_phase85.zip` with `DEPLOYMENT_GUIDE.txt`.
