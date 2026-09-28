#!/usr/bin/env python3
"""Create Phase 60 deployment zip for Hostinger"""
import zipfile, os, time

EXCLUDE_DIRS = {
    '.git', '.idea', '.agents', 'node_modules', '__pycache__',
    'FastSiteApp', '_build_archives', '_dev_tools', 'chatbot-server',
    'whatsapp-bot', 'APK FILE USER', 'apk file ADMIN', 'AFFILIATE PARTNERS LOGO',
    'MEDIA PHOTO', 'SQL FILE',
}
EXCLUDE_EXTS = {'.zip', '.apk', '.db', '.log', '.py', '.xlsx', '.csv'}
EXCLUDE_FILES = {'.env', 'fix_all_bugs.py', 'fix_encoding.py', 'fix_apache.py',
                 'fix_sql.py', 'repair_xampp.py', 'update_env.py',
                 'create_fixed_zip.py', 'create_zip_safe.py', 'create_p33_zip.py',
                 'create_phase60_zip.py'}

deployment_guide = """DEPLOYMENT GUIDE — FAST SITE Phase 60
=======================================
Created: """ + time.strftime('%Y-%m-%d %H:%M') + """
Phase: 60 — Gamified Affiliate Missions & Tiers

WHAT'S IN THIS ZIP:
  All PHP files, CSS, JS, uploads folder, assets updated up to Phase 60

HOSTINGER UPLOAD STEPS:
  1. Login to Hostinger File Manager
  2. Navigate to public_html/
  3. Upload this zip file (fastsite_phase60.zip)
  4. Extract > Overwrite all existing files
  5. Delete the zip file after extraction

FILES DESTINATION:
  All files → public_html/ (root)
  Example: home.php → public_html/home.php
           admin/   → public_html/admin/

CRITICAL: After upload, check these URLs work:
  https://fastsite.best-travel.ltd/           (marketplace)
  https://fastsite.best-travel.ltd/admin/     (admin panel)
  https://fastsite.best-travel.ltd/user/login.php

WHAT WAS ADDED IN PHASE 34-60:
  - 5 Major Marketplace Engines (72h Auto-Release, Digital Auto-Fulfillment, Verified Reviews, Seller Analytics, 1-Click Disputes)
  - Gamified Affiliate Missions & Tiers
  - Affiliate Gamification Dashboard (affiliate/dashboard.php)
  - Tier Engine (Bronze, Silver, Gold, Platinum) with percentage-based commission bonuses
  - Missions system where affiliates earn direct Coin Rewards for reaching specific milestones.

DO NOT OVERWRITE:
  - .env (keep your Hostinger DB credentials)
  - uploads/ folder (keep all user uploaded files)

Current Active Phase: 60
AI Last Worked On: Complete Phase 60 Package
"""

zip_name = 'fastsite_phase61.zip'
count = 0

with zipfile.ZipFile(zip_name, 'w', zipfile.ZIP_DEFLATED, compresslevel=6) as zf:
    zf.writestr('DEPLOYMENT_GUIDE.txt', deployment_guide)
    
    for root, dirs, files in os.walk('.'):
        # Skip excluded directories
        dirs[:] = [d for d in dirs if d not in EXCLUDE_DIRS and not d.startswith('.')]
        
        for file in files:
            if file in EXCLUDE_FILES:
                continue
            ext = os.path.splitext(file)[1].lower()
            if ext in EXCLUDE_EXTS:
                continue
            
            full_path = os.path.join(root, file)
            arc_name = full_path[2:]  # Remove '.\\' or './'
            if arc_name.startswith('\\') or arc_name.startswith('/'):
                arc_name = arc_name[1:]
                
            try:
                zf.write(full_path, arc_name)
                count += 1
            except Exception as e:
                print(f"Skip {arc_name}: {e}")

print(f"Created {zip_name} with {count} files")
size_mb = os.path.getsize(zip_name) / 1024 / 1024
print(f"Size: {size_mb:.1f} MB")
