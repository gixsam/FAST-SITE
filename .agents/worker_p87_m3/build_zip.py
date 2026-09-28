import os
import zipfile

root_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
zip_path = os.path.join(root_dir, "fastsite_phase87.zip")

if os.path.exists(zip_path):
    os.remove(zip_path)

files_to_archive = [
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

print("================================================================================")
print("BUILDING ARCHIVE: fastsite_phase87.zip")
print("================================================================================")

with zipfile.ZipFile(zip_path, "w", compression=zipfile.ZIP_DEFLATED) as zf:
    for rel_path in files_to_archive:
        # Normalize to forward slash for archive entry
        arcname = rel_path.replace("\\", "/")
        full_path = os.path.join(root_dir, *rel_path.split("/"))
        if not os.path.exists(full_path):
            raise FileNotFoundError(f"Missing file: {full_path}")
        zf.write(full_path, arcname)
        file_size = os.path.getsize(full_path)
        print(f"Added: {arcname:<32} ({file_size:>6} bytes)")

print("\nArchive successfully written. Size:", os.path.getsize(zip_path), "bytes")

print("\n================================================================================")
print("VERIFYING ARCHIVE INTEGRITY & PATH NORMALIZATION")
print("================================================================================")

with zipfile.ZipFile(zip_path, "r") as zf:
    infolist = zf.infolist()
    print(f"Total entries: {len(infolist)}")
    has_error = False
    for idx, info in enumerate(infolist, 1):
        filename = info.filename
        if "\\" in filename:
            print(f"ERROR: Entry contains backslash: {filename}")
            has_error = True
        else:
            print(f"[{idx:2d}] {filename:<32} | Uncompressed: {info.file_size:>6} bytes | CRC: {info.CRC:08X}")

    test_result = zf.testzip()
    if test_result is not None:
        print(f"ERROR: Corrupt archive entry: {test_result}")
        has_error = True
    else:
        print("\nIntegrity Test: PASSED (Zero CRC errors, zero corrupt records)")

if not has_error and len(infolist) == len(files_to_archive):
    print("STATUS: ARCHIVE VERIFIED 100% CLEAN WITH NORMALIZED UNIX FORWARD SLASHES.")
else:
    print("STATUS: VERIFICATION FAILED!")
    exit(1)
