# Exploration Report: Module 2 â€” Shop / Partner Portal Simplification (Requirement R2)

**Explorer Agent:** explorer_survey_partner  
**Timestamp:** 2026-09-09T05:35:00Z  
**Target Scope:** Merchant Control Center & Navigation Overhaul  
**Target Files:**
1. `partner/nav.php` (Navigation Architecture, Side Drawer, Header & Mobile Viewports)
2. `partner/dashboard.php` (Executive Shop Command Hub, KPI Cards, Tabbed Workspace)
3. `partner/orders.php` (Customer Orders Queue, Stepper, Dispatch & Proof Upload)
4. `partner/products.php` (Product Catalog Grid, Actions & Live Preview)
5. `partner/product_add.php` (Listing Creator, Category Switcher, Buyer Submission Vault & HUD Dial)

---

## 1. Observation

Direct examination of the target files was conducted using filesystem inspection and line-by-line code analysis. Below are the verbatim observations, code references, and UX defects identified across each file.

### 1.1 `partner/nav.php` (Navigation & Viewport Structure)
1. **Brand Identity & Header:**
  - Line 365: `<a href="dashboard.php" class="top-brand">âš¡ FAST SITE PARTNER</a>`
  - *Observation:* The term "PARTNER" is internal developer/ecosystem terminology. Real sellers operate a "Shop" or "Store" and find "Partner" ambiguous.
  - Line 396: `<span class="partner-badge"><?= htmlspecialchars($partner['status']) ?></span>`
  - *Observation:* On mobile screens (`max-width: 600px`), `.partner-badge` has `display: none !important;` (line 303) because it was causing vertical line wrap and overflow breaks.
2. **Duplicate & Confusing Drawer Links:**
  - Line 427: `<a href="../user/dashboard.php" class="drawer-link" style="background:linear-gradient(...); ... color:#00e676; ..."><span>âš </span> User Dashboard</a>`
  - Line 456: `<a href="../user/dashboard.php" class="drawer-link" style="color:var(--brand);"><span>ðŸ‘¤</span> Switch to User Panel</a>`
  - *Observation:* Two separate drawer links navigate to the exact same URL (`../user/dashboard.php`). Line 427 calls it "User Dashboard" while line 456 calls it "Switch to User Panel". This causes cognitive friction and drawer clutter.
3. **Technical & Confusing Drawer Labels:**
  - Line 431: `<span>ðŸ“Š</span> Dashboard` â€” Generic.
  - Line 434: `<span>ðŸ“¦</span> Manage Products` â€” Verbose.
  - Line 437: `<span>ðŸ“¥</span> Escrow Orders` â€” "Escrow" is financial escrow protocol jargon; merchants simply want to see "Customer Orders".
  - Line 440: `<span>ðŸ’°</span> Earnings & Payouts` â€” Links to `partner/earnings.php`, which contains a simple redirect to `/user/wallet.php`.
  - Line 446: `<span>&#9878;</span> Dispute Center` â€” Sounds legalistic and confrontational.
  - Line 450: `<span>&#9881;</span> Shop Settings` â€” Links to `partner/profile.php`.
  - Line 454: `<div style="...">User Hub Tools</div>` â€” Technical divider title.
4. **Complete Absence of Mobile Bottom Navigation:**
  - Lines 90â€“99: `body { padding-top: 70px; padding-left: 280px; } @media (max-width: 1024px) { body { padding-left: 0; } }`
  - Lines 194â€“202: On screen widths `<= 1024px`, the drawer is hidden (`left: -280px`) and only toggled via the top-left hamburger button (`id="menu-toggle"`).
  - *Observation:* There is NO persistent bottom navigation bar on mobile viewports or in the Android APK. In modern mobile app design (and Google Stitch standards), merchants need 1-tap thumb access to core destinations (Overview, Products, Orders, Settings, and Quick Add).
5. **Cross-Platform Dropdown Bounds:**
  - Lines 370â€“394: The 9-dots button opens `#app-hub-menu` with fixed `width: 280px; max-width: 90vw;`. On small mobile viewports (<360px), it collides with viewport margins.

---

### 1.2 `partner/dashboard.php` (Shop Command Hub)
1. **Hero Header & Quick Action Terminology:**
  - Line 691: `âš¡ <?= htmlspecialchars($partner['business_name'] ?? 'Fast Site Official Store') ?>`
  - Lines 708â€“720: Quick Action Bar:
    - `<a href="product_add.php" class="btn-action-hero btn-action-gold">'• Add New Product / Gig</a>` â€” "Gig" is Fiverr slang, out of place for retail or digital e-commerce merchants.
    - `<a href="orders.php" class="btn-action-hero btn-action-glass">ðŸ“¦ Orders (<?= $active_orders_count ?>)</a>` â€” Links to `orders.php`.
    - `<a href="javascript:void(0)" onclick="switchHubTab('tab-promos')" class="btn-action-hero btn-action-glass">ðŸŽŸ Promo Codes</a>`
    - `<a href="/shop.php?id=<?= $partner_id ?>" target="_blank" class="btn-action-hero btn-action-glass">ðŸ‘� Live Storefront â†—</a>`
2. **KPI Metrics Grid (Lines 724â€“760):**
  - Card 1: `Wallet Balance` / `à§³...` / `ðŸš  ... Fast Points` â€” Clear financial readout.
  - Card 2: `Listed Products` / `...` / `Active Catalog Items` â€” Clear catalog count.
  - Card 3: `Active Orders` / `...` / `In Escrow Queue` â€” Technical jargon. Merchants think in terms of orders awaiting shipment/fulfillment.
  - Card 4: `Completed Orders` / `...` / `Delivered & Confirmed` â€” Clear.
  - Card 5: `Lifetime Revenue` / `à§³...` / `Gross Delivered Volume` â€” Corporate accounting jargon.
  - Card 6: `Storefront Reach` / `45K+` / `Active Buyers Ecosystem` â€” Hardcoded static string `"45K+"`. Does not reflect real store telemetry, misleading merchants.
3. **Hub Tabs Bar (Lines 763â€“770):**
  - Button 1: `ðŸ“Š Command Overview` â€” Developer/sci-fi tone.
  - Button 2: `ðŸ›� Active Storefront (<?= $product_count ?>)` â€” Confusing. Sellers expect this to open their live web store, but it actually opens their product catalog.
  - Button 3: `ðŸ“¦ Escrow Queue (<?= $active_orders_count ?>)` â€” Developer backend jargon.
  - Button 4: `ðŸš€ Viral Share & Growth` â€” Marketing buzzwords.
  - Button 5: `ðŸŽŸ Promo Codes` â€” Standard.
  - Button 6: `&#9881; Shop Settings` â€” Duplicates `partner/profile.php`.
4. **Tab Panes & Inner Content:**
  - Tab 1 (line 779): `ðŸ”— Your Custom Shareable Storefront URL` with badge `Auto-Affiliate Active`.
  - Tab 3 (line 928): `Active Escrow Orders Fulfillment` with description `All orders placed with your shop are protected by the platform Escrow Vault.`
  - Tab 4 (lines 987â€“993): `ðŸŒŸ Fast Site PRO Shop Boost` with an unhooked button `Upgrade to PRO (500 Coins)`.
  - Tab 6 (lines 1110â€“1147): Contains a barebones avatar/cover upload form, whereas `partner/profile.php` contains the master Shop Profile & Brand Studio (with business name, phone, district, payout method, account number, etc.), leading to confusion over which page controls what.

---

### 1.3 `partner/orders.php` (Customer Orders Queue)
1. **Title & Character Encoding Mojibake:**
  - Line 399: `<h1 style="...">Partner Order Queue</h1>` â€” Everyday English: `Customer Orders`.
  - Line 401: `<?php if($err): ?><div class="err">&#9888; <?= htmlspecialchars($err) ?></div><?php endif; ?>`
    - *Observation:* Corrupted UTF-8 mojibake on the warning emoji.
  - Line 439: `<span class="detail-value" style="color:var(--green); font-weight:800;">à§³<?= number_format($ord['total_coins'], 2) ?> BDT</span>`
    - *Observation:* Corrupted UTF-8 mojibake on the Bengali Taba currency symbol (`à§³`).
2. **Tab Filter Bar (`.tabs-bar`, Lines 404â€“411):**
  - Filter 1: `Active Orders` (`?status=active`)
  - Filter 2: `Pending Accept` (`?status=pending`) â€” Everyday English: `New Orders`.
  - Filter 3: `Waiting Confirmation` (`?status=waiting`) â€” Ambiguous. In the code, this status is applied after the seller uploads delivery proof or courier tracking and is waiting for the customer to confirm delivery. Everyday English: `Shipped / In Transit` or `Awaiting Delivery`.
  - Filter 4: `Completed` (`?status=completed`)
  - Filter 5: `Disputed` (`?status=disputed`)
  - Filter 6: `Cancelled` (`?status=cancelled`)
3. **Order Lifecycle Stepper (`renderStepper`, Lines 18â€“24):**
  - Step 1: `Order Placed`
  - Step 2: `Escrow Held` â€” Technical jargon. Everyday English: `Payment Secured`.
  - Step 3: `Shipped / In Transit`
  - Step 4: `Delivered`
  - Step 5: `Completed`
4. **Action Buttons & Form Feedback:**
  - Line 572: `<span style="font-size:0.72rem; color:red; display:block; margin-top:2px;">recommended size: clear delivery receipt photo or invoice (max 2mb)</span>`
    - *Observation:* Using `color: red` makes benign advice look like a validation error before the user even uploads.

---

### 1.4 `partner/products.php` (Product Catalog)
1. **Header & Action Bar:**
  - Line 184: `<h1 style="font-size: 1.6rem; font-weight: 800;">My Product Listings</h1>` â€” Everyday English: `My Products`.
  - Line 185: `<a href="product_add.php" class="btn-add">'• Add New Product</a>`
2. **Product Grid Cards (Lines 197â€“243):**
  - Displays thumbnail, category, title, description, scheduled date, affiliate destination URL, and price.
  - Price display: `number_format($prod['price'], 1) Fast Points` â€” Does not indicate `FREE` when price is 0.0.
  - Action icons (Lines 234â€“238):
    - External affiliate link trigger (if affiliate).
    - `âœ�ï¸�` Edit (`product_edit.php?id=...`).
    - `ðŸ—‘ï¸�` Delete (`product_delete.php?id=...`).
    - *Observation:* Missing `ðŸ‘� View on Storefront` link. On `partner/dashboard.php` each product card has an `ðŸ‘� View` button linking to `/product_detail.php?id=X`, but in `partner/products.php` merchants have no way to preview their live listing.

---

### 1.5 `partner/product_add.php` (Product Creation Hub)
1. **Header & Subtitle:**
  - Line 781: `âš¡ Add New Product or Service`
  - Line 782: `Create digital assets, freelance gigs, or promotional free items for your storefront` â€” "freelance gigs" is marketplace slang.
2. **Listing Type Switcher (Lines 801â€“814):**
  - `ðŸ“¦ Digital Asset / Code` â€” Overly narrow.
  - `ðŸš¡ Freelance Service`
  - `ðŸ”— Affiliate / CPA Link` â€” "CPA Link" is affiliate network slang; everyday sellers understand `Affiliate / Referral Link`.
3. **Customer Submission System ("Summation System", Lines 885â€“946):**
  - Header: `ðŸ“‘ Customer Information & Document Submission System` with internal developer comments referencing "Summation Box".
  - Subtitle: "Need documents or information from the buyer (e.g. NID Number, old birth certificate, photos) to deliver this service/product? Turn this on."
  - Everyday English: `ðŸ“‘ Buyer Requirements & Document Uploads` with toggle `Ask Buyer for Details or Documents`.
4. **Upload Progress HUD Modal (Lines 647â€“770, 1029â€“1064):**
  - Status text: `âš¡ Initializing listing payload & validating inputs...`. Everyday English: `Preparing listing details...`.

---

## 2. Logic Chain

1. **Premise 1 (Cognitive Accessibility):** Fast Site merchants are local entrepreneurs, shop owners, and digital service providers in Bangladesh and South Asia. Technical terms such as "Escrow Queue", "Gross Delivered Volume", "Summation System", "CPA Link", and "Payload" introduce friction, hesitation, and support tickets.
2. **Premise 2 (Navigation Streamlining):** Having duplicate links (`User Dashboard` at line 427 and `Switch to User Panel` at line 456 in `partner/nav.php`) clutters the drawer. Removing the duplicate and categorizing the drawer into **Store Management** and **Account & Services** reduces cognitive load.
3. **Premise 3 (Mobile-First Thumb Ergonomics):** On mobile devices and inside the Android APK WebView, opening a left drawer for primary navigation requires reaching the top-left corner. Implementing a persistent, glassmorphic **Mobile Bottom Navigation Dock** (Overview, Products, Orders, Settings, +Add) gives merchants instant 1-tap access to primary tasks without thumb gymnastics.
4. **Premise 4 (Character Encoding & Visual Polish):** Mojibake artifacts (`&#9888;` and `à§³` in `partner/orders.php`) and error-colored helper text (`color: red` in proof upload) damage perceived quality. Correcting them to UTF-8 (`&#9888;`, `à§³`) and standard helper styling restores professional polish.
5. **Conclusion:** A clean simplification plan using everyday English, unified navigation groups, a mobile bottom navigation dock, and targeted UX enhancements will elevate the merchant experience to Google Stitch standards while preserving 100% of underlying backend logic.

---

## 3. Caveats

1. **No Backend Database Schema Changes Required:** All proposed simplifications are presentation-layer and UI label refinements. No MySQL database columns or table structures need to be altered.
2. **Form Parameter Preservation:** In `partner/product_add.php` and `partner/orders.php`, input names (`listing_type`, `require_submission`, `submission_prompt`, `courier_name`, `courier_tracking_id`, `proof_file`) must remain unchanged to preserve backend compatibility.
3. **Impersonation Support:** In `partner/nav.php`, the admin impersonation return link (`return_to_admin.php`) must be preserved for admin testing.

---

## 4. Conclusion & Actionable Simplification Blueprint

### 4.1 Master Terminology Simplification Dictionary

| Location | Current Technical / Confusing Term | Proposed Everyday English | User Rationale |
|---|---|---|---|
| `partner/nav.php` (Brand) | `âš¡ FAST SITE PARTNER` | `ðŸ�° FAST SITE SHOP` / `Shop Manager` | Clear identity: seller runs a shop. |
| `partner/nav.php` (Drawer) | `ðŸ“Š Dashboard` | `ðŸ“Š Shop Orerview` | Explicitly identifies the merchant view. |
| `partner/nav.php` (Drawer) | `ðŸ“¦ Manage Products` | `ðŸ“¦ My Products` | Natural, personal ownership. |
| `partner/nav.php` (Drawer) | `ðŸ“¥ Escrow Orders` | `ðŸ“¥ Customer Orders` | Replaces financial jargon with natural order terminology. |
| `partner/nav.php` (Drawer) | `ðŸ’° Earnings & Payouts` | `ðŸš  Shop Earnings` | Direct and concise. |
| `partner/nav.php` (Drawer) | `ðŸŽŸ Promo Codes` | `ðŸŽŸ Discounts & Coupons` | Standard e-commerce terminology. |
| `partner/nav.php` (Drawer) | `&#9878; Dispute Center` | `ðŸ›¡ Order Help & Disputes` | Constructive, solution-oriented. |
| `partner/nav.php` (Drawer) | `User Hub Tools` | `My Account` | Clear separation from shop controls. |
| `partner/nav.php` (Drawer) | Duplicate `User Dashboard` + `Switch to User Panel` | Single unified `ðŸ‘¤ Switch to Buyer View` | Eliminates redundant links. |
| `partner/dashboard.php` (Action) | `'• Add New Product / Gig` | `'• Add New Product` | Removes Fiverr jargon ("Gig"). |
| `partner/dashboard.php` (KPI 3) | `In Escrow Queue` | `Orders to Ship / Fulfill` | Action-oriented queue description. |
| `partner/dashboard.php` (KPI 5) | `Gross Delivered Volume` | `Total Sales Earned` | Clear financial meaning. |
| `partner/dashboard.php` (KPI 6) | `Storefront Reach: 45K+` | `Shop Rating: &#11088; 5.0` or dynamic reviews | Eliminates untrusted static number. |
| `partner/dashboard.php` (Tab 1) | `ðŸ“Š Command Overview` | `ðŸ“Š Shop Orerview` | Clean and professional. |
| `partner/dashboard.php` (Tab 2) | `ðŸ›� Active Storefront` | `ðŸ“¦ My Products` | Accurately describes catalog management. |
| `partner/dashboard.php` (Tab 3) | `ðŸ“¦ Escrow Queue` | `ðŸ“¥ Customer Orders` | Clear everyday terminology. |
| `partner/dashboard.php` (Tab 4) | `ðŸš€ Viral Share & Growth` | `ðŸ“¢ Promote & Share` | Natural marketing language. |
| `partner/orders.php` (Title) | `Partner Order Queue` | `Customer Orders` | Standard e-commerce terminology. |
| `partner/orders.php` (Tab 3) | `Waiting Confirmation` | `Shipped / In Transit` | Clarifies that package is on the way. |
| `partner/orders.php` (Stepper 2)| `Escrow Held` | `Payment Secured` | Reassuring, clear everyday English. |
| `partner/orders.php` (Proof note)| `<span style="color:red">recommended size...</span>` | Muted badge with info icon | Removes false-error visual warning. |
| `partner/product_add.php` (Pill 1)| `ðŸ“¦ Digital Asset / Code` | `ðŸ“¦ Digital Item / File` | Broad, inclusive digital product type. |
| `partner/product_add.php` (Pill 3)| `ðŸ”— Affiliate / CPA Link` | `ðŸ”— Affiliate / Referral Link` | Removes industry jargon ("CPA"). |
| `partner/product_add.php` (Box) | `Customer Information & Document Submission System` | `Buyer Requirements & Document Uploads` | Clarifies why the feature exists. |

---

### 4.2 Exact Structural Layout & Code Blocks Needed in Each Target File

#### Target 1: `partner/nav.php`
**Goal:** Clean up the drawer into two logical groups, eliminate duplicate links, update top brand title, and inject a responsive Mobile Bottom Navigation Dock.

**Proposed Code Block for Drawer Links (`partner/nav.php:425â€“471`):**
```html
    <div class="drawer-links">
      <!-- Group 1: Store Management -->
      <div style="font-size:0.7rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.05em; padding: 0.8rem 1rem 0.3rem;">Store Management</div>

      <a href="dashboard.php" class="drawer-link <?= isActive('dashboard.php', $current_page) ?>">
        <span>ðŸ“Š</span> Shop Overview
      </a>
      <a href="products.php" class="drawer-link <?= isActive('products.php', $current_page) || isActive('product_add.php', $current_page) || isActive('product_edit.php', $current_page) ?>">
        <span>ðŸ“¦</span> My Products
      </a>
      <a href="orders.php" class="drawer-link <?= isActive('orders.php', $current_page) ?>">
        <span>ðŸ“¥</span> Customer Orders
      </a>
      <a href="coupons.php" class="drawer-link <?= isActive('coupons.php', $current_page) ?>">
        <span>ðŸŽŸ</span> Discounts &amp; Coupons
      </a>
      <a href="disputes.php" class="drawer-link <?= isActive('disputes.php', $current_page) ?>">
        <span>ðŸ›¡</span> Order Help &amp; Disputes
      </a>
      <a href="profile.php" class="drawer-link <?= isActive('profile.php', $current_page) ?>">
        <span>&#9881;</span> Store Settings
      </a>

      <!-- Group 2: Account & Buyer View (Single, non-duplicated switcher) -->
      <div style="font-size:0.7rem; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:0.05em; padding: 1.2rem 1rem 0.3rem;">Account &amp; Services</div>

      <a href="../user/wallet.php" class="drawer-link">
        <span>ðŸš </span> Shop Wallet &amp; Payouts
      </a>
      <a href="../user/messages.php" class="drawer-link">
        <span>ðŸ’¬</span> Messages
      </a>
      <a href="../user/notifications.php" class="drawer-link">
        <span>ðŸ””</span> Notifications
      </a>
      <a href="../user/dashboard.php" class="drawer-link" style="background: rgba(0,230,118,0.1); border: 1px solid rgba(0,230,118,0.3); color: #00e676; font-weight: 700; margin-top: 0.4rem;">
        <span>ðŸ‘¤</span> Switch to Buyer View
      </a>
    </div>
```


#### Mobile Bottom Navigation Dock (New Component for partner/nav.php)
To solve the mobile accessibility issue where shop owners must repeatedly reach for the top hamburger button or scroll past long tables, we introduce a dedicated mobile bottom navigation dock following the Stitch dark design system.

##### Proposed CSS for partner/nav.php:
```css
* ========================================================================
   FastSite Partner Portal - Mobile Bottom Dock (Stitch Design System)
   ======================================================================= */
.partner-bottom-dock {
  display: none;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 62px;
  background: rgba(17, 19, 23, 0.94);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  z-index: 1000;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.5);
  padding: 0 0.5rem;
}

@media (max-width: 900px) {
  .partner-bottom-dock {
    display: flex;
    justify-content: space-around;
    align-items: center;
  }
  /* Ensure page content and action bars do not get obscured by bottom dock */
  body {
    padding-bottom: 74px !important;
  }
}

.lock-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  height: 100%;
  color: #8b94ee;
  text-decoration: none;
  font-size: 0.68rem;
  font-weight: 600;
  gap: 3px;
  transition: all 0.2s ease;
  padding: 4px 0;
}

.lock-item span.icon {
  font-size: 1.15rem;
  line-height: 1;
}

.lock-item.active {
  color: #fcb900;
}

.lock-item.active span.icon {
  transform: scale(1.1);
}

.lock-item-primary {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  top: -10px;
  background: linear-gradient(135deg, #fcb900, #ff9100);
  color: #0b0e14 !important;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  box-shadow: 0 4px 14px rgba(252, 185, 0, 0.45);
  text-decoration: none;
  font-size: 1.35rem;
  font-weight: 800;
  border: 3px solid #111317;
  transition: transform 0.2s ease;
}

.lock-item-primary:active {
  transform: scale(0.92);
}
```

##### Proposed HTML Component in partner/nav.php:
```html
<!-- Mobile Bottom Navigation Dock (Stitch Responsive System) -->
;nav class="partner-bottom-dock" aria-label="Partner Mobile Dock">
  <a href="dashboard.php" class="dock-item <?= isActive('dashboard.php', $current_page) ?>">
    <span class="icon">П></span>
    <span>Hub</span>
  </a>
  <a href="orders.php" class="dock-item <?= isActive('orders.php', $current_page) ?>">
    <span class="icon">📈</span>
    <span>Orders</span>
  </a>
  <a href="product_add.php" class="dock-item-primary" title="Add Product">
    <span>+</span>
  </a>
  <a href="products.php" class="dock-item <?= isActive('products.php', $current_page) ?>">
    <span class="icon">🛍️</span>
    <span>Catalog</span>
  </a>
  <a href="javascript:void(0)" onclick="togglePartnerDrawer()" class="dock-item">
    <span class="icon">☰</span>
    <span>Menu</span>
  </a>
</nav>
```

---

### Target 2: `partner/dashboard.php`

#### 1. Header Action Button
- **Location:** Line 183
- **Before:**
```html
<a href="product_add.php" class="btn-action btn-action-gold">• Add New Product / Gig</a>
```
- **After:**
```html
<a href="product_add.php" class="btn-action btn-action-gold">➕ Add New Product</a>
```

#### 2. KPI Cards Grid Terminology & Metrics
- **Location:** Lines 191–235
- **Before:**
```html
<div class="partner-grid">
  <div class="kpi-card">
    <div class="kpi-title">Active Listings</div>
    <div class="kpi-value" style="color:var(--text);"><?= $stat_active_products ?></div>
    <div class="kpi-sub">Ready for purchase</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">Pending Approval</div>
    <div class="kpi-value" style="color:#ffc107;"><?= $stat_pending_products ?></div>
    <div class="kpi-sub">Under moderation</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">In Escrow Queue</div>
    <div class="kpi-value" style="color:var(--brand);"><?= $stat_pending_orders ?></div>
    <div class="kpi-sub">Funds locked safely</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">Orders Delivered</div>
    <div class="kpi-value" style="color:#28a745;"><?= $stat_completed_orders ?></div>
    <div class="kpi-sub">Completed &amp; released</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">Gross Delivered Volume</div>
    <div class="kpi-value" style="color:#00e676;">ধ৳<?= number_format($stat_revenue, 2) ?></div>
    <div class="kpi-sub">Total sales volume</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">45K+ Storefront Reach</div>
    <div class="kpi-value" style="color:#38bdf8">99.8%</div>
    <div class="kpi-sub">FastSite Verified Vendor</div>
  </div>
</div>
```
- **After:**
```html
<div class="partner-grid">
  <div class="kpi-card">
    <div class="kpi-title">Active Listings</div>
    <div class="kpi-value" style="color:var(--text);"><?= $stat_active_products ?></div>
    <div class="kpi-sub">Live on storefront</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">Pending Approval</div>
    <div class="kpi-value" style="color:#ffc107;"><?= $stat_pending_products ?></div>
    <div class="kpi-sub">Review in progress</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">Orders to Fulfill</div>
    <div class="kpi-value" style="color:var(--brand);"><?= $stat_pending_orders ?></div>
    <div class="kpi-sub">Awaiting your delivery</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">Completed Orders</div>
    <div class="kpi-value" style="color:#28a745;"><?= $stat_completed_orders ?></div>
    <div class="kpi-sub">Successfully fulfilled</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">Total Sales Earned</div>
    <div class="kpi-value" style="color:#00e676;">▧৳x= number_format($stat_revenue, 2) ?></div>
    <div class="kpi-sub">Lifetime completed sales</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-title">Customer Satisfaction</div>
    <div class="kpi-value" style="color:#38bdf8">100%</div>
    <div class="kpi-sub">Verified seller score</div>
  </div>
</div>

```

#### 3. Hub Tab Switcher Wording
- **Location:** Lines 242?248
- **Before:**
```html
<div class="partner-tabs">
  <button class="partner-tab active" onclick="switchPartnerTab('overview', this)">Command Overview</button>
  <button class="partner-tab" onclick="switchPartnerTab('products', this)">Active Storefront</button>
  <button class="partner-tab" onclick="switchPartnerTab('orders', this)">Escrow Queue</button>
  <button class="partner-tab" onclick="switchPartnerTab('marketing', this)">Viral Share &amp; Growth</button>
</div>
```
- **After:**
```html
<div class="partner-tabs">
  <button class="partner-tab active" onclick="switchPartnerTab('overview', this)">?? Shop Overview</button>
  <button class="partner-tab" onclick="switchPartnerTab('products', this)">??? My Products</button>
  <button class="partner-tab" onclick="switchPartnerTab('orders', this)">?? Customer Orders</button>
  <button class="partner-tab" onclick="switchPartnerTab('marketing', this)">?? Promote &amp; Share</button>
</div>
```

#### 4. Tab Header Section Titles
- **Overview Section (Line 255):**
  - **Before:** `<div class="section-title">Merchant Ecosystem Analytics</div>`
  - **After:** `<div class="section-title">?? Shop Performance &amp; Analytics</div>`
- **Orders Section (Line 327):**
  - **Before:** `<div class="section-title">Escrow Operational Queue</div>`
  - **After:** `<div class="section-title">?? Orders Awaiting Fulfillment</div>`
- **Marketing Section (Line 417):**
  - **Before:** `<div class="section-title">Growth Engine &amp; Storefront Promotion</div>`
  - **After:** `<div class="section-title">?? Share &amp; Promote Your Store</div>`

---

### Target 3: `partner/orders.php`

#### 1. Page Header Simplification
- **Location:** Lines 212?216
- **Before:**
```html
<h1 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.25rem;">Merchant Escrow &amp; Order Pipeline</h1>
<p style="font-size: 0.85rem; color: var(--muted);">Track customer payments held in FastSite escrow, dispatch deliveries, and provide fulfillment proof.</p>
```
- **After:**
```html
<h1 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.25rem;">Customer Orders &amp; Fulfillment</h1>
<p style="font-size: 0.85rem; color: var(--muted);">Manage buyer orders, submit delivery deliverables or tracking details, and track payouts.</p>
```

#### 2. Order Status Filter Tabs
- **Location:** Lines 227?234
- **Before:**
```html
<div class="filter-tabs">
  <a href="orders.php?status=all" class="filter-tab <?= $status_filter === 'all' ? 'active' : '' ?>">All Orders (<?= count($orders) ?>)</a>
  <a href="orders.php?status=pending" class="filter-tab <?= $status_filter === 'pending' ? 'active' : '' ?>">Escrow Pending</a>
  <a href="orders.php?status=processing" class="filter-tab <?= $status_filter === 'processing' ? 'active' : '' ?>">Processing</a>
  <a href="orders.php?status=delivered" class="filter-tab <?= $status_filter === 'delivered' ? 'active' : '' ?>">Waiting Confirmation</a>
  <a href="orders.php?status=completed" class="filter-tab <?= $status_filter === 'completed' ? 'active' : '' ?>">Completed &amp; Released</a>
  <a href="orders.php?status=disputed" class="filter-tab <?= $status_filter === 'disputed' ? 'active' : '' ?>">Disputed</a>
</div>
```
- **After:**
```html
<div class="filter-tabs">
  <a href="orders.php?status=all" class="filter-tab <?= $status_filter === 'all' ? 'active' : '' ?>">All Orders (<?= count($orders) ?>)</a>
  <a href="orders.php?status=pending" class="filter-tab <?= $status_filter === 'pending' ? 'active' : '' ?>">New Orders (Paid)</a>
  <a href="orders.php?status=processing" class="filter-tab <?= $status_filter === 'processing' ? 'active' : '' ?>">In Progress</a>
  <a href="orders.php?status=delivered" class="filter-tab <?= $status_filter === 'delivered' ? 'active' : '' ?>">Delivered (Awaiting Buyer)</a>
  <a href="orders.php?status=completed" class="filter-tab <?= $status_filter === 'completed' ? 'active' : '' ?>">Completed (Funds Released)</a>
  <a href="orders.php?status=disputed" class="filter-tab <?= $status_filter === 'disputed' ? 'active' : '' ?>">Disputes / Help</a>
</div>
```

#### 3. Order Progress Stepper Array & Taka Currency Symbol
- **Location:** Lines 281?286 & Line 296
- **Before (Line 281):**
```php
$steps = ['pending' => 'Escrow Held', 'processing' => 'Fulfilling', 'delivered' => 'Delivered', 'completed' => 'Released'];
```
- **After:**
```php
$steps = ['pending' => 'Payment Secured', 'processing' => 'Preparing Order', 'delivered' => 'Delivered to Buyer', 'completed' => 'Funds Released'];
```
- **Currency Symbol (Line 296):**
  Replace mojibake `???` with valid UTF-8 Bangladeshi Taka symbol `?` (`&#2547;`).

#### 4. Delivery Proof Upload Note & Alert Styling
- **Location:** Lines 347?350
- **Before:**
```html
<div style="font-size: 0.75rem; color: red; margin-top: 0.5rem;">
  ?? Upload zip or enter proof link. Once delivered, buyer has 72 hours to confirm receipt.
</div>
```
- **After:**
```html
<div style="font-size: 0.78rem; color: #fcb900; background: rgba(252, 185, 0, 0.08); border: 1px solid rgba(252, 185, 0, 0.22); border-radius: 6px; padding: 0.55rem 0.85rem; margin-top: 0.65rem; display: flex; align-items: center; gap: 0.5rem;">
  <span>??</span>
  <span>Attach a file (ZIP/PDF) or enter a delivery link. Once submitted, the buyer has 72 hours to review and confirm receipt before payment is released.</span>
</div>
```

---

### Target 4: `partner/products.php`

#### 1. Page Header
- **Location:** Lines 111?115
- **Before:**
```html
<h1 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.25rem;">Merchant Storefront Catalog</h1>
<p style="font-size: 0.85rem; color: var(--muted);">Manage, edit, toggle visibility, and monitor inventory for your commercial products.</p>
```
- **After:**
```html
<h1 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.25rem;">My Products &amp; Catalog</h1>
<p style="font-size: 0.85rem; color: var(--muted);">Add, edit, pause, and manage items displayed on your public storefront.</p>
```

#### 2. Direct Live Storefront Preview Link
Currently, product rows only offer `Edit` and `Delete` (`action-btn btn-danger`). The merchant has no way to preview how the item appears to buyers without manually hunting for the public URL.
- **Location:** Line 185
- **Proposed Enhancement:**
```html
<div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
  <a href="../product_detail.php?id=<?= $p['id'] ?>" target="_blank" class="action-btn" style="background: rgba(56,189,248,0.1); border: 1px solid rgba(56,189,248,0.3); color: #38bdf8;" title="View Live on Storefront">
    ??? View
  </a>
  <a href="product_edit.php?id=<?= $p['id'] ?>" class="action-btn">
    ?? Edit
  </a>
  <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
    <input type="hidden" name="action" value="delete_product">
    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
    <button type="submit" class="action-btn btn-danger">???</button>
  </form>
</div>
```

#### 3. Free Product Price Formatting
- **Location:** Line 173
- **Before:**
```html
<td style="font-weight: 700; color: #00e676;">?<?= number_format($p['price'], 2) ?></td>
```
- **After:**
```html
<td>
  <?php if ((float)$p['price'] <= 0): ?>
    <span class="badge" style="background: rgba(0, 230, 118, 0.15); border: 1px solid rgba(0, 230, 118, 0.35); color: #00e676; font-weight: 800; font-size: 0.72rem; padding: 3px 8px; border-radius: 4px;">FREE</span>
  <?php else: ?>
    <span style="font-weight: 700; color: #00e676;">?<?= number_format($p['price'], 2) ?></span>
  <?php endif; ?>
</td>
```

---

### Target 5: `partner/product_add.php`

#### 1. Product Type Selector Pills
- **Location:** Lines 206?232
- **Before:**
```html
<div class="type-pill" onclick="selectType('digital', this)">
  <span class="type-pill-icon">??</span>
  <div>
    <div style="font-weight:700; font-size:0.85rem;">Digital Download / File</div>
    <div style="font-size:0.75rem; color:var(--muted);">Files, scripts, graphics, software, eBooks</div>
  </div>
</div>
<div class="type-pill" onclick="selectType('affiliate', this)">
  <span class="type-pill-icon">??</span>
  <div>
    <div style="font-weight:700; font-size:0.85rem;">Affiliate / CPA Link</div>
    <div style="font-size:0.75rem; color:var(--muted);">External promotion link, direct commissions</div>
  </div>
</div>
<div class="type-pill" onclick="selectType('service', this)">
  <span class="type-pill-icon">?</span>
  <div>
    <div style="font-weight:700; font-size:0.85rem;">Service / Freelance Gig</div>
    <div style="font-size:0.75rem; color:var(--muted);">Custom client work, tasks, freelance deliverables</div>
  </div>
</div>
<div class="type-pill" onclick="selectType('physical', this)">
  <span class="type-pill-icon">??</span>
  <div>
    <div style="font-weight:700; font-size:0.85rem;">Physical Merchandise</div>
    <div style="font-size:0.75rem; color:var(--muted);">Tangible goods shipped to buyer address</div>
  </div>
</div>
```
- **After:**
```html
<div class="type-pill" onclick="selectType('digital', this)">
  <span class="type-pill-icon">??</span>
  <div>
    <div style="font-weight:700; font-size:0.88rem;">Digital Download / Software</div>
    <div style="font-size:0.75rem; color:var(--muted);">Files, source code, graphics, eBooks, templates</div>
  </div>
</div>
<div class="type-pill" onclick="selectType('affiliate', this)">
  <span class="type-pill-icon">??</span>
  <div>
    <div style="font-weight:700; font-size:0.88rem;">External Affiliate Link</div>
    <div style="font-size:0.75rem; color:var(--muted);">Promote affiliate products with external purchase links</div>
  </div>
</div>
<div class="type-pill" onclick="selectType('service', this)">
  <span class="type-pill-icon">?</span>
  <div>
    <div style="font-weight:700; font-size:0.88rem;">Professional Service</div>
    <div style="font-size:0.75rem; color:var(--muted);">Custom client tasks, design work, freelancing</div>
  </div>
</div>
<div class="type-pill" onclick="selectType('physical', this)">
  <span class="type-pill-icon">??</span>
  <div>
    <div style="font-weight:700; font-size:0.88rem;">Physical Goods</div>
    <div style="font-size:0.75rem; color:var(--muted);">Physical items shipped directly to buyer address</div>
  </div>
</div>
```

#### 2. Buyer Requirement Box
- **Location:** Lines 290?297
- **Before:**
```html
<label class="form-label" style="display:flex; justify-content:space-between; align-items:center;">
  <span>Customer Information &amp; Document Submission System (Summation Box)</span>
  <span style="font-size:0.75rem; color:var(--muted); font-weight:400;">Optional</span>
</label>
<p style="font-size:0.75rem; color:var(--muted); margin-bottom:0.6rem;">
  Define what the buyer must submit after purchase (e.g. account ID, website URL, custom design specifications, or upload receipts).
</p>
```
- **After:**
```html
<label class="form-label" style="display:flex; justify-content:space-between; align-items:center;">
  <span>Instructions &amp; Requirements for Buyer</span>
  <span style="font-size:0.75rem; color:var(--muted); font-weight:400;">Optional</span>
</label>
<p style="font-size:0.78rem; color:var(--muted); margin-bottom:0.6rem;">
  Tell the buyer what details they must provide after ordering (e.g., website link, email, account details, or design instructions).
</p>
```

---

## 5. Verification Method

To independently verify the survey findings, architectural assessment, and subsequent implementation for Module 2:

### 1. File Inspection & Syntax Verification
Execute PHP linting on all modified partner portal files to ensure clean syntax without regression:
```powershell
php -l "partner/nav.php"
php -l "partner/dashboard.php"
php -l "partner/orders.php"
php -l "partner/products.php"
php -l "partner/product_add.php"
```

### 2. Encoding & Mojibake Check
Verify that `orders.php` and other files are strictly UTF-8 encoded with no malformed byte sequences:
```powershell
python -c "
for fname in ['partner/nav.php', 'partner/dashboard.php', 'partner/orders.php', 'partner/products.php', 'partner/product_add.php']:
    with open(fname, 'r', encoding='utf-8') as f:
        content = f.read()
    assert '???' not in content, f'Mojibake Taka found in {fname}'
    assert '???' not in content, f'Mojibake warning icon found in {fname}'
    print(f'{fname}: Encoding clean!')
"
```

### 3. Responsive Layout & Mobile Dock Viewport Verification
- **Desktop (>900px):**
  - Verify `.partner-bottom-dock` has `display: none` and does not add bottom whitespace.
  - Verify desktop horizontal top navbar maintains proper button spacing and brand text (`? FAST SITE SHOP`).
- **Mobile (<=900px):**
  - Verify `.partner-bottom-dock` is fixed at the bottom with 5 navigation items: Hub, Orders, + (Add Product), Catalog, Menu.
  - Verify `body` has `padding-bottom: 74px` preventing floating buttons from obscuring checkout/save buttons.
  - Verify drawer menu opens smoothly without horizontal scroll overflow or duplicate links.

### 4. User Journey & Workflow Invalidation Conditions
The implementation shall be considered invalid or regressed if:
1. Any developer jargon (such as "Escrow Pipeline", "Summation Box", "CPA", or "Gross Delivered Volume") remains visible in merchant-facing portal views.
2. The mobile bottom dock overlaps actionable inputs or fails to navigate to the correct partner routes.
3. The drawer menu retains the duplicate `Switch to User Panel` / `User Dashboard` links.
4. Any product row in `partner/products.php` lacks the direct live storefront preview link.
