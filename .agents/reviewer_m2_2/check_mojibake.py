import os
import sys

files = [
    'partner/nav.php',
    'partner/dashboard.php',
    'partner/orders.php',
    'partner/products.php',
    'partner/product_add.php'
]

mojibake_signatures = [
    'à§³', '⚠️ï¸', 'ï¸', 'â€', 'Ã', 'Â', 'ðŸ', 'â€™', 'â€œ', 'â€\x9d', 'â€“', 'â€”', 'Ã©', 'Ã¡', 'Ã³'
]

findings = []
for fpath in files:
    full_path = os.path.join('d:/TECH/WEBSITE/FAST SITE/fast site', fpath)
    with open(full_path, 'rb') as f:
        content_bytes = f.read()
    try:
        content_str = content_bytes.decode('utf-8')
    except UnicodeDecodeError as e:
        findings.append(f'{fpath}: UTF-8 decode error: {e}')
        continue
    
    lines = content_str.splitlines()
    for idx, line in enumerate(lines, 1):
        for sig in mojibake_signatures:
            if sig in line:
                findings.append(f'{fpath}:{idx}: contains mojibake signature "{sig}": {line.strip()[:100]}')

if findings:
    print('MOJIBAKE DETECTED:')
    for f in findings:
        print(f)
    sys.exit(1)
else:
    print('SUCCESS: No mojibake signatures found in any of the 5 files.')
    sys.exit(0)
