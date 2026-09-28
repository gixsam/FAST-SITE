import os

replacements = {
    'fast site logo only.jpeg': '/assets/img/fast site logo only.jpeg',
    '../fast site logo only.jpeg': '/assets/img/fast site logo only.jpeg',
    '/fast site logo only.jpeg': '/assets/img/fast site logo only.jpeg',
    'google-play.png': '/assets/img/google-play.png',
    '../google-play.png': '/assets/img/google-play.png',
    'logo with letter.jpeg': '/assets/img/logo with letter.jpeg',
    '../logo with letter.jpeg': '/assets/img/logo with letter.jpeg'
}

for root, _, files in os.walk('.'):
    if '.git' in root or '.idea' in root: continue
    for file in files:
        if not file.endswith('.php') and not file.endswith('.js') and not file.endswith('.html'): continue
        if file == 'generate_checker.py' or file == 'check_files.php': continue
        
        filepath = os.path.join(root, file)
        try:
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
            
            modified = False
            for old, new in replacements.items():
                if old in content:
                    content = content.replace(old, new)
                    modified = True
            
            if modified:
                # Need to handle exact edge cases, e.g., if it was already replaced as '/assets/img//assets/img/'
                content = content.replace('/assets/img//assets/img/', '/assets/img/')
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(content)
                print(f"Fixed {filepath}")
        except Exception as e:
            pass
