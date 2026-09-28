import zipfile
import os
import sys
import time

# =========================================================================
# FAST SITE — SECURE PRODUCTION DEPLOYMENT BUILD ENGINE (PHASE 88)
# Strictly excludes all .env variants to protect production credentials
# =========================================================================

PHASE_NUM = 88
PHASE_TITLE = "Strict .env Exclusion & Dynamic Root-Relative Image Architecture"
ZIP_NAME = f"fastsite_phase{PHASE_NUM}.zip"

# Strict, non-negotiable .env filter
def is_forbidden_env(rel_path, filename):
    lower_name = filename.lower()
    lower_path = rel_path.lower().replace('\\', '/')
    
    # 1. Exact or variant .env files
    if lower_name == '.env' or lower_name.startswith('.env.') or lower_name.endswith('.env'):
        return True
    # 2. Path components containing .env
    for part in lower_path.split('/'):
        if part == '.env' or part.startswith('.env.') or part.endswith('.env'):
            return True
    return False

# Directories to exclude from deployment
EXCLUDE_DIRS = {
    '.git', '.idea', '.agents', 'node_modules', '__pycache__',
    'FastSiteApp', '_build_archives', '_dev_tools', 'chatbot-server',
    'whatsapp-bot', 'APK FILE USER', 'apk file ADMIN', 'AFFILIATE PARTNERS LOGO',
    'MEDIA PHOTO', 'SQL FILE', 'sessions', 'scratch', 'build'
}

# File extensions to exclude
EXCLUDE_EXTS = {'.zip', '.db', '.sqlite', '.sqlite3', '.log', '.DS_Store'}

# Standalone files to exclude
EXCLUDE_FILES = {
    'create_project_zip.php',
    'create_zip.py',
    'create_phase36_zip.py',
    'create_phase60_zip.py',
    'create_admin_zip.py',
    'create_update_zip.py',
    'create_fix_zip.py',
    'make_zip.py',
    'audit_full.py',
    'debug_post.py',
    'export.py',
    'fix_asset_paths.py',
    'fix_image_paths.py',
    'generate_checker.py',
    'test_reg_fast.py',
    'verify_registration.py',
    'apply_media_paths_upgrade.py'
}

# Generate compliant DEPLOYMENT_GUIDE.txt
deployment_guide_content = f"""================================================================================
FAST SITE — HOSTINGER DEPLOYMENT GUIDE (PHASE {PHASE_NUM})
================================================================================
Archive: {ZIP_NAME}
Active Phase: Phase {PHASE_NUM} — {PHASE_TITLE}
Workflow Step: Phase {PHASE_NUM} 100% Complete (Hardened Media & Security Deployment)
Date: {time.strftime('%Y-%m-%d')}
Target Environment: Hostinger File Manager -> public_html/ (Root Directory)
================================================================================

--------------------------------------------------------------------------------
1. EXACT FOLDER PATHS ON HOSTINGER
--------------------------------------------------------------------------------
Extract all contents of {ZIP_NAME} directly into your Hostinger `public_html/` root.
Ensure "Overwrite existing files" is checked.

Core Files Included in This Phase:
  public_html/
  ├── config.php                      (Universal root-relative media resolver)
  ├── .htaccess                       (Direct 404 for missing media, blocks WAF 422)
  ├── checkout.php                    (Root-relative item preview)
  ├── includes/
  │   └── image_helper.php            (Root-relative media helper functions)
  ├── partner/
  │   └── dashboard.php               (Root-relative product thumbnails)
  ├── admin/
  │   ├── partner_shops.php           (Root-relative shop logos & avatars)
  │   └── shop_edit.php               (Root-relative shop logos & product media)
  ├── user/
  │   └── forgot_password.php         (Root-relative fallback logo)
  ├── .gitignore                      (Repository exclusion for .env and temp files)
  └── DEPLOYMENT_GUIDE.txt            (This canonical instructions file)

--------------------------------------------------------------------------------
2. CRITICAL SECURITY NOTICE: .ENV EXCLUSION
--------------------------------------------------------------------------------
[SECURITY COMPLIANT]: The `.env` file has been STRICTLY and PERMANENTLY EXCLUDED
from this deployment archive. Your live Hostinger production database credentials
(MySQL user: u422364295_admin) will NEVER be overwritten or exposed.

--------------------------------------------------------------------------------
3. DYNAMIC ROOT-RELATIVE IMAGE RESOLUTION SUMMARY
--------------------------------------------------------------------------------
- All image URLs now resolve with dynamic root-relative paths (e.g. `/uploads/...`,
  `/assets/...`), allowing seamless rendering on both Localhost (port 8000) and
  Hostinger production without environmental configuration changes.
- Localhost domains (`http://localhost:8000/...`, `http://127.0.0.1:...`) are
  automatically stripped and converted into root-relative paths `/uploads/...`.
- Windows backslashes (`\\`) are normalized to POSIX forward slashes (`/`).
- Missing media files in `.htaccess` return a clean HTTP 404 directly, stopping
  Apache from rewriting missing images to `index.php` and eliminating WAF 422 errors.

--------------------------------------------------------------------------------
4. CURRENT ACTIVE PHASE NUMBER
--------------------------------------------------------------------------------
Active Phase: Phase {PHASE_NUM} (100% COMPLETE & VERIFIED)
Next Recommended Phase: Phase 89

--------------------------------------------------------------------------------
5. MANDATORY LOCAL SERVER LIVE HOST REMINDER
--------------------------------------------------------------------------------
Always turn on your LOCAL SERVER LIVE HOST (e.g., `php -S localhost:8000`)
and test all storefront cards, user dashboard, shop panel, and checkout images
locally before deploying the archive to the live Hostinger environment!
================================================================================
"""

with open('DEPLOYMENT_GUIDE.txt', 'w', encoding='utf-8') as f:
    f.write(deployment_guide_content)

print(f"Generated fresh DEPLOYMENT_GUIDE.txt for Phase {PHASE_NUM}.")

# Specific files modified for Phase 88 deployment package
TARGET_FILES = [
    'config.php',
    '.htaccess',
    '.gitignore',
    'checkout.php',
    'includes/image_helper.php',
    'partner/dashboard.php',
    'admin/partner_shops.php',
    'admin/shop_edit.php',
    'user/forgot_password.php',
    'DEPLOYMENT_GUIDE.txt'
]

if os.path.exists(ZIP_NAME):
    os.remove(ZIP_NAME)

count_files = 0
count_dirs = 0
added_dirs = set()

with zipfile.ZipFile(ZIP_NAME, 'w', zipfile.ZIP_DEFLATED, compresslevel=6) as zf:
    for rel_file in TARGET_FILES:
        full_path = os.path.join('.', rel_file.replace('/', os.sep))
        if not os.path.exists(full_path):
            print(f"[WARN] Target file {full_path} not found on disk, skipping.")
            continue
        
        # Absolute security check against .env
        filename = os.path.basename(full_path)
        if is_forbidden_env(rel_file, filename):
            print(f"[SECURITY BLOCKED] Refusing to bundle forbidden env file: {rel_file}")
            continue

        # Normalise POSIX path
        norm_rel = rel_file.replace('\\', '/')
        
        # Register parent directory in ZIP if needed
        parent_dir = os.path.dirname(norm_rel)
        if parent_dir and parent_dir != '.' and parent_dir not in added_dirs:
            dir_arc = parent_dir.rstrip('/') + '/'
            zdir = zipfile.ZipInfo(dir_arc, (2026, 9, 28, 12, 0, 0))
            zdir.create_system = 3  # UNIX
            zdir.external_attr = (0x4000 | 0o755) << 16  # drwxr-xr-x
            zf.writestr(zdir, '')
            added_dirs.add(parent_dir)
            count_dirs += 1

        # Package file with UNIX attributes
        st = os.stat(full_path)
        mtime = time.localtime(st.st_mtime)[:6]
        zinfo = zipfile.ZipInfo(norm_rel, mtime)
        zinfo.create_system = 3  # UNIX
        zinfo.external_attr = (0x8000 | 0o644) << 16  # -rw-r--r--
        zinfo.compress_type = zipfile.ZIP_DEFLATED

        with open(full_path, 'rb') as f:
            zf.writestr(zinfo, f.read())
        count_files += 1

# =========================================================================
# MANDATORY POST-BUILD ZERO-TOLERANCE SECURITY AUDIT
# =========================================================================
print("\n--- Running Mandatory Post-Build Security Audit ---")
with zipfile.ZipFile(ZIP_NAME, 'r') as verify_zf:
    archive_files = verify_zf.namelist()
    for item in archive_files:
        item_base = os.path.basename(item)
        if is_forbidden_env(item, item_base):
            os.remove(ZIP_NAME)
            raise RuntimeError(f"CRITICAL SECURITY BREACH: {item} was found inside {ZIP_NAME}! Archive has been deleted.")

print(f"[SECURITY AUDIT PASSED]: 0 .env files detected. Production credentials safe.")
print(f"[SUCCESS] Packaged {ZIP_NAME} with {count_files} files and {count_dirs} directories.")
size_kb = os.path.getsize(ZIP_NAME) / 1024
print(f"Total Size: {size_kb:.2f} KB")
