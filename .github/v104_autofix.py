from pathlib import Path
import json

ROOT = Path('src/addons/Warext/MinecraftVote')


def replace(path, old, new, count=1):
    p = Path(path)
    text = p.read_text(encoding='utf-8')
    if old not in text:
        raise SystemExit(f'Missing patch marker in {path}: {old[:80]}')
    p.write_text(text.replace(old, new, count), encoding='utf-8')


def safe_get(path, signature, redirect):
    old = signature + "\n    {\n        $this->assertPostOnly();\n"
    new = signature + "\n    {\n        if (!$this->isPost())\n        {\n            return $this->redirect(" + redirect + ");\n        }\n"
    replace(ROOT / path, old, new)


safe_get('Admin/Controller/Server.php', '    public function actionVoteModerate()', "$this->buildLink('warext-minecraft/suspect-votes')")
safe_get('Admin/Controller/Server.php', '    public function actionVoteRetry()', "$this->buildLink('warext-minecraft/votes')")
safe_get('Admin/Controller/Server.php', '    public function actionRunVoteQueue()', "$this->buildLink('warext-minecraft/votes')")
safe_get('Admin/Controller/Server.php', '    public function actionRanking()', "$this->buildLink('warext-minecraft', null, ['state' => 'all'])")
safe_get('Admin/Controller/Server.php', '    public function actionState()', "$this->buildLink('warext-minecraft')")
safe_get('Admin/Controller/Server.php', '    public function actionPing()', "$this->buildLink('warext-minecraft', null, ['state' => 'all'])")
safe_get('Admin/Controller/Server.php', '    public function actionDelete()', "$this->buildLink('warext-minecraft')")
safe_get('Admin/Controller/Achievement.php', '    public function actionToggle(ParameterBag $params)', "$this->buildLink('warext-minecraft/achievements')")
safe_get('Admin/Controller/Achievement.php', '    public function actionRebuild()', "$this->buildLink('warext-minecraft/achievements')")
safe_get('Admin/Controller/Health.php', '    public function actionRetryFailed()', "$this->buildLink('warext-minecraft/health')")
safe_get('Admin/Controller/Health.php', '    public function actionRecoverStale()', "$this->buildLink('warext-minecraft/health')")
safe_get('Admin/Controller/Sponsor.php', '    public function actionToggle(ParameterBag $params)', "$this->buildLink('warext-minecraft/sponsors')")
safe_get('Admin/Controller/Sponsor.php', '    public function actionDelete(ParameterBag $params)', "$this->buildLink('warext-minecraft/sponsors')")
safe_get('Admin/Controller/Report.php', '    public function actionUpdate(ParameterBag $params)', "$this->buildLink('warext-minecraft/reports')")
replace(ROOT / 'Pub/Controller/Report.php', "        if ($this->isPost())\n        {\n            $this->assertPostOnly();\n", "        if ($this->isPost())\n        {\n")

replace(ROOT / 'Entity/Sponsor.php', "            'display_order' => ['type' => self::UINT, 'default' => 10],\n            'created_by'", "            'display_order' => ['type' => self::UINT, 'default' => 10],\n            'purchase_request_key' => ['type' => self::STR, 'maxLength' => 32, 'default' => ''],\n            'created_by'")

p = ROOT / 'Purchasable/Sponsor.php'
s = p.read_text(encoding='utf-8')
s = s.replace("        $paymentProfile = $this->app->em()->find('XF:PaymentProfile', $profileId);\n", "        if ($this->hasIndefiniteSponsor((int)$server->server_id))\n        {\n            $error = 'Bu sunucunun süresiz sponsorluğu zaten aktif.';\n            return null;\n        }\n\n        $paymentProfile = $this->app->em()->find('XF:PaymentProfile', $profileId);\n", 1)
s = s.replace("        $latest = $this->app->finder('Warext\\\\MinecraftVote:Sponsor')\n", "        $requestKey = (string)$purchaseRequest->request_key;\n        if ($requestKey !== '')\n        {\n            $existing = $this->app->finder('Warext\\\\MinecraftVote:Sponsor')\n                ->where('purchase_request_key', $requestKey)\n                ->fetchOne();\n            if ($existing)\n            {\n                return;\n            }\n        }\n\n        $latest = $this->app->finder('Warext\\\\MinecraftVote:Sponsor')\n", 1)
s = s.replace("        $sponsor->display_order = 10;\n        $sponsor->created_by = (int)$purchaseRequest->user_id;\n", "        $sponsor->display_order = 10;\n        $sponsor->purchase_request_key = $requestKey;\n        $sponsor->created_by = (int)$purchaseRequest->user_id;\n", 1)
old_reverse = """        $sponsor = $this->app->finder('Warext\\\\MinecraftVote:Sponsor')
            ->where('server_id', $serverId)
            ->where('created_by', (int)$purchaseRequest->user_id)
            ->where('state', 'active')
            ->order('sponsor_id', 'DESC')
            ->fetchOne();
"""
new_reverse = """        $requestKey = (string)$purchaseRequest->request_key;
        $sponsor = null;
        if ($requestKey !== '')
        {
            $sponsor = $this->app->finder('Warext\\\\MinecraftVote:Sponsor')
                ->where('purchase_request_key', $requestKey)
                ->fetchOne();
        }
        if (!$sponsor)
        {
            $sponsor = $this->app->finder('Warext\\\\MinecraftVote:Sponsor')
                ->where('server_id', $serverId)
                ->where('created_by', (int)$purchaseRequest->user_id)
                ->where('state', 'active')
                ->order('sponsor_id', 'DESC')
                ->fetchOne();
        }
"""
if old_reverse not in s:
    raise SystemExit('Missing sponsor reverse block')
s = s.replace(old_reverse, new_reverse, 1)
needle = "        $price = $this->getPackagePrice($days);\n"
first = s.find(needle)
second = s.find(needle, first + 1)
if second < 0:
    raise SystemExit('Missing second sponsor price block')
guard = "        if ($this->hasIndefiniteSponsor((int)$server->server_id))\n        {\n            $error = 'Bu sunucunun süresiz sponsorluğu zaten aktif.';\n            return null;\n        }\n\n" + needle
s = s[:second] + s[second:].replace(needle, guard, 1)
marker = "    protected function encodePurchasableId(int $serverId, int $days): int\n"
helper = """    protected function hasIndefiniteSponsor(int $serverId): bool
    {
        return (bool)$this->app->finder('Warext\\\\MinecraftVote:Sponsor')
            ->where('server_id', $serverId)
            ->where('placement', 'list_top')
            ->where('state', 'active')
            ->where('end_date', 0)
            ->fetchOne();
    }

"""
if marker not in s:
    raise SystemExit('Missing sponsor helper marker')
s = s.replace(marker, helper + marker, 1)
p.write_text(s, encoding='utf-8')

p = ROOT / 'Setup.php'
s = p.read_text(encoding='utf-8')
s = s.replace("    public function installStep1(): void\n    {\n        $this->schemaManager()->createTable('xf_warext_mc_server', function (Create $table)\n", "    public function installStep1(): void\n    {\n        $sm = $this->schemaManager();\n        if ($sm->tableExists('xf_warext_mc_server'))\n        {\n            return;\n        }\n\n        $sm->createTable('xf_warext_mc_server', function (Create $table)\n", 1)
s = s.replace("    public function installStep2(): void\n    {\n        $this->schemaManager()->createTable('xf_warext_mc_category', function (Create $table)\n        {\n", "    public function installStep2(): void\n    {\n        $sm = $this->schemaManager();\n        if (!$sm->tableExists('xf_warext_mc_category'))\n        {\n            $sm->createTable('xf_warext_mc_category', function (Create $table)\n            {\n", 1)
s = s.replace("            $table->addKey(['is_active', 'display_order'], 'warext_mc_category_order');\n        });\n\n        $this->db()->insertBulk('xf_warext_mc_category', [\n            ['title' => 'Survival', 'slug' => 'survival', 'description' => '', 'display_order' => 10, 'is_active' => 1],\n            ['title' => 'SkyBlock', 'slug' => 'skyblock', 'description' => '', 'display_order' => 20, 'is_active' => 1],\n            ['title' => 'BoxPvP', 'slug' => 'boxpvp', 'description' => '', 'display_order' => 30, 'is_active' => 1],\n            ['title' => 'OneBlock', 'slug' => 'oneblock', 'description' => '', 'display_order' => 40, 'is_active' => 1],\n            ['title' => 'Factions', 'slug' => 'factions', 'description' => '', 'display_order' => 50, 'is_active' => 1],\n            ['title' => 'Towny', 'slug' => 'towny', 'description' => '', 'display_order' => 60, 'is_active' => 1],\n            ['title' => 'Prison', 'slug' => 'prison', 'description' => '', 'display_order' => 70, 'is_active' => 1],\n            ['title' => 'SMP', 'slug' => 'smp', 'description' => '', 'display_order' => 80, 'is_active' => 1],\n            ['title' => 'Roleplay', 'slug' => 'roleplay', 'description' => '', 'display_order' => 90, 'is_active' => 1],\n            ['title' => 'Minigames', 'slug' => 'minigames', 'description' => '', 'display_order' => 100, 'is_active' => 1],\n            ['title' => 'Modlu', 'slug' => 'modlu', 'description' => '', 'display_order' => 110, 'is_active' => 1],\n            ['title' => 'Vanilla', 'slug' => 'vanilla', 'description' => '', 'display_order' => 120, 'is_active' => 1]\n        ]);\n", "            $table->addKey(['is_active', 'display_order'], 'warext_mc_category_order');\n            });\n        }\n\n        $defaults = [\n            ['Survival', 'survival', '', 10, 1], ['SkyBlock', 'skyblock', '', 20, 1],\n            ['BoxPvP', 'boxpvp', '', 30, 1], ['OneBlock', 'oneblock', '', 40, 1],\n            ['Factions', 'factions', '', 50, 1], ['Towny', 'towny', '', 60, 1],\n            ['Prison', 'prison', '', 70, 1], ['SMP', 'smp', '', 80, 1],\n            ['Roleplay', 'roleplay', '', 90, 1], ['Minigames', 'minigames', '', 100, 1],\n            ['Modlu', 'modlu', '', 110, 1], ['Vanilla', 'vanilla', '', 120, 1]\n        ];\n        foreach ($defaults as [$title, $slug, $description, $displayOrder, $isActive])\n        {\n            $this->db()->query(\n                'INSERT IGNORE INTO xf_warext_mc_category (title, slug, description, display_order, is_active) VALUES (?, ?, ?, ?, ?)',\n                [$title, $slug, $description, $displayOrder, $isActive]\n            );\n        }\n", 1)
for step, table in [(3,'xf_warext_mc_server_category'),(4,'xf_warext_mc_vote'),(5,'xf_warext_mc_server_team'),(6,'xf_warext_mc_ping_history')]:
    old = f"    public function installStep{step}(): void\n    {{\n        $this->schemaManager()->createTable('{table}', function (Create $table)\n"
    new = f"    public function installStep{step}(): void\n    {{\n        $sm = $this->schemaManager();\n        if ($sm->tableExists('{table}'))\n        {{\n            return;\n        }}\n\n        $sm->createTable('{table}', function (Create $table)\n"
    if old not in s:
        raise SystemExit(f'Missing install step {step}')
    s = s.replace(old, new, 1)
s = s.replace("    public function installStep16(): void\n    {\n        $this->createReportTable();\n    }\n\n", "    public function installStep16(): void\n    {\n        $this->createReportTable();\n    }\n\n    public function installStep17(): void\n    {\n        $this->ensureSponsorPurchaseSupport();\n    }\n\n", 1)
s = s.replace("    public function upgrade1000130Step2(): void\n    {\n        $this->createReportTable();\n    }\n\n", "    public function upgrade1000130Step2(): void\n    {\n        $this->createReportTable();\n    }\n\n    public function upgrade1010040Step1(): void\n    {\n        $this->repairCurrentSchema();\n    }\n\n    public function upgrade1010040Step2(): void\n    {\n        $this->ensureSponsorPurchaseSupport();\n    }\n\n", 1)
s = s.replace("            $table->addColumn('display_order', 'int')->setDefault(10);\n            $table->addColumn('created_by', 'int')->setDefault(0);\n", "            $table->addColumn('display_order', 'int')->setDefault(10);\n            $table->addColumn('purchase_request_key', 'varchar', 32)->setDefault('');\n            $table->addColumn('created_by', 'int')->setDefault(0);\n", 1)
s = s.replace("            $table->addKey(['server_id', 'start_date', 'end_date'], 'warext_mc_sponsor_server_date');\n", "            $table->addKey(['server_id', 'start_date', 'end_date'], 'warext_mc_sponsor_server_date');\n            $table->addKey('purchase_request_key', 'warext_mc_sponsor_purchase_request');\n", 1)
helpers = """    protected function repairCurrentSchema(): void
    {
        $this->createVotifierTable();
        $this->createMinecraftAccountTable();
        $this->createSeasonTables();
        $this->createReviewTable();
        $this->createFavoriteTable();
        $this->ensureFavoriteTrackingColumns();
        $this->createServerUpdateTable();
        $this->createAchievementTables();
        $this->createSponsorTable();
        $this->createAuditLogTable();
        $this->createReportTable();
        $this->addRankingColumns();
        $this->addSeasonSnapshotColumns();

        $sm = $this->schemaManager();
        foreach ([
            'last_ping_error' => ['varchar', 500, 'detected_version'],
            'verification_token_date' => ['int', None, 'verification_token'],
            'verified_date' => ['int', None, 'verification_token_date']
        ] as $column => $spec)
        {
            if (!$sm->columnExists('xf_warext_mc_server', $column))
            {
                $sm->alterTable('xf_warext_mc_server', function (Alter $table) use ($column, $spec)
                {
                    $definition = $spec[1] ? $table->addColumn($column, $spec[0], $spec[1]) : $table->addColumn($column, $spec[0]);
                    $definition->setDefault($spec[0] === 'int' ? 0 : '')->after($spec[2]);
                });
            }
        }
    }

    protected function ensureSponsorPurchaseSupport(): void
    {
        $this->createSponsorTable();
        $sm = $this->schemaManager();
        if (!$sm->columnExists('xf_warext_mc_sponsor', 'purchase_request_key'))
        {
            $sm->alterTable('xf_warext_mc_sponsor', function (Alter $table)
            {
                $table->addColumn('purchase_request_key', 'varchar', 32)->setDefault('')->after('display_order');
                $table->addKey('purchase_request_key', 'warext_mc_sponsor_purchase_request');
            });
        }
        $this->db()->query(
            'INSERT INTO xf_purchasable (purchasable_type_id, purchasable_class, addon_id) VALUES (?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE purchasable_class = VALUES(purchasable_class), addon_id = VALUES(addon_id)',
            ['warext_mc_sponsor', 'Warext\\MinecraftVote:Sponsor', 'Warext/MinecraftVote']
        );
    }

"""
marker = "    protected function createAuditLogTable(): void\n"
if marker not in s:
    raise SystemExit('Missing Setup helper marker')
s = s.replace(marker, helpers + marker, 1)
s = s.replace("    public function uninstallStep1(): void\n    {\n        $sm = $this->schemaManager();\n", "    public function uninstallStep1(): void\n    {\n        $this->db()->delete('xf_purchasable', 'purchasable_type_id = ?', 'warext_mc_sponsor');\n        $sm = $this->schemaManager();\n", 1)
p.write_text(s, encoding='utf-8')

addon = json.loads((ROOT / 'addon.json').read_text(encoding='utf-8'))
addon['version_id'] = 1010040
addon['version_string'] = '1.0.4'
(ROOT / 'addon.json').write_text(json.dumps(addon, ensure_ascii=False, indent=4) + '\n', encoding='utf-8')

p = Path('.github/security_regression.py')
s = p.read_text(encoding='utf-8')
s = s.replace("if addon.get('version_string') != '1.0.3' or int(addon.get('version_id', 0)) < 1010030:\n    raise SystemExit('Sürüm numarası 1.0.3 değil.')", "if addon.get('version_string') != '1.0.4' or int(addon.get('version_id', 0)) < 1010040:\n    raise SystemExit('Sürüm numarası 1.0.4 değil.')")
extra = """require('Admin/Controller/Server.php', ['actionVoteModerate(', 'actionVoteRetry(', 'actionRunVoteQueue(', 'actionRanking(', 'actionState(', 'actionPing(', 'actionDelete(', 'if (!$this->isPost())'])
require('Admin/Controller/Achievement.php', ['actionToggle(', 'actionRebuild(', 'if (!$this->isPost())'])
require('Admin/Controller/Health.php', ['actionRetryFailed(', 'actionRecoverStale(', 'if (!$this->isPost())'])
require('Admin/Controller/Sponsor.php', ['actionToggle(', 'actionDelete(', 'if (!$this->isPost())'])
require('Admin/Controller/Report.php', ['actionUpdate(', 'if (!$this->isPost())'])
require('Setup.php', ['installStep17', 'upgrade1010040Step1', 'upgrade1010040Step2', 'repairCurrentSchema', 'ensureSponsorPurchaseSupport', 'purchase_request_key', 'INSERT IGNORE INTO xf_warext_mc_category'])
require('Entity/Sponsor.php', ['purchase_request_key'])
"""
marker = "\nfor template in (ROOT / '_output/templates').rglob('*.html'):\n"
if extra not in s:
    s = s.replace(marker, '\n' + extra + marker, 1)
marker2 = "    for dangerous in ['eval(', 'shell_exec(', 'passthru(']:\n"
s = s.replace(marker2, "    if 'assertPostOnly()' in text:\n        raise SystemExit(f'{path}: genel POST-only hata ekranı oluşturabilecek assertPostOnly kaldı')\n\n" + marker2, 1)
p.write_text(s, encoding='utf-8')

p = Path('README.md')
s = p.read_text(encoding='utf-8').replace('Warext-MinecraftVote-1.0.3.zip', 'Warext-MinecraftVote-1.0.4.zip')
s = s.replace('Kurulumdan sonra **Admin CP > Minecraft Sunucuları > Kurulum & Yapılandırma** ekranını takip edin.', '1.0.4, kurulum/güncelleme adımlarını tekrar çalıştırılabilir hale getirir ve eksik şema parçalarını mevcut verileri silmeden onarır.\n\nKurulumdan sonra **Admin CP > Minecraft Sunucuları > Kurulum & Yapılandırma** ekranını takip edin.')
p.write_text(s, encoding='utf-8')

Path('.github/workflows/v104-autofix.yml').unlink(missing_ok=True)
Path('.github/v104_autofix.py').unlink(missing_ok=True)
