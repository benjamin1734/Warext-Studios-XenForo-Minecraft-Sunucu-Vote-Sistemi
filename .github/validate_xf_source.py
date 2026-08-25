#!/usr/bin/env python3
import json
import re
import sys
from pathlib import Path

ADDON_ID = 'Warext/MinecraftVote'
ADDON_NS = 'Warext\\MinecraftVote'
MAX_XF_SHORT_ID = 25


def load_json(path: Path):
    with path.open('r', encoding='utf-8-sig') as f:
        return json.load(f)


def addon_short_to_path(addon: Path, value: str, kind: str) -> Path | None:
    prefix = ADDON_NS + ':'
    if not value.startswith(prefix):
        return None
    short = value[len(prefix):].replace('\\', '/')
    return addon / kind / f'{short}.php'


def class_to_path(addon: Path, value: str) -> Path | None:
    prefix = ADDON_NS + '\\'
    if not value.startswith(prefix):
        return None
    short = value[len(prefix):].replace('\\', '/')
    return addon / f'{short}.php'


def validate_short_ids(addon: Path):
    errors = []
    for folder, label in [
        ('admin_navigation', 'admin navigation ID'),
        ('admin_permissions', 'admin permission ID'),
        ('cron_entries', 'cron entry ID')
    ]:
        root = addon / '_output' / folder
        if not root.exists():
            continue
        for path in sorted(root.glob('*.json')):
            value = path.stem
            if len(value) > MAX_XF_SHORT_ID:
                errors.append(
                    f'{path}: {label} {len(value)} karakter; XenForo sınırı {MAX_XF_SHORT_ID}'
                )

    for path in sorted((addon / '_output' / 'content_type_fields').glob('*.json')):
        obj = load_json(path)
        content_type = str(obj.get('content_type', ''))
        if len(content_type) > MAX_XF_SHORT_ID:
            errors.append(
                f'{path}: content_type {len(content_type)} karakter; XenForo sınırı {MAX_XF_SHORT_ID}'
            )
    return errors


def validate_routes(addon: Path):
    routes_dir = addon / '_output' / 'routes'
    seen = set()
    errors = []
    for path in sorted(routes_dir.glob('*.json')):
        obj = load_json(path)
        route_type = str(obj.get('route_type', ''))
        prefix = str(obj.get('route_prefix', ''))
        sub_name = str(obj.get('sub_name', ''))
        controller = str(obj.get('controller', ''))
        key = (route_type, prefix, sub_name)
        if key in seen:
            errors.append(f'{path}: yinelenen route {key}')
        seen.add(key)
        if route_type not in {'public', 'admin', 'api'}:
            errors.append(f'{path}: geçersiz route_type {route_type!r}')
            continue
        if not prefix or not controller:
            errors.append(f'{path}: route_prefix/controller eksik')
            continue
        if controller.startswith(ADDON_NS + ':'):
            base = {'public': 'Pub/Controller', 'admin': 'Admin/Controller', 'api': 'Api/Controller'}[route_type]
            expected = addon_short_to_path(addon, controller, base)
            if expected and not expected.is_file():
                errors.append(f'{path}: controller bulunamadı: {expected.relative_to(addon)}')
    return errors


def validate_cron(addon: Path):
    errors = []
    for path in sorted((addon / '_output' / 'cron_entries').glob('*.json')):
        obj = load_json(path)
        cls = str(obj.get('cron_class', ''))
        method = str(obj.get('cron_method', 'run'))
        expected = class_to_path(addon, cls)
        if expected and not expected.is_file():
            errors.append(f'{path}: cron sınıfı bulunamadı: {expected.relative_to(addon)}')
            continue
        if expected and expected.is_file():
            content = expected.read_text(encoding='utf-8-sig')
            if not re.search(r'function\s+' + re.escape(method) + r'\s*\(', content):
                errors.append(f'{path}: cron metodu bulunamadı: {cls}::{method}()')
    return errors


def validate_content_types(addon: Path):
    errors = []
    class_fields = {'entity', 'alert_handler_class', 'attachment_handler_class'}
    for path in sorted((addon / '_output' / 'content_type_fields').glob('*.json')):
        obj = load_json(path)
        field_name = str(obj.get('field_name', ''))
        field_value = str(obj.get('field_value', ''))
        if field_name not in class_fields or not field_value:
            continue
        expected = class_to_path(addon, field_value)
        if expected and not expected.is_file():
            errors.append(f'{path}: content-type sınıfı bulunamadı: {expected.relative_to(addon)}')
    return errors


def validate_controller_templates(addon: Path):
    errors = []
    template_roots = {
        'Pub/Controller': addon / '_output' / 'templates' / 'public',
        'Admin/Controller': addon / '_output' / 'templates' / 'admin'
    }
    pattern = re.compile(r"->view\([^,]+,\s*['\"]([^'\"]+)['\"]")
    for relative_root, template_root in template_roots.items():
        controller_root = addon / relative_root
        if not controller_root.exists():
            continue
        for php in sorted(controller_root.rglob('*.php')):
            text = php.read_text(encoding='utf-8-sig')
            for title in pattern.findall(text):
                html = template_root / f'{title}.html'
                less = template_root / title
                if not html.is_file() and not less.is_file():
                    errors.append(f'{php}: template bulunamadı: {title}')
    return errors


def validate_addon(addon: Path):
    errors = []
    addon_json = addon / 'addon.json'
    if not addon_json.is_file():
        return ['addon.json bulunamadı']
    meta = load_json(addon_json)
    if int(meta.get('version_id', 0)) <= 0 or not str(meta.get('version_string', '')).strip():
        errors.append('addon.json sürüm bilgisi geçersiz')
    if not (addon / 'Setup.php').is_file():
        errors.append('Setup.php bulunamadı')
    errors += validate_short_ids(addon)
    errors += validate_routes(addon)
    errors += validate_cron(addon)
    errors += validate_content_types(addon)
    errors += validate_controller_templates(addon)
    return errors


def main():
    addon = Path(sys.argv[1] if len(sys.argv) > 1 else 'src/addons/Warext/MinecraftVote').resolve()
    errors = validate_addon(addon)
    if errors:
        print('XenForo kaynak doğrulaması başarısız:', file=sys.stderr)
        for error in errors:
            print(f'- {error}', file=sys.stderr)
        raise SystemExit(1)
    print('XenForo ID/route/controller/cron/content-type/template doğrulaması başarılı.')


if __name__ == '__main__':
    main()
