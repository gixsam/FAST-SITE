import os
import zipfile

root_dir = os.path.abspath("d:/TECH/WEBSITE/FAST SITE/fast site")
zip_path = os.path.join(root_dir, "fastsite_phase85.zip")

files_to_pack = [
    "user/dashboard.php",
    "includes/user_sidebar.php",
    "partner/dashboard.php",
    "partner/orders.php",
    "partner/products.php",
    "partner/product_add.php",
    "partner/nav.php",
    "home.php",
    "includes/nav_public.php",
    "assets/css/user.css",
    "assets/css/mobile_responsive.css",
    "PROJECT_STATE.md",
    "DEPLOYMENT_GUIDE.txt",
]

print(f"Building {zip_path}...")
if os.path.exists(zip_path):
    os.remove(zip_path)

with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as zf:
    for rel_path in files_to_pack:
        full_path = os.path.join(root_dir, rel_path)
        if not os.path.exists(full_path):
            raise FileNotFoundError(f"File not found: {full_path}")
        # Ensure forward slashes in archive relative path
        arcname = rel_path.replace("\\", "/")
        zf.write(full_path, arcname=arcname)
        file_size = os.path.getsize(full_path)
        print(f"  + Added: {arcname} ({file_size} bytes)")

zip_size = os.path.getsize(zip_path)
print(f"\nSUCCESS: Created {zip_path} ({zip_size} bytes)")

# Verification: Read back archive contents
print("\n--- Verifying Archive Contents ---")
with zipfile.ZipFile(zip_path, "r") as zf:
    infolist = zf.infolist()
    for info in infolist:
        print(f"  [OK] {info.filename} -> size: {info.file_size} bytes, compressed: {info.compress_size} bytes")
        if "\\" in info.filename:
            raise ValueError(f"Backslash detected in entry name: {info.filename}")
print(f"Total entries verified: {len(infolist)}")
