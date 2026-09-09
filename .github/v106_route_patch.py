from pathlib import Path
import json
import re

ROOT = Path('src/addons/Warext/MinecraftVote')


def write_json(path, data):
    path.write_text(json.dumps(data, ensure_ascii=False, indent=4) + '\n', encoding='utf-8')


root_route_path = ROOT / '_output/routes/admin_warext-minecraft_.json'
root_route = json.loads(root_route_path.read_text(encoding='utf-8'))
root_route['controller'] = 'Warext\\MinecraftVote:Server'
root_route['context'] = 'warextMinecraftServers'
write_json(root_route_path, root_route)

servers_route_path = ROOT / '_output/routes/admin_warext-minecraft_servers.json'
servers_route = json.loads(servers_route_path.read_text(encoding='utf-8'))
servers_route['controller'] = 'Warext\\MinecraftVote:Server'
servers_route['context'] = 'warextMinecraftServers'
servers_route['action_prefix'] = 'servers'
write_json(servers_route_path, servers_route)

nav_path = ROOT / '_output/admin_navigation/warextMinecraftVote.json'
nav = json.loads(nav_path.read_text(encoding='utf-8'))
nav['parent_navigation_id'] = ''
nav['link'] = 'warext-minecraft/setup'
nav['hide_no_children'] = True
write_json(nav_path, nav)

server_path = ROOT / 'Admin/Controller/Server.php'
text = server_path.read_text(encoding='utf-8')
pattern = re.compile(r"    public function actionIndex\(\)\n    \{\n(?P<body>.*?)\n    \}\n\n    public function actionVotes\(\)", re.DOTALL)
match = pattern.search(text)
if not match:
    raise SystemExit('Server actionIndex bloğu bulunamadı')
body = match.group('body')
replacement = "    public function actionIndex()\n    {\n        return $this->redirect($this->buildLink('warext-minecraft/setup'));\n    }\n\n    public function actionServers()\n    {\n" + body + "\n    }\n\n    public function actionVotes()"
text = pattern.sub(replacement, text, count=1)
server_path.write_text(text, encoding='utf-8')

security_path = Path('.github/security_regression.py')
security = security_path.read_text(encoding='utf-8')
security = security.replace("require('_output/admin_navigation/warextMinecraftVote.json', ['\"parent_navigation_id\": \"\"', '\"link\": \"warext-minecraft\"', '\"hide_no_children\": true'])", "require('_output/admin_navigation/warextMinecraftVote.json', ['\"parent_navigation_id\": \"\"', '\"link\": \"warext-minecraft/setup\"', '\"hide_no_children\": true'])")
security = security.replace("require('_output/routes/admin_warext-minecraft_.json', ['MinecraftVote:Setup', 'warextMinecraftVote'])", "require('_output/routes/admin_warext-minecraft_.json', ['MinecraftVote:Server', 'warextMinecraftServers'])")
security = security.replace("require('_output/routes/admin_warext-minecraft_servers.json', ['MinecraftVote:Server', 'warextMinecraftServers'])", "require('_output/routes/admin_warext-minecraft_servers.json', ['MinecraftVote:Server', 'warextMinecraftServers', '\"action_prefix\": \"servers\"'])")
security = security.replace("require('Admin/Controller/Server.php', ['actionVoteModerate(', 'actionVoteRetry(', 'actionRunVoteQueue(', 'actionRanking(', 'actionState(', 'actionPing(', 'actionDelete(', 'if (!$this->isPost())'])", "require('Admin/Controller/Server.php', ['actionServers(', \"buildLink('warext-minecraft/setup')\", 'actionVoteModerate(', 'actionVoteRetry(', 'actionRunVoteQueue(', 'actionRanking(', 'actionState(', 'actionPing(', 'actionDelete(', 'if (!$this->isPost())'])")
security_path.write_text(security, encoding='utf-8')
