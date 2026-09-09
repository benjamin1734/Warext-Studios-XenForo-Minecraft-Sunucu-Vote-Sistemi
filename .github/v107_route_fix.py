from pathlib import Path
import json
import re

ROOT = Path('src/addons/Warext/MinecraftVote')
ROUTES = ROOT / '_output/routes'


def write_json(path: Path, data: dict) -> None:
    path.write_text(json.dumps(data, ensure_ascii=False, indent=4) + '\n', encoding='utf-8')


for path in sorted(ROUTES.glob('*.json')):
    data = json.loads(path.read_text(encoding='utf-8'))
    sub = str(data.get('sub_name', '')).strip('/')
    if sub:
        fmt = str(data.get('format', '')).lstrip('/')
        required_prefix = sub + '/'
        if not fmt.startswith(required_prefix):
            data['format'] = required_prefix + fmt
            write_json(path, data)

server_actions = [
    'votes',
    'suspect-votes',
    'vote-moderate',
    'vote-retry',
    'run-vote-queue',
    'ranking',
    'state',
    'ping',
    'delete',
]
for sub in server_actions:
    path = ROUTES / f"admin_warext-minecraft_{sub}.json"
    write_json(path, {
        'route_type': 'admin',
        'route_prefix': 'warext-minecraft',
        'sub_name': sub,
        'format': sub + '/',
        'build_class': '',
        'build_method': '',
        'controller': 'Warext\\MinecraftVote:Server',
        'context': 'warextMinecraftServers',
        'action_prefix': sub,
    })

root_route_path = ROUTES / 'admin_warext-minecraft_.json'
root_route = json.loads(root_route_path.read_text(encoding='utf-8'))
root_route['controller'] = 'Warext\\MinecraftVote:Setup'
root_route['context'] = 'warextMinecraftVote'
root_route['action_prefix'] = ''
root_route['format'] = ''
write_json(root_route_path, root_route)

server_path = ROOT / 'Admin/Controller/Server.php'
server_text = server_path.read_text(encoding='utf-8')
server_text = server_text.replace(
    "    public function actionIndex()\n    {\n        return $this->redirect($this->buildLink('warext-minecraft/setup'));\n    }",
    "    public function actionIndex()\n    {\n        return $this->actionServers();\n    }"
)
server_path.write_text(server_text, encoding='utf-8')

nav_path = ROOT / '_output/admin_navigation/warextMinecraftVote.json'
nav = json.loads(nav_path.read_text(encoding='utf-8'))
nav['parent_navigation_id'] = ''
nav['link'] = 'warext-minecraft'
nav['hide_no_children'] = True
write_json(nav_path, nav)

addon_path = ROOT / 'addon.json'
addon = json.loads(addon_path.read_text(encoding='utf-8'))
addon['version_id'] = 1010070
addon['version_string'] = '1.0.7'
write_json(addon_path, addon)

readme = Path('README.md')
readme_text = readme.read_text(encoding='utf-8').replace('1.0.6', '1.0.7')
readme.write_text(readme_text, encoding='utf-8')

security_path = Path('.github/security_regression.py')
security = security_path.read_text(encoding='utf-8')
security = security.replace("'\"link\": \"warext-minecraft/setup\"'", "'\"link\": \"warext-minecraft\"'")
security = security.replace("require('_output/routes/admin_warext-minecraft_.json', ['MinecraftVote:Server', 'warextMinecraftServers'])", "require('_output/routes/admin_warext-minecraft_.json', ['MinecraftVote:Setup', 'warextMinecraftVote'])")
security = security.replace("require('_output/routes/admin_warext-minecraft_servers.json', ['MinecraftVote:Server', 'warextMinecraftServers', '\"action_prefix\": \"servers\"'])", "require('_output/routes/admin_warext-minecraft_servers.json', ['MinecraftVote:Server', 'warextMinecraftServers', '\"format\": \"servers/\"', '\"action_prefix\": \"servers\"'])")
security = security.replace("require('Admin/Controller/Server.php', ['actionServers(', \"buildLink('warext-minecraft/setup')\",", "require('Admin/Controller/Server.php', ['actionServers(',")
security = security.replace("if addon.get('version_string') != '1.0.6' or int(addon.get('version_id', 0)) < 1010060:\n    raise SystemExit('Sürüm numarası 1.0.6 değil.')", "if addon.get('version_string') != '1.0.7' or int(addon.get('version_id', 0)) < 1010070:\n    raise SystemExit('Sürüm numarası 1.0.7 değil.')")

route_guard = r'''
route_seen = set()
route_index = {}
for route_path in sorted((ROOT / '_output/routes').glob('*.json')):
    data = json.loads(route_path.read_text(encoding='utf-8'))
    key = (data.get('route_type', ''), data.get('route_prefix', ''), data.get('sub_name', ''))
    if key in route_seen:
        raise SystemExit(f'{route_path}: yinelenen route anahtarı: {key}')
    route_seen.add(key)
    route_index[key] = data

    sub_name = str(data.get('sub_name', '')).strip('/')
    route_format = str(data.get('format', '')).lstrip('/')
    if sub_name and not route_format.startswith(sub_name + '/'):
        raise SystemExit(f'{route_path}: sub_name URL formatına dahil değil: sub_name={sub_name!r}, format={route_format!r}')

for sub_name in [
    'servers', 'votes', 'suspect-votes', 'vote-moderate', 'vote-retry',
    'run-vote-queue', 'ranking', 'state', 'ping', 'delete'
]:
    key = ('admin', 'warext-minecraft', sub_name)
    data = route_index.get(key)
    if not data:
        raise SystemExit(f'Eksik Server admin route: {sub_name}')
    if data.get('controller') != 'Warext\\MinecraftVote:Server':
        raise SystemExit(f'{sub_name}: yanlış controller')
    if data.get('format') != sub_name + '/':
        raise SystemExit(f'{sub_name}: yanlış format: {data.get("format")!r}')
    if data.get('action_prefix') != sub_name:
        raise SystemExit(f'{sub_name}: yanlış action_prefix: {data.get("action_prefix")!r}')

root_admin = route_index.get(('admin', 'warext-minecraft', ''))
if not root_admin or root_admin.get('controller') != 'Warext\\MinecraftVote:Setup':
    raise SystemExit('warext-minecraft root route Setup controllerına bağlı değil')

server_admin = (ROOT / 'Admin/Controller/Server.php').read_text(encoding='utf-8')
index_match = re.search(r'public function actionIndex\(\)\s*\{(?P<body>.*?)\n\s*\}', server_admin, re.DOTALL)
if not index_match or 'return $this->actionServers();' not in index_match.group('body'):
    raise SystemExit('Server::actionIndex redirectsiz actionServers fallbackı değil')
'''

marker = "for template in (ROOT / '_output/templates').rglob('*.html'):\n"
if 'route_seen = set()' not in security:
    security = security.replace(marker, route_guard + '\n' + marker, 1)
security_path.write_text(security, encoding='utf-8')
