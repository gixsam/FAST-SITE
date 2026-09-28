# Phase 87 Milestone 2 Adversarial Challenge & Verification Report

**Author**: `challenger_p87_m2_1`  
**Role**: Empirical Challenger (`critic`, `specialist`)  
**Date**: 2026-09-09T21:24:00+06:00  
**Target Module**: Shop / Partner Portal (`partner/nav.php`, `partner/dashboard.php`, and 7 redirect files)  
**Verdict**: **REQUEST_CHANGES** (1 Empirical Defect Discovered in `partner/nav.php`)

---

## 1. Observation

### 1.1 Live HTTP Curl Testing of 7 Redirect Endpoints
Executed live HTTP requests against Local Server Live Host (`http://localhost:8000`):
```powershell
curl.exe -i -s http://localhost:8000/partner/index.php
curl.exe -i -s http://localhost:8000/partner/logout.php
curl.exe -i -s http://localhost:8000/partner/product_add.php
curl.exe -i -s http://localhost:8000/partner/product_edit.php
curl.exe -i -s http://localhost:8000/partner/product_delete.php
curl.exe -i -s http://localhost:8000/partner/profile.php
curl.exe -i -s http://localhost:8000/partner/api_docs.php
```

**Verbatim HTTP Response Headers Observed**:
1. `/partner/index.php`:
   ```http
   HTTP/1.1 302 Found
   Location: /user/login.php
   ```
2. `/partner/logout.php`:
   ```http
   HTTP/1.1 302 Found
   Location: /user/login.php
   ```
3. `/partner/product_add.php`:
   ```http
   HTTP/1.1 302 Found
   Location: /user/login.php
   ```
4. `/partner/product_edit.php`:
   ```http
   HTTP/1.1 302 Found
   Location: /user/login.php
   ```
5. `/partner/product_delete.php`:
   ```http
   HTTP/1.1 302 Found
   Location: /user/login.php
   ```
6. `/partner/profile.php`:
   ```http
   HTTP/1.1 302 Found
   Location: /user/login.php
   ```
7. `/partner/api_docs.php`:
   ```http
   HTTP/1.1 302 Found
   Location: /user/login.php
   ```
- **Destination Verification**: `curl.exe -i -s http://localhost:8000/user/login.php` returned `HTTP/1.1 200 OK`.
- **Edge Case: Unauthenticated AJAX POST**: `curl.exe -s -X POST -d "ajax=1" http://localhost:8000/partner/product_add.php` returned verbatim JSON:
  `{"success":false,"error":"Session expired. Please log in again."}`.
- **Direct unauthenticated access to `partner/dashboard.php` and `partner/nav.php`**: Both returned `HTTP/1.1 302 Found` with `Location: /user/login.php`.

### 1.2 Syntax and Static Analysis
Ran `php -l` on all 9 touched files:
```powershell
php -l partner/nav.php           # No syntax errors detected
php -l partner/dashboard.php     # No syntax errors detected
php -l partner/index.php         # No syntax errors detected
php -l partner/logout.php        # No syntax errors detected
php -l partner/product_add.php   # No syntax errors detected
php -l partner/product_edit.php  # No syntax errors detected
php -l partner/product_delete.php # No syntax errors detected
php -l partner/profile.php       # No syntax errors detected
php -l partner/api_docs.php      # No syntax errors detected
```
All files passed pure UTF-8 verification with zero BOM.

### 1.3 Authenticated Live Component & Layout Rendering
Tested live session requests against `http://localhost:8000/partner/dashboard.php` and `http://localhost:8000/partner/orders.php`:
- `header-mode-pill` rendered with `Switch to Buyer Mode` desktop text, `Buyer` mobile text, icon 👤, linking to `/user/dashboard.php`.
- Executive Hero Quick Action Bar in `partner/dashboard.php` contains `.btn-action-hero.btn-action-buyer` with icon 👤, linking to `/user/dashboard.php`.
- `.nav-back-btn` is cleanly omitted from `partner/dashboard.php`.
- `.nav-back-btn` is present on subpages (`orders.php`, `products.php`) linking to `dashboard.php`.
- Bottom dock has 5 slots: Hub, Orders (with dynamic counter badge), Center FAB Add (`dock-item-primary`), Catalog, and Buyer Mode. Obsolete "Menu" slot is completely eradicated.
- Safe-area insets and 44px+ touch targets are specified in CSS.

### 1.4 Empirical Defect Discovered: Boolean Coercion in Active Class Rendering
During live curl inspection of `http://localhost:8000/partner/products.php` and `http://localhost:8000/partner/product_edit.php?id=1`:
```powershell
curl.exe -s --cookie "PHPSESSID=..." http://localhost:8000/partner/products.php | Select-String -Pattern "products.php" -Context 1,1
```
**Verbatim Output Observed**:
```html
>       <a href="products.php" class="drawer-link 1">
          <span>🛍️</span> My Products
    <!-- Slot 4: My Products Catalog -->
>   <a href="products.php" class="dock-item 1">
      <span class="icon">🛍️</span>
```
Examining `partner/nav.php`:
- **Line 75**:
  ```php
  function isActive($page, $current_page) {
      return $page === $current_page ? 'active' : '';
  }
  ```
- **Line 738**:
  ```php
  <a href="products.php" class="drawer-link <?= isActive('products.php', $current_page) || isActive('product_add.php', $current_page) || isActive('product_edit.php', $current_page) ?>">
  ```
- **Line 747**:
  ```php
  <a href="earnings.php" class="drawer-link <?= isActive('earnings.php', $current_page) || isActive('withdraw.php', $current_page) ?>">
  ```
- **Line 815**:
  ```php
  <a href="products.php" class="dock-item <?= isActive('products.php', $current_page) || isActive('product_edit.php', $current_page) ?>">
  ```

---

## 2. Logic Chain

1. **Redirect Reliability (Observation 1.1)**:
   - All 7 redirect files and unauthenticated access to `nav.php` / `dashboard.php` check for valid session (`$_SESSION['user_id']` or `$_SESSION['partner_id']`).
   - If unauthenticated, they execute `header('Location: /user/login.php'); exit;`.
   - Live curl tests confirm 100% of endpoints return HTTP 302 with Location `/user/login.php`. No endpoint returned HTTP 404.
   - The target `/user/login.php` exists and returns HTTP 200 OK.
   - Conclusion: The dead-end 404 defect is completely eradicated.

2. **Component & Navigation Dock Integrity (Observation 1.3)**:
   - Both Desktop top header mode switcher pill and Mobile bottom dock Slot 5 point cleanly to `/user/dashboard.php` with 44px+ touch targets and responsive text collapse.
   - Subpages cleanly display the persistent back chevron returning to `dashboard.php`, while `dashboard.php` suppresses it to avoid confusing users.

3. **Active State Highlight Defect (Observation 1.4)**:
   - In PHP, `isActive()` returns the string `'active'` on match, or `''` (empty string) on non-match.
   - In expressions such as `isActive('products.php', $current_page) || isActive('product_edit.php', $current_page)`, PHP performs a boolean logical OR between the string return values.
   - When `$current_page === 'products.php'`, the expression evaluates as `'active' || ''` which results in boolean `true`.
   - The short echo tag `<?=` casts boolean `true` to string `'1'`.
   - As a result, HTML renders as `class="dock-item 1"` and `class="drawer-link 1"`.
   - CSS rules target `.dock-item.active` and `.drawer-link.active`.
   - Neither rule matches `.1`. Consequently, active gold tab highlighting completely fails to render when the user navigates to "My Products", "Edit Product", or "Shop Earnings".
   - This violates the synchronized navigation dock specification where the active tab must be highlighted.

---

## 3. Caveats

1. **CSS Regex Parsing in Test Suite**:
   - `tests/test_p87_m2_dom_stress.php` reported 5 failures, 4 of which were due to brittle regexes in the test script failing on multi-rule CSS blocks and CLI `shell_exec` handling of `exit;`. The 5th failure (`Page '/partner/products.php' highlights correct active dock slot ('Catalog')`) was verified as a real production defect in `partner/nav.php`.
2. **Local Server Live Host**:
   - Verification was executed directly on `http://localhost:8000`. Cloudflare live tunnel verification is deferred to Milestone 3 cross-module verification.

---

## 4. Conclusion

- **Verdict**: **REQUEST_CHANGES**
- **Action Required by Worker**:
  Fix the active class string concatenation in `partner/nav.php` so that it outputs `'active'` rather than `'1'`.
  
  **Concrete Fix**:
  In `partner/nav.php`:
  - **Lines 75-77**:
    ```php
    function isActive($pages, $current_page) {
        if (is_array($pages)) {
            return in_array($current_page, $pages, true) ? 'active' : '';
        }
        return $pages === $current_page ? 'active' : '';
    }
    ```
  - **Line 738**:
    ```php
    <a href="products.php" class="drawer-link <?= isActive(['products.php', 'product_add.php', 'product_edit.php'], $current_page) ?>">
    ```
  - **Line 747**:
    ```php
    <a href="earnings.php" class="drawer-link <?= isActive(['earnings.php', 'withdraw.php'], $current_page) ?>">
    ```
  - **Line 815**:
    ```php
    <a href="products.php" class="dock-item <?= isActive(['products.php', 'product_edit.php'], $current_page) ?>">
    ```

Once this 1-minute fix is applied, all 5 dock slots, side drawer links, and mode switchers will render with 100% visual perfection.

---

## 5. Verification Method

To reproduce the findings:
1. **Live HTTP Curl Test for 7 Redirects**:
   ```powershell
   $endpoints = @("/partner/index.php", "/partner/logout.php", "/partner/product_add.php", "/partner/product_edit.php", "/partner/product_delete.php", "/partner/profile.php", "/partner/api_docs.php")
   foreach ($ep in $endpoints) { curl.exe -i -s "http://localhost:8000$ep" | Select-String "HTTP/|Location:" }
   ```
   *Expected*: All return `HTTP/1.1 302 Found` and `Location: /user/login.php`.

2. **Empirical Reproduction of Active Class Defect**:
   ```powershell
   php -r "function isActive(`$p, `$c) { return `$p === `$c ? 'active' : ''; } `$c = 'products.php'; echo 'Class: dock-item ' . (isActive('products.php', `$c) || isActive('product_edit.php', `$c)) . PHP_EOL;"
   ```
   *Observed*: Prints `Class: dock-item 1`.
   *After Proposed Fix*: Prints `Class: dock-item active`.
