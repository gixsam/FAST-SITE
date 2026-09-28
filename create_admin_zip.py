import zipfile, os, time

zip_name = 'fastsite_admin_only.zip'
if os.path.exists(zip_name):
    os.remove(zip_name)

EXCLUDE_EXTS = {'.DS_Store'}

count = 0
with zipfile.ZipFile(zip_name, 'w', zipfile.ZIP_DEFLATED, compresslevel=6) as zf:
    admin_dir = os.path.join('.', 'admin')
    for root, dirs, files in os.walk(admin_dir):
        dirs[:] = [d for d in dirs if not d.startswith('.')]
        for file in files:
            ext = os.path.splitext(file)[1].lower()
            if ext in EXCLUDE_EXTS:
                continue
            # Skip files with spaces
            if ' ' in file:
                continue

            full_path = os.path.join(root, file)
            arc_name = full_path[2:].replace('\\', '/')
            if arc_name.startswith('/'):
                arc_name = arc_name[1:]

            try:
                st = os.stat(full_path)
                zinfo = zipfile.ZipInfo(arc_name, time.localtime(st.st_mtime)[:6])
                zinfo.create_system = 3  # UNIX
                zinfo.external_attr = (0x8000 | 0o644) << 16
                zinfo.compress_type = zipfile.ZIP_DEFLATED
                with open(full_path, 'rb') as src:
                    zf.writestr(zinfo, src.read())
                count += 1
            except Exception as e:
                print(f"Error: {e}")

print(f"\nCreated {zip_name} with {count} files")
size_kb = os.path.getsize(zip_name) / 1024
print(f"Size: {size_kb:.1f} KB")
print("Contents: admin/ folder only")
