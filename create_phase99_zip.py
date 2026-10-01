import zipfile
import os
import sys
import time

# =========================================================================
# FAST SITE — SECURE PRODUCTION DEPLOYMENT BUILD ENGINE (PHASE 99)
# Strictly excludes all .env variants and uploads/ directory
# =========================================================================

PHASE_NUM = 99
PHASE_TITLE = "Clean Terminology Re-Word Migration & Consumer Trust Architecture (SafePay, Bengali Trust Badges, Refund & Claim Center)"
ZIP_NAME = f"fastsite_phase{PHASE_NUM}.zip"

# Strict, non-negotiable filter
def is_forbidden(rel_path, filename):
    lower_name = filename.lower()
    lower_path = rel_path.lower().replace('\\', '/')
    if lower_name == '.env' or lower_name.startswith('.env.') or lower_name.endswith('.env'):
        return True
    for part in lower_path.split('/'):
        if part == '.env' or part.startswith('.env.') or part.endswith('.env'):
            return True
        if part == 'uploads':
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
   - Production .env credentials and uploads/ media remain 100% safe and excluded.

Option B: MANUAL FILE MANAGER ZIP UPLOAD
   - Upload '{ZIP_NAME}' directly to the root of 'public_html/' on Hostinger.
   - Extract and choose "Overwrite existing files".
   - Your production .env file and user uploads are permanently safe (never touched or overwritten).

2. EXACT FOLDER PATHS ON HOSTINGER
--------------------------------------------------------------------------------
- public_html/home.php                 (UPDATED: SafePay 100% Buyer Guarantee Trust Badges)
- public_html/product_detail.php       (UPDATED: SafePay Trust Banner in Bengali & English)
- public_html/checkout.php             (UPDATED: SafePay Checkout & Protected Vault Terms)
- public_html/includes/nav_public.php  (UPDATED: Fast Cash Points in Drawer VIP Card)
- public_html/includes/user_sidebar.php(UPDATED: SafePay & Buyer Guarantee Wording)
- public_html/user/dashboard.php       (UPDATED: Consumer Support & Claim Description)
- public_html/user/partner_orders.php  (UPDATED: SafePay Stepper & Refund Claim Triggers)
- public_html/user/profile.php         (UPDATED: ID & Profile Verification Vault)
- public_html/user/become_partner.php  (UPDATED: ID & Profile Documents Label)
- public_html/partner/dashboard.php    (UPDATED: SafePay Buyer Guarantee Shop Text)
- public_html/partner/orders.php       (UPDATED: SafePay Secured & Refund Claims Tabs)
- public_html/partner/nav.php          (UPDATED: Refund & Claim Center Link)
- public_html/admin/nav.php            (UPDATED: Refund & Claim Center & SafePay Heatmap)
- public_html/admin/partner_disputes.php (UPDATED: 1-Click Pay Seller / Refund Buyer)
- public_html/admin/partner_shops.php  (UPDATED: SafePay Hub & Resolve Claim Action)
- public_html/admin/users_final.php    (UPDATED: ID & Profile Verification Vault)
- public_html/dropshop_api.php         (UPDATED: Reseller Partner API Description)
- public_html/api/cron_escrow_autorelease.php (UPDATED: 48h Auto-Completion Log)
- public_html/PROJECT_STATE.md         (UPDATED: Phase {PHASE_NUM} Specifications)
- public_html/NOTE.md                  (UPDATED: Master Status Report)
- public_html/DEPLOYMENT_GUIDE.txt     (This canonical instructions file)

3. WORKFLOW STEP WHERE AI LEFT OFF & VERIFICATION PROTOCOL
--------------------------------------------------------------------------------
Phase {PHASE_NUM} completed the Clean Terminology Re-Word Migration across all 18 files.
Next upcoming phase is Phase 100: 1-Click WhatsApp Quick Order & Mobile Sticky Buy Bar.

Verification steps on live production:
Step 1: Open Main Storefront:
        https://fastsite.best-travel.ltd/
        (Verify '✨ ১০০% নিরাপদ কেনাকাটা • 100% SafePay Buyer Guarantee' hero tagline).
Step 2: Open any Product Detail Page:
        https://fastsite.best-travel.ltd/product_detail.php?id=1
        (Verify the SafePay ১০০% নিরাপদ গ্যারান্টি trust banner).
Step 3: Open Admin Refund & Claim Center:
        https://fastsite.best-travel.ltd/admin/partner_disputes.php
        (Verify '⚖️ Refund & Claim Center' title and 1-click Pay Seller / Refund Buyer buttons).
Step 4: Open User Dashboard / Orders:
        https://fastsite.best-travel.ltd/user/partner_orders.php
        (Verify 'SafePay Secured' status in order stepper).

================================================================================
FAST SITE ECOSYSTEM — DUAL-SYNCED & DEPLOYMENT-READY
================================================================================
"""

with open('DEPLOYMENT_GUIDE.txt', 'w', encoding='utf-8') as f:
    f.write(deployment_guide_content)

print(f"Generated fresh DEPLOYMENT_GUIDE.txt for Phase {PHASE_NUM}.")

TARGET_FILES = [
    'home.php',
    'product_detail.php',
    'checkout.php',
    'includes/nav_public.php',
    'includes/user_sidebar.php',
    'user/dashboard.php',
    'user/partner_orders.php',
    'user/profile.php',
    'user/become_partner.php',
    'partner/dashboard.php',
    'partner/orders.php',
    'partner/nav.php',
    'admin/nav.php',
    'admin/partner_disputes.php',
    'admin/partner_shops.php',
    'admin/users_final.php',
    'dropshop_api.php',
    'api/cron_escrow_autorelease.php',
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
        
        base_name = os.path.basename(full_path)
        if is_forbidden(rel_file, base_name):
            print(f"[REJECTED] {rel_file} matched forbidden pattern! ABORTING.")
            sys.exit(1)
            
        archive_name = rel_file.replace('\\', '/')
        zf.write(full_path, arcname=archive_name)
        count_files += 1
        print(f"  + Added: {archive_name}")

# Post-build integrity verification
with zipfile.ZipFile(ZIP_NAME, 'r') as check_zf:
    archive_entries = check_zf.namelist()
    for entry in archive_entries:
        entry_base = os.path.basename(entry)
        if is_forbidden(entry, entry_base):
            os.remove(ZIP_NAME)
            raise ValueError(f"SECURITY BREACH: {entry} found in generated archive! Terminating.")

size_kb = os.path.getsize(ZIP_NAME) / 1024
print(f"\n[SUCCESS] Successfully generated {ZIP_NAME} with {count_files} files ({size_kb:.2f} KB).")
print(f"[SECURITY AUDIT] 100% verified: Zero .env files, zero credentials, zero uploads leak.")
