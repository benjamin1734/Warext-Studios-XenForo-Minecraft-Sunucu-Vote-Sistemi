#!/usr/bin/env python3
import json
import sys
from pathlib import Path
import xml.etree.ElementTree as ET


def load(path):
    return json.loads(path.read_text(encoding='utf-8-sig'))


def write_xml(root, path):
    ET.ElementTree(root).write(path, encoding='utf-8', xml_declaration=True)


def build_listeners(addon):
    root = ET.Element('code_event_listeners')
    source = addon / '_output' / 'code_event_listeners'
    for path in sorted(source.glob('*.json')):
        data = load(path)
        node = ET.SubElement(root, 'listener')
        for key in ['event_id', 'execute_order', 'callback_class', 'callback_method', 'active', 'hint', 'description']:
            value = data.get(key, '')
            if isinstance(value, bool):
                value = '1' if value else '0'
            node.set(key, str(value))
    write_xml(root, addon / '_data' / 'code_event_listeners.xml')


def build_modifications(addon):
    root = ET.Element('template_modifications')
    source = addon / '_output' / 'template_modifications'
    for style in ['public', 'admin', 'email']:
        folder = source / style
        if not folder.exists():
            continue
        for path in sorted(folder.glob('*.json')):
            data = load(path)
            node = ET.SubElement(root, 'modification')
            node.set('type', style)
            node.set('template', str(data.get('template', '')))
            node.set('modification_key', path.stem)
            node.set('description', str(data.get('description', '')))
            node.set('execution_order', str(data.get('execution_order', 10)))
            node.set('enabled', '1' if data.get('enabled', True) else '0')
            node.set('action', str(data.get('action', 'str_replace')))
            find = ET.SubElement(node, 'find')
            find.text = str(data.get('find', ''))
            replace = ET.SubElement(node, 'replace')
            replace.text = str(data.get('replace', ''))
    write_xml(root, addon / '_data' / 'template_modifications.xml')


def main():
    addon = Path(sys.argv[1] if len(sys.argv) > 1 else 'src/addons/Warext/MinecraftVote')
    (addon / '_data').mkdir(parents=True, exist_ok=True)
    build_listeners(addon)
    build_modifications(addon)


if __name__ == '__main__':
    main()
