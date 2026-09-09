from pathlib import Path
import json
import re

ROOT = Path('src/addons/Warext/MinecraftVote')


def write_json(path, data):
    path.write_text(json.dumps(data, ensure_ascii=False, indent=4) + '\n', encoding='utf-8')


routes = ROOT / '_output/routes'
for path in routes.glob('admin_warext-minecraft*.json'):
    data = json.loads(path.read_text(encoding='utf-8'))
    sub = data.get('sub_name', '')
    if sub == '':
        data['controller'] = 'Warext\\MinecraftVote:Setup'
        data['context'] = 'warextMinecraftVote'
    elif sub == 'setup':
        data['context'] = 'warextMinecraftSetup'
    elif sub == 'servers':
        data['context'] = 'warextMinecraftServers'
    elif sub == 'categories' or sub.startswith('category-'):
        data['context'] = 'warextMinecraftCategories'
    elif sub == 'health' or sub.startswith('health/'):
        data['context'] = 'warextMinecraftHealth'
    elif sub == 'achievements' or sub.startswith('achievement-'):
        data['context'] = 'warextMcAchievements'
    elif sub == 'audit':
        data['context'] = 'warextMinecraftAudit'
    elif sub == 'reports' or sub.startswith('report-'):
        data['context'] = 'warextMcReports'
    elif sub == 'sponsors' or sub.startswith('sponsor-'):
        data['context'] = 'warextMinecraftSponsors'
    else:
        data['context'] = 'warextMinecraftServers'
    write_json(path, data)

servers_route = routes / 'admin_warext-minecraft_servers.json'
write_json(servers_route, {
    'route_type': 'admin',
    'route_prefix': 'warext-minecraft',
    'sub_name': 'servers',
    'format': '',
    'build_class': '',
    'build_method': '',
    'controller': 'Warext\\MinecraftVote:Server',
    'context': 'warextMinecraftServers',
    'action_prefix': ''
})

nav = ROOT / '_output/admin_navigation'
root_nav = json.loads((nav / 'warextMinecraftVote.json').read_text(encoding='utf-8'))
root_nav['parent_navigation_id'] = ''
root_nav['display_order'] = 450
root_nav['link'] = 'warext-minecraft'
root_nav['hide_no_children'] = True
write_json(nav / 'warextMinecraftVote.json', root_nav)

servers_nav = json.loads((nav / 'warextMinecraftServers.json').read_text(encoding='utf-8'))
servers_nav['link'] = 'warext-minecraft/servers'
write_json(nav / 'warextMinecraftServers.json', servers_nav)

phrase = ROOT / '_output/phrases/admin_navigation.warextMinecraftVote.txt'
phrase.write_text('Minecraft Sunucu & Vote Sistemi', encoding='utf-8')

server_controller = ROOT / 'Admin/Controller/Server.php'
text = server_controller.read_text(encoding='utf-8')
text = re.sub(r"buildLink\('warext-minecraft'(?=[,)])", "buildLink('warext-minecraft/servers'", text)
server_controller.write_text(text, encoding='utf-8')

for path in (ROOT / '_output/templates/admin').glob('*.html'):
    text = path.read_text(encoding='utf-8')
    text = re.sub(r"link\('warext-minecraft'(?=[,)])", "link('warext-minecraft/servers'", text)
    path.write_text(text, encoding='utf-8')

addon_path = ROOT / 'addon.json'
addon = json.loads(addon_path.read_text(encoding='utf-8'))
addon['version_id'] = 1010060
addon['version_string'] = '1.0.6'
write_json(addon_path, addon)

readme = Path('README.md')
readme_text = readme.read_text(encoding='utf-8')
readme_text = readme_text.replace('Warext-MinecraftVote-1.0.5.zip', 'Warext-MinecraftVote-1.0.6.zip')
readme_text = readme_text.replace('1.0.5', '1.0.6')
readme.write_text(readme_text, encoding='utf-8')

security_path = Path('.github/security_regression.py')
security = security_path.read_text(encoding='utf-8')
security = security.replace("require('_output/admin_navigation/warextMinecraftVote.json', ['warext-minecraft/setup'])", "require('_output/admin_navigation/warextMinecraftVote.json', ['\"parent_navigation_id\": \"\"', '\"link\": \"warext-minecraft\"', '\"hide_no_children\": true'])")
security = security.replace("require('_output/admin_navigation/warextMinecraftSetup.json', ['warext-minecraft/setup'])", "require('_output/admin_navigation/warextMinecraftSetup.json', ['warext-minecraft/setup'])\nrequire('_output/admin_navigation/warextMinecraftServers.json', ['warext-minecraft/servers'])\nrequire('_output/routes/admin_warext-minecraft_.json', ['Warext\\\\MinecraftVote:Setup', 'warextMinecraftVote'])\nrequire('_output/routes/admin_warext-minecraft_servers.json', ['Warext\\\\MinecraftVote:Server', 'warextMinecraftServers'])")
security = security.replace("if addon.get('version_string') != '1.0.5' or int(addon.get('version_id', 0)) < 1010050:\n    raise SystemExit('Sürüm numarası 1.0.5 değil.')", "if addon.get('version_string') != '1.0.6' or int(addon.get('version_id', 0)) < 1010060:\n    raise SystemExit('Sürüm numarası 1.0.6 değil.')")
insert = "\nserver_admin = (ROOT / 'Admin/Controller/Server.php').read_text(encoding='utf-8')\nif re.search(r\"buildLink\\('warext-minecraft'(?=[,)])\", server_admin):\n    raise SystemExit('Server controller hala ana admin routeunu sunucu listesi olarak kullanıyor')\n\nfor template in (ROOT / '_output/templates/admin').rglob('*.html'):\n    text = template.read_text(encoding='utf-8')\n    if re.search(r\"link\\('warext-minecraft'(?=[,)])\", text):\n        raise SystemExit(f'{template}: admin ana route sunucu listesine bağlanmış')\n"
security = security.replace("for template in (ROOT / '_output/templates').rglob('*.html'):\n", insert + "\nfor template in (ROOT / '_output/templates').rglob('*.html'):\n", 1)
security_path.write_text(security, encoding='utf-8')
