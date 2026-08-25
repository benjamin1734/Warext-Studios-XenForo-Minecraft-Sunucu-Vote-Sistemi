#!/usr/bin/env python3
import json
import re
import sys
from pathlib import Path

ADDON_ID = 'Warext/MinecraftVote'
ADDON_NS = 'Warext\\MinecraftVote'
MAX_XF_SHORT_ID = 25
ID_RE = re.compile(r'^[A-Za-z0-9]+$')


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


def action_prefix_to_method(action_prefix: str) -> str:
    parts = [part for part in re.split(r'[^A-Za-z0-9]+', action_prefix) if part]
    return 'action' + ''.join(part[:1].upper() + part[1:] for part in parts)


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
        action_prefix = str(obj.get('action_prefix', ''))
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
                continue
            if expected and expected.is_file() and action_prefix:
                content = expected.read_text(encoding='utf-8-sig')
                method = action_prefix_to_method(action_prefix)
                if not re.search(r'function\s+' + re.escape(method) + r'\s*\(', content):
                    errors.append(f'{path}: route action metodu bulunamadı: {method}()')
    return errors


def validate_admin_navigation(addon: Path):
    errors = []
    nav_root = addon / '_output' / 'admin_navigation'
    phrase_root = addon / '_output' / 'phrases'
    routes_root = addon / '_output' / 'routes'

    admin_routes = set()
    for route_path in sorted(routes_root.glob('*.json')):
        obj = load_json(route_path)
        if str(obj.get('route_type', '')) != 'admin':
            continue
        prefix = str(obj.get('route_prefix', '')).strip('/')
        sub_name = str(obj.get('sub_name', '')).strip('/')
        if prefix:
            admin_routes.add(prefix if not sub_name else f'{prefix}/{sub_name}')

    nav_ids = {path.stem for path in nav_root.glob('*.json')}
    for path in sorted(nav_root.glob('*.json')):
        nav_id = path.stem
        obj = load_json(path)
        phrase = phrase_root / f'admin_navigation.{nav_id}.txt'
        if not phrase.is_file():
            errors.append(f'{path}: admin navigation phrase bulunamadı: {phrase.name}')

        parent_id = str(obj.get('parent_navigation_id', ''))
        if parent_id.startswith('warext') and parent_id not in nav_ids:
            errors.append(f'{path}: addon parent navigation bulunamadı: {parent_id}')

        link = str(obj.get('link', '')).strip('/')
        if link.startswith('warext-minecraft') and link not in admin_routes:
            errors.append(f'{path}: admin navigation link route bulunamadı: {link}')

    return errors


def validate_permissions(addon: Path):
    errors = []
    output = addon / '_output'
    phrase_root = output / 'phrases'
    interface_root = output / 'permission_interface_groups'
    permission_root = output / 'permissions'

    interface_ids = set()
    for path in sorted(interface_root.glob('*.json')):
        interface_id = path.stem
        if len(interface_id) > 50 or not ID_RE.fullmatch(interface_id):
            errors.append(f'{path}: geçersiz permission interface ID')
            continue
        interface_ids.add(interface_id)
        phrase = phrase_root / f'permission_interface.{interface_id}.txt'
        if not phrase.is_file() or not phrase.read_text(encoding='utf-8-sig').strip():
            errors.append(f'{path}: permission interface phrase bulunamadı veya boş: {phrase.name}')

    if not interface_ids:
        errors.append('permission interface tanımı bulunamadı')

    permission_count = 0
    for path in sorted(permission_root.glob('*.json')):
        if '-' not in path.stem:
            errors.append(f'{path}: permission dosya adı group-permission biçiminde değil')
            continue
        group_id, permission_id = path.stem.split('-', 1)
        if len(group_id) > 25 or len(permission_id) > 25:
            errors.append(f'{path}: permission group/id 25 karakter sınırını aşıyor')
        if not ID_RE.fullmatch(group_id) or not ID_RE.fullmatch(permission_id):
            errors.append(f'{path}: permission group/id yalnız alfanumerik olabilir')

        obj = load_json(path)
        interface_id = str(obj.get('interface_group_id', ''))
        if interface_id not in interface_ids:
            errors.append(f'{path}: permission interface bulunamadı: {interface_id}')
        if str(obj.get('permission_type', '')) not in {'flag', 'integer'}:
            errors.append(f'{path}: geçersiz permission_type')

        phrase = phrase_root / f'permission.{group_id}_{permission_id}.txt'
        if not phrase.is_file() or not phrase.read_text(encoding='utf-8-sig').strip():
            errors.append(f'{path}: permission phrase bulunamadı veya boş: {phrase.name}')
        permission_count += 1

    if permission_count == 0:
        errors.append('kullanıcı permission tanımı bulunamadı')

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
    errors += validate_admin_navigation(addon)
    errors += validate_permissions(addon)
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
    print('XenForo ID/route/action/navigation/permission/cron/content-type/template doğrulaması başarılı.')


if __name__ == '__main__':
    main()
