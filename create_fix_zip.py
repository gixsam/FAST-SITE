import os
import zipfile

source_dir = r"D:\TECH\WEBSITE\FAST SITE\fast site"
zip_path = r"D:\TECH\WEBSITE\FAST SITE\fastsite_fix_500_error.zip"

files_to_zip = [
    "partner/dashboard.php",
    "user/withdraw_coins.php",
    "user/my_jobs.php",
    "user/place_order.php"
]

with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
    for file_path in files_to_zip:
        full_path = os.path.join(source_dir, os.path.normpath(file_path))
        if os.path.exists(full_path):
            print(f"Adding {file_path}")
            zipf.write(full_path, arcname=file_path)

print(f"\nSuccessfully created hotfix zip at: {zip_path}")
