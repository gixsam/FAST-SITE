import sys, zipfile, os, hashlib

root_dir = sys.argv[1]
zip_path = os.path.join(root_dir, 'fastsite_phase87.zip')

expected_entries = [
    'assets/css/user.css',
    'includes/user_sidebar.php',
    'partner/api_docs.php',
    'partner/dashboard.php',
    'partner/index.php',
    'partner/logout.php',
    'partner/nav.php',
    'partner/product_add.php',
    'partner/product_delete.php',
    'partner/product_edit.php',
    'partner/profile.php',
    'user/dashboard.php',
    'DEPLOYMENT_GUIDE.txt'
]

results = []

try:
    with zipfile.ZipFile(zip_path, 'r') as zf:
        # CRC test
        bad_crc = zf.testzip()
        results.append(('fastsite_phase87.zip opens cleanly with CRC check', bad_crc is None, str(bad_crc)))
        
        infolist = zf.infolist()
        namelist = [info.filename for info in infolist]
        
        # Check backslashes
        has_backslashes = any('\\' in name for name in namelist)
        results.append(('Zip contains zero Windows backslashes in paths', not has_backslashes, 'Found backslashes'))
        
        # Check expected entries
        for exp in expected_entries:
            found = exp in namelist
            results.append((f'Zip contains {exp}', found, f'Missing {exp}'))
            
            if found:
                zip_data = zf.read(exp)
                disk_path = os.path.join(root_dir, exp.replace('/', os.sep))
                with open(disk_path, 'rb') as df:
                    disk_data = df.read()
                matches = (zip_data == disk_data)
                results.append((f'Zip content matches disk exactly: {exp}', matches, 'Content mismatch'))

except Exception as e:
    results.append(('Zip inspection completed without exception', False, str(e)))

import json
print(json.dumps(results))