# Handoff Report: User Panel Architecture & 1-Tap Mode Switcher Survey

## 1. Observation

### Current Top Navigation Bar
- **File**: `includes/user_sidebar.php` (lines 147–159, 230–258, 270–307)
- **Container**: `<div class="top-nav">` fixed at `top: 0; left: 0; right: 0; height: 60px; z-index: 1000;`, styled with `background: rgba(13, 13, 20, 0.95); backdrop-filter: blur(20px); border-bottom: 1px solid var(--border);`.
- **Current Three-Slot Grid**:
  - **Left Slot** (lines 275–283): Hamburger button (`<button class="hamburger" onclick="toggleSidebar()">` with 44x44px target) followed by `<a href="/index.php">Store</a>` with back arrow.
  - **Center Slot** (lines 286–291): Centered logo link (`<a href="/index.php" class="nav-logo">`) containing `<img>` (36px max-height) and gradient wordmark `<span class="nav-logo-text-user">FAST SITE</span>`.
  - **Right Slot** (lines 294–306): Notification bell button (`<button onclick="toggleNotificationDrawer()">` with 44x44px target and red badge dot if `$user_unread_notif_count > 0`) and Messages link (`<a href="/user/messages.php">` with 44x44px target).
- **Observation on Mode Switcher in Top Bar**:
  - Currently, there is **no mode toggle pill** in `includes/user_sidebar.php`.
  - The left slot has a redundant `<a href="/index.php">Store</a>` link, which duplicates the bottom dock's Position 1 ("Store") and side drawer's "Storefront Home".

### Current Floating Bottom Navigation Dock
- **File**: `user/dashboard.php` (lines 1596–1654)
- **Container**: `<div class="bottom-nav mobile-only-bottom-nav" id="user-floating-bottom-nav">`
- **CSS**: `assets/css/user.css` (lines 399–460) and `assets/css/native_mobile.css` (lines 227–273).
  - Floating pill: `bottom: 15px; left: 50%; transform: translateX(-50%); width: 94%; max-width: 440px; height: 65px; border-radius: 35px; z-index: 990;`.
  - Glassmorphic styling: `background: rgba(13, 13, 20, 0.94); backdrop-filter: blur(25px); border: 1px solid rgba(252, 185, 0, 0.25);`.
  - Desktop suppression: `@media (min-width: 1025px) { .mobile-only-bottom-nav, .bottom-nav { display: none !important; } }`.
- **Current Five Items**:
  1. `Store` (`/index.php`) — lines 1601–1606
  2. `Orders` (`/user/dashboard.php?tab=orders`) with active orders badge (`$activeOrders`) — lines 1609–1619
  3. `Wallet` (`/user/wallet.php`) with gold highlight — lines 1622–1627
  4. `Alerts` (`toggleNotificationDrawer()`) with unread red indicator — lines 1630–1640
  5. `Profile` (`/user/profile.php`) with circular avatar and gold border — lines 1643–1652
- **Observation on Mode Switcher in Bottom Dock**:
  - The bottom dock currently has **no mode switcher**.
  - Slot 4 ("Alerts") is redundant because the Top Header Bar already provides a prominent 44x44px notification bell with unread badge in the top right.

### User Shop / Partner Status Detection
- **File**: `user/dashboard.php` (lines 137–151)
  ```php
  $isPartner = false;
  $partnerInfo = null;
  $partner_products = [];
  $p_stmt = $pdo->prepare("SELECT * FROM partners WHERE (user_id = :uid OR phone = :p) AND (status IS NULL OR status != 'suspended') ORDER BY id DESC LIMIT 1");
  $p_stmt->execute([':uid' => $userId, ':p' => $user['phone']]);
  if ($partnerRow = $p_stmt->fetch()) {
      $isPartner = true;
      $partnerInfo = $partnerRow;
  ```
- **File**: `includes/user_sidebar.php` (lines 109–114, 352–360)
  ```php
  if (isset($partnerInfo) && !empty($partnerInfo['id'])) { ... }
  ...
  <?php if (isset($isPartner) && $isPartner && isset($partnerInfo) && $partnerInfo['status'] === 'approved'): ?>
      <a href="/partner/dashboard.php" class="sidebar-item" style="color:var(--gold); font-weight:700;">⚡ <?= htmlspecialchars($partnerInfo['business_name']) ?></a>
      <a href="/partner/dashboard.php?tab=upload" class="sidebar-item" ...>➕ Add New Product</a>
  <?php elseif (isset($isPartner) && $isPartner && isset($partnerInfo) && $partnerInfo['status'] === 'pending'): ?>
      <a href="/partner/dashboard.php" class="sidebar-item" style="color:var(--muted);">🏪 Store (Pending Approval)</a>
  <?php else: ?>
      <a href="/user/create_shop.php" class="sidebar-item <?= ($currentPage === 'create_shop.php') ? 'active' : '' ?>" style="color:var(--gold);">➕ Open a Free Shop</a>
  <?php endif; ?>
  ```
- **Critical Scope Defect in `includes/user_sidebar.php`**:
  `includes/user_sidebar.php` is included by `user/dashboard.php`, `user/wallet.php`, `user/profile.php`, `user/partner_orders.php`, `user/notifications.php`, `user/missions.php`, `user/messages.php`, and `user/create_shop.php`.
  However, `$partnerInfo` is **only** defined in `user/dashboard.php`! When any other user page loads `user_sidebar.php`, `$partnerInfo` is null/undefined, which causes the sidebar to erroneously treat even approved shop owners as shopless (`➕ Open a Free Shop`).

### Partner Shop Status Values in Codebase
- **Approved / Active**: `status = 'approved'`, `status = 'active'`, or `empty(status)` (historical approved partners per `PROJECT_STATE.md` Phase 86).
- **Pending Review**: `status = 'pending'` (from `user/create_shop.php` line 99).
- **Shopless / Suspended**: No row returned, or `status = 'suspended'`.

### Shop Panel Reference
- **File**: `partner/nav.php` (lines 573–593):
  The Shop bottom dock has 5 slots: `[ Hub | Orders | ➕ Add (Elevated Center Circle) | Catalog | Menu ]`.
  In `partner/nav.php`, the elevated center circle button (`.dock-item-primary`, line 425–447) has a 48x48px circular gold gradient, 3px solid border, and `-10px` elevation.

---

## 2. Logic Chain

1. **User Request R1 & R2** require a 1-tap mode switcher between Buyer Mode (User Panel) and Shop Mode (Shop Panel) visible in BOTH the Top Header Bar and the Floating Bottom Navigation Dock, with synchronized 44px+ touch targets and native app feel.
2. In the **Top Header Bar** (`includes/user_sidebar.php`):
   - The current left slot contains `<a href="/index.php">Store</a>`, which is redundant with the bottom dock and sidebar.
   - By replacing this redundant button with the dynamic 1-tap Mode Toggle Pill, the top bar gains immediate mode-switching capability without overcrowding the 60px header or colliding with the center logo.
   - On Desktop (>=768px), the pill renders full text: `[ 🏪 Switch to Shop Mode ]`, `[ ⏳ Shop Under Review ]`, or `[ ➕ Open Free Shop ]`.
   - On Mobile (<768px), the pill collapses gracefully to compact text: `[ 🏪 Shop Mode ]`, `[ ⏳ In Review ]`, or `[ ➕ Free Shop ]`, preserving the 44px touch target.
3. In the **Floating Bottom Navigation Dock** (`user/dashboard.php`):
   - The current Slot 4 is "Alerts", which is redundant because the Top Header Bar already houses a persistent notification bell with an unread badge.
   - Slot 3 (or Slot 4) can be transformed into the **Mode Switcher** action:
     - When Approved: `[ 🏪 Shop Mode ]` linking to `/partner/dashboard.php`.
     - When Pending: `[ ⏳ In Review ]` triggering the pending status bottom sheet/modal.
     - When Shopless: `[ ➕ Free Shop ]` triggering the shop creation onboarding sheet / directing to `/user/create_shop.php`.
   - Styling the center or dedicated slot with an elevated glowing circle (mirroring `partner/nav.php`'s center circle) gives the User Panel and Shop Panel an identical structural design language.
4. For **Shopless & Pending Users (R4)**:
   - Clicking the mode toggle must not lead to a dead end.
   - For shopless users: directs to `/user/create_shop.php` or opens an interactive onboarding bottom sheet with 1-tap store registration.
   - For pending users: opens an informative `#shopReviewModal` bottom sheet explaining that their store application is under review with live review status and support contact.
5. For **Cross-Page State Integrity**:
   - `includes/user_sidebar.php` must self-contain the partner status query. If `$partnerInfo` is not already defined in the caller scope, `user_sidebar.php` executes a quick, cached query on `partners` table to determine `$shopStatus` (`approved`, `pending`, or `none`). This guarantees that whether a user is on `dashboard.php`, `wallet.php`, `profile.php`, or `missions.php`, the mode toggle pill always displays the correct state.

---

## 3. Caveats

1. **Active Tab Highlighting**: The current `user/dashboard.php` bottom dock items do not have server-side `.active` class assignment. An active state helper based on `$currentPage` and `$_GET['tab']` should be applied so the currently viewed section glows with `--mobile-gold` (`#fcb900`).
2. **Desktop Viewport Behavior**: On desktop viewports (>=1025px), `.bottom-nav` is hidden via CSS media query, so the Top Header Bar mode toggle pill and Side Drawer mode switcher are the primary mode-switching controls for desktop users.
3. **Impersonation State**: When an admin impersonates a user/shop (`$_SESSION['is_impersonating']`), switching modes should preserve the impersonation session state without dropping admin credentials.

---

## 4. Conclusion & Concrete Code Recommendations

### Architectural Recommendation Summary

| Component | Current State | Proposed Phase 87 State |
|-----------|---------------|-------------------------|
| **Top Header Bar** (`includes/user_sidebar.php`) | Has redundant `Store` button; no mode toggle | High-visibility `.mode-switch-pill` in left/center-right slot with 3 dynamic states (Approved / Pending / Shopless) |
| **Bottom Dock** (`user/dashboard.php`) | 5 items: Store, Orders, Wallet, Alerts, Profile | 5 synchronized items: Store, Orders, **Mode Switcher (Center)**, Wallet, Profile |
| **Partner Status Resolution** | Only fetched in `dashboard.php` | Self-contained in `includes/user_sidebar.php` so all 8 user sub-pages have accurate status |
| **Pending & Shopless Flow** | Broken links / non-informative | Slide-up modal for `[ ⏳ In Review ]` and 1-tap onboarding for `[ ➕ Free Shop ]` |
| **Google Stitch Tokens** | Mixed legacy styles | Nocturne Aurum tokens: `#0A0D1A`, `rgba(18, 22, 43, 0.85)`, `#F59E0B`, `scale(0.96)` |

---

### Concrete Implementation Specification

#### File 1: `includes/user_sidebar.php`
1. **At line 89**, insert self-contained partner status resolution:
   ```php
   // Self-healing partner status detection for all User Panel pages
   if (!isset($partnerInfo) && isset($pdo) && isset($_SESSION['user_id'])) {
       $uid = (int)$_SESSION['user_id'];
       $uPhone = $user['phone'] ?? '';
       if (empty($uPhone)) {
           try {
               $st_u = $pdo->prepare("SELECT phone FROM users WHERE id = ? LIMIT 1");
               $st_u->execute([$uid]);
               $uPhone = $st_u->fetchColumn() ?: '';
           } catch(Exception $e) {}
       }
       try {
           $st_p = $pdo->prepare("SELECT * FROM partners WHERE (user_id = :uid OR (phone = :p AND :p != '')) AND (status IS NULL OR status != 'suspended') ORDER BY id DESC LIMIT 1");
           $st_p->execute([':uid' => $uid, ':p' => $uPhone]);
           $partnerInfo = $st_p->fetch(PDO::FETCH_ASSOC) ?: null;
           $isPartner = !empty($partnerInfo);
       } catch(Exception $e) {}
   }

   $shopStatus = 'none'; // 'approved', 'pending', 'none'
   if (!empty($partnerInfo)) {
       $pStatus = strtolower(trim($partnerInfo['status'] ?? ''));
       if ($pStatus === 'pending') {
           $shopStatus = 'pending';
       } elseif ($pStatus === 'approved' || $pStatus === 'active' || empty($pStatus)) {
           $shopStatus = 'approved';
       }
   }
   ```

2. **In `.top-nav` (lines 275–284)**, replace the plain `Store` link with the 1-Tap Mode Toggle Pill:
   ```php
   <!-- Left Slot: Hamburger + 1-Tap Mode Switcher Pill -->
   <div style="flex:1; display:flex; align-items:center; justify-content:flex-start; gap:8px;">
     <button class="hamburger" onclick="toggleSidebar()" title="Toggle Dashboard Hub" style="width:44px; height:44px; min-width:44px; min-height:44px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); cursor:pointer;">
       <svg width="22" height="22" viewBox="0 0 24 24" style="width:22px; height:22px; fill:currentColor;"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
     </button>

     <?php if ($shopStatus === 'approved'): ?>
       <a href="/partner/dashboard.php" class="top-mode-pill mode-approved" title="Switch to Shop Mode">
         <span class="pill-icon">🏪</span>
         <span class="pill-text-desktop">Switch to Shop</span>
         <span class="pill-text-mobile">Shop</span>
         <span class="pill-arrow">⇄</span>
       </a>
     <?php elseif ($shopStatus === 'pending'): ?>
       <button type="button" onclick="openShopReviewModal()" class="top-mode-pill mode-pending" title="Shop Application Under Review">
         <span class="pill-icon">⏳</span>
         <span class="pill-text-desktop">Shop In Review</span>
         <span class="pill-text-mobile">Review</span>
       </button>
     <?php else: ?>
       <a href="/user/create_shop.php" class="top-mode-pill mode-shopless" title="Open Your Free Shop">
         <span class="pill-icon">➕</span>
         <span class="pill-text-desktop">Open Free Shop</span>
         <span class="pill-text-mobile">Free Shop</span>
       </a>
     <?php endif; ?>
   </div>
   ```

3. **In the Side Drawer (lines 352–360)**:
   Ensure the shop switcher callout at the top of drawer links is synchronized with `$shopStatus`.

4. **At the end of `includes/user_sidebar.php` (line 420)**, add the `#shopReviewModal` bottom sheet:
   ```html
   <!-- Shop Review Status Bottom Sheet Modal -->
   <div id="shopReviewModal" class="shop-review-modal-backdrop" onclick="closeShopReviewModal(event)">
     <div class="shop-review-modal-sheet" onclick="event.stopPropagation()">
       <div class="modal-handle"></div>
       <div style="font-size:2.4rem; margin-bottom:0.5rem; text-align:center;">⏳</div>
       <h3 style="color:#fff; font-size:1.2rem; font-weight:800; text-align:center; margin-bottom:0.4rem;">Shop Under Review</h3>
       <p style="color:var(--muted); font-size:0.85rem; text-align:center; line-height:1.5; margin-bottom:1.2rem;">
         Your store application for <strong style="color:var(--gold);"><?= htmlspecialchars($partnerInfo['business_name'] ?? 'Your Shop') ?></strong> is currently being verified by our compliance team.
       </p>
       <div style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:0.9rem; margin-bottom:1.2rem; font-size:0.8rem; color:#e2e8f0;">
         <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
           <span style="color:var(--muted);">Store Name:</span>
           <span style="font-weight:700;"><?= htmlspecialchars($partnerInfo['business_name'] ?? 'N/A') ?></span>
         </div>
         <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
           <span style="color:var(--muted);">Current Status:</span>
           <span style="color:var(--gold); font-weight:800; text-transform:uppercase;">Pending Approval</span>
         </div>
         <div style="display:flex; justify-content:space-between;">
           <span style="color:var(--muted);">Estimated Review:</span>
           <span style="color:#10b981; font-weight:700;">2–6 Hours</span>
         </div>
       </div>
       <a href="https://wa.me/8801963601472?text=Hello%2C%20checking%20status%20of%20my%20shop%20application" target="_blank" class="btn" style="background:linear-gradient(135deg, var(--gold), #f59e0b); color:#000; font-weight:800; font-size:0.9rem; border-radius:12px; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:6px; padding:0.8rem; margin-bottom:0.5rem;">
         💬 Speed Up Review via WhatsApp
       </a>
       <button type="button" onclick="closeShopReviewModal()" style="background:transparent; border:none; color:var(--muted); font-size:0.85rem; cursor:pointer; width:100%; padding:0.6rem;">Close</button>
     </div>
   </div>
   ```

#### File 2: `user/dashboard.php`
- **Lines 1596–1654 (`#user-floating-bottom-nav`)**:
  Update the 5 slots to:
  1. `Store` (`/index.php`)
  2. `Orders` (`/user/dashboard.php?tab=orders`) with active orders badge
  3. **Mode Switcher Slot (Center)**:
     - If `$shopStatus === 'approved'`:
       ```html
       <a href="/partner/dashboard.php" class="b-nav-item b-nav-center-pill" title="Switch to Shop Mode">
         <div class="dock-center-circle mode-shop">
           <span class="circle-icon">🏪</span>
         </div>
         <span class="dock-label">Shop Mode</span>
       </a>
       ```
     - If `$shopStatus === 'pending'`:
       ```html
       <button type="button" onclick="openShopReviewModal()" class="b-nav-item b-nav-center-pill" title="Shop Under Review">
         <div class="dock-center-circle mode-review">
           <span class="circle-icon">⏳</span>
         </div>
         <span class="dock-label">In Review</span>
       </button>
       ```
     - If `$shopStatus === 'none'`:
       ```html
       <a href="/user/create_shop.php" class="b-nav-item b-nav-center-pill" title="Open Free Shop">
         <div class="dock-center-circle mode-create">
           <span class="circle-icon">➕</span>
         </div>
         <span class="dock-label">Free Shop</span>
       </a>
       ```
  4. `Wallet` (`/user/wallet.php`)
  5. `Profile` (`/user/profile.php`)

#### File 3: `assets/css/user.css` & `assets/css/native_mobile.css`
Add Nocturne Aurum styles for `.top-mode-pill`, `.dock-center-circle`, and `.shop-review-modal-sheet`:
```css
/* Nocturne Aurum Mode Toggle Pills */
.top-mode-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 38px;
  min-height: 44px;
  padding: 0 12px;
  border-radius: 9999px;
  text-decoration: none;
  font-size: 0.76rem;
  font-weight: 800;
  white-space: nowrap;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-sizing: border-box;
}
.top-mode-pill:active {
  transform: scale(0.96);
}
.top-mode-pill.mode-approved {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(16, 185, 129, 0.2));
  border: 1px solid rgba(245, 158, 11, 0.45);
  color: #f59e0b;
  box-shadow: 0 2px 10px rgba(245, 158, 11, 0.2);
}
.top-mode-pill.mode-pending {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #94a3b8;
}
.top-mode-pill.mode-shopless {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(33, 150, 243, 0.2));
  border: 1px solid rgba(16, 185, 129, 0.45);
  color: #10b981;
}

@media (max-width: 600px) {
  .pill-text-desktop { display: none; }
  .pill-text-mobile  { display: inline; }
}
@media (min-width: 601px) {
  .pill-text-desktop { display: inline; }
  .pill-text-mobile  { display: none; }
}

/* Bottom Dock Center Action Circle */
.dock-center-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  margin-bottom: 2px;
  transition: transform 0.2s ease;
  box-shadow: 0 4px 12px rgba(0,0,0,0.5);
}
.dock-center-circle.mode-shop {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  border: 2px solid #0a0d1a;
  color: #0a0d1a;
}
.dock-center-circle.mode-review {
  background: rgba(255, 255, 255, 0.15);
  border: 2px solid rgba(255, 255, 255, 0.3);
  color: #fff;
}
.dock-center-circle.mode-create {
  background: linear-gradient(135deg, #10b981, #059669);
  border: 2px solid #0a0d1a;
  color: #fff;
}
.b-nav-item:active .dock-center-circle {
  transform: scale(0.92);
}
```

---

## 5. Verification Method

1. **PHP Syntax Verification**:
   ```bash
   php -l "includes/user_sidebar.php"
   php -l "user/dashboard.php"
   php -l "user/create_shop.php"
   ```
   *Pass criteria*: No syntax or compilation errors detected (`No syntax errors detected in ...`).

2. **Cross-Page Partner Status Verification**:
   - Inspect `user/wallet.php` and `user/profile.php` in a logged-in partner session.
   - *Pass criteria*: Top header bar renders `[ 🏪 Switch to Shop Mode ]` (or `[ 🏪 Shop ]`) rather than falling back to `[ ➕ Open Free Shop ]`.

3. **Three State Transitions**:
   - Test user with approved partner: Clicking the top or bottom mode switch pill directly opens `/partner/dashboard.php`.
   - Test user with pending shop: Clicking the switch pill opens `#shopReviewModal` with live business name and review status.
   - Test user without a shop: Clicking the switch pill directly opens `/user/create_shop.php`.

4. **Touch Target Inspection**:
   - Verify all interactive controls (`.top-mode-pill`, `.b-nav-item`, `.hamburger`, notification bell, messages icon) have computed bounding box >= 44x44px.

5. **Local Server Live Host Verification**:
   - Navigate to `http://localhost:8000/user/dashboard.php` and test viewport transitions across 360px, 390px, 414px, 768px, and 1200px widths.
   - *Pass criteria*: 0px horizontal scroll, 0 element collisions, and fluid 1-tap mode switching.
