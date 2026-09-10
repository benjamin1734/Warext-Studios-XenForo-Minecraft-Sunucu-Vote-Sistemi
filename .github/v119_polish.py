from pathlib import Path

root = Path('src/addons/Warext/MinecraftVote')
template = root / '_output/templates/public/warext_mc_server_view.html'
text = template.read_text(encoding='utf-8')
needle = '<xf:description>{$seoDescription}</xf:description>\n<xf:css src="warext_mc_server_profile.less" />'
replacement = '<xf:description>{$seoDescription}</xf:description>\n<xf:h1 hidden="true" />\n<xf:css src="warext_mc_server_profile.less" />'
if needle not in text:
    raise SystemExit('server view header anchor missing')
text = text.replace(needle, replacement, 1)
template.write_text(text, encoding='utf-8')

style = root / '_output/templates/public/warext_mc_server_profile.less'
text = style.read_text(encoding='utf-8')
text = text.replace('@xf-borderColorAccent', '@xf-borderColor')
style.write_text(text, encoding='utf-8')

regression = Path('.github/security_regression.py')
text = regression.read_text(encoding='utf-8')
old = "require('_output/templates/public/warext_mc_server_view.html', ['warext_mc_server_profile.less', 'warextMcProfileHero', 'Hızlı Erişim', 'Son Oy Verenler', 'Bu Ay Top Voter', 'Sunucuyu Yönet', 'Şimdi Oy Ver'])"
new = "require('_output/templates/public/warext_mc_server_view.html', ['warext_mc_server_profile.less', '<xf:h1 hidden=\"true\" />', 'warextMcProfileHero', 'Hızlı Erişim', 'Son Oy Verenler', 'Bu Ay Top Voter', 'Sunucuyu Yönet', 'Şimdi Oy Ver'])"
if old not in text:
    raise SystemExit('server view regression anchor missing')
text = text.replace(old, new, 1)
regression.write_text(text, encoding='utf-8')
