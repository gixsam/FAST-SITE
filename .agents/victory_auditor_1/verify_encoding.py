import os

files = [
    'user/dashboard.php',
    'includes/user_sidebar.php',
    'partner/dashboard.php',
    'partner/orders.php',
    'partner/products.php',
    'partner/product_add.php',
    'partner/nav.php',
    'home.php',
    'includes/nav_public.php',
    'assets/css/user.css',
    'assets/css/mobile_responsive.css',
    'PROJECT_STATE.md',
    'DEPLOYMENT_GUIDE.txt'
]

mojibake_signatures = [
    '\xc3\xa9', '\xc3\xa0', '\xc3\xa8', '\xc3\xa7', '\xc3\xae',
    '\xe2\x80\x99', '\xe2\x80\x9c', '\xe2\x80\x9d', '\xe2\x80\x93',
    '\xe2\x80\x94', '\xef\xbf\xbd', '\xe2\x80\xa2'
]

all_clean = True
for f in files:
    try:
        with open(f, 'rb') as fp:
            raw = fp.read()
        
        has_bom = raw.startswith(b'\xef\xbb\xbf')
        text = raw.decode('utf-8')
        
        found = []
        for sig in mojibake_signatures:
            if sig in text:
                found.append(repr(sig))
                
        status = 'CLEAN UTF-8 (No BOM)'
        if has_bom:
            status = 'HAS BOM'
            all_clean = False
        if found:
            status += f' - MOJIBAKE FOUND: {", ".join(found)}'
            all_clean = False
            
        print(f'{f:35}: {status}')
    except UnicodeDecodeError as e:
        print(f'{f:35}: DECODE ERROR: {e}')
        all_clean = False

print(f'\nOverall encoding check: {"PASS" if all_clean else "FAIL"}')
