from pathlib import Path
import json

ROOT = Path('src/addons/Warext/MinecraftVote')


def require(path: str, needles: list[str]) -> None:
    text = (ROOT / path).read_text(encoding='utf-8')
    for needle in needles:
        if needle not in text:
            raise SystemExit(f'{path}: gerekli güvenlik deseni bulunamadı: {needle}')


def forbid(path: str, needles: list[str]) -> None:
    text = (ROOT / path).read_text(encoding='utf-8')
    for needle in needles:
        if needle in text:
            raise SystemExit(f'{path}: yasak/deseni riskli içerik bulundu: {needle}')


require('Pub/Controller/Vote.php', ['captchaIsValid()', 'warextMcRequireVerifiedAccountForVotes', "verification_state !== 'verified'", 'setRequestFingerprint(', 'enqueueVoteDelivery()', 'enqueueWebhookDelivery('])
require('_output/templates/public/warext_mc_server_vote.html', ['<xf:captcharow', 'force="true"', '$requireVerifiedAccount'])
require('Service/Vote/Creator.php', ["hash_hmac('sha256', $ip", 'assertCooldown(', 'assertIpVelocity(', 'calculateFraudScore('])
require('Network/EndpointResolver.php', ['FILTER_FLAG_NO_PRIV_RANGE', 'FILTER_FLAG_NO_RES_RANGE'])
require('Service/Webhook/Dispatcher.php', ["$scheme !== 'https'", "'allow_redirects' => false", 'X-Warext-Signature', 'resolveTcp('])
require('Pub/Controller/Api.php', ['warextMcPublicApiEnabled', "->where('state', 'active')", "'warextMcApi'"])
require('Cron/Maintenance.php', ['warextMcPingHistoryRetentionDays', "status = 'processing'", "'status' => 'retry'"])
require('Pub/Controller/Sitemap.php', ["->where('state', 'active')", 'canonical:sunucular/detay', '->limit(50000)'])
require('Pub/Controller/Sponsor.php', ['XF:Purchasable', 'findPaymentProfilesForList()', 'warextMcSponsorSalesEnabled'])
require('Purchasable/Sponsor.php', ['completePurchase(', 'reversePurchase(', 'Warext\\\\MinecraftVote:Sponsor'])
require('_output/templates/public/warext_mc_sponsor_purchase.html', ['payment-provider-container', 'js-paymentProviderReply-warext_mc_sponsor'])

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
if addon.get('version_string') != '1.0.0' or int(addon.get('version_id', 0)) < 1010000:
    raise SystemExit('Stable sürüm numarası 1.0.0 değil.')

for path in ROOT.rglob('*.php'):
    text = path.read_text(encoding='utf-8')
    if 'eval(' in text or 'base64_decode(' in text or 'shell_exec(' in text or 'passthru(' in text:
        raise SystemExit(f'{path}: yasak yürütme deseni bulundu')

print('Warext MinecraftVote güvenlik regresyon kontrolleri başarılı.')
