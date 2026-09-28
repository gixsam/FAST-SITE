import zipfile, os, time

zip_name = 'fastsite_phase62_v6.zip'
if os.path.exists(zip_name):
    os.remove(zip_name)

EXCLUDE_DIRS = {
    '.git', '.idea', '.agents', 'node_modules', '__pycache__',
    'FastSiteApp', '_build_archives', '_dev_tools', 'chatbot-server',
    'whatsapp-bot', 'APK FILE USER', 'apk file ADMIN', 'AFFILIATE PARTNERS LOGO',
    'MEDIA PHOTO', 'SQL FILE', 'uploads'
}
EXCLUDE_EXTS = {'.zip', '.apk', '.db', '.log', '.py', '.xlsx', '.csv', '.DS_Store'}
EXCLUDE_FILES = {'.env', 'create_project_zip.php'}

count = 0
skipped_spaces = []

with zipfile.ZipFile(zip_name, 'w', zipfile.ZIP_DEFLATED, compresslevel=6) as zf:
    for root, dirs, files in os.walk('.'):
        dirs[:] = [d for d in dirs if d not in EXCLUDE_DIRS and not d.startswith('.')]
        for file in files:
            # Skip excluded files
            if file in EXCLUDE_FILES:
                continue
            # Skip excluded extensions
            ext = os.path.splitext(file)[1].lower()
            if ext in EXCLUDE_EXTS:
                continue
            # CRITICAL FIX: Skip files with spaces in their name
            # Hostinger's Linux extractor crashes with 500 error on these
            if ' ' in file:
                skipped_spaces.append(os.path.join(root, file))
                continue

            full_path = os.path.join(root, file)
            arc_name = full_path[2:].replace('\\', '/')
            if arc_name.startswith('/'):
                arc_name = arc_name[1:]

            try:
                st = os.stat(full_path)
                zinfo = zipfile.ZipInfo(arc_name, time.localtime(st.st_mtime)[:6])
                # Force UNIX system ID so Hostinger reads permissions correctly
                zinfo.create_system = 3
                # 0o644 = rw-r--r-- standard file permission
                zinfo.external_attr = (0x8000 | 0o644) << 16
                zinfo.compress_type = zipfile.ZIP_DEFLATED

                with open(full_path, 'rb') as src:
                    zf.writestr(zinfo, src.read())
                count += 1
            except Exception as e:
                print(f"Error packaging {arc_name}: {e}")

print(f"\nCreated {zip_name} with {count} files")
size_mb = os.path.getsize(zip_name) / 1024 / 1024
print(f"Size: {size_mb:.1f} MB")

if skipped_spaces:
    print(f"\nSkipped {len(skipped_spaces)} file(s) with spaces in name (already exist on Hostinger):")
    for f in skipped_spaces:
        print(f"  - {f}")
