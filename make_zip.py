import os, zipfile

zip_name = 'fastsite_phase37.zip'
if os.path.exists(zip_name):
    try:
        os.remove(zip_name)
    except:
        pass

exclude_dirs = {
    '.git', '.gemini', 'vendor', '__pycache__', 'backups', 'build', 
    '.gradle', 'intermediates', '.idea', 'node_modules', 
    'apk file ADMIN', 'apk file user', 'fast site admin apk', 'fast site world user apk'
}

count = 0
with zipfile.ZipFile(zip_name, 'w', zipfile.ZIP_DEFLATED) as zf:
    for root, dirs, files in os.walk('.'):
        dirs[:] = [d for d in dirs if d not in exclude_dirs and not d.startswith('.')]
        for f in files:
            ext = os.path.splitext(f)[1].lower()
            if ext in {'.zip', '.log', '.tmp'} or f.startswith('.'):
                continue
            path = os.path.join(root, f)
            arcname = os.path.relpath(path, '.')
            zf.write(path, arcname)
            count += 1

print(f"Successfully created {zip_name} containing {count} files! Size: {os.path.getsize(zip_name) / 1024 / 1024:.2f} MB")
