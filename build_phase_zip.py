import zipfile
import os
import sys
import time

# =========================================================================
# build_phase_zip.py — General Phase Packaging Script with Strict .env Exclusion
# =========================================================================

PHASE_NUM = 88
zip_name = f'fastsite_phase{PHASE_NUM}.zip'

def is_forbidden_env(rel_path, filename):
    lower_name = filename.lower()
    lower_path = rel_path.lower().replace('\\', '/')
    if lower_name == '.env' or lower_name.startswith('.env.') or lower_name.endswith('.env'):
        return True
    for part in lower_path.split('/'):
        if part == '.env' or part.startswith('.env.') or part.endswith('.env'):
            return True
    return False

if os.path.exists(zip_name):
    os.remove(zip_name)

excludes = [
    '.git', '.idea', '.agents', 'node_modules', '__pycache__',
    'fastsite_phase', 'fastsite_admin', '.zip', '.db', '.sqlite',
    'apk file ADMIN', 'apk file User', 'build', 'sessions', 'uploads'
]

# Run build_hostinger_zip.py which handles Phase 88 target packaging
import subprocess
result = subprocess.run([sys.executable, 'build_hostinger_zip.py'], capture_output=True, text=True)
print(result.stdout)
if result.stderr:
    print(result.stderr)
sys.exit(result.returncode)
