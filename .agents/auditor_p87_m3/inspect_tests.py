import os
import re

ROOT = r"d:\TECH\WEBSITE\FAST SITE\fast site"
test_files = [
    r"tests\test_p87_m1_dom_verification.php",
    r"tests\test_p87_m1_encoding.php",
    r"tests\test_p87_m1_shop_state.php",
    r"tests\test_p87_m1_ui_empirical.php",
    r"tests\test_p87_m2_dom_stress.php",
    r"tests\test_p87_m2_products_active.php",
    r".agents\worker_p87_m3\audit_encoding.php",
    r".agents\worker_p87_m3\live_endpoints_test.php",
    r".agents\worker_p87_m3\test_extract.py"
]

print("=" * 80)
print("TEST INTEGRITY & FAKE/BYPASS AUDIT")
print("=" * 80)

for tf in test_files:
    full_path = os.path.join(ROOT, tf)
    if not os.path.exists(full_path):
        print(f"File not found: {tf}")
        continue
    with open(full_path, "r", encoding="utf-8", errors="replace") as f:
        code = f.read()

    print(f"\nAnalyzing: {tf} ({len(code):,} bytes)")

    # Check for early exit without tests
    lines = code.splitlines()
    early_exits = []
    for idx, l in enumerate(lines[:30], 1):
        if re.search(r'^\s*exit\s*\(?\s*0?\s*\)?\s*;', l) or re.search(r'^\s*sys\.exit\(0\)', l):
            early_exits.append((idx, l))

    if early_exits:
        print(f"  WARNING: Early exit detected: {early_exits}")
    else:
        print("  Early exit check: CLEAN")

    # Check for fake assertions
    fake_assertions = re.findall(r'(assert\s*\(\s*true\s*\)|assert\s+True|assertEquals\s*\(\s*true\s*,\s*true\s*\))', code, re.IGNORECASE)
    if fake_assertions:
        print(f"  WARNING: Fake assertions found: {fake_assertions}")
    else:
        print("  Fake assertion check: CLEAN")

    # Count real assertions / checks
    checks = re.findall(r'(assert|if\s*\(|strpos|preg_match|===|!==|==|!=)', code)
    print(f"  Condition/assertion density: {len(checks)} conditional/assertion checks")

print("\n" + "=" * 80)
