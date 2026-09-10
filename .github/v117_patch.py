from pathlib import Path

ROOT = Path('src/addons/Warext/MinecraftVote')


def replace_exact(path: Path, old: str, new: str, count: int = 1) -> None:
    text = path.read_text(encoding='utf-8')
    found = text.count(old)
    if found != count:
        raise SystemExit(f'{path}: expected {count} occurrence(s), found {found}')
    path.write_text(text.replace(old, new), encoding='utf-8')


media = ROOT / 'Service/Server/Media.php'
replace_exact(
    media,
    "                'width' => 360,\n                'height' => 120,\n                'min_width' => 180,\n                'min_height' => 60,",
    "                'width' => 468,\n                'height' => 60,\n                'min_width' => 468,\n                'min_height' => 60,",
    2
)
replace_exact(media, 'Liste bannerı en az 180×60 piksel olmalıdır.', 'Liste bannerı en az 468×60 piksel olmalıdır.')
replace_exact(media, 'Hareketli banner en az 180×60 piksel olmalıdır.', 'Hareketli banner en az 468×60 piksel olmalıdır.')

less = ROOT / '_output/templates/public/warext_mc_server_directory.less'
replace_exact(
    less,
    "    flex: 0 0 376px;\n    width: 376px;\n    min-width: 376px;",
    "    flex: 0 0 484px;\n    width: 484px;\n    min-width: 484px;"
)
replace_exact(
    less,
    "    width: 360px;\n    height: 120px;",
    "    width: 468px;\n    height: 60px;"
)
replace_exact(less, '    font-size: 26px;', '    font-size: 20px;')
replace_exact(
    less,
    "        flex: 0 0 256px;\n        width: 256px;\n        min-width: 256px;",
    "        flex: 0 0 328px;\n        width: 328px;\n        min-width: 328px;"
)
replace_exact(
    less,
    "        width: 240px;\n        height: 80px;",
    "        width: 312px;\n        height: 40px;"
)
replace_exact(less, '        flex: 1 1 calc(100% - 320px);', '        flex: 1 1 calc(100% - 392px);')
replace_exact(less, '        aspect-ratio: 3 / 1;', '        aspect-ratio: 39 / 5;')

for template_name in ['warext_mc_server_add.html', 'warext_mc_server_edit.html']:
    template = ROOT / '_output/templates/public' / template_name
    text = template.read_text(encoding='utf-8')
    occurrences = text.count('360×120')
    if occurrences < 1:
        raise SystemExit(f'{template}: 360×120 text not found')
    template.write_text(text.replace('360×120', '468×60'), encoding='utf-8')

addon = ROOT / 'addon.json'
replace_exact(addon, '"version_id": 1011060', '"version_id": 1011070')
replace_exact(addon, '"version_string": "1.1.6"', '"version_string": "1.1.7"')

regression = Path('.github/security_regression.py')
text = regression.read_text(encoding='utf-8')
replacements = {
    "'360×120', '1200×400'": "'468×60', '1200×400'",
    "'flex: 0 0 376px;', 'width: 360px;', 'height: 120px;'": "'flex: 0 0 484px;', 'width: 468px;', 'height: 60px;'",
    "'aspect-ratio: 3 / 1'": "'aspect-ratio: 39 / 5'",
    "\"'width' => 360\", \"'height' => 120\"": "\"'width' => 468\", \"'height' => 60\"",
    "'discussion_thread_url', '360×120'": "'discussion_thread_url', '468×60'",
    "if version_string != '1.1.6' or int(addon.get('version_id', 0)) < 1011060:": "if version_string != '1.1.7' or int(addon.get('version_id', 0)) < 1011070:",
    "raise SystemExit('Sürüm numarası 1.1.6 değil.')": "raise SystemExit('Sürüm numarası 1.1.7 değil.')"
}
for old, new in replacements.items():
    if old not in text:
        raise SystemExit(f'security_regression.py missing expected text: {old}')
    text = text.replace(old, new)
regression.write_text(text, encoding='utf-8')

readme = Path('README.md')
replace_exact(readme, 'Warext-MinecraftVote-1.1.6.zip', 'Warext-MinecraftVote-1.1.7.zip')

checks = {
    media: ["'width' => 468", "'height' => 60", "'min_width' => 468", '468×60'],
    less: ['flex: 0 0 484px;', 'width: 468px;', 'height: 60px;', 'aspect-ratio: 39 / 5;'],
    addon: ['"version_id": 1011070', '"version_string": "1.1.7"'],
    regression: ["'468×60'", "'width' => 468", "'height' => 60", "version_string != '1.1.7'"]
}
for path, needles in checks.items():
    text = path.read_text(encoding='utf-8')
    for needle in needles:
        if needle not in text:
            raise SystemExit(f'{path}: missing {needle}')
