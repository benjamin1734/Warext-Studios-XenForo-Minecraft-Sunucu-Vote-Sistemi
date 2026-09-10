from pathlib import Path

root = Path('src/addons/Warext/MinecraftVote')
regression = Path('.github/security_regression.py')
text = regression.read_text(encoding='utf-8')
anchor = "require('_output/templates/public/warext_mc_server_vote.less', ['.warextMcVotePage-hero', '.warextMcVotePage-banner', 'width: 468px;', 'height: 60px;', '.warextMcVotePage-metrics', '.warextMcVotePage-layout', '.warextMcVotePage-voteCard'])\n"
addition = "require('Pub/Controller/Detail.php', ['findRecentPublicVotes(', 'getTopVotersThisMonth(', \"findVisibleForServer\", \"PublicPermissions::allows('vote', false, true)\", \"['Owner', 'DiscussionThread']\"])\nrequire('Repository/Vote.php', ['findRecentPublicVotes(', 'getTopVotersThisMonth(', 'INNER JOIN xf_user', \"status <> 'rejected'\"])\nrequire('_output/templates/public/warext_mc_server_view.html', ['warext_mc_server_profile.less', 'warextMcProfileHero', 'Hızlı Erişim', 'Son Oy Verenler', 'Bu Ay Top Voter', 'Sunucuyu Yönet', 'Şimdi Oy Ver'])\nrequire('_output/templates/public/warext_mc_server_profile.less', ['.warextMcProfileHero', 'width: 468px;', 'height: 60px;', '.warextMcProfileGrid', 'grid-template-columns: 250px minmax(0, 1fr) 280px;', '.warextMcProfileSupport', '.warextMcProfileLeaderboard'])\n"
if addition not in text:
    if anchor not in text:
        raise SystemExit('profile regression insertion point missing')
    text = text.replace(anchor, anchor + addition, 1)
text = text.replace("if version_string != '1.1.8' or int(addon.get('version_id', 0)) < 1011080:", "if version_string != '1.1.9' or int(addon.get('version_id', 0)) < 1011090:", 1)
text = text.replace("raise SystemExit('Sürüm numarası 1.1.8 değil.')", "raise SystemExit('Sürüm numarası 1.1.9 değil.')", 1)
regression.write_text(text, encoding='utf-8')

checks = {
    root / 'Pub/Controller/Detail.php': ['findRecentPublicVotes', 'getTopVotersThisMonth', "PublicPermissions::allows('vote', false, true)"],
    root / 'Repository/Vote.php': ['getTopVotersThisMonth', 'INNER JOIN xf_user'],
    root / '_output/templates/public/warext_mc_server_view.html': ['warextMcProfileHero', 'Sunucuyu Yönet', 'Bu Ay Top Voter'],
    root / '_output/templates/public/warext_mc_server_profile.less': ['grid-template-columns: 250px minmax(0, 1fr) 280px;', 'width: 468px;', 'height: 60px;'],
    root / 'addon.json': ['"version_id": 1011090', '"version_string": "1.1.9"']
}
for path, needles in checks.items():
    value = path.read_text(encoding='utf-8')
    for needle in needles:
        if needle not in value:
            raise SystemExit(f'{path}: missing {needle}')
