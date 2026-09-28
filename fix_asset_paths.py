import os

for root, dirs, files in os.walk('.'):
    for f in files:
        if f.endswith('.php') or f.endswith('.js') or f == 'check_files.php':
            path = os.path.join(root, f)
            try:
                with open(path, 'r', encoding='utf-8') as file:
                    content = file.read()
            except Exception:
                continue
            
            new_content = content.replace('//assets/img/assets/img/', '/assets/img/')
            new_content = new_content.replace('..//assets/img/assets/img/', '../assets/img/')
            new_content = new_content.replace('/assets/img/assets/img/', '/assets/img/')
            new_content = new_content.replace('../assets/img/assets/img/', '../assets/img/')
            new_content = new_content.replace('assets/img/assets/img/', 'assets/img/')
            
            if new_content != content:
                with open(path, 'w', encoding='utf-8') as file:
                    file.write(new_content)
                print(f"Fixed paths in {path}")
