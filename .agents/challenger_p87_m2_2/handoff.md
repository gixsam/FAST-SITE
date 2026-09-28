# Phase 87 Milestone 2 Challenger Report: Adversarial DOM, Component & CSS Stress Testing

**Author**: `challenger_p87_m2_2`  
**Target Module**: Shop / Partner Portal Navigation (`partner/nav.php`, `partner/dashboard.php`)  
**Design Standard**: Google Stitch *Nocturne Aurum* Standards  
**Empirical Verdict**: `REQUEST_CHANGES` (1 critical bug discovered via empirical test harness)  

---

## 1. Challenge Summary

**Overall risk assessment**: **HIGH**  
While `partner/nav.php` successfully passed 69 out of 70 adversarial checks (including back button omission on `dashboard.php`, persistent back button on all subpages, exact 5-slot bottom dock architecture, orders counter badge thresholds, touch target dimensions, safe-area padding, and unauthenticated redirects), empirical testing revealed a **critical PHP boolean stringification flaw in multi-page active state styling** (`partner/nav.php:738, 748, 815`).

Evaluating `<?= isActive(...) || isActive(...) ?>` produces a boolean `true` in PHP, which stringifies to `"1"`. Consequently, `<a class="dock-item 1">` and `<a class="drawer-link 1">` are rendered in the DOM rather than `class="... active"`. As a result, merchants viewing `products.php` (Catalog) or `earnings.php` never see active tab styling, and invalid DOM class names are emitted.

---

## 2. Challenges

### [High] Challenge 1: PHP Boolean Stringification in Multi-Page Active Class Expressions

- **Assumption challenged**: The worker assumed that chaining `isActive(...) || isActive(...)` inside `class="dock-item <?= ... ?>"` would output the string `'active'`.
- **Attack scenario**: When a merchant visits `/partner/products.php`, line 815 evaluates:
  ```php
  class="dock-item <?= isActive('products.php', $current_page) || isActive('product_edit.php', $current_page) ?>"
  ```
  `isActive('products.php', 'products.php')` returns `'active'` (a truthy string).  
  In PHP, `'active' || ''` is a logical OR operation that evaluates to boolean `true`.  
  When PHP echoes boolean `true`, it is stringified to `"1"`.  
  The rendered HTML is:
  ```html
  <a href="products.php" class="dock-item 1">
  ```
- **Blast radius**:
  1. The CSS rule `.dock-item.active` (which applies the gold color `--brand: #fcb900` and icon scale `transform: scale(1.1)`) fails to match. The merchant receives zero visual indication of which dock tab is active on the Catalog page.
  2. The same defect is present in the Side Drawer at line 738 (`products.php`) and line 748 (`earnings.php`):
     - Line 738: `<a href="products.php" class="drawer-link <?= isActive('products.php', $current_page) || isActive('product_add.php', $current_page) || isActive('product_edit.php', $current_page) ?>">` -> outputs `class="drawer-link 1"`.
     - Line 748: `<a href="earnings.php" class="drawer-link <?= isActive('earnings.php', $current_page) || isActive('withdraw.php', $current_page) ?>">` -> outputs `class="drawer-link 1"`.
  3. Emits malformed/invalid CSS class attribute `1` into production DOM.
- **Mitigation**:
  Upgrade `isActive()` in `partner/nav.php:75-77` to support array input, or wrap multiple conditions in a ternary operator:
  **Recommended Defense (Clean & Idiomatic)**:
  ```php
  function isActive($page, $current_page) {
      if (is_array($page)) {
          return in_array($current_page, $page, true) ? 'active' : '';
      }
      return $page === $current_page ? 'active' : '';
  }
  ```
  Then update:
  - Line 738: `isActive(['products.php', 'product_add.php', 'product_edit.php'], $current_page)`
  - Line 748: `isActive(['earnings.php', 'withdraw.php'], $current_page)`
  - Line 815: `isActive(['products.php', 'product_edit.php'], $current_page)`

### [Low] Challenge 2: Unguarded Function Declaration `isActive()`

- **Assumption challenged**: `partner/nav.php` assumes it will only ever be loaded via `require_once`.
- **Attack scenario**: If any page or test harness includes `partner/nav.php` twice, line 75 `function isActive(...)` throws `Fatal error: Cannot redeclare isActive()`.
- **Blast radius**: Low in production if `require_once` is consistently used, but fragile during automated testing or modular includes.
- **Mitigation**: Wrap with `if (!function_exists('isActive')) { ... }`.

---

## 3. Observation

1. **Empirical Test Suite Execution**:
   - Built standalone empirical test harness: `d:/TECH/WEBSITE/FAST SITE/fast site/tests/test_p87_m2_dom_stress.php`.
   - Executed against PHP 8.3.31 CLI.
   - Command: `php tests/test_p87_m2_dom_stress.php`
   - Output summary:
     ```
     Total Checks: 70
     Passed Checks: 69
     Failed Checks: 1
     FAILED CHECKS DETAILS:
       - Page '/partner/products.php' properly applies class 'active' to Catalog dock item -> Details: ACTUAL RENDERED CLASS IS: 'dock-item 1' (Boolean '1' stringification bug!)
     FINAL VERDICT: REQUEST_CHANGES
     ```
2. **Task 1 Verification (Conditional Rendering Cases A, B, C, D)**:
   - **Case A (`$current_page = 'dashboard.php'`)**:
     - `.nav-back-btn` count in DOM: `0` (`[PASS]`).
     - Brand link points to `dashboard.php` (`[PASS]`).
   - **Case B (`$current_page = 'orders.php'`)**:
     - `.nav-back-btn` count in DOM: `1` (`[PASS]`).
     - `href` is `dashboard.php`, title is `Back to Shop Overview`, contains SVG chevron (`[PASS]`).
   - **Case C (`$current_page = 'products.php'`)**:
     - `.nav-back-btn` count in DOM: `1`, points to `dashboard.php` (`[PASS]`).
     - Also verified on `product_add.php`, `product_edit.php`, `coupons.php`, `earnings.php`, `disputes.php`, `profile.php` (`[PASS]`).
   - **Case D (Pending Orders Badge Thresholds)**:
     - `count = 0`: Dock badge count = 0, Drawer badge count = 0 (`[PASS]`).
     - `count = 5`: Dock badge text = `'5'`, Drawer badge text = `'5'` (`[PASS]`).
     - `count = 99`: Dock badge text = `'99'`, Drawer badge text = `'99'` (`[PASS]`).
     - `count = 100`: Dock badge text = `'99+'`, Drawer badge text = `'99+'` (`[PASS]`).
     - `count = 250`: Dock badge text = `'99+'`, Drawer badge text = `'99+'` (`[PASS]`).
3. **Task 2 Verification (Bottom Dock Structure & Slots)**:
   - Exactly 5 direct slot children in `<nav class="partner-bottom-dock">` (`[PASS]`).
   - Slot 1: `href="dashboard.php"`, label "Hub", icon "📊" (`[PASS]`).
   - Slot 2: `href="orders.php"`, label "Orders", icon "📦" (`[PASS]`).
   - Slot 3: `href="product_add.php"`, class `dock-item-primary`, icon "➕" (`[PASS]`).
   - Slot 4: `href="products.php"`, label "Catalog", icon "🛍️" (`[PASS]`).
   - Slot 5: `href="/user/dashboard.php"`, class `dock-item dock-item-buyer`, label "Buyer Mode", icon "👤" (`[PASS]`).
   - Obsolete "Menu" slot is completely eradicated (`[PASS]`).
4. **Task 3 Verification (CSS Rules & Responsiveness)**:
   - `@media (max-width: 600px)` media query exists (`[PASS]`).
   - `.mode-pill-text-desktop { display: none !important; }` (`[PASS]`).
   - `.mode-pill-text-mobile { display: inline !important; }` (`[PASS]`).
   - `.partner-badge { display: none !important; }` (`[PASS]`).
   - Touch targets >= 44px:
     - `.nav-back-btn`: `min-width: 44px; min-height: 44px;` (`[PASS]`).
     - `.hamburger-btn`: `min-width: 44px; min-height: 44px;` (`[PASS]`).
     - `.header-mode-pill`: `min-width: 44px; min-height: 44px;` (`[PASS]`).
     - `.app-hub-dropdown button`: `min-width: 44px; min-height: 44px;` (`[PASS]`).
     - `.dock-item`: `min-height: 44px;` (`[PASS]`).
     - `.dock-item-primary`: `min-width: 48px; min-height: 48px;` (`[PASS]`).
   - Safe-area insets:
     - `.partner-bottom-dock` height: `calc(64px + env(safe-area-inset-bottom, 0px))` (`[PASS]`).
     - `.partner-bottom-dock` padding: `calc(env(safe-area-inset-bottom, 0px) + 6px) !important` (`[PASS]`).
     - `.side-drawer` bottom offset: `calc(64px + env(safe-area-inset-bottom, 0px)) !important` (`[PASS]`).
     - `body` padding-bottom: `calc(76px + env(safe-area-inset-bottom, 0px)) !important` (`[PASS]`).
5. **Adversarial Edge Cases**:
   - Unauthenticated access: `partner/nav.php:8-11` strictly emits `Location: /user/login.php` and exits immediately; verified live with `curl.exe -I -s http://localhost:8000/partner/dashboard.php` returning `HTTP/1.1 302 Found` with `Location: /user/login.php` (`[PASS]`).
   - Non-existent partner record: falls back cleanly without PHP errors or warnings (`[PASS]`).
   - Impersonation: renders "Return to Admin Panel" link (`[PASS]`).
   - Quick action buyer button in `partner/dashboard.php:739`: links to `/user/dashboard.php` with `.btn-action-buyer` styling and `transform: scale(0.96)` active feedback (`[PASS]`).

---

## 4. Logic Chain

1. `partner/nav.php` line 75 defines:
   ```php
   function isActive($page, $current_page) {
       return $page === $current_page ? 'active' : '';
   }
   ```
2. When the caller attempts multi-page matching (e.g. line 815: `<?= isActive('products.php', $current_page) || isActive('product_edit.php', $current_page) ?>`), the PHP interpreter processes the `||` operator prior to outputting to the buffer.
3. If `$current_page` is `'products.php'`, `isActive('products.php', 'products.php')` returns string `'active'`.
4. The expression `'active' || ''` is evaluated by PHP as a boolean logical operation, returning `true`.
5. The `<?=` shorthand casts boolean `true` to string `"1"`.
6. The resulting DOM node is emitted as `<a href="products.php" class="dock-item 1">`.
7. Because `"1"` is neither `'active'` nor a valid selector for `.dock-item.active`, the CSS rule `.dock-item.active { color: #fcb900; }` never matches.
8. Therefore, the requirement that bottom dock items reflect proper active navigation state is broken for `products.php` (and the side drawer links for Products and Earnings).
9. Per the Role Directives of EMPIRICAL CHALLENGER, this finding requires an explicit verdict of `REQUEST_CHANGES`.

---

## 5. Caveats

- **Scope boundary**: Review-only role strictly observed. No production or template implementation files were modified.
- **Local Server Live Host**: Live HTTP curl tests were executed against `http://localhost:8000`. The server must remain active for full end-to-end browser inspection.

---

## 6. Conclusion & Actionable Next Steps

**Verdict: `REQUEST_CHANGES`**

The worker `worker_p87_m2_exec` did exceptional architectural work on the 5-slot bottom dock, 1-tap mode switchers, persistent back button, and 404 redirect cleanup. However, the worker must apply a fast 1-line remediation to fix the boolean active class evaluation in `partner/nav.php`:

### Required Remediation for Worker:
In `partner/nav.php`:
1. Modify `function isActive($page, $current_page)` at lines 75-77:
   ```php
   function isActive($page, $current_page) {
       if (is_array($page)) {
           return in_array($current_page, $page, true) ? 'active' : '';
       }
       return $page === $current_page ? 'active' : '';
   }
   ```
2. Update line 738:
   ```php
   <a href="products.php" class="drawer-link <?= isActive(['products.php', 'product_add.php', 'product_edit.php'], $current_page) ?>">
   ```
3. Update line 748:
   ```php
   <a href="earnings.php" class="drawer-link <?= isActive(['earnings.php', 'withdraw.php'], $current_page) ?>">
   ```
4. Update line 815:
   ```php
   <a href="products.php" class="dock-item <?= isActive(['products.php', 'product_edit.php'], $current_page) ?>">
   ```

Once this fix is applied, all 70 tests in `tests/test_p87_m2_dom_stress.php` will pass with 100% clean green execution.

---

## 7. Verification Method

Execute the automated empirical test suite in project root:
```powershell
php "tests/test_p87_m2_dom_stress.php"
```

**Expected Result Before Fix**:
- 69 PASS, 1 FAIL (`Page '/partner/products.php' properly applies class 'active' to Catalog dock item`).

**Expected Result After Fix**:
- 70 PASS, 0 FAIL (`FINAL VERDICT: ALL 70 CHECKS PASSED (APPROVE)`).
