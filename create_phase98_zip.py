import zipfile
import os
import sys
import time

# =========================================================================
# FAST SITE — SECURE PRODUCTION DEPLOYMENT BUILD ENGINE (PHASE 98)
# Strictly excludes all .env variants to protect production credentials
# =========================================================================

PHASE_NUM = 98
PHASE_TITLE = "New Products Featuring Slideable Bar Integration & High-Conversion Showcase Architecture"
ZIP_NAME = f"fastsite_phase{PHASE_NUM}.zip"

# Strict, non-negotiable .env filter
def is_forbidden_env(rel_path, filename):
    lower_name = filename.lower()
    lower_path = rel_path.lower().replace('\\', '/')
    if lower_name == '.env' or lower_name.startswith('.env.') or lower_name.endswith('.env'):
        return True
    for part in lower_path.split('/'):
        if part == '.env' or part.startswith('.env.') or part.endswith('.env'):
            return True
    return False

deployment_guide_content = f"""================================================================================
FAST SITE — HOSTINGER DEPLOYMENT GUIDE (PHASE {PHASE_NUM})
================================================================================
Archive: {ZIP_NAME}
Current Active Phase: Phase {PHASE_NUM}
Phase Title: {PHASE_TITLE}
Deployment Target: Hostinger Production (public_html)
Repository: https://github.com/gixsam/FAST-SITE (Branch: main)
Date: {time.strftime('%Y-%m-%d')}
================================================================================

1. DEPLOYMENT INSTRUCTIONS
--------------------------------------------------------------------------------
Option A: AUTOMATED GIT DEPLOYMENT (Recommended & Already Active)
   - Every push to 'main' automatically deploys to Hostinger public_html within ~5 seconds.
   - Production .env credentials remain 100% safe and excluded.

Option B: MANUAL FILE MANAGER ZIP UPLOAD
   - Upload '{ZIP_NAME}' directly to the root of 'public_html/' on Hostinger.
   - Extract and choose "Overwrite existing files".
   - Your production .env file is permanently safe (never touched or overwritten).

2. EXACT FOLDER PATHS ON HOSTINGER
--------------------------------------------------------------------------------
- public_html/home.php                 (UPDATED: New Products Featuring Slideable Bar)
- public_html/PROJECT_STATE.md         (UPDATED: Phase {PHASE_NUM} Specifications)
- public_html/NOTE.md                  (UPDATED: Master Status Report)
- public_html/DEPLOYMENT_GUIDE.txt     (This canonical instructions file)

3. WORKFLOW STEP WHERE AI LEFT OFF & VERIFICATION PROTOCOL
--------------------------------------------------------------------------------
After uploading or automated Git push:
Step 1: Open the Main Storefront on Hostinger:
        https://fastsite.best-travel.ltd/
        (Verify that the '✨ New Products Featuring' Slideable Bar appears directly
        beneath the search bar with smooth touch swiping and desktop chevrons).
Step 2: Verify Product Photos & Badges:
        (All cards render high-definition imagery via resolveProductArtwork(),
        with '✨ NEW' gold pills, shop verification dots, and dual BDT/Coins prices).
Step 3: Test Desktop / Mobile Responsiveness:
        (On mobile screens <640px, the track swipes natively with momentum and
        scroll-snap; on desktop, the ‹ and › chevron buttons slide smoothly).

================================================================================
FAST SITE ECOSYSTEM — DUAL-SYNCED & DEPLOYMENT-READY
================================================================================
"""

with open('DEPLOYMENT_GUIDE.txt', 'w', encoding='utf-8') as f:
    f.write(deployment_guide_content)

print(f"Generated fresh DEPLOYMENT_GUIDE.txt for Phase {PHASE_NUM}.")

TARGET_FILES = [
    'home.php',
    'PROJECT_STATE.md',
    'NOTE.md',
    'DEPLOYMENT_GUIDE.txt'
]

if os.path.exists(ZIP_NAME):
    os.remove(ZIP_NAME)

count_files = 0

with zipfile.ZipFile(ZIP_NAME, 'w', zipfile.ZIP_DEFLATED, compresslevel=6) as zf:
    for rel_file in TARGET_FILES:
        full_path = os.path.join('.', rel_file.replace('/', os.sep))
        if not os.path.exists(full_path):
            print(f"[WARN] Target file {full_path} not found on disk, skipping.")
            continue
        
        filename = os.path.basename(full_path)
        if is_forbidden_env(rel_file, filename):
            print(f"[SECURITY BLOCKED] Refusing to bundle forbidden env file: {rel_file}")
            continue

        norm_rel = rel_file.replace('\\', '/')
        st = os.stat(full_path)
        mtime = time.localtime(st.st_mtime)[:6]
        zinfo = zipfile.ZipInfo(norm_rel, mtime)
        zinfo.create_system = 3  # UNIX
        zinfo.external_attr = (0x8000 | 0o644) << 16  # -rw-r--r--
        zinfo.compress_type = zipfile.ZIP_DEFLATED

        with open(full_path, 'rb') as f:
            zf.writestr(zinfo, f.read())
        count_files += 1

# Mandatory Post-Build Zero-Tolerance Security Audit
print("\n--- Running Mandatory Post-Build Security Audit ---")
with zipfile.ZipFile(ZIP_NAME, 'r') as verify_zf:
    archive_files = verify_zf.namelist()
    for item in archive_files:
        item_base = os.path.basename(item)
        if is_forbidden_env(item, item_base):
            os.remove(ZIP_NAME)
            raise RuntimeError(f"CRITICAL SECURITY BREACH: {item} was found inside {ZIP_NAME}! Archive has been deleted.")

print(f"[SECURITY AUDIT PASSED]: 0 .env files detected. Production credentials safe.")
print(f"[SUCCESS] Packaged {ZIP_NAME} with {count_files} files.")
size_kb = os.path.getsize(ZIP_NAME) / 1024
print(f"Total Size: {size_kb:.2f} KB")
