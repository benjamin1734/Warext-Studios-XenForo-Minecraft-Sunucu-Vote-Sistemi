from pathlib import Path

ROOT = Path('src/addons/Warext/MinecraftVote')


def replace_exact(path: Path, old: str, new: str, count: int = 1) -> None:
    text = path.read_text(encoding='utf-8')
    found = text.count(old)
    if found != count:
        raise SystemExit(f'{path}: expected {count} occurrence(s), found {found}')
    path.write_text(text.replace(old, new), encoding='utf-8')

addon = ROOT / 'addon.json'
replace_exact(addon, '"version_id": 1011070', '"version_id": 1011080')
replace_exact(addon, '"version_string": "1.1.7"', '"version_string": "1.1.8"')

readme = Path('README.md')
replace_exact(readme, 'Warext-MinecraftVote-1.1.7.zip', 'Warext-MinecraftVote-1.1.8.zip')

regression = Path('.github/security_regression.py')
text = regression.read_text(encoding='utf-8')
text = text.replace("if version_string != '1.1.7' or int(addon.get('version_id', 0)) < 1011070:", "if version_string != '1.1.8' or int(addon.get('version_id', 0)) < 1011080:")
text = text.replace("raise SystemExit('Sürüm numarası 1.1.7 değil.')", "raise SystemExit('Sürüm numarası 1.1.8 değil.')")
regression.write_text(text, encoding='utf-8')
