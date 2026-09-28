# Phase 87 — Shop Panel Architecture & 1-Tap Buyer Mode Switcher Investigation
**Author**: `explorer_p87_shop`  
**Date**: 2026-09-09T13:25:00Z  
**Target Subsystems**: `partner/nav.php`, `partner/dashboard.php`, `partner/orders.php`, `partner/products.php`, `partner/product_add.php`, session & authentication state.  
**Design Standard**: Google Stitch *Nocturne Aurum* Tokens (`#0A0D1A`, `rgba(18, 22, 43, 0.85)`, `#F59E0B`, `transform: scale(0.96)`, 44px+ touch targets).

---

## 1. Observation

### 1.1 Centralized Navigation Architecture (`partner/nav.php`)
Every page in the Partner / Shop Panel includes `partner/nav.php`:
- `partner/dashboard.php` (line 6)
- `partner/orders.php` (line 5)
- `partner/products.php` (line 5)
- `partner/product_add.php` (line 284)
- `partner/product_edit.php` (line 6)
- `partner/profile.php` (line 6)
- `partner/coupons.php` (line 6)
- `partner/disputes.php` (line 5)
- `partner/bid_on_job.php` (line 2)
- `partner/api_docs.php` (line 11)

**Architectural Advantage:** Any structural or visual enhancement made to `partner/nav.php` instantly propagates across 100% of the Partner / Shop Panel.

### 1.2 Current Top Navbar & Deficiencies (`partner/nav.php:451-488`)
- `<nav class="top-nav">`:
  - **Left (`.nav-left`)**: `<button class="hamburger-btn" id="menu-toggle">☰</button>` + `<a href="dashboard.php" class="top-brand">⚡ FAST SITE SHOP</a>`.
  - **Right (`.nav-right`)**: 9-dot Cross-Platform App Hub button (`.app-hub-dropdown`), status badge `<span class="partner-badge">` (hidden on mobile `<= 600px` via line 303), and 38px circular shop avatar (`.partner-avatar`).
- **Deficiencies Observed:**
  1. **Zero 1-Tap Mode Switcher in Header**: There is NO mode toggle button in the top bar. Users must open the 9-dot dropdown or hamburger drawer to find a link back to User Dashboard.
  2. **Touch Target Non-Compliance**: `.hamburger-btn` (`line 124`) and `.app-hub-dropdown button` (`line 459`) have no explicit `min-width: 44px; min-height: 44px;`.
  3. **No Persistent Back Navigation**: On subpages (`orders.php`, `products.php`, `product_add.php`, `profile.php`), the navbar still shows only `☰` and `⚡ FAST SITE SHOP`, offering no direct back navigation to `dashboard.php`.

### 1.3 Current Mobile Bottom Navigation Dock (`partner/nav.php:573-593`)
- Rendered on screens `<= 900px` (`lines 363-448`):
  ```html
  <nav class="partner-bottom-dock" aria-label="Partner Mobile Dock">
    <a href="dashboard.php" class="dock-item <?= isActive('dashboard.php', $current_page) ?>">
      <span class="icon">📊</span><span>Hub</span>
    </a>
    <a href="orders.php" class="dock-item <?= isActive('orders.php', $current_page) ?>">
      <span class="icon">📦</span><span>Orders</span>
    </a>
    <a href="product_add.php" class="dock-item-primary" title="Add Product">
      <span>➕</span>
    </a>
    <a href="products.php" class="dock-item <?= isActive('products.php', $current_page) ?>">
      <span class="icon">🛍️</span><span>Catalog</span>
    </a>
    <button type="button" onclick="openNavDrawer()" class="dock-item" title="Open Menu">
      <span class="icon">☰</span><span>Menu</span>
    </button>
  </nav>
  ```
- **Deficiencies Observed:**
  1. **Missing 1-Tap Buyer Mode Switcher**: The dock has no slot or toggle to jump back to Buyer Mode (`/user/dashboard.php`).
  2. **Redundant Slot 5**: Slot 5 is a "Menu" button that toggles `side-drawer`. However, the top navbar ALREADY has a hamburger menu button at top-left. Having "Menu" in the dock wastes 20% of thumb-friendly dock real estate.
  3. **No Order/Unread Badges**: The Orders item (`orders.php`) has no notification badge, even when orders are waiting for fulfillment.
  4. **Hardware Safe-Area Deficiency**: `.partner-bottom-dock` uses fixed `height: 62px` and lacks `padding-bottom: calc(env(safe-area-inset-bottom, 0px) + ...)` and `body` bottom padding is fixed `74px` without `env(safe-area-inset-bottom)`.
  5. **Missing Google Stitch Tokens**: `partner/nav.php` does NOT link `/assets/css/native_mobile.css`. It uses legacy `#08080c` and `#fcb900` instead of Google Stitch Nocturne Aurum tokens (`#0a0d1a`, `rgba(18, 22, 43, 0.85)`, `#f59e0b`).

### 1.4 Current Side Drawer (`partner/nav.php:506-567`)
- Line 516 features:
  `<a href="../user/dashboard.php" class="drawer-link" style="... color:#00e676; ..."><span>🏠</span> Return to User Dashboard</a>`
- Uses relative path `../user/dashboard.php` rather than clean root path `/user/dashboard.php`.
- Uses generic green styling instead of Google Stitch Buyer Mode tokens (Sky Blue / Cyan `#38bdf8` or Nocturne Aurum Gold).

### 1.5 Session & Auth Flow Verification
- `user/login.php:19` authenticates the user and sets `$_SESSION['user_id'] = $user['id']`.
- `partner/nav.php:8-10` verifies `$_SESSION['user_id']`. If valid, it queries `partners` table by `user_id` or `phone` and sets `$_SESSION['partner_id']`.
- `user/dashboard.php:6` checks `$_SESSION['user_id']`.
- **Finding**: Both portals share the exact same PHP session cookie (`PHPSESSID`) and master session key `$_SESSION['user_id']`. Switching between `/partner/dashboard.php` and `/user/dashboard.php` requires ZERO re-authentication, ZERO token exchange, and creates ZERO session key conflicts.

### 1.6 Critical Bug: Dead-End 404 Redirections to `/partner/login.php`
- Seven files across the partner module attempt to redirect unauthenticated or invalid sessions to `/partner/login.php`:
  1. `partner/index.php:13`
  2. `partner/logout.php:9`
  3. `partner/product_add.php:17`
  4. `partner/product_edit.php:10`
  5. `partner/product_delete.php:7`
  6. `partner/profile.php:10`
  7. `partner/api_docs.php:7`
- Empirical Verification via Live Host (`http://localhost:8000/partner/login.php`):
  `HTTP/1.1 404 Not Found`. The file does not exist.
  Visiting `/partner/index.php` directly while logged out triggers an immediate HTTP 404 dead-end. All these files MUST point to `/user/login.php`.

---

## 2. Logic Chain

1. **Session Equivalence Proof**:
   - Because `$_SESSION['user_id']` is universally maintained across all user and partner routes, navigating between Buyer Mode (`/user/dashboard.php`) and Shop Mode (`/partner/dashboard.php`) is a direct, stateless HTTP GET link.
   - It requires 0ms friction, no intermediate confirmation screens, and no session mutations.

2. **Dual Switcher Placement Strategy (Top Header + Floating Bottom Dock)**:
   - **Top Header Bar**: On desktop/tablets, user eye gaze is focused on the header. On mobile, the header is visible during initial page load. Embedding a high-visibility, 1-tap pill `[ 👤 Switch to Buyer Mode ]` in the top navbar (`.nav-right`) guarantees visibility regardless of scroll depth.
     - Styling: Glassmorphism pill with sky-blue/cyan accents (`#38bdf8`, `rgba(56, 189, 248, 0.15)`) to distinctly contrast with the shop's gold aura.
     - Responsive behavior: Displays `👤 Switch to Buyer Mode` on desktop (`> 600px`) and compact `👤 Buyer` on mobile (`<= 600px`).
   - **Floating Bottom Navigation Dock**: On mobile, the thumb zone is the bottom 25% of the screen.
     - Repurposing Slot 5 from the redundant "Menu" button into `[ 👤 Buyer Mode ]` (`href="/user/dashboard.php"`) provides permanent 1-tap thumb switching.
     - The merchant's primary center FAB `➕ Add Product` is retained in Slot 3.
     - Alternatively, if symmetric center switching is desired (User Panel Center = Shop Mode; Shop Panel Center = Buyer Mode), Slot 3 can be the Mode Switcher and `+ Add` is kept in Slot 4 or the top bar. **Recommendation**: Slot 5 is cleaner because merchant daily workflows rely heavily on `+ Add Product` in the center thumb zone.

3. **Bottom Dock 5-Slot Synchronization**:
   To align with the User Panel's bottom dock under Google Stitch Nocturne Aurum:
   | Slot | Shop Panel Dock | User Panel Dock | Parity Role |
   |---|---|---|---|
   | **1** | 📊 **Hub** (`dashboard.php`) | 🏪 **Store / Home** (`/index.php`) | Home / Primary Hub |
   | **2** | 📦 **Orders** (`orders.php` + Badge) | 📦 **Orders** (`/user/dashboard.php?tab=orders` + Badge) | Order Management & Fulfillment |
   | **3** | ➕ **Add** (`product_add.php` FAB) | 🪙 **Wallet / Cash Out** (`/user/wallet.php`) | Primary Action FAB |
   | **4** | 🛍️ **Products** (`products.php`) | 💬 **Messages / Chat** (`/user/messages.php`) | Core Feature Link |
   | **5** | 👤 **Buyer Mode** (`/user/dashboard.php`) | 🏪 **Shop Mode** (`/partner/dashboard.php`) | **1-Tap Cross-Mode Teleport** |

4. **Real-time Order Badge Integration**:
   - In `partner/nav.php`, execute a lightweight indexed count query:
     `SELECT COUNT(*) FROM partner_orders WHERE partner_id = ? AND status IN ('pending', 'accepted', 'in_progress', 'waiting_confirmation')`
   - Render a glowing red/gold notification badge (`.dock-badge`) on Slot 2 (`Orders`) and inside the side drawer.

5. **Persistent Back Navigation on Subpages**:
   - Subpages (`orders.php`, `products.php`, `product_add.php`, `product_edit.php`, `profile.php`, `coupons.php`, `disputes.php`) currently trap users unless they use the bottom dock.
   - In `partner/nav.php`, when `$current_page !== 'dashboard.php'`, render a dedicated 44px `[ ← ]` back chevron in `.nav-left` linking to `dashboard.php`.

---

## 3. Caveats

1. **Shopless or Pending Merchant State**:
   - If a customer visits `/partner/dashboard.php` without an approved shop:
     - `partner/nav.php:40-51` initializes `$partner['status'] = 'pending'`.
     - `partner/product_add.php:287` displays `⏳ Shop Under Review` and prevents product uploads.
     - The top header and bottom dock must still render cleanly so the user can tap `[ 👤 Switch to Buyer Mode ]` to return to safety without getting stuck.
2. **Safe-Area Insets on Notch Devices**:
   - If `env(safe-area-inset-bottom)` is omitted, bottom dock items collide with Android gesture pills and iOS home bars. Both `height` and `padding-bottom` must incorporate safe area insets.
3. **Screen Size Breakpoint Gap**:
   - `partner/nav.php` currently uses `max-width: 900px` for the bottom dock, while `assets/css/native_mobile.css` uses `max-width: 768px`, and the side drawer closes at `1024px`. Standardizing to `max-width: 900px` (or unifying with `native_mobile.css`) avoids tablet layout clipping.

---

## 4. Conclusion

1. **Zero Session Friction**: Mode switching between Shop Mode and Buyer Mode is completely frictionless. Both portals read `$_SESSION['user_id']`. A simple `<a href="/user/dashboard.php">` switch pill operates instantaneously with 0ms overhead and zero session degradation.
2. **Dual Placement is Essential**: Placing the 1-tap pill in BOTH the Top Header Bar (`.top-nav`) and Floating Bottom Navigation Dock (`.partner-bottom-dock`) fulfills Airbnb/Fiverr parity across both mobile and desktop.
3. **Single Point of Implementation**: Because `partner/nav.php` is globally required by all 10 partner subpages, upgrading `partner/nav.php` updates the top navbar, bottom dock, touch targets, badges, and back navigation across the entire Shop Panel simultaneously.
4. **Dead-End 404 Bug Resolution**: Fixing the 7 occurrences of `/partner/login.php` to `/user/login.php` eliminates fatal 404 traps for unauthenticated or session-expired users.

---

## 5. Concrete Code Recommendations

### Recommendation 1: Top Header Bar Switcher & Persistent Back (`partner/nav.php`)
**Target**: `partner/nav.php:451-488`

```php
<!-- Top Navbar -->
<nav class="top-nav">
  <div class="nav-left" style="display:flex; align-items:center; gap:8px;">
    <?php if ($current_page !== 'dashboard.php'): ?>
      <a href="dashboard.php" class="nav-back-btn" title="Back to Shop Overview" style="width:44px; height:44px; min-width:44px; min-height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); color:#fff; text-decoration:none; transition:all 0.2s;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
      </a>
    <?php endif; ?>
    <button class="hamburger-btn" id="menu-toggle" aria-label="Open Navigation Menu" style="width:44px; height:44px; min-width:44px; min-height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:10px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); color:var(--text); cursor:pointer;">☰</button>
    <a href="dashboard.php" class="top-brand" style="display:inline-flex; align-items:center; gap:6px;">
      <span>⚡ FAST SITE SHOP</span>
    </a>
  </div>

  <div class="nav-right" style="display:flex; align-items:center; gap:8px;">
    <!-- 1-Tap Buyer Mode Switcher Pill in Top Bar -->
    <a href="/user/dashboard.php" class="header-mode-pill" title="Switch to Buyer Mode">
      <span class="mode-pill-icon">👤</span>
      <span class="mode-pill-text-desktop">Switch to Buyer Mode</span>
      <span class="mode-pill-text-mobile">Buyer</span>
    </a>

    <!-- Cross-Platform Hub Dropdown -->
    <div style="position:relative;" class="app-hub-dropdown">
      <button onclick="document.getElementById('app-hub-menu').classList.toggle('show-hub')" style="width:44px; height:44px; min-width:44px; min-height:44px; display:inline-flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:var(--text); cursor:pointer;" title="App Hub">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M4 4h4v4H4V4zm6 0h4v4h-4V4zm6 0h4v4h-4V4zM4 10h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4zM4 16h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4z"/></svg>
      </button>
      <!-- app-hub-menu contents -->
    </div>
    
    <img src="<?= $partner['profile_pic'] ? '/uploads/partners/' . htmlspecialchars($partner['profile_pic']) : '/assets/img/fast site logo only.jpeg' ?>" alt="Logo" class="partner-avatar" style="width:38px; height:38px; border-radius:50%; object-fit:cover; border:2px solid var(--brand);"/>
  </div>
</nav>
```

### Recommendation 2: Aligned 5-Slot Bottom Navigation Dock (`partner/nav.php`)
**Target**: `partner/nav.php:573-593`

```php
<!-- Mobile Bottom Navigation Dock (Google Stitch Nocturne Aurum Standards) -->
<nav class="partner-bottom-dock" aria-label="Partner Mobile Dock">
  <!-- Slot 1: Shop Hub -->
  <a href="dashboard.php" class="dock-item <?= isActive('dashboard.php', $current_page) ?>">
    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
    <span>Hub</span>
  </a>

  <!-- Slot 2: Customer Orders with Live Badge -->
  <a href="orders.php" class="dock-item <?= isActive('orders.php', $current_page) ?>" style="position:relative;">
    <div style="position:relative; display:inline-flex;">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>
      <?php if (!empty($partner_pending_orders_count) && $partner_pending_orders_count > 0): ?>
        <span class="dock-badge-counter"><?= $partner_pending_orders_count > 99 ? '99+' : $partner_pending_orders_count ?></span>
      <?php endif; ?>
    </div>
    <span>Orders</span>
  </a>

  <!-- Slot 3: Center FAB - Add Product -->
  <a href="product_add.php" class="dock-item-primary" title="Add Product">
    <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
  </a>

  <!-- Slot 4: My Products Catalog -->
  <a href="products.php" class="dock-item <?= isActive('products.php', $current_page) ?>">
    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M19 6h-2c0-2.76-2.24-5-5-5S7 3.24 7 6H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-3c1.66 0 3 1.34 3 3H9c0-1.66 1.34-3 3-3zm7 17H5V8h14v12z"/></svg>
    <span>Catalog</span>
  </a>

  <!-- Slot 5: 1-Tap Buyer Mode Switcher -->
  <a href="/user/dashboard.php" class="dock-item dock-item-buyer" title="Switch to Buyer Mode">
    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
    <span>Buyer Mode</span>
  </a>
</nav>
```

### Recommendation 3: Google Stitch Nocturne Aurum CSS Tokens (`partner/nav.php`)
```css
/* Google Stitch Nocturne Aurum Mode Switcher & Dock Styling */
.header-mode-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(33, 150, 243, 0.12);
  border: 1px solid rgba(56, 189, 248, 0.35);
  color: #38bdf8;
  font-size: 0.78rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 8px 14px;
  border-radius: 50px;
  text-decoration: none;
  min-height: 44px;
  box-sizing: border-box;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 10px rgba(33, 150, 243, 0.15);
}
.header-mode-pill:active {
  transform: scale(0.96);
}
.header-mode-pill:hover {
  background: rgba(33, 150, 243, 0.22);
  border-color: rgba(56, 189, 248, 0.6);
  box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
}

.mode-pill-text-mobile { display: none; }
@media (max-width: 600px) {
  .mode-pill-text-desktop { display: none; }
  .mode-pill-text-mobile { display: inline; font-size: 0.75rem; }
  .header-mode-pill { padding: 6px 10px; }
}

/* Bottom Dock Safe Area Clearance */
.partner-bottom-dock {
  background: rgba(10, 13, 26, 0.94) !important;
  backdrop-filter: blur(24px) !important;
  -webkit-backdrop-filter: blur(24px) !important;
  border-top: 1px solid rgba(245, 158, 11, 0.2) !important;
  padding: 6px 8px calc(env(safe-area-inset-bottom, 0px) + 6px) !important;
  height: calc(64px + env(safe-area-inset-bottom, 0px)) !important;
  box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.6) !important;
}

.dock-item-buyer {
  color: #38bdf8 !important;
}
.dock-item-buyer:hover, .dock-item-buyer:active {
  color: #7dd3fc !important;
}

.dock-badge-counter {
  position: absolute;
  top: -4px;
  right: -8px;
  background: #ff5252;
  color: #fff;
  font-size: 0.62rem;
  font-weight: 800;
  min-width: 17px;
  height: 17px;
  line-height: 17px;
  border-radius: 9999px;
  text-align: center;
  padding: 0 4px;
  box-shadow: 0 0 8px rgba(255, 82, 82, 0.6);
}
```

### Recommendation 4: Quick Action Bar Addition (`partner/dashboard.php:707`)
Add direct `👤 Switch to Buyer Mode` button to the executive hero quick action bar:
```html
<a href="/user/dashboard.php" class="btn-action-hero" style="background:rgba(33,150,243,0.15); border:1px solid rgba(56,189,248,0.4); color:#38bdf8;">
  👤 Switch to Buyer Mode
</a>
```

### Recommendation 5: Fix 7 Dead-End `/partner/login.php` Redirections
Replace all occurrences of `header('Location: /partner/login.php');` with `header('Location: /user/login.php');`:
- `partner/index.php:13`
- `partner/logout.php:9`
- `partner/product_add.php:17`
- `partner/product_edit.php:10`
- `partner/product_delete.php:7`
- `partner/profile.php:10`
- `partner/api_docs.php:7`

---

## 6. Verification Method

1. **Syntax & Lint Verification**:
   Execute PHP lint command across updated files:
   ```powershell
   php -l partner/nav.php
   php -l partner/dashboard.php
   php -l partner/orders.php
   php -l partner/products.php
   php -l partner/product_add.php
   ```
2. **Local Live Host Routing Test**:
   Ensure local server is running on `http://localhost:8000`:
   ```powershell
   curl.exe -I -s http://localhost:8000/partner/dashboard.php
   curl.exe -I -s http://localhost:8000/partner/index.php
   ```
   Confirm `/partner/index.php` redirects to `/user/login.php` (HTTP 302) instead of 404.
3. **Viewport & Touch Target Verification**:
   - Inspect top navbar on `<= 390px` mobile viewport: verify `[ 👤 Buyer ]` fits with logo without wrapping.
   - Inspect floating bottom dock: verify 5 items are evenly spaced, all touch targets `>= 44px`, tap triggers `transform: scale(0.96)`.
   - Tap `[ 👤 Switch to Buyer Mode ]`: verify immediate teleport to `/user/dashboard.php` with 0ms delay and zero session prompt.
