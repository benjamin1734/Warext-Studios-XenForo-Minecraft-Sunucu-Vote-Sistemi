#!/usr/bin/env python3
import argparse
import json
from pathlib import Path
import xml.etree.ElementTree as ET

MAX_XF_SHORT_ID = 25


def scalar(value):
    if isinstance(value, bool):
        return "1" if value else "0"
    return str(value)


def ensure_short_id(value: str, label: str, source: Path | None = None):
    if len(value) > MAX_XF_SHORT_ID:
        where = f" ({source})" if source else ""
        raise SystemExit(
            f"{label} {value!r} {len(value)} karakter; XenForo sınırı {MAX_XF_SHORT_ID}{where}"
        )


def write_xml(path: Path, root: ET.Element):
    ET.indent(root, space="  ")
    tree = ET.ElementTree(root)
    tree.write(path, encoding="utf-8", xml_declaration=True, short_empty_elements=True)
    with path.open("ab") as f:
        f.write(b"\n")


def load_json(path: Path):
    with path.open("r", encoding="utf-8-sig") as f:
        return json.load(f)


def json_files(path: Path):
    if not path.exists():
        return []
    return sorted(p for p in path.glob("*.json") if not p.name.startswith("_"))


def build_admin_navigation(output: Path, data: Path):
    src = output / "admin_navigation"
    files = json_files(src)
    if not files:
        return
    root = ET.Element("admin_navigation")
    for p in files:
        obj = load_json(p)
        ensure_short_id(p.stem, "admin navigation ID", p)
        parent_id = str(obj.get("parent_navigation_id", ""))
        admin_permission_id = str(obj.get("admin_permission_id", ""))
        if parent_id:
            ensure_short_id(parent_id, "parent navigation ID", p)
        if admin_permission_id:
            ensure_short_id(admin_permission_id, "admin permission ID", p)
        attrs = {"navigation_id": p.stem}
        for key in ["parent_navigation_id", "display_order", "link", "icon", "admin_permission_id", "debug_only", "development_only", "hide_no_children"]:
            value = obj.get(key, "")
            if key in {"parent_navigation_id", "link", "icon", "admin_permission_id"} and value == "":
                continue
            attrs[key] = scalar(value)
        ET.SubElement(root, "admin_navigation_entry", attrs)
    write_xml(data / "admin_navigation.xml", root)


def build_admin_permissions(output: Path, data: Path):
    src = output / "admin_permissions"
    files = json_files(src)
    if not files:
        return
    root = ET.Element("admin_permission")
    for p in files:
        obj = load_json(p)
        ensure_short_id(p.stem, "admin permission ID", p)
        ET.SubElement(root, "admin_permission", {
            "admin_permission_id": p.stem,
            "display_order": scalar(obj.get("display_order", 0))
        })
    write_xml(data / "admin_permission.xml", root)


def build_content_type_fields(output: Path, data: Path):
    src = output / "content_type_fields"
    files = json_files(src)
    if not files:
        return
    root = ET.Element("content_type_fields")
    for p in files:
        obj = load_json(p)
        content_type = str(obj["content_type"])
        ensure_short_id(content_type, "content type ID", p)
        node = ET.SubElement(root, "field", {
            "content_type": content_type,
            "field_name": str(obj["field_name"])
        })
        node.text = str(obj.get("field_value", ""))
    write_xml(data / "content_type_fields.xml", root)


def build_cron(output: Path, data: Path):
    src = output / "cron_entries"
    files = json_files(src)
    if not files:
        return
    root = ET.Element("cron")
    for p in files:
        obj = load_json(p)
        ensure_short_id(p.stem, "cron entry ID", p)
        node = ET.SubElement(root, "entry", {
            "entry_id": p.stem,
            "cron_class": str(obj.get("cron_class", "")),
            "cron_method": str(obj.get("cron_method", "run")),
            "active": scalar(obj.get("active", True))
        })
        node.text = json.dumps(obj.get("run_rules", {}), ensure_ascii=False, separators=(",", ":"))
    write_xml(data / "cron.xml", root)


def build_option_groups(output: Path, data: Path):
    src = output / "option_groups"
    files = json_files(src)
    if not files:
        return
    root = ET.Element("option_groups")
    for p in files:
        obj = load_json(p)
        attrs = {
            "group_id": p.stem,
            "icon": str(obj.get("icon", "")),
            "display_order": scalar(obj.get("display_order", 0)),
            "debug_only": scalar(obj.get("debug_only", False))
        }
        ET.SubElement(root, "group", attrs)
    write_xml(data / "option_groups.xml", root)


def build_options(output: Path, data: Path):
    src = output / "options"
    files = json_files(src)
    if not files:
        return
    root = ET.Element("options")
    for p in files:
        obj = load_json(p)
        attrs = {
            "option_id": p.stem,
            "edit_format": str(obj.get("edit_format", "textbox")),
            "data_type": str(obj.get("data_type", "string")),
            "advanced": scalar(obj.get("advanced", False))
        }
        if obj.get("validation_class"):
            attrs["validation_class"] = str(obj["validation_class"])
        if obj.get("validation_method"):
            attrs["validation_method"] = str(obj["validation_method"])
        node = ET.SubElement(root, "option", attrs)
        default = ET.SubElement(node, "default_value")
        default.text = str(obj.get("default_value", ""))
        params = ET.SubElement(node, "edit_format_params")
        params.text = str(obj.get("edit_format_params", ""))
        subs = ET.SubElement(node, "sub_options")
        sub_options = obj.get("sub_options", [])
        if isinstance(sub_options, list):
            subs.text = "\n".join(str(v) for v in sub_options)
        else:
            subs.text = str(sub_options or "")
        relations = obj.get("relations", {}) or {}
        for group_id, display_order in relations.items():
            ET.SubElement(node, "relation", {
                "group_id": str(group_id),
                "display_order": scalar(display_order)
            })
    write_xml(data / "options.xml", root)


def build_phrases(output: Path, data: Path, version_id: int, version_string: str):
    src = output / "phrases"
    if not src.exists():
        return
    files = sorted(p for p in src.glob("*.txt") if not p.name.startswith("_"))
    if not files:
        return
    root = ET.Element("phrases")
    for p in files:
        node = ET.SubElement(root, "phrase", {
            "title": p.name[:-4],
            "version_id": str(version_id),
            "version_string": version_string
        })
        node.text = p.read_text(encoding="utf-8-sig")
    write_xml(data / "phrases.xml", root)


def build_routes(output: Path, data: Path):
    src = output / "routes"
    files = json_files(src)
    if not files:
        return
    root = ET.Element("routes")
    keys = ["route_type", "route_prefix", "sub_name", "format", "build_class", "build_method", "controller", "context", "action_prefix"]
    seen = set()
    for p in files:
        obj = load_json(p)
        route_type = str(obj.get("route_type", ""))
        route_prefix = str(obj.get("route_prefix", ""))
        sub_name = str(obj.get("sub_name", ""))
        controller = str(obj.get("controller", ""))
        if route_type not in {"public", "admin", "api"}:
            raise SystemExit(f"Geçersiz route_type: {p}")
        if not route_prefix or not controller:
            raise SystemExit(f"Eksik route bilgisi: {p}")
        route_key = (route_type, route_prefix, sub_name)
        if route_key in seen:
            raise SystemExit(f"Yinelenen route: {route_key}")
        seen.add(route_key)
        attrs = {}
        for key in keys:
            value = obj.get(key, "")
            if value == "":
                continue
            attrs[key] = scalar(value)
        ET.SubElement(root, "route", attrs)
    write_xml(data / "routes.xml", root)


def template_title(path: Path):
    if path.suffix == ".html":
        return path.stem
    return path.name


def build_templates(output: Path, data: Path, version_id: int, version_string: str):
    src = output / "templates"
    if not src.exists():
        return
    root = ET.Element("templates")
    count = 0
    seen = set()
    for template_type in ["public", "admin", "email"]:
        folder = src / template_type
        if not folder.exists():
            continue
        for p in sorted(x for x in folder.iterdir() if x.is_file() and not x.name.startswith("_")):
            title = template_title(p)
            key = (template_type, title)
            if key in seen:
                raise SystemExit(f"Yinelenen template: {template_type}:{title}")
            seen.add(key)
            node = ET.SubElement(root, "template", {
                "type": template_type,
                "title": title,
                "version_id": str(version_id),
                "version_string": version_string
            })
            node.text = p.read_text(encoding="utf-8-sig")
            count += 1
    if count:
        write_xml(data / "templates.xml", root)


def validate_master_data(data: Path):
    expected_roots = {
        "admin_navigation.xml": "admin_navigation",
        "admin_permission.xml": "admin_permission",
        "content_type_fields.xml": "content_type_fields",
        "cron.xml": "cron",
        "option_groups.xml": "option_groups",
        "options.xml": "options",
        "phrases.xml": "phrases",
        "routes.xml": "routes",
        "templates.xml": "templates"
    }
    for xml_file in sorted(data.glob("*.xml")):
        root = ET.parse(xml_file).getroot()
        expected = expected_roots.get(xml_file.name)
        if expected and root.tag != expected:
            raise SystemExit(f"{xml_file.name}: beklenen kök <{expected}>, bulunan <{root.tag}>")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("addon_dir")
    args = parser.parse_args()
    addon = Path(args.addon_dir).resolve()
    output = addon / "_output"
    data = addon / "_data"
    addon_json = load_json(addon / "addon.json")
    version_id = int(addon_json["version_id"])
    version_string = str(addon_json["version_string"])
    if not output.is_dir():
        raise SystemExit("_output bulunamadı")
    data.mkdir(parents=True, exist_ok=True)
    for old in data.glob("*.xml"):
        old.unlink()
    build_admin_navigation(output, data)
    build_admin_permissions(output, data)
    build_content_type_fields(output, data)
    build_cron(output, data)
    build_option_groups(output, data)
    build_options(output, data)
    build_phrases(output, data, version_id, version_string)
    build_routes(output, data)
    build_templates(output, data, version_id, version_string)
    required = ["routes.xml", "templates.xml", "phrases.xml", "admin_permission.xml"]
    missing = [name for name in required if not (data / name).exists()]
    if missing:
        raise SystemExit("Eksik release data: " + ", ".join(missing))
    validate_master_data(data)
    print(f"{len(list(data.glob('*.xml')))} _data XML dosyası üretildi")


if __name__ == "__main__":
    main()
