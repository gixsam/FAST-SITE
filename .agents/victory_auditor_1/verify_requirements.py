import re

results = []

def check(condition, desc):
    results.append((condition, desc))
    print(f"[{'PASS' if condition else 'FAIL'}] {desc}")

# 1. user/dashboard.php
with open('user/dashboard.php', 'r', encoding='utf-8') as f:
    ud = f.read()

check('tab-orders' in ud, "user/dashboard.php contains tab-orders")
check('tab-social' not in ud, "user/dashboard.php eliminates tab-social dead route")
check('reflink-quick' in ud and 'reflink-share' in ud and 'reflink-agent' in ud, "user/dashboard.php has distinct reflink IDs")
check('activeOrders' in ud and '$totalActiveOrders' not in ud, "user/dashboard.php uses $activeOrders (no undefined variable)")
check('bottom-nav' in ud or 'floating-nav' in ud or 'bottom-bar' in ud or 'user-bottom-dock' in ud or 'user-bottom-bar' in ud, "user/dashboard.php has bottom navigation bar")
check('Store' in ud and 'Orders' in ud and 'Wallet' in ud and 'Alert' in ud and 'Profile' in ud, "user/dashboard.php bottom nav has 5 primary slots")
check('toggleSidebar' not in ud or ud.count('function toggleSidebar') <= 1, "user/dashboard.php has no duplicate toggleSidebar definition")

# 2. includes/user_sidebar.php
with open('includes/user_sidebar.php', 'r', encoding='utf-8') as f:
    us = f.read()

check('closeAllDrawers' in us, "includes/user_sidebar.php has closeAllDrawers decoupling")
check('min-height: 44px' in us or 'min-width: 44px' in us or '44px' in us, "includes/user_sidebar.php enforces 44px minimum touch targets")
check('toggleNotificationDrawer' in us, "includes/user_sidebar.php has notification drawer trigger")

# 3. assets/css/user.css & mobile_responsive.css
with open('assets/css/user.css', 'r', encoding='utf-8') as f:
    ucss = f.read()
with open('assets/css/mobile_responsive.css', 'r', encoding='utf-8') as f:
    mcss = f.read()

check('var(--top-nav-height' in ucss or 'var(--top-nav-height' in mcss, "CSS defines top navbar clearance variable")
check('var(--bottom-nav-height' in ucss or 'var(--bottom-nav-height' in mcss or 'calc(' in ucss, "CSS defines bottom clearance calculation")

# 4. partner/dashboard.php & products.php & orders.php & nav.php
with open('partner/products.php', 'r', encoding='utf-8') as f:
    pp = f.read()
with open('partner/orders.php', 'r', encoding='utf-8') as f:
    po = f.read()
with open('partner/nav.php', 'r', encoding='utf-8') as f:
    pn = f.read()

check('👁️ View' in pp or 'product_detail.php' in pp, "partner/products.php has live storefront preview link")
check('FREE' in pp, "partner/products.php has FREE badge for 0-price products")
check('<<div' not in po, "partner/orders.php has no stray <<div tag")
check('partner-bottom-dock' in pn or 'partner-bottom-nav' in pn or 'dock' in pn, "partner/nav.php has partner mobile dock")
check('bottom: 62px' in pn, "partner/nav.php has bottom: 62px clearance for side drawer on mobile")

# 5. home.php & includes/nav_public.php
with open('includes/nav_public.php', 'r', encoding='utf-8') as f:
    np = f.read()
with open('home.php', 'r', encoding='utf-8') as f:
    hp = f.read()

check('1fr auto 1fr' in np or 'grid-template-columns' in np, "includes/nav_public.php has symmetrical 3-zone grid")
check('nav-brand-centered' in np or 'logo' in np, "includes/nav_public.php has centered brand")
check('safe-area-inset-top' in np, "includes/nav_public.php handles safe-area-inset-top")
check('btn-filter-trigger' in hp, "home.php has btn-filter-trigger")
check('categoryDrawer' in hp, "home.php has categoryDrawer")
check('categoryDrawerScrim' in hp, "home.php has categoryDrawerScrim")
check('openCategoryDrawer' in hp and 'closeCategoryDrawer' in hp, "home.php has open/close CategoryDrawer functions")
check('pull-to-refresh' in hp or 'ptr' in hp or 'category-drawer' in hp, "home.php includes pull-to-refresh exclusion/compatibility")

print("\n--- Summary ---")
passed = sum(1 for r, _ in results if r)
total = len(results)
print(f"Passed: {passed}/{total}")
