## 2026-09-09T05:59:47Z
You are a Worker agent for the Fast Site project.
Your working directory is: d:/TECH/WEBSITE/FAST SITE/fast site/.agents/worker_m2/

MANDATORY INPUTS:
- Authoritative User Request: d:/TECH/WEBSITE/FAST SITE/fast site/.agents/ORIGINAL_REQUEST.md
- Project Scope: d:/TECH/WEBSITE/FAST SITE/fast site/.agents/orchestrator_1/PROJECT.md
- Detailed Survey & Blueprint: d:/TECH/WEBSITE/FAST SITE/fast site/.agents/explorer_survey_partner/handoff.md

EXCLUSIVE FILE WRITE OWNERSHIP:
- partner/dashboard.php
- partner/orders.php
- partner/products.php
- partner/product_add.php
- partner/nav.php

MANDATORY INTEGRITY WARNING:
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_preview_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

YOUR ASSIGNMENT (Milestone M2: Shop / Partner Portal Simplification):
Implement the complete recommendations from `explorer_survey_partner/handoff.md`:

1. `partner/nav.php`:
   - Consolidate duplicate user dashboard links in the mobile drawer (remove redundant button, keep single clean `🏠 Return to User Dashboard` link).
   - Change developer brand title `⚡ FAST SITE PARTNER` to everyday title `⚡ FAST SITE SHOP`.
   - Implement the modern Stitch mobile bottom navigation dock (62px height, dark glassmorphism `#111317` with blur, 5 thumb-friendly items: `[ 📊 Hub (dashboard.php) | 📦 Orders (orders.php) | ➕ Add (product_add.php) | 🛍️ Catalog (products.php) | ☰ Menu (openNavDrawer) ]`) with mobile viewport clearance `body { padding-bottom: 74px; }`.

2. `partner/dashboard.php`:
   - Replace Fiverr slang `• Add New Product / Gig` with `➕ Add New Product`.
   - Simplify 6 KPI cards:
     - `In Escrow Queue` -> `Orders to Fulfill`
     - `Gross Delivered Volume` -> `Total Sales Earned`
     - `Marketplace Reach` placeholder -> `Customer Satisfaction (100% Verified)`
   - Rename tabs to everyday merchant terms:
     - `Command Overview` -> `📊 Shop Overview`
     - `Active Storefront` -> `🛍️ My Products`
     - `Escrow Queue` -> `📦 Customer Orders`
     - `Viral Share & Growth` -> `🚀 Promote & Share`

3. `partner/orders.php`:
   - Fix encoding mojibake: replace `à§³` with clean `৳` (Bangladeshi Taka symbol), and `⚠️ï¸ ` with `⚠️`.
   - Clarify order filter tabs:
     - `Escrow Pending` -> `New Orders (Paid)`
     - `Waiting Confirmation` -> `Delivered (Awaiting Buyer)`
     - `Completed & Released` -> `Completed (Funds Released)`
   - Update order progress stepper:
     - `Escrow Held` -> `Payment Secured`
     - `Fulfilling` -> `Preparing Order`
   - Convert fulfillment file upload note from red error text into a warm advisory banner.

4. `partner/products.php`:
   - Add direct `👁️ View` live storefront preview link (`../product_detail.php?id=<?= $product['id'] ?>`) alongside Edit and Delete actions.
   - Format free products to display a vibrant green `FREE` badge instead of `৳0.00`.

5. `partner/product_add.php`:
   - Simplify product type pills:
     - `Affiliate / CPA Link` -> `External Affiliate Link`
     - `Service / Freelance Gig` -> `Professional Service`
   - Simplify `Customer Information & Document Submission System (Summation Box)` header and explanation into `Instructions & Requirements for Buyer`.

VERIFICATION:
- Run PHP syntax linting (`php -l`) on all 5 modified files.
- Verify that no mojibake characters remain in partner/orders.php.
- Document changes in `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/worker_m2/handoff.md`.
- Notify orchestrator (Recipient: "bee31ca9-af9f-4ea9-b602-c7247f534ed9") via `send_message`.
