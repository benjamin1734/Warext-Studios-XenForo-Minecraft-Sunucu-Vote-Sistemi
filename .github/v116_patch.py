from pathlib import Path

root = Path('src/addons/Warext/MinecraftVote')

media = root / 'Service/Server/Media.php'
text = media.read_text(encoding='utf-8')
text = text.replace("'width' => 300,\n                'height' => 100,", "'width' => 360,\n                'height' => 120,")
text = text.replace("'min_width' => 150,\n                'min_height' => 50,", "'min_width' => 180,\n                'min_height' => 60,")
text = text.replace('Liste bannerı en az 150×50 piksel olmalıdır.', 'Liste bannerı en az 180×60 piksel olmalıdır.')
text = text.replace('Hareketli banner en az 150×50 piksel olmalıdır.', 'Hareketli banner en az 180×60 piksel olmalıdır.')
media.write_text(text, encoding='utf-8')

less = root / '_output/templates/public/warext_mc_server_directory.less'
text = less.read_text(encoding='utf-8')
text = text.replace('min-height: 132px;', 'min-height: 152px;')
text = text.replace('flex: 0 0 316px;', 'flex: 0 0 376px;')
text = text.replace('width: 316px;', 'width: 376px;')
text = text.replace('min-width: 316px;', 'min-width: 376px;')
text = text.replace('width: 300px;\n    height: 100px;', 'width: 360px;\n    height: 120px;')
less.write_text(text, encoding='utf-8')

for name in ['warext_mc_server_add.html', 'warext_mc_server_edit.html']:
    path = root / '_output/templates/public' / name
    path.write_text(path.read_text(encoding='utf-8').replace('300×100', '360×120'), encoding='utf-8')

vote_controller = root / 'Pub/Controller/Vote.php'
text = vote_controller.read_text(encoding='utf-8')
text = text.replace("        $allowGuests = (bool)(\\XF::options()->warextMcAllowGuestVotes ?? true);\n", '')
text = text.replace("PublicPermissions::allows('vote', $allowGuests, true)", "PublicPermissions::allows('vote', false, true)")
text = text.replace(
"""        if (!$visitor->user_id && (!$allowGuests || $requireVerifiedAccount))
        {
            return $this->noPermission();
        }

        $linkedAccounts = $visitor->user_id
            ? $this->repository('Warext\\MinecraftVote:MinecraftAccount')->findForUser($visitor->user_id)->fetch()
            : [];
""",
"""        if (!$visitor->user_id)
        {
            return $this->noPermission();
        }

        $voteRepo = $this->repository('Warext\\MinecraftVote:Vote');
        $voteBlocked = $voteRepo->hasRecentUserVote(
            (int)$server->server_id,
            (int)$visitor->user_id,
            \\XF::$time - 86400
        );

        $linkedAccounts = $this->repository('Warext\\MinecraftVote:MinecraftAccount')
            ->findForUser($visitor->user_id)
            ->fetch();
"""
)
text = text.replace(
"""        if ($this->isPost())
        {
""",
"""        if ($this->isPost())
        {
            if ($voteBlocked)
            {
                return $this->error('Bu sunucuya son 24 saat içinde zaten oy verdiniz. 24 saat sonra tekrar deneyin.', 429);
            }

""",
1
)
text = text.replace(
"""            'cooldownHours' => min(168, max(1, (int)(\\XF::options()->warextMcVoteCooldownHours ?? 24))),
            'allowGuests' => $allowGuests,
""",
"""            'cooldownHours' => 24,
            'allowGuests' => false,
            'voteBlocked' => $voteBlocked,
"""
)
for needle in ["PublicPermissions::allows('vote', false, true)", "if (!$visitor->user_id)", "'voteBlocked' => $voteBlocked", "\\XF::$time - 86400", '24 saat sonra tekrar deneyin']:
    if needle not in text:
        raise SystemExit(f'Vote controller missing: {needle}')
vote_controller.write_text(text, encoding='utf-8')

creator = root / 'Service/Vote/Creator.php'
text = creator.read_text(encoding='utf-8')
text = text.replace(
"""        if (!$this->user->user_id && !(bool)(\\XF::options()->warextMcAllowGuestVotes ?? true))
        {
            throw new PrintableException('Oy verebilmek için giriş yapmanız gerekiyor.');
        }
""",
"""        if (!$this->user->user_id)
        {
            throw new PrintableException('Oy verebilmek için forum hesabınızla giriş yapmanız gerekiyor.');
        }
"""
)
text = text.replace(
"""            $cooldownHours = min(168, max(1, (int)(\\XF::options()->warextMcVoteCooldownHours ?? 24)));
            $since = \\XF::$time - ($cooldownHours * 3600);
""",
"""            $cooldownHours = 24;
            $since = \\XF::$time - 86400;
"""
)
text = text.replace('throw new PrintableException("Bu sunucuya son {$cooldownHours} saat içinde zaten oy verdiniz.");', "throw new PrintableException('Bu sunucuya son 24 saat içinde zaten oy verdiniz. 24 saat sonra tekrar deneyin.');")
for needle in ["if (!$this->user->user_id)", '$since = \\XF::$time - 86400', '24 saat sonra tekrar deneyin']:
    if needle not in text:
        raise SystemExit(f'Vote creator missing: {needle}')
creator.write_text(text, encoding='utf-8')

vote_template = root / '_output/templates/public/warext_mc_server_vote.html'
text = vote_template.read_text(encoding='utf-8')
form_marker = '<xf:form action="{{ link(\'sunucular/oy\', $server) }}" class="block" ajax="true">'
if form_marker not in text:
    raise SystemExit('Vote form marker not found')
text = text.replace(
    form_marker,
    '<xf:if is="$voteBlocked">\n                <div class="block-rowMessage block-rowMessage--warning">Bu sunucuya son 24 saat içinde zaten oy verdiniz. 24 saat sonra tekrar deneyin.</div>\n            <xf:else />\n            ' + form_marker,
    1
)
text = text.replace('            </xf:form>\n        </div>', '            </xf:form>\n            </xf:if>\n        </div>', 1)
if '$voteBlocked' not in text or '24 saat sonra tekrar deneyin' not in text:
    raise SystemExit('Vote template cooldown message missing')
vote_template.write_text(text, encoding='utf-8')

addon = root / 'addon.json'
text = addon.read_text(encoding='utf-8').replace('"version_id": 1011050', '"version_id": 1011060').replace('"version_string": "1.1.5"', '"version_string": "1.1.6"')
addon.write_text(text, encoding='utf-8')

reg = Path('.github/security_regression.py')
text = reg.read_text(encoding='utf-8')
text = text.replace("'300×100', '1200×400'", "'360×120', '1200×400'")
text = text.replace("['.warextMcDirectoryRow', 'flex: 0 0 316px;', 'width: 300px;', 'height: 100px;'", "['.warextMcDirectoryRow', 'flex: 0 0 376px;', 'width: 360px;', 'height: 120px;'")
text = text.replace("[\"'width' => 300\", \"'height' => 100\"", "[\"'width' => 360\", \"'height' => 120\"")
text = text.replace("['upload=\"true\"', 'animated_banner', 'discussion_thread_url', '300×100']", "['upload=\"true\"', 'animated_banner', 'discussion_thread_url', '360×120']")
text = text.replace("require('Pub/Controller/Vote.php', ['captchaIsValid()', 'warextMcRequireVerifiedAccountForVotes', \"verification_state !== 'verified'\", 'setRequestFingerprint(', 'enqueueVoteDelivery()', 'enqueueWebhookDelivery('])", "require('Pub/Controller/Vote.php', ['captchaIsValid()', 'warextMcRequireVerifiedAccountForVotes', \"verification_state !== 'verified'\", 'setRequestFingerprint(', 'enqueueVoteDelivery()', 'enqueueWebhookDelivery(', '!$visitor->user_id', 'voteBlocked', '86400', '24 saat sonra tekrar deneyin'])")
text = text.replace("require('Service/Vote/Creator.php', [\"hash_hmac('sha256', $ip\", 'assertCooldown(', 'assertIpVelocity(', 'calculateFraudScore('])", "require('Service/Vote/Creator.php', [\"hash_hmac('sha256', $ip\", 'assertCooldown(', 'assertIpVelocity(', 'calculateFraudScore(', '!$this->user->user_id', '$since = \\XF::$time - 86400', '24 saat sonra tekrar deneyin'])")
text = text.replace("if version_string != '1.1.5' or int(addon.get('version_id', 0)) < 1011050:\n    raise SystemExit('Sürüm numarası 1.1.5 değil.')", "if version_string != '1.1.6' or int(addon.get('version_id', 0)) < 1011060:\n    raise SystemExit('Sürüm numarası 1.1.6 değil.')")
reg.write_text(text, encoding='utf-8')

readme = Path('README.md')
readme.write_text(readme.read_text(encoding='utf-8').replace('Warext-MinecraftVote-1.1.5.zip', 'Warext-MinecraftVote-1.1.6.zip'), encoding='utf-8')
