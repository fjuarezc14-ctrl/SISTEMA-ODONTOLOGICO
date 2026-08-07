import os
import glob
import re

auth_guard_statement = "require_once 'includes/auth_guard.php';"

def replace_in_file(filepath):
    if 'index.php' in filepath or 'logout.php' in filepath or 'auth_guard.php' in filepath:
        return
        
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
        
    # Search for variations of the session_start block
    pattern = re.compile(r'session_start\(\);\s*(?:if\s*\(!isset\(\$_SESSION\[[\'"]usuario_id[\'"]\]\)\)\s*\{\s*header\(["\']Location:\s*index\.php["\']\);\s*exit;\s*\})?', re.DOTALL)
    
    if pattern.search(content):
        new_content = pattern.sub(auth_guard_statement, content)
        if new_content != content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated {filepath}")

for file in glob.glob("*.php"):
    replace_in_file(file)
