import re
import json

# Read HTML files
files = {
    'index.html': r'c:\Users\WINDOWS\Desktop\iyad\projet web dev\index.html',
    'formation.html': r'c:\Users\WINDOWS\Desktop\iyad\projet web dev\formation.html',
    'evenements.html': r'c:\Users\WINDOWS\Desktop\iyad\projet web dev\evenements.html'
}

all_classes = set()
all_ids = set()

for name, path in files.items():
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
        
    # Extract classes
    class_matches = re.findall(r'class=["\']([\w\s\-]+)["\']', content)
    for match in class_matches:
        all_classes.update(match.split())
    
    # Extract IDs
    id_matches = re.findall(r'id=["\']([\w\-]+)["\']', content)
    all_ids.update(id_matches)

print('=== CLASSES USED IN HTML ===')
for cls in sorted(all_classes):
    print(f'  {cls}')

print('\n=== IDS USED IN HTML ===')
for id_name in sorted(all_ids):
    print(f'  {id_name}')

print(f'\nTotal classes: {len(all_classes)}')
print(f'Total IDs: {len(all_ids)}')

# Save to file for reference
with open('html_usage.json', 'w', encoding='utf-8') as f:
    json.dump({
        'classes': sorted(list(all_classes)),
        'ids': sorted(list(all_ids))
    }, f, indent=2)
