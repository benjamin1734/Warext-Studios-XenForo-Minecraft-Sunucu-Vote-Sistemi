#!/usr/bin/env python3
import json
import re
import sys
from pathlib import Path
import xml.etree.ElementTree as ET

ID_RE = re.compile(r'^[A-Za-z0-9]+$')


def load_json(path: Path):
    with path.open('r', encoding='utf-8-sig') as f:
        return json.load(f)


def write_xml(path: Path, root: ET.Element):
    ET.indent(root, space='  ')
    ET.ElementTree(root).write(path, encoding='utf-8', xml_declaration=True, short_empty_elements=True)
    with path.open('ab') as f:
        f.write(b'\n')


def require_phrase(phrase_root: Path, name: str, source: Path):
    path = phrase_root / name
    if not path.is_file():
        raise SystemExit(f'Permission phrase bulunamadı: {name} ({source.name})')
    if not path.read_text(encoding='utf-8-sig').strip():
        raise SystemExit(f'Permission phrase boş: {name} ({source.name})')


def main():
    addon = Path(sys.argv[1] if len(sys.argv) > 1 else 'src/addons/Warext/MinecraftVote').resolve()
    output = addon / '_output'
    data = addon / '_data'
    phrase_root = output / 'phrases'
    data.mkdir(parents=True, exist_ok=True)

    interface_root = ET.Element('permission_interface_groups')
    interface_ids = set()
    for path in sorted((output / 'permission_interface_groups').glob('*.json')):
        interface_id = path.stem
        if len(interface_id) > 50 or not ID_RE.fullmatch(interface_id):
            raise SystemExit(f'Geçersiz permission interface ID: {interface_id}')
        require_phrase(phrase_root, f'permission_interface.{interface_id}.txt', path)
        obj = load_json(path)
        interface_ids.add(interface_id)
        ET.SubElement(interface_root, 'interface_group', {
            'interface_group_id': interface_id,
            'display_order': str(int(obj.get('display_order', 1))),
            'is_moderator': '1' if bool(obj.get('is_moderator', False)) else '0'
        })

    permission_root = ET.Element('permissions')
    count = 0
    for path in sorted((output / 'permissions').glob('*.json')):
        if '-' not in path.stem:
            raise SystemExit(f'Permission dosya adı geçersiz: {path.name}')
        group_id, permission_id = path.stem.split('-', 1)
        if len(group_id) > 25 or len(permission_id) > 25:
            raise SystemExit(f'Permission ID 25 karakter sınırını aşıyor: {path.name}')
        if not ID_RE.fullmatch(group_id) or not ID_RE.fullmatch(permission_id):
            raise SystemExit(f'Permission ID yalnız alfanumerik olabilir: {path.name}')
        require_phrase(phrase_root, f'permission.{group_id}_{permission_id}.txt', path)
        obj = load_json(path)
        interface_id = str(obj.get('interface_group_id', ''))
        if interface_id not in interface_ids:
            raise SystemExit(f'Permission interface bulunamadı: {path.name} -> {interface_id}')
        permission_type = str(obj.get('permission_type', 'flag'))
        if permission_type not in {'flag', 'integer'}:
            raise SystemExit(f'Geçersiz permission_type: {path.name}')
        depend = str(obj.get('depend_permission_id', ''))
        if depend and (len(depend) > 25 or not ID_RE.fullmatch(depend)):
            raise SystemExit(f'Geçersiz depend_permission_id: {path.name}')
        ET.SubElement(permission_root, 'permission', {
            'permission_group_id': group_id,
            'permission_id': permission_id,
            'permission_type': permission_type,
            'depend_permission_id': depend,
            'interface_group_id': interface_id,
            'display_order': str(int(obj.get('display_order', 1)))
        })
        count += 1

    if not interface_ids or not count:
        raise SystemExit('Permission master data boş bırakılamaz.')

    write_xml(data / 'permission_interface_groups.xml', interface_root)
    write_xml(data / 'permissions.xml', permission_root)
    print(f'{len(interface_ids)} permission interface, {count} permission üretildi.')


if __name__ == '__main__':
    main()
