from pathlib import Path
import json
import re

ROOT = Path('src/addons/Warext/MinecraftVote')


def require(path: str, needles: list[str]) -> None:
    text = (ROOT / path).read_text(encoding='utf-8')
    for needle in needles:
        if needle not in text:
            raise SystemExit(f'{path}: gerekli güvenlik deseni bulunamadı: {needle}')


require('Pub/Controller/Vote.php', ['captchaIsValid()', 'warextMcRequireVerifiedAccountForVotes', "verification_state !== 'verified'", 'setRequestFingerprint(', 'enqueueVoteDelivery()', 'enqueueWebhookDelivery('])
require('_output/templates/public/warext_mc_server_vote.html', ['<xf:captcharow', 'force="true"', '$requireVerifiedAccount'])
require('Service/Vote/Creator.php', ["hash_hmac('sha256', $ip", 'assertCooldown(', 'assertIpVelocity(', 'calculateFraudScore('])
require('Network/EndpointResolver.php', ['FILTER_FLAG_NO_PRIV_RANGE', 'FILTER_FLAG_NO_RES_RANGE'])
require('Service/Webhook/Dispatcher.php', ["$scheme !== 'https'", "'allow_redirects' => false", 'X-Warext-Signature', 'resolveTcp('])
require('Pub/Controller/Api.php', ['warextMcPublicApiEnabled', "->where('state', 'active')", "'warextMcApi'"])
require('Cron/Maintenance.php', ['warextMcPingHistoryRetentionDays', "status = 'processing'", "'status' => 'retry'"])
require('Pub/Controller/Sitemap.php', ["->where('state', 'active')", 'canonical:sunucular/detay', '->limit(50000)'])
require('Pub/Controller/Sponsor.php', ['XF:Purchasable', 'findPaymentProfilesForList()', 'warextMcSponsorSalesEnabled'])
require('Purchasable/Sponsor.php', ['completePurchase(', 'reversePurchase(', "'Warext\\\\MinecraftVote:Sponsor'"])
require('_output/templates/public/warext_mc_sponsor_purchase.html', ['payment-provider-container', 'js-paymentProviderReply-warext_mc_sponsor'])
require('Security/SecretCipher.php', ['aes-256-gcm', 'OPENSSL_RAW_DATA', 'base64_decode($encoded, true)', 'hash_hkdf('])
require('_output/templates/public/warext_mc_server_compare.html', ['$selected0', '$selected1', '$selected2', '$selected3'])
require('Admin/Controller/Setup.php', ["'categories'", "'pending'", "'active'", 'warextMcVoteCaptcha', 'warextMcSponsorSalesEnabled'])
require('Admin/Controller/Category.php', ['actionEdit(', 'actionToggle(', 'actionDelete(', 'if (!$this->isPost())', "'categoryRows' => $categoryRows"])
require('_output/templates/admin/warext_mc_admin_setup.html', ['Kurulum ve Yapılandırma', 'Kategoriler', 'Kullanıcı Grubu İzinleri', 'NuVotifier'])
require('_output/templates/admin/warext_mc_admin_category_index.html', ['$categoryRows', '$row.usageCount'])
require('_output/templates/public/warext_mc_server_add.html', ['kategori seçimi yeni form alanı açmaz', 'Ana sunucu adresi', 'Crossplay'])
require('Pub/Controller/Index.php', ['networkStatsRow', "'votes_month'", 'sortLinks', 'categoryItems', 'hasAdvancedFilters'])
require('_output/templates/public/warext_mc_server_index.html', ['warextMcVoteList', "link('sunucular/oy'", 'copy-to-clipboard', 'data-copy-text', 'Gelişmiş filtreler', '$networkStats.server_count'])
require('_output/templates/public/warext_mc_servers.less', ['.warextMcDirectoryHero', '.warextMcVoteRow', '.warextMcFeaturedGrid', '.warextMcAdvancedFilters'])
require('_output/admin_navigation/warextMinecraftVote.json', ['"parent_navigation_id": ""', '"link": "warext-minecraft"', '"hide_no_children": true'])
require('_output/admin_navigation/warextMinecraftSetup.json', ['warext-minecraft/setup'])
require('_output/admin_navigation/warextMinecraftServers.json', ['warext-minecraft/servers'])
require('_output/routes/admin_warext-minecraft_.json', ['MinecraftVote:Setup', 'warextMinecraftVote'])
require('_output/routes/admin_warext-minecraft_servers.json', ['MinecraftVote:Server', 'warextMinecraftServers', '"format": "servers/"', '"action_prefix": "servers"'])
require('_output/admin_navigation/warextMinecraftCategories.json', ['warext-minecraft/categories'])
require('Pub/Controller/Favorite.php', ['if (!$this->isPost())', "buildLink('sunucular/detay'", "buildLink('sunucular/favoriler'"])
require('Pub/Controller/Server.php', ['actionHesapSil(', 'actionHesapBirincil(', 'if (!$this->isPost())'])
require('Pub/Controller/Team.php', ['actionRemove(', 'if (!$this->isPost())'])
require('Pub/Controller/Update.php', ['actionDelete(', 'if (!$this->isPost())'])
require('Pub/Controller/Review.php', ['actionDelete(', 'actionModerate(', 'if (!$this->isPost())'])
require('Pub/Controller/MyServers.php', ['$this->app->db()', 'xf_warext_mc_server_team', 'owner_user_id'])
require('_output/templates/public/warext_mc_server_mine.html', ['Sunucularım', "link('sunucular/ekle')", "link('sunucular/duzenle'"])
require('_output/routes/public_sunucular_benim.json', ['MinecraftVote:MyServers', '"format": "benim/"'])
require('Admin/Controller/Server.php', ['actionServers(', 'actionVoteModerate(', 'actionVoteRetry(', 'actionRunVoteQueue(', 'actionRanking(', 'actionState(', 'actionPing(', 'actionDelete(', 'if (!$this->isPost())'])
require('Admin/Controller/Achievement.php', ['actionToggle(', 'actionRebuild(', 'if (!$this->isPost())'])
require('Admin/Controller/Health.php', ['actionRetryFailed(', 'actionRecoverStale(', 'if (!$this->isPost())'])
require('Admin/Controller/Sponsor.php', ['actionToggle(', 'actionDelete(', 'if (!$this->isPost())'])
require('Admin/Controller/Report.php', ['actionUpdate(', 'if (!$this->isPost())'])
require('Setup.php', ['installStep17', 'upgrade1010040Step1', 'upgrade1010040Step2', 'repairCurrentSchema', 'ensureSponsorPurchaseSupport', 'purchase_request_key', 'INSERT IGNORE INTO xf_warext_mc_category'])
require('Entity/Sponsor.php', ['purchase_request_key'])


server_admin = (ROOT / 'Admin/Controller/Server.php').read_text(encoding='utf-8')
if re.search(r"buildLink\('warext-minecraft'(?=[,)])", server_admin):
    raise SystemExit('Server controller hala ana admin routeunu sunucu listesi olarak kullanıyor')

for template in (ROOT / '_output/templates/admin').rglob('*.html'):
    text = template.read_text(encoding='utf-8')
    if re.search(r"link\('warext-minecraft'(?=[,)])", text):
        raise SystemExit(f'{template}: admin ana route sunucu listesine bağlanmış')

for controller_root in [ROOT / 'Admin/Controller', ROOT / 'Pub/Controller']:
    for controller_path in controller_root.rglob('*.php'):
        text = controller_path.read_text(encoding='utf-8')
        if '$this->db()' in text:
            raise SystemExit(
                f'{controller_path}: AbstractController içinde bulunmayan $this->db() kullanımı var; '
                '$this->app->db() kullanılmalı'
            )


navigation_root = ROOT / '_output/navigation'
expected_navigation = {
    'warextMcServers': ('', "{{ link('sunucular') }}", ''),
    'warextMcServerList': ('warextMcServers', "{{ link('sunucular') }}", ''),
    'warextMcMyServers': ('warextMcServers', "{{ link('sunucular/benim') }}", '$xf.visitor.user_id'),
    'warextMcServerAdd': ('warextMcServers', "{{ link('sunucular/ekle') }}", '$xf.visitor.user_id'),
    'warextMcCompare': ('warextMcServers', "{{ link('sunucular/karsilastir') }}", ''),
    'warextMcSeasons': ('warextMcServers', "{{ link('sunucular/sezonlar') }}", ''),
    'warextMcFavorites': ('warextMcServers', "{{ link('sunucular/favoriler') }}", '$xf.visitor.user_id'),
    'warextMcAccounts': ('warextMcServers', "{{ link('sunucular/hesaplar') }}", '$xf.visitor.user_id')
}
for navigation_id, (parent, link, condition) in expected_navigation.items():
    path = navigation_root / f'{navigation_id}.json'
    if not path.exists():
        raise SystemExit(f'Eksik public navigation: {navigation_id}')
    data = json.loads(path.read_text(encoding='utf-8'))
    if data.get('parent_navigation_id', '') != parent:
        raise SystemExit(f'{navigation_id}: yanlış parent navigation')
    type_config = data.get('type_config', {})
    if type_config.get('link') != link or type_config.get('display_condition', '') != condition:
        raise SystemExit(f'{navigation_id}: yanlış navigation link/condition')
    if not data.get('enabled', False):
        raise SystemExit(f'{navigation_id}: navigation devre dışı')

navigation_builder = Path('.github/build_xf_navigation.py').read_text(encoding='utf-8')
for needle in ["data / 'navigation.xml'", "route.get('route_prefix') == 'sunucular'", "route.set('context', 'warextMcServers')"]:
    if needle not in navigation_builder:
        raise SystemExit(f'Navigation build koruması eksik: {needle}')


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

for template in (ROOT / '_output/templates').rglob('*.html'):
    text = template.read_text(encoding='utf-8')
    if 'isset(' in text:
        raise SystemExit(f'{template}: desteklenmeyen isset kullanımı bulundu')

    if re.search(r'\$[A-Za-z_][A-Za-z0-9_]*\s*\[[^\]\n]+\]', text):
        raise SystemExit(f'{template}: XenForo template dilinde desteklenmeyen PHP tarzı dizi indeksleme bulundu')

    for match in re.finditer(r'<xf:option\b[^>]*>(.*?)</xf:option>', text, flags=re.IGNORECASE | re.DOTALL):
        if re.search(r'<\s*/?\s*[A-Za-z]', match.group(1)):
            raise SystemExit(f'{template}: xf:option içinde child element bulundu')

for option in [
    'warextMcVoteCaptcha.json',
    'warextMcRequireVerifiedAccountForVotes.json',
    'warextMcPublicApiEnabled.json',
    'warextMcWebhookEnabled.json',
    'warextMcPingHistoryRetentionDays.json',
    'warextMcSponsorSalesEnabled.json',
    'warextMcSponsorPrice7.json',
    'warextMcSponsorPrice30.json',
    'warextMcSponsorCurrency.json'
]:
    data = json.loads((ROOT / '_output/options' / option).read_text(encoding='utf-8'))
    if 'relations' not in data or 'warextMinecraftVote' not in data['relations']:
        raise SystemExit(f'{option}: seçenek grubu ilişkisi eksik')

addon = json.loads((ROOT / 'addon.json').read_text(encoding='utf-8'))
if addon.get('version_string') != '1.0.10' or int(addon.get('version_id', 0)) < 1010100:
    raise SystemExit('Sürüm numarası 1.0.10 değil.')

for path in ROOT.rglob('*.php'):
    text = path.read_text(encoding='utf-8')
    if 'assertPostOnly()' in text:
        raise SystemExit(f'{path}: genel POST-only hata ekranı oluşturabilecek assertPostOnly kaldı')

    if 'None' in text:
        raise SystemExit(f'{path}: PHP içinde Python None sabiti bulundu')

    for dangerous in ['eval(', 'shell_exec(', 'passthru(']:
        if dangerous in text:
            raise SystemExit(f'{path}: yasak yürütme deseni bulundu: {dangerous}')

    if 'base64_decode(' in text:
        is_cipher = path.as_posix().endswith('/Security/SecretCipher.php')
        if not is_cipher or 'base64_decode($encoded, true)' not in text:
            raise SystemExit(f'{path}: kontrolsüz base64_decode kullanımı bulundu')

print('Warext MinecraftVote güvenlik regresyon kontrolleri başarılı.')
