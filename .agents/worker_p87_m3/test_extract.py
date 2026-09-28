import os
import zipfile
import tempfile
import shutil

zip_path = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "fastsite_phase87.zip"))
temp_dir = tempfile.mkdtemp(prefix="fastsite_extract_test_")

print("Extracting archive to temp dir:", temp_dir)
try:
    with zipfile.ZipFile(zip_path, "r") as zf:
        zf.extractall(temp_dir)
        print("Extracted files count:", len(zf.namelist()))
        for name in zf.namelist():
            extracted_file = os.path.join(temp_dir, *name.split("/"))
            assert os.path.isfile(extracted_file), f"Extracted file missing: {extracted_file}"
            print(f"  Verified: {name:<30} ({os.path.getsize(extracted_file)} bytes)")
    print("\nEXTRACTION TEST: 100% SUCCESSFUL!")
finally:
    shutil.rmtree(temp_dir)
    print("Cleaned up temporary extraction directory.")
