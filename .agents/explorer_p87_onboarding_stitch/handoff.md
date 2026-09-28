# Phase 87 Investigation Report: Shopless User Onboarding Flow & Google Stitch Design Standards

**Agent:** `explorer_p87_onboarding_stitch`  
**Working Directory:** `d:/TECH/WEBSITE/FAST SITE/fast site/.agents/explorer_p87_onboarding_stitch/`  
**Date:** 2026-09-09  
**Status:** Complete  

---

## 1. Observation

Direct observations extracted from code inspection and database verification across the Fast Site codebase:

### 1.1 Shop Creation & Registration Logic (`user/create_shop.php`)
- **Lines 22–35**: Checks for existing shop:
  ```php
  $chk = $pdo->prepare("SELECT id, status FROM partners WHERE user_id = :uid OR phone = :phone LIMIT 1");
  $chk->execute([':uid' => $userId, ':phone' => $user['phone']]);
  $has_shop = $chk->fetch();
  ```
- **Lines 43–46**: If `$has_shop && !$user_extra`, the script immediately executes:
  ```php
  header('Location: /partner/dashboard.php');
  exit;
  ```
  *Flaw observed:* Even if `$has_shop['status'] === 'pending'`, the user is unconditionally redirected to `/partner/dashboard.php` without informing them of their pending status.
- **Lines 97–124**: Submission inserts directly into `partners` table with `'pending'` status:
  ```php
  $stmt = $pdo->prepare("INSERT INTO partners 
      (user_id, business_name, owner_name, email, phone, password_hash, nid, trade_license, profile_pic, cover_pic, description, payout_method, payout_account, status) 
      VALUES (:uid, :bn, :on, :e, :p, :h, :nid, :tl, :pp, :cp, :d, :pm, :pa, 'pending')");
  ```
  Sets `$_SESSION['partner_id'] = $new_partner_id;` and displays `$msg = 'Shop application submitted successfully! Please wait for admin approval.';`
- **Lines 199–267**: The current form is a heavy, multi-screen legacy form requiring:
  - Business Name (manual input)
  - Owner Full Name (pre-filled, readonly)
  - Email Address (pre-filled, readonly)
  - Phone Number (pre-filled, readonly)
  - Business Description (manual textarea)
  - NID Copy upload (file input)
  - Trade License Copy upload (optional file input)
  - Payout Method (bKash/Nagad select)
  - Payout Wallet Number (manual input)
  - Terms & Conditions checkbox (required)
  *Ergonomic friction:* A user in User Panel wishing to open a shop must navigate away to a separate page, scroll past 9 fields, and upload files before they can have a registered pending shop.

### 1.2 User Dashboard Shop State Detection (`user/dashboard.php`)
- **Lines 138–150**:
  ```php
  $isPartner = false;
  $partnerInfo = null;
  $p_stmt = $pdo->prepare("SELECT * FROM partners WHERE (user_id = :uid OR phone = :p) AND (status IS NULL OR status != 'suspended') ORDER BY id DESC LIMIT 1");
  $p_stmt->execute([':uid' => $userId, ':p' => $user['phone']]);
  if ($partnerRow = $p_stmt->fetch()) {
      $isPartner = true;
      $partnerInfo = $partnerRow;
  ```
- **Lines 678–680**:
  ```php
  <?php if ($isPartner && ($partnerInfo['status'] ?? 'approved') !== 'suspended'): ?>
  ```
  *Defect observed:* If `$partnerInfo['status'] === 'pending'`, `$isPartner` is `true`, and line 679 evaluates to true! The dashboard renders active seller controls ("UPLOAD PRODUCT", "Manage your products", and orders) as though the shop were already approved, creating false expectations.
- **Lines 1596–1654**: The mobile floating bottom navigation bar in `user/dashboard.php` contains 5 static buttons:
  1. `Store` (`/index.php`)
  2. `Orders` (`/user/dashboard.php?tab=orders`)
  3. `Wallet` (`/user/wallet.php` - center highlighted)
  4. `Alerts` (`toggleNotificationDrawer()`)
  5. `Profile` (`/user/profile.php`)
  *Missing:* Zero 1-tap mode switcher pill exists in either the top navigation bar or the floating bottom dock of `user/dashboard.php`.

### 1.3 User Sidebar Navigation Component (`includes/user_sidebar.php`)
- **Lines 109–114**:
  ```php
  if (isset($partnerInfo) && !empty($partnerInfo['id'])) {
      $st_po = $pdo->prepare("SELECT COUNT(*) FROM partner_orders WHERE partner_id = ? AND status != 'completed'");
      $st_po->execute([$partnerInfo['id']]);
      $partner_shop_orders_count = (int)$st_po->fetchColumn();
  }
  ```
  *Architectural dependency bug:* `$partnerInfo` is NOT queried inside `includes/user_sidebar.php`; it expects the parent caller to have defined it. Across `user/wallet.php`, `user/create_shop.php`, `user/profile.php`, and `user/tasks.php`, `$partnerInfo` is never initialized, so the sidebar defaults to showing `➕ Open a Free Shop` even when a user has an approved or pending shop!
- **Lines 280–307**: Top header contains Hamburger, Brand Logo, Notifications, and Messages. No mode switcher pill exists.

### 1.4 Partner Portal Navigation & Dashboard (`partner/nav.php` & `partner/dashboard.php`)
- **Lines 19–57 of `partner/nav.php`**:
  ```php
  if (!$partner) {
      $partner = [
          'id' => 0, 
          'status' => 'pending', 
          'profile_pic' => '', 
          'business_name' => 'New Partner', 
          'owner_name' => 'Partner', 
          'user_id' => $_SESSION['user_id'] ?? 0, 
          'description' => '', 
          'phone' => '', 
          'email' => ''
      ];
  }
  ```
- **Lines 301–304 of `partner/nav.php`**:
  ```css
  @media (max-width: 600px) {
    .partner-badge {
      display: none !important; /* Badge causes "PROVED" vertical overflow on mobile */
    }
  }
  ```
  *Defect observed:* On mobile screens, the pending badge is completely hidden via CSS, so a pending partner sees zero notification or review banner.
- **Lines 686–706 of `partner/dashboard.php`**:
  The hero card displays `🛡️ Verified Partner Store` and `⭐ 5.0 Rating` unconditionally, even for a shop with `status = 'pending'`.
- **Lines 573–593 of `partner/nav.php`**:
  The partner bottom dock contains:
  1. `Hub` (`dashboard.php`)
  2. `Orders` (`orders.php`)
  3. `➕` (`product_add.php` - center floating action)
  4. `Catalog` (`products.php`)
  5. `Menu` (`openNavDrawer()`)
  *Missing:* No 1-tap `[ 👤 Switch to Buyer Mode ]` exists in the partner bottom dock or top navbar.

### 1.5 Admin Shop Approval Pipeline (`admin/partner_shops.php` & `admin/partner_requests.php`)
- **`admin/partner_shops.php` Lines 30–56**:
  When admin approves a shop (`$action === 'approve'`):
  - Sets `partners.status = 'approved'`
  - Sets `partners.registration_number = 'FS-SHOP-' . str_pad($userId, 5, '0', STR_PAD_LEFT)`
  - Updates `users SET role = 'partner', is_active = 1 WHERE id = :uid`
  - Sends notification to user: `Shop Approved! 🎉` (`type = 'partner'`)
- **`admin/partner_requests.php` Lines 23–38**:
  When admin approves a request:
  - Sets `partner_requests.status = 'approved'`
  - Sets `users.role = 'partner'`
  - Sets `partners.status = 'approved', is_hidden = 0`

### 1.6 Database Schema Verification
Verified via direct SQLite PRAGMA and `config.php` inspection:
1. `partners`:
   - `id` (INTEGER, PK, Auto-increment)
   - `user_id` (INTEGER, Default 0)
   - `business_name` (TEXT, NOT NULL)
   - `owner_name` (TEXT, NOT NULL)
   - `email` (TEXT)
   - `phone` (TEXT, NOT NULL)
   - `status` (TEXT: `'pending'`, `'approved'`, `'suspended'`)
   - `registration_number` (TEXT, e.g. `'FS-SHOP-00001'`)
   - `category` (TEXT)
   - `shop_slug` (TEXT)
   - `created_at` (DATETIME)
2. `partner_requests`:
   - `id` (INTEGER, PK, Auto-increment)
   - `user_id` (INTEGER, NOT NULL)
   - `status` (TEXT: `'pending'`, `'approved'`, `'rejected'`)
   - `admin_notes` (TEXT)
   - `created_at` (DATETIME)
3. `users`:
   - `id` (INTEGER, PK, Auto-increment)
   - `name` (TEXT)
   - `phone` (TEXT UNIQUE)
   - `email` (TEXT)
   - `role` (TEXT: `'user'`, `'partner'`, `'admin'`, `'staff'`)
   - `coins_balance` (REAL)
   - `kyc_status` (TEXT)

### 1.7 Existing Design System Tokens (`assets/css/native_mobile.css`)
Inspected `assets/css/native_mobile.css`:
- Background Void: `--mobile-bg: #0a0d1a;`
- Elevated Glass Surface: `--mobile-surface: rgba(18, 22, 43, 0.85);` with `backdrop-filter: blur(16px);`
- Modal/Drawer Elevated Surface: `--mobile-surface-elevated: rgba(26, 32, 59, 0.95);` with `backdrop-filter: blur(24px);`
- Google Stitch Amber Gold: `--mobile-gold: #f59e0b;`, `--mobile-gold-bright: #ffc174;`
- Border: `--mobile-border: rgba(255, 215, 0, 0.15);`, `--mobile-border-active: rgba(245, 158, 11, 0.45);`
- Emerald Success: `--mobile-green: #10b981;`
- Micro-interaction Active Compression:
  ```css
  button:active, .btn:active, .product-card:active, .chip-pill:active, .dock-item:active, .tap-card:active {
    transform: scale(0.96) !important;
    transition: transform 0.12s cubic-bezier(0.4, 0, 0.2, 1) !important;
  }
  ```
- Native Bottom Sheet Drawer:
  `.bottom-sheet-backdrop`, `.bottom-sheet`, `.bottom-sheet-handle`
- *Key Finding:* `assets/css/native_mobile.css` is currently loaded ONLY on `home.php` and `includes/nav_public.php`. It is NOT loaded in `user/dashboard.php`, `includes/user_sidebar.php`, `partner/dashboard.php`, `partner/nav.php`, or `user/create_shop.php`.

---

## 2. Logic Chain

From the observations above, the following logical inferences emerge:

1. **Fractured User Shop State Detection**:
   - `includes/user_sidebar.php` relies on external variable `$partnerInfo` which is only set in `user/dashboard.php`.
   - `user/dashboard.php` treats `status = 'pending'` identically to `status = 'approved'`, rendering full seller controls.
   - `partner/nav.php` generates an in-memory dummy partner with `status = 'pending'` when no shop is found, but mobile CSS hides the badge, and `partner/dashboard.php` shows "Verified Partner Store".
   - *Inference:* The platform requires a single, authoritative, self-contained PHP helper: `getUserShopState($pdo, $userId, $userPhone)` that queries both `partners` and `partner_requests` and returns one of three discrete states:
     - `NO_SHOP`: No active, approved, or pending shop.
     - `SHOP_PENDING`: Partner application registered, awaiting admin approval.
     - `SHOP_APPROVED`: Approved partner with active store.

2. **Frictionless 1-Tap Onboarding Bottom Sheet vs Legacy Redirect**:
   - `user/create_shop.php` requires filling a lengthy form and uploading files. This causes high drop-off for casual buyers wanting to test selling.
   - `partner/dashboard.php` (lines 41–58) already proves that a shop can be provisioned with just `business_name`, `phone`, and `category`.
   - When in User Panel and state is `NO_SHOP`, the mode switcher should display `[ ➕ Open Free Shop ]`.
   - Tapping it should NOT navigate away. It should slide up a Google Stitch Nocturne Aurum bottom sheet (`.bottom-sheet`):
     - Autofocused shop name input
     - Category dropdown
     - Pre-filled readonly badges for Owner Name, Phone, Email, and Payout Method
     - 1-tap submit button `[ 🚀 Launch Free Shop ]` (active scale 0.96)
     - Link to `user/create_shop.php` for optional document uploads.
   - On submission via AJAX to `api/quick_shop_register.php` (or inline POST), it creates the `partners` entry with `status = 'pending'`, fires a user notification, and transitions the mode switcher to `[ ⏳ Shop Under Review ]` with zero layout jump.

3. **Pending Review State & Live Status Inspection**:
   - In `includes/nav_public.php`, pending currently uses `alert('Your shop is pending admin approval.');` which violates modern UX standards.
   - In `user/dashboard.php` and `partner/dashboard.php`, pending state has no informative explanation.
   - When state is `SHOP_PENDING`, the mode switcher pill should display:
     `[ ⏳ Shop Under Review ]` with an animated pulsating amber border.
   - Tapping this pill triggers a high-fidelity **Live Review Bottom Sheet / Modal**:
     - Visual badge: `🟡 Pending Admin Verification`
     - Shop Name: `⚡ {business_name}`
     - Registration Number: `{registration_number}`
     - Submission Date & Time
     - 3-Step Interactive Progress Stepper:
       1. Application Submitted (✅ Green)
       2. Compliance & Identity Review (⏳ Amber Pulsing)
       3. Storefront Activation & Catalog Live (⚪ Inactive Gray)
     - SLA Notice: "Applications are reviewed within 24 to 48 hours."
     - 1-Tap Fast-Track Support: `[ 💬 WhatsApp Support Escalation ]` pre-filling the registration number and shop name.
   - If a pending user directly visits `/partner/dashboard.php`, a prominent Nocturne Aurum Warning Banner is displayed at the top:
     `[ ⏳ Your shop application for "{business_name}" is currently under review. Selling tools will activate upon approval. ]`
     and public storefront links are disabled.

4. **1-Tap Mode Switcher Architecture & Synchronization**:
   - In User Panel:
     - Top Navbar (`includes/user_sidebar.php`): Insert high-visibility mode pill on right side:
       - Approved: `[ 🏪 Shop Mode ➔ ]` (teleports to `/partner/dashboard.php`)
       - Pending: `[ ⏳ Under Review ]` (opens review modal)
       - None: `[ ➕ Open Shop ]` (opens quick shop bottom sheet)
     - Bottom Dock (`user/dashboard.php` & `includes/user_sidebar.php`):
       Replace static center item or slot 3 with dynamic Mode Switcher Pill.
   - In Shop Panel:
     - Top Navbar (`partner/nav.php`): Insert high-visibility mode pill:
       `[ 👤 Buyer Mode ➔ ]` (teleports to `/user/dashboard.php`)
     - Bottom Dock (`partner/nav.php`):
       Insert `[ 👤 Buyer Mode ]` into center slot or synchronized position.
   - Tapping either teleport switcher preserves active session, switches context with 0ms delay, and provides instant tactile feedback via `transform: scale(0.96)`.

5. **Google Stitch Nocturne Aurum Visual Styling**:
   - Load `/assets/css/native_mobile.css` in both User and Shop panels.
   - Apply consistent styling: `#0A0D1A` background, `rgba(18, 22, 43, 0.85)` frosted glass with `backdrop-filter: blur(16px)`, luminous gold `#F59E0B` highlights, and active tap compression `transform: scale(0.96)`.
   - Maintain 44px+ minimum touch targets and safe-area insets (`env(safe-area-inset-top)`, `env(safe-area-inset-bottom)`).

---

## 3. Caveats

1. **Local SQLite vs Hostinger MySQL**:
   - In SQLite (`fast_site_local.db`), `partners.user_id` and `partners.phone` are both present, but some seeded shops have `user_id = 0` or `1`. Queries must always check `(user_id = :uid OR phone = :phone)`.
   - On Hostinger MySQL, `ENUM('pending', 'approved', 'suspended')` is used for `partners.status`. Values inserted must be lowercase `'pending'`, `'approved'`, `'suspended'`.
2. **Session Impersonation**:
   - When an Admin uses "SSO Teleport" (`admin/impersonate_official.php`), `$_SESSION['is_impersonating']` is active. Switching between Buyer Mode and Shop Mode must preserve impersonation session state without logging out the admin.
3. **Multiple Shops (`allow_extra_shop`)**:
   - If a user has `users.allow_extra_shop = 1`, they may have more than one shop. The primary query should order by `id DESC LIMIT 1`.

---

## 4. Conclusion

The current shop onboarding and review mechanism in Fast Site suffers from:
1. Fragmented status queries across different files.
2. Premature active status rendering for pending shops in `user/dashboard.php`.
3. An intimidating, multi-step registration form in `user/create_shop.php` that lacks an inline 1-tap mobile experience.
4. Complete absence of a 1-tap Buyer Mode ⇄ Shop Mode switcher in both top navigation bars and bottom docks.
5. Incomplete deployment of Google Stitch Nocturne Aurum tokens in user and partner templates.

By introducing:
- A standardized `getUserShopState()` helper function in `config.php` (or navigation partials)
- A 30-second 1-tap Google Stitch bottom sheet for shopless users
- A comprehensive `[ ⏳ Shop Under Review ]` status pill with interactive live progress sheet
- Synchronized 1-tap mode switcher pills in both top bars and bottom docks
- Universal inclusion of `assets/css/native_mobile.css` across both portals

Fast Site can achieve a native mobile application feel on par with Airbnb and Fiverr, fulfilling Phase 87 requirements.

---

## 5. Concrete Recommendations & Technical Specifications

### Component 1: Unified Helper Function (`config.php` or `includes/user_sidebar.php`)
```php
function getUserShopState(PDO $pdo, int $userId, string $userPhone): array {
    $result = [
        'state'     => 'none', // 'none' | 'pending' | 'approved'
        'shop'      => null,
        'request'   => null,
        'shop_name' => 'My Shop',
        'shop_id'   => 0
    ];

    if ($userId <= 0) return $result;

    try {
        // 1. Check partners table
        $stmt = $pdo->prepare("SELECT * FROM partners WHERE (user_id = :uid OR (phone = :phone AND :phone != '')) AND (status IS NULL OR status != 'suspended') ORDER BY id DESC LIMIT 1");
        $stmt->execute([':uid' => $userId, ':phone' => $userPhone]);
        $partner = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($partner) {
            $status = strtolower($partner['status'] ?? 'pending');
            $result['shop']      = $partner;
            $result['shop_name'] = $partner['business_name'] ?: 'My Shop';
            $result['shop_id']   = (int)$partner['id'];

            if ($status === 'approved') {
                $result['state'] = 'approved';
                return $result;
            } elseif ($status === 'pending') {
                $result['state'] = 'pending';
                return $result;
            }
        }

        // 2. Check partner_requests table
        $rStmt = $pdo->prepare("SELECT * FROM partner_requests WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
        $rStmt->execute([':uid' => $userId]);
        $req = $rStmt->fetch(PDO::FETCH_ASSOC);

        if ($req) {
            $result['request'] = $req;
            if (strtolower($req['status']) === 'pending') {
                $result['state'] = 'pending';
                return $result;
            } elseif (strtolower($req['status']) === 'approved') {
                $result['state'] = 'approved';
                return $result;
            }
        }
    } catch (Exception $e) {}

    return $result;
}
```

### Component 2: Mode Switcher Top Header Pill Specifications
#### In User Panel (`includes/user_sidebar.php`):
- HTML Structure:
```php
<?php
$shopStateData = getUserShopState($pdo, (int)$_SESSION['user_id'], $user['phone'] ?? '');
$shopState = $shopStateData['state'];
?>
<?php if ($shopState === 'approved'): ?>
    <a href="/partner/dashboard.php" class="fs-mode-pill fs-mode-shop" title="Switch to Shop Mode">
        <span class="pill-icon">🏪</span>
        <span class="pill-label">Shop Mode</span>
        <span class="pill-arrow">➔</span>
    </a>
<?php elseif ($shopState === 'pending'): ?>
    <button type="button" onclick="openShopReviewModal()" class="fs-mode-pill fs-mode-pending" title="Shop Application Under Review">
        <span class="pill-icon pulse-gold">⏳</span>
        <span class="pill-label">Shop In Review</span>
    </button>
<?php else: ?>
    <button type="button" onclick="openQuickShopDrawer()" class="fs-mode-pill fs-mode-create" title="Open Your Free Shop">
        <span class="pill-icon">➕</span>
        <span class="pill-label">Open Free Shop</span>
    </button>
<?php endif; ?>
```

#### In Shop Panel (`partner/nav.php`):
- HTML Structure:
```php
<a href="/user/dashboard.php" class="fs-mode-pill fs-mode-buyer" title="Switch to Buyer Mode">
    <span class="pill-icon">👤</span>
    <span class="pill-label">Buyer Mode</span>
    <span class="pill-arrow">➔</span>
</a>
```

### Component 3: Google Stitch Nocturne Aurum CSS Tokens & Pill Styling
To be included in `assets/css/native_mobile.css`:
```css
/* ─── Mode Switcher Pills (Google Stitch Nocturne Aurum) ─── */
.fs-mode-pill {
  display: inline-flex !important;
  align-items: center !important;
  gap: 6px !important;
  padding: 6px 14px !important;
  border-radius: 9999px !important;
  font-family: 'Inter', sans-serif !important;
  font-size: 0.76rem !important;
  font-weight: 800 !important;
  text-decoration: none !important;
  text-transform: uppercase !important;
  letter-spacing: 0.04em !important;
  min-height: 38px !important;
  box-sizing: border-box !important;
  backdrop-filter: blur(16px) !important;
  -webkit-backdrop-filter: blur(16px) !important;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
  cursor: pointer !important;
  border: 1px solid transparent !important;
}

.fs-mode-pill:active {
  transform: scale(0.96) !important;
}

/* Shop Mode Switcher */
.fs-mode-shop {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.18), rgba(217, 119, 6, 0.28)) !important;
  border-color: rgba(245, 158, 11, 0.45) !important;
  color: #f59e0b !important;
  box-shadow: 0 0 14px rgba(245, 158, 11, 0.22) !important;
}

/* Buyer Mode Switcher */
.fs-mode-buyer {
  background: linear-gradient(135deg, rgba(33, 150, 243, 0.18), rgba(0, 123, 181, 0.28)) !important;
  border-color: rgba(33, 150, 243, 0.45) !important;
  color: #60a5fa !important;
  box-shadow: 0 0 14px rgba(33, 150, 243, 0.22) !important;
}

/* Pending Review Status Pill */
.fs-mode-pending {
  background: rgba(245, 158, 11, 0.12) !important;
  border: 1px dashed rgba(245, 158, 11, 0.6) !important;
  color: #f59e0b !important;
  animation: pulsePillBorder 2.4s infinite ease-in-out !important;
}

/* Create Shop Pill */
.fs-mode-create {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.18), rgba(5, 150, 105, 0.28)) !important;
  border-color: rgba(16, 185, 129, 0.45) !important;
  color: #34d399 !important;
  box-shadow: 0 0 14px rgba(16, 185, 129, 0.2) !important;
}

@keyframes pulsePillBorder {
  0%, 100% {
    border-color: rgba(245, 158, 11, 0.4);
    box-shadow: 0 0 8px rgba(245, 158, 11, 0.15);
  }
  50% {
    border-color: rgba(245, 158, 11, 0.9);
    box-shadow: 0 0 18px rgba(245, 158, 11, 0.4);
  }
}
```

### Component 4: Synchronized 5-Slot Bottom Dock
#### User Panel Bottom Dock (`user/dashboard.php`):
1. **Store** (`/index.php`)
2. **Orders** (`/user/dashboard.php?tab=orders`) with dynamic badge
3. **Mode Switcher (Center Highlight)**:
   - Approved: `[ 🏪 Shop ]` (navigates to `/partner/dashboard.php`)
   - Pending: `[ ⏳ Review ]` (triggers review modal)
   - None: `[ ➕ Shop ]` (triggers quick shop bottom sheet)
4. **Wallet** (`/user/wallet.php`)
5. **Account / Menu** (`toggleSidebar()` or `/user/profile.php`)

#### Shop Panel Bottom Dock (`partner/nav.php`):
1. **Hub** (`/partner/dashboard.php`)
2. **Orders** (`/partner/orders.php`) with active order badge
3. **Mode Switcher (Center Highlight)**:
   - `[ 👤 Buyer ]` (1-tap switches to `/user/dashboard.php`)
4. **Catalog** (`/partner/products.php`)
5. **Menu** (`openNavDrawer()`)

### Component 5: 1-Tap Frictionless Quick Shop Bottom Sheet
Implemented via clean HTML drawer in `includes/user_sidebar.php`:
```html
<div class="bottom-sheet-backdrop" id="quickShopBackdrop" onclick="closeQuickShopDrawer()"></div>
<div class="bottom-sheet" id="quickShopDrawer">
  <div class="bottom-sheet-handle"></div>
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
    <h3 style="margin:0; font-size:1.2rem; color:#fff; display:flex; align-items:center; gap:8px;">
      <span>🏪</span> Open Your Free Shop
    </h3>
    <button type="button" onclick="closeQuickShopDrawer()" style="background:none; border:none; color:#94a3b8; font-size:1.4rem; cursor:pointer;">&times;</button>
  </div>
  <p style="color:#94a3b8; font-size:0.85rem; margin-top:0; margin-bottom:1.2rem;">
    Setup in 30 seconds. Start selling directly on Fast Site Escrow Marketplace.
  </p>
  
  <form id="quickShopForm" onsubmit="handleQuickShopSubmit(event)">
    <div style="margin-bottom:1rem;">
      <label style="display:block; font-size:0.75rem; font-weight:700; color:#94a3b8; text-transform:uppercase; margin-bottom:6px;">Shop / Business Name *</label>
      <input type="text" name="business_name" required placeholder="e.g. Dhaka Tech Store" style="width:100%; background:rgba(10,13,26,0.9); border:1px solid rgba(255,255,255,0.15); color:#fff; padding:0.8rem 1rem; border-radius:12px; font-size:0.92rem; outline:none;"/>
    </div>
    <div style="margin-bottom:1.2rem;">
      <label style="display:block; font-size:0.75rem; font-weight:700; color:#94a3b8; text-transform:uppercase; margin-bottom:6px;">Primary Category *</label>
      <select name="category" style="width:100%; background:rgba(10,13,26,0.9); border:1px solid rgba(255,255,255,0.15); color:#fff; padding:0.8rem 1rem; border-radius:12px; font-size:0.92rem; outline:none; cursor:pointer;">
        <option value="Retail & E-commerce">Retail &amp; E-commerce</option>
        <option value="Digital Services & Software">Digital Services &amp; Software</option>
        <option value="Fashion & Apparel">Fashion &amp; Apparel</option>
        <option value="Electronics & Gadgets">Electronics &amp; Gadgets</option>
        <option value="Official Services">Official Services</option>
      </select>
    </div>
    <div style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); padding:0.8rem; border-radius:10px; font-size:0.78rem; color:#94a3b8; margin-bottom:1.2rem;">
      👤 Owner: <strong style="color:#fff;"><?= htmlspecialchars($user['name']) ?></strong> | 📞 Phone: <strong style="color:#fff;"><?= htmlspecialchars($user['phone']) ?></strong> | 💰 Payout: <strong style="color:var(--mobile-gold);">bKash/Nagad</strong>
    </div>
    <button type="submit" id="btnLaunchShop" class="btn" style="width:100%; background:linear-gradient(135deg, #f59e0b, #d97706); color:#000; font-weight:900; font-size:1rem; padding:0.9rem; border-radius:50px; border:none; cursor:pointer; box-shadow:0 6px 20px rgba(245,158,11,0.35);">
      🚀 Launch My Free Shop
    </button>
    <div style="text-align:center; margin-top:1rem;">
      <a href="/user/create_shop.php" style="color:#94a3b8; font-size:0.78rem; text-decoration:underline;">Advanced setup (Upload NID &amp; Trade License) ➔</a>
    </div>
  </form>
</div>
```

### Component 6: Live Status Review Bottom Sheet / Modal
```html
<div class="bottom-sheet-backdrop" id="shopReviewBackdrop" onclick="closeShopReviewModal()"></div>
<div class="bottom-sheet" id="shopReviewDrawer">
  <div class="bottom-sheet-handle"></div>
  <div style="text-align:center; padding: 0.5rem 0 1rem;">
    <div style="font-size:2.8rem; margin-bottom:0.4rem;" class="pulse-gold">⏳</div>
    <h3 style="margin:0 0 0.4rem 0; font-size:1.3rem; color:#fff; font-family:'Oswald',sans-serif;">Shop Application Under Review</h3>
    <div style="display:inline-block; background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.4); color:#f59e0b; font-size:0.75rem; font-weight:800; padding:3px 12px; border-radius:50px; text-transform:uppercase;">
      Status: Pending Admin Approval
    </div>
  </div>

  <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:1.2rem; margin-bottom:1.5rem;">
    <div style="display:flex; justify-content:space-between; margin-bottom:0.6rem; font-size:0.85rem;">
      <span style="color:#94a3b8;">Shop Name:</span>
      <strong style="color:#fff;">⚡ <?= htmlspecialchars($shopStateData['shop_name']) ?></strong>
    </div>
    <div style="display:flex; justify-content:space-between; margin-bottom:0.6rem; font-size:0.85rem;">
      <span style="color:#94a3b8;">Tracking ID:</span>
      <strong style="font-family:monospace; color:var(--mobile-gold);"><?= htmlspecialchars($shopStateData['shop']['registration_number'] ?? ('FS-APP-' . (int)$_SESSION['user_id'])) ?></strong>
    </div>
    <div style="display:flex; justify-content:space-between; font-size:0.85rem;">
      <span style="color:#94a3b8;">Estimated Review:</span>
      <strong style="color:#10b981;">24 – 48 Hours</strong>
    </div>
  </div>

  <!-- 3-Step Review Progress Stepper -->
  <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:1.5rem; padding:0 0.5rem;">
    <div style="display:flex; align-items:center; gap:12px;">
      <div style="width:28px; height:28px; border-radius:50%; background:#10b981; color:#000; font-weight:800; display:flex; align-items:center; justify-content:center; font-size:0.85rem;">✓</div>
      <div>
        <div style="color:#fff; font-size:0.85rem; font-weight:700;">1. Application Submitted</div>
        <div style="color:#94a3b8; font-size:0.72rem;">Shop profile &amp; details recorded successfully</div>
      </div>
    </div>
    <div style="display:flex; align-items:center; gap:12px;">
      <div style="width:28px; height:28px; border-radius:50%; background:rgba(245,158,11,0.2); border:2px solid #f59e0b; color:#f59e0b; font-weight:800; display:flex; align-items:center; justify-content:center; font-size:0.85rem;">2</div>
      <div>
        <div style="color:#f59e0b; font-size:0.85rem; font-weight:700;">2. Verification &amp; Security Review (Current)</div>
        <div style="color:#94a3b8; font-size:0.72rem;">Admin compliance team is verifying account credentials</div>
      </div>
    </div>
    <div style="display:flex; align-items:center; gap:12px; opacity:0.5;">
      <div style="width:28px; height:28px; border-radius:50%; background:rgba(255,255,255,0.1); color:#fff; font-weight:800; display:flex; align-items:center; justify-content:center; font-size:0.85rem;">3</div>
      <div>
        <div style="color:#fff; font-size:0.85rem; font-weight:700;">3. Storefront Live Activation</div>
        <div style="color:#94a3b8; font-size:0.72rem;">Instant product upload and escrow payments unlock</div>
      </div>
    </div>
  </div>

  <!-- Escalation WhatsApp Link -->
  <a href="https://wa.me/8801963601472?text=Hello+Fast+Site+Admin%2C+I+submitted+my+shop+application+for+<?= urlencode($shopStateData['shop_name']) ?>.+Please+check+my+review." target="_blank" style="display:flex; align-items:center; justify-content:center; gap:8px; width:100%; background:#25D366; color:#fff; font-weight:800; padding:0.85rem; border-radius:12px; text-decoration:none; font-size:0.9rem; margin-bottom:0.8rem;">
    <span>💬</span> WhatsApp Priority Review Support
  </a>
  <button type="button" onclick="closeShopReviewModal()" style="width:100%; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); color:#fff; padding:0.75rem; border-radius:12px; font-size:0.85rem; font-weight:700; cursor:pointer;">
    Continue Browsing
  </button>
</div>
```

---

## 6. Verification Method

To independently verify these findings and implementations:

1. **PHP Syntax & Linter Check**:
   ```bash
   php -l "user/create_shop.php"
   php -l "user/dashboard.php"
   php -l "includes/user_sidebar.php"
   php -l "partner/dashboard.php"
   php -l "partner/nav.php"
   php -l "config.php"
   ```
   *Expected output:* `No syntax errors detected in <file>`.

2. **Database Query Verification**:
   Inspect pending vs approved status across local SQLite or Hostinger MySQL:
   ```bash
   php -r '$p = new PDO("sqlite:fast_site_local.db"); foreach ($p->query("SELECT id, business_name, status, user_id FROM partners") as $r) { echo $r["id"]." | ".$r["business_name"]." | ".$r["status"].PHP_EOL; }'
   ```

3. **HTTP Status & Endpoint Verification**:
   Run live curl requests on `http://localhost:8000`:
   ```bash
   curl -I http://localhost:8000/
   curl -I http://localhost:8000/user/create_shop.php
   curl -I http://localhost:8000/partner/dashboard.php
   ```
   *Expected output:* HTTP 200 or clean HTTP 302 redirect.

4. **Visual & Touch Target Inspection**:
   - Verify that tapping the mode switcher pill adheres to `transform: scale(0.96)`.
   - Verify that `.bottom-sheet` slides up smoothly with grab handle and dark blurred backdrop.
   - Verify that all touch targets measure $\ge 44\text{px} \times 44\text{px}$ on mobile viewports.
