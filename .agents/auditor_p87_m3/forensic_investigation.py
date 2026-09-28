import os
import sys
import zipfile
import hashlib
import json
import re

ROOT_DIR = r"d:\TECH\WEBSITE\FAST SITE\fast site"
AUDIT_DIR = os.path.join(ROOT_DIR, ".agents", "auditor_p87_m3")
EXTRACT_DIR = os.path.join(AUDIT_DIR, "extracted_zip")

results = {
    "zip_integrity": {},
    "deployment_guide": {},
    "project_state": {},
    "test_integrity": {},
    "code_authenticity": {}
}

def sha256_file(filepath):
    h = hashlib.sha256()
    with open(filepath, "rb") as f:
        while chunk := f.read(65536):
            h.update(chunk)
    return h.hexdigest()

print("=" * 80)
print("PHASE 87 MILESTONE 3 FORENSIC AUDIT INVESTIGATION")
print("=" * 80)

# ----------------------------------------------------------------------
# 1. Zip Archive Integrity & Unix Path Normalization
# ----------------------------------------------------------------------
zip_path = os.path.join(ROOT_DIR, "fastsite_phase87.zip")
print(f"\n[CHECK 1] Inspecting ZIP Archive: {zip_path}")

if not os.path.exists(zip_path):
    print("FATAL: fastsite_phase87.zip does NOT exist!")
    results["zip_integrity"]["exists"] = False
else:
    zip_size = os.path.getsize(zip_path)
    zip_hash = sha256_file(zip_path)
    print(f"  Size: {zip_size:,} bytes")
    print(f"  SHA256: {zip_hash}")
    results["zip_integrity"]["size"] = zip_size
    results["zip_integrity"]["sha256"] = zip_hash

    os.makedirs(EXTRACT_DIR, exist_ok=True)

    with zipfile.ZipFile(zip_path, 'r') as z:
        # Test CRC integrity
        crc_test = z.testzip()
        print(f"  Zip CRC Test: {'PASSED (No corrupt files)' if crc_test is None else f'FAILED: {crc_test}'}")
        results["zip_integrity"]["crc_test_passed"] = (crc_test is None)

        infolist = z.infolist()
        print(f"  Total Entries in Zip: {len(infolist)}")
        results["zip_integrity"]["entry_count"] = len(infolist)

        backslash_paths = []
        entries_details = []
        for info in infolist:
            is_backslash = '\\' in info.filename
            if is_backslash:
                backslash_paths.append(info.filename)
            entries_details.append({
                "filename": info.filename,
                "file_size": info.file_size,
                "compress_size": info.compress_size,
                "crc": hex(info.CRC),
                "is_backslash": is_backslash
            })
            print(f"    - {info.filename} ({info.file_size:,} bytes) CRC: {hex(info.CRC)}")

        results["zip_integrity"]["backslash_paths"] = backslash_paths
        results["zip_integrity"]["all_paths_normalized_unix"] = len(backslash_paths) == 0

        # Extract all files
        z.extractall(EXTRACT_DIR)
        print(f"  Extracted all files to: {EXTRACT_DIR}")

# ----------------------------------------------------------------------
# Compare Extracted Files vs Source Files on Disk
# ----------------------------------------------------------------------
print("\n[CHECK 2] Comparing Extracted Files vs Working Disk Files")
mismatches = []
file_comparisons = []

expected_files = [
    "includes/user_sidebar.php",
    "user/dashboard.php",
    "assets/css/user.css",
    "partner/nav.php",
    "partner/dashboard.php",
    "partner/index.php",
    "partner/logout.php",
    "partner/product_add.php",
    "partner/product_edit.php",
    "partner/product_delete.php",
    "partner/profile.php",
    "partner/api_docs.php",
    "DEPLOYMENT_GUIDE.txt"
]

for rel_path in expected_files:
    disk_path = os.path.join(ROOT_DIR, rel_path.replace('/', os.sep))
    extracted_path = os.path.join(EXTRACT_DIR, rel_path.replace('/', os.sep))

    if not os.path.exists(disk_path):
        print(f"  MISSING ON DISK: {rel_path}")
        mismatches.append({"file": rel_path, "error": "missing_on_disk"})
        continue

    if not os.path.exists(extracted_path):
        print(f"  MISSING IN ZIP: {rel_path}")
        mismatches.append({"file": rel_path, "error": "missing_in_zip"})
        continue

    disk_size = os.path.getsize(disk_path)
    ext_size = os.path.getsize(extracted_path)
    disk_hash = sha256_file(disk_path)
    ext_hash = sha256_file(extracted_path)

    match = (disk_hash == ext_hash and disk_size == ext_size)
    print(f"  {rel_path}:")
    print(f"    Disk:      {disk_size:,} bytes | SHA256: {disk_hash}")
    print(f"    Extracted: {ext_size:,} bytes | SHA256: {ext_hash}")
    print(f"    Match:     {'YES (EXACT)' if match else 'NO (MISMATCH)'}")

    if not match:
        mismatches.append({
            "file": rel_path,
            "error": "hash_mismatch",
            "disk_hash": disk_hash,
            "ext_hash": ext_hash
        })

    file_comparisons.append({
        "file": rel_path,
        "disk_size": disk_size,
        "ext_size": ext_size,
        "disk_hash": disk_hash,
        "ext_hash": ext_hash,
        "match": match
    })

results["zip_integrity"]["mismatches"] = mismatches
results["zip_integrity"]["files_match_disk"] = len(mismatches) == 0

# ----------------------------------------------------------------------
# 2. DEPLOYMENT_GUIDE.txt Forensic Inspection
# ----------------------------------------------------------------------
print("\n[CHECK 3] Forensic Inspection of DEPLOYMENT_GUIDE.txt")
guide_path = os.path.join(ROOT_DIR, "DEPLOYMENT_GUIDE.txt")
if not os.path.exists(guide_path):
    print("FATAL: DEPLOYMENT_GUIDE.txt missing!")
    results["deployment_guide"]["exists"] = False
else:
    with open(guide_path, "r", encoding="utf-8") as f:
        guide_content = f.read()

    # Rule 3 Checks:
    # 1. Exact folder paths on Hostinger where the files go
    has_hostinger_paths = "public_html/" in guide_content and "assets/css/user.css" in guide_content
    # 2. Exact workflow step where AI left off
    has_workflow_step = "AI left off at:" in guide_content or "Workflow Step:" in guide_content
    # 3. Current active Phase number
    has_phase_number = "Phase 87" in guide_content and "Active Phase:" in guide_content
    # Rule 5 Check: Local Server Live Host reminder
    has_live_host_reminder = "LOCAL SERVER LIVE HOST" in guide_content and "php -S localhost:8000" in guide_content

    print(f"  Rule 3.1 (Hostinger folder paths specified): {has_hostinger_paths}")
    print(f"  Rule 3.2 (Exact workflow step specified):    {has_workflow_step}")
    print(f"  Rule 3.3 (Active phase number specified):    {has_phase_number}")
    print(f"  Rule 5   (Local Server Live Host reminder):  {has_live_host_reminder}")

    results["deployment_guide"]["rule_3_paths"] = has_hostinger_paths
    results["deployment_guide"]["rule_3_workflow"] = has_workflow_step
    results["deployment_guide"]["rule_3_phase"] = has_phase_number
    results["deployment_guide"]["rule_5_live_host"] = has_live_host_reminder

    # Verify SHA256 checksums documented in DEPLOYMENT_GUIDE.txt vs actual disk files
    guide_manifest_errors = []
    for item in file_comparisons:
        rel = item["file"]
        if rel == "DEPLOYMENT_GUIDE.txt":
            continue
        # Look for rel in table
        pattern = re.escape(rel) + r"\s+\|\s+(\d+)\s+\|\s+([a-fA-F0-9]{64})"
        match = re.search(pattern, guide_content)
        if not match:
            print(f"  WARNING: {rel} not found in DEPLOYMENT_GUIDE.txt manifest table!")
            guide_manifest_errors.append({"file": rel, "error": "not_in_manifest"})
        else:
            table_size = int(match.group(1))
            table_hash = match.group(2).lower()
            actual_size = item["disk_size"]
            actual_hash = item["disk_hash"].lower()

            size_ok = (table_size == actual_size)
            hash_ok = (table_hash == actual_hash)

            print(f"  Manifest check for {rel}: Size={table_size} (Actual={actual_size}, {'OK' if size_ok else 'FAIL'}) | Hash={'OK' if hash_ok else 'FAIL'}")
            if not size_ok or not hash_ok:
                guide_manifest_errors.append({
                    "file": rel,
                    "table_size": table_size,
                    "actual_size": actual_size,
                    "table_hash": table_hash,
                    "actual_hash": actual_hash
                })

    results["deployment_guide"]["manifest_errors"] = guide_manifest_errors
    results["deployment_guide"]["manifest_accurate"] = len(guide_manifest_errors) == 0

# ----------------------------------------------------------------------
# 3. Code Authenticity & Facade / Dummy Code Detection
# ----------------------------------------------------------------------
print("\n[CHECK 4] Code Authenticity & Dummy Code Inspection in Phase 87 Files")
code_authenticity_issues = []

for rel_path in expected_files:
    if rel_path == "DEPLOYMENT_GUIDE.txt":
        continue
    disk_path = os.path.join(ROOT_DIR, rel_path.replace('/', os.sep))
    with open(disk_path, "r", encoding="utf-8", errors="replace") as f:
        content = f.read()

    # Check for empty files
    if len(content.strip()) == 0:
        code_authenticity_issues.append({"file": rel_path, "error": "empty_file"})
        continue

    # Check for obvious facades
    if len(content.strip().splitlines()) < 5 and "return true" in content.lower():
        code_authenticity_issues.append({"file": rel_path, "error": "facade_placeholder"})

    # Check specific functionality promised
    if rel_path == "includes/user_sidebar.php":
        if "function getUserShopState" not in content:
            code_authenticity_issues.append({"file": rel_path, "error": "missing_getUserShopState"})
        if "quickShopDrawer" not in content:
            code_authenticity_issues.append({"file": rel_path, "error": "missing_quickShopDrawer"})
        if "shopReviewModal" not in content:
            code_authenticity_issues.append({"file": rel_path, "error": "missing_shopReviewModal"})
        if "top-mode-pill" not in content:
            code_authenticity_issues.append({"file": rel_path, "error": "missing_top_mode_pill"})

    if rel_path == "partner/nav.php":
        if "header-mode-pill" not in content and "Switch to Buyer Mode" not in content:
            code_authenticity_issues.append({"file": rel_path, "error": "missing_buyer_mode_pill"})
        if "nav-back-btn" not in content:
            code_authenticity_issues.append({"file": rel_path, "error": "missing_nav_back_btn"})
        if "partner-bottom-dock" not in content:
            code_authenticity_issues.append({"file": rel_path, "error": "missing_partner_bottom_dock"})
        if "isActive" not in content or "is_array" not in content:
            code_authenticity_issues.append({"file": rel_path, "error": "missing_isActive_array_support"})

    if rel_path == "partner/dashboard.php":
        if "btn-action-buyer" not in content:
            code_authenticity_issues.append({"file": rel_path, "error": "missing_btn_action_buyer"})

    # Check partner files for eliminated 404 redirects
    if rel_path in ["partner/index.php", "partner/logout.php", "partner/product_add.php", "partner/product_edit.php", "partner/product_delete.php", "partner/profile.php", "partner/api_docs.php"]:
        if "partner/login.php" in content:
            code_authenticity_issues.append({"file": rel_path, "error": "still_references_partner_login.php"})
        if "user/login.php" not in content:
            code_authenticity_issues.append({"file": rel_path, "error": "does_not_redirect_to_user_login.php"})

print(f"  Authenticity Issues Found: {len(code_authenticity_issues)}")
for issue in code_authenticity_issues:
    print(f"    - {issue}")

results["code_authenticity"]["issues"] = code_authenticity_issues
results["code_authenticity"]["authentic"] = len(code_authenticity_issues) == 0

# ----------------------------------------------------------------------
# 4. Save Forensic Summary
# ----------------------------------------------------------------------
summary_path = os.path.join(AUDIT_DIR, "forensic_summary.json")
with open(summary_path, "w", encoding="utf-8") as f:
    json.dump(results, f, indent=2)

print(f"\nForensic audit data written to: {summary_path}")
print("=" * 80)
