import os
import sys

sys.stdout.reconfigure(encoding='utf-8')

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
    'assets/css/mobile_responsive.css'
]

# Common mojibake substrings when UTF-8 is decoded as Windows-1252 or ISO-8859-1
mojibake_tokens = [
    '\ufffd',              # Unicode replacement character
    '\u00c3',              # Ã
    '\u00e0\u00a7',        # à§ (common Bengali mojibake for ৳)
    '\u00e2\u20ac\u2122',  # â€™
    '\u00e2\u20ac\u0153',  # â€œ
    '\u00e2\u20ac\u2014',  # â€”
    '\u00e2\u20ac\u2013',  # â€“
    '\u00e2\u20ac',        # â€
    '\u00f0\u0178',        # ðŸ (4-byte emoji mojibake)
]

clean = True
for path in files:
    if not os.path.exists(path):
        print(f"[MISSING] {path}")
        clean = False
        continue
    with open(path, 'rb') as f:
        data = f.read()
    try:
        content = data.decode('utf-8')
    except Exception as e:
        print(f"[FAIL] {path}: Decode error {e}")
        clean = False
        continue
    
    # Check for BOM
    if data.startswith(b'\xef\xbb\xbf'):
        print(f"[WARN] {path} contains UTF-8 BOM")
        
    for idx, line in enumerate(content.splitlines(), 1):
        for tok in mojibake_tokens:
            if tok in line:
                print(f"[SUSPICIOUS] {path}:{idx} (contains {repr(tok)}): {line.strip()[:100]}")
                clean = False

if clean:
    print("[PASS] All 11 files verified: 100% valid UTF-8 with zero mojibake!")
