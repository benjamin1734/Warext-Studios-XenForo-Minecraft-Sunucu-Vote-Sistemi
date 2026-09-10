from pathlib import Path

root = Path('src/addons/Warext/MinecraftVote')
regression = Path('.github/security_regression.py')
text = regression.read_text(encoding='utf-8')
old = "require('_output/templates/public/warext_mc_server_vote.html', ['<xf:captcharow', 'force=\"true\"', '$requireVerifiedAccount'])"
new = "require('_output/templates/public/warext_mc_server_vote.html', ['<xf:captcharow', 'force=\"true\"', '$requireVerifiedAccount', 'warext_mc_server_vote.less', 'Sunucu Bilgileri', 'Sunucu Hakkında', 'IP Kopyala', 'Sıralama', '24 saat sonra tekrar deneyin', '$hasEligibleAccount'])\nrequire('_output/templates/public/warext_mc_server_vote.less', ['.warextMcVotePage-hero', '.warextMcVotePage-banner', 'width: 468px;', 'height: 60px;', '.warextMcVotePage-metrics', '.warextMcVotePage-layout', '.warextMcVotePage-voteCard'])"
if old not in text:
    raise SystemExit('vote template regression line not found')
text = text.replace(old, new, 1)
text = text.replace("if version_string != '1.1.7' or int(addon.get('version_id', 0)) < 1011070:", "if version_string != '1.1.8' or int(addon.get('version_id', 0)) < 1011080:", 1)
text = text.replace("raise SystemExit('Sürüm numarası 1.1.7 değil.')", "raise SystemExit('Sürüm numarası 1.1.8 değil.')", 1)
regression.write_text(text, encoding='utf-8')

readme = Path('README.md')
text = readme.read_text(encoding='utf-8')
text = text.replace('Warext-MinecraftVote-1.1.7.zip', 'Warext-MinecraftVote-1.1.8.zip', 1)
text = text.replace('Otomatik 300×100 statik banner, 300×100 optimize GIF banner, 1200×400 detay kapağı ve trailer', 'Otomatik 468×60 statik banner, 468×60 optimize GIF banner, 1200×400 detay kapağı ve trailer', 1)
needle = '- Güvenli oy, CAPTCHA ve Minecraft hesap doğrulama\n'
if needle not in text:
    raise SystemExit('README feature insertion point not found')
text = text.replace(needle, needle + '- Sunucu profil bilgilerini, bannerı, canlı metrikleri ve oy panelini birleştiren modern vote sayfası\n', 1)
readme.write_text(text, encoding='utf-8')

vote = (root / 'Pub/Controller/Vote.php').read_text(encoding='utf-8')
for needle in ["'hasEligibleAccount' => $hasEligibleAccount", "['Owner', 'DiscussionThread']"]:
    if needle not in vote:
        raise SystemExit(f'Vote.php missing {needle}')

for path, needles in {
    root / '_output/templates/public/warext_mc_server_vote.html': ['warextMcVotePage-hero', 'Sunucu Bilgileri', 'Sunucu Hakkında', 'IP Kopyala', '$hasEligibleAccount'],
    root / '_output/templates/public/warext_mc_server_vote.less': ['width: 468px;', 'height: 60px;', '.warextMcVotePage-voteCard'],
    root / 'addon.json': ['"version_id": 1011080', '"version_string": "1.1.8"']
}.items():
    value = path.read_text(encoding='utf-8')
    for needle in needles:
        if needle not in value:
            raise SystemExit(f'{path}: missing {needle}')
