#!/usr/bin/env python3
import json
from pathlib import Path
import sys
import xml.etree.ElementTree as ET

addon = Path(sys.argv[1]).resolve()
output = addon / '_output' / 'navigation'
data = addon / '_data'
files = sorted(output.glob('*.json'))
if not files:
    raise SystemExit('Navigation verisi bulunamadı')

root = ET.Element('navigation')
for path in files:
    item = json.loads(path.read_text(encoding='utf-8-sig'))
    navigation_id = path.stem
    if len(navigation_id) > 25:
        raise SystemExit(f'Navigation ID çok uzun: {navigation_id}')
    parent = str(item.get('parent_navigation_id', ''))
    if len(parent) > 25:
        raise SystemExit(f'Parent navigation ID çok uzun: {parent}')
    attrs = {
        'navigation_id': navigation_id,
        'display_order': str(int(item.get('display_order', 0))),
        'navigation_type_id': str(item.get('navigation_type_id', 'basic')),
        'enabled': '1' if item.get('enabled', True) else '0'
    }
    if parent:
        attrs['parent_navigation_id'] = parent
    node = ET.SubElement(root, 'navigation_entry', attrs)
    node.text = json.dumps(item.get('type_config', {}), ensure_ascii=False, separators=(',', ':'))

ET.indent(root, space='  ')
ET.ElementTree(root).write(data / 'navigation.xml', encoding='utf-8', xml_declaration=True, short_empty_elements=True)
with (data / 'navigation.xml').open('ab') as handle:
    handle.write(b'\n')

routes_path = data / 'routes.xml'
routes = ET.parse(routes_path)
changed = 0
for route in routes.getroot().findall('route'):
    if route.get('route_type') == 'public' and route.get('route_prefix') == 'sunucular':
        route.set('context', 'warextMcServers')
        changed += 1
if not changed:
    raise SystemExit('sunucular public route bulunamadı')
ET.indent(routes.getroot(), space='  ')
routes.write(routes_path, encoding='utf-8', xml_declaration=True, short_empty_elements=True)
with routes_path.open('ab') as handle:
    handle.write(b'\n')

print(f'{len(files)} navigation kaydı ve {changed} public route contexti üretildi')
