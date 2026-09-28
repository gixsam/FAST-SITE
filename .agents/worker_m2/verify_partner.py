import sys

files = [
    'partner/nav.php',
    'partner/dashboard.php',
    'partner/orders.php',
    'partner/products.php',
    'partner/product_add.php'
]

bad_byte_sequences = [
    b'\xc3\xa0\xc2\xa7\xc2\xb3', # à§³
    b'\xc3\xaf\xc2\xb8',         # ï¸
]

all_passed = True
for fpath in files:
    with open(fpath, 'rb') as f:
        content = f.read()
    for seq in bad_byte_sequences:
        if seq in content:
            print(f"FAILED: Found bad sequence {seq} in {fpath}")
            all_passed = False

if all_passed:
    print("VERIFIED: Zero mojibake sequences found across all 5 partner files!")
else:
    sys.exit(1)
