import os
import zipfile

# Source directory
source_dir = r"D:\TECH\WEBSITE\FAST SITE\fast site"
# Output zip file
zip_path = r"D:\TECH\WEBSITE\FAST SITE\fastsite_update_phase62_to_64.zip"

# The exact files updated during Phases 62, 63, and 64
files_to_zip = [
    "config.php",
    "schema.php",
    "PROJECT_STATE.md",
    "DEPLOYMENT_GUIDE.txt",
    "HIGH CODING BY CLAUDE SONNET.md",
    "includes/user_sidebar.php",
    "includes/nav_public.php",
    "user/withdraw_coins.php",
    "user/wallet.php",
    "user/deposit.php",
    "user/dashboard.php",
    "user/place_order.php",
    "user/partner_orders.php",
    "user/missions.php",
    "admin/nav.php",
    "admin/wallet.php",
    "admin/db_repair.php",
    "partner/dashboard.php"
]

with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
    for file_path in files_to_zip:
        full_path = os.path.join(source_dir, os.path.normpath(file_path))
        if os.path.exists(full_path):
            print(f"Adding {file_path}")
            zipf.write(full_path, arcname=file_path)
        else:
            print(f"WARNING: {file_path} not found!")

print(f"\nSuccessfully created update zip at: {zip_path}")
