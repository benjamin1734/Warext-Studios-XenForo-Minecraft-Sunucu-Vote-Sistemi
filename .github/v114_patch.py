from pathlib import Path

reg = Path('.github/security_regression.py')
text = reg.read_text(encoding='utf-8')
text = text.replace(
    "require('_output/templates/public/warext_mc_server_directory.less', ['grid-template-columns: 64px 300px minmax(280px, 1fr) 128px;', 'width: 300px;', 'height: 100px;', 'aspect-ratio: 3 / 1'])",
    "require('_output/templates/public/warext_mc_server_directory.less', ['display: flex;', 'flex: 0 0 300px;', 'width: 300px;', 'height: 100px;', 'flex: 1 1 0;', 'flex: 0 0 128px;', 'aspect-ratio: 3 / 1'])"
)
text = text.replace(
    "if version_string != '1.1.3' or int(addon.get('version_id', 0)) < 1011030:\n    raise SystemExit('Sürüm numarası 1.1.3 değil.')",
    "if version_string != '1.1.4' or int(addon.get('version_id', 0)) < 1011040:\n    raise SystemExit('Sürüm numarası 1.1.4 değil.')"
)
reg.write_text(text, encoding='utf-8')

readme = Path('README.md')
text = readme.read_text(encoding='utf-8')
text = text.replace('Warext-MinecraftVote-1.1.3.zip', 'Warext-MinecraftVote-1.1.4.zip')
text = text.replace('v1.1.3', 'v1.1.4')
readme.write_text(text, encoding='utf-8')
