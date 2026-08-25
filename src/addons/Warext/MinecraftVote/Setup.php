<?php

namespace Warext\MinecraftVote;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1(): void
    {
        $this->schemaManager()->createTable('xf_warext_mc_server', function (Create $table)
        {
            $table->addColumn('server_id', 'int')->autoIncrement();
            $table->addColumn('owner_user_id', 'int')->setDefault(0);
            $table->addColumn('title', 'varchar', 100)->setDefault('');
            $table->addColumn('slug', 'varchar', 100)->setDefault('');
            $table->addColumn('description', 'mediumtext')->nullable(true);
            $table->addColumn('server_type', 'varchar', 20)->setDefault('java');
            $table->addColumn('host', 'varchar', 255)->setDefault('');
            $table->addColumn('port', 'int')->setDefault(25565);
            $table->addColumn('bedrock_host', 'varchar', 255)->setDefault('');
            $table->addColumn('bedrock_port', 'int')->setDefault(19132);
            $table->addColumn('website_url', 'varchar', 255)->setDefault('');
            $table->addColumn('discord_url', 'varchar', 255)->setDefault('');
            $table->addColumn('store_url', 'varchar', 255)->setDefault('');
            $table->addColumn('game_modes', 'varchar', 255)->setDefault('');
            $table->addColumn('version_min', 'varchar', 30)->setDefault('');
            $table->addColumn('version_max', 'varchar', 30)->setDefault('');
            $table->addColumn('country_code', 'char', 2)->setDefault('');
            $table->addColumn('is_premium', 'tinyint')->setDefault(0);
            $table->addColumn('allow_cracked', 'tinyint')->setDefault(0);
            $table->addColumn('state', 'varchar', 20)->setDefault('pending');
            $table->addColumn('is_verified', 'tinyint')->setDefault(0);
            $table->addColumn('verification_method', 'varchar', 20)->setDefault('');
            $table->addColumn('verification_token', 'varchar', 64)->setDefault('');
            $table->addColumn('is_online', 'tinyint')->setDefault(0);
            $table->addColumn('ping_ms', 'int')->setDefault(0);
            $table->addColumn('players_online', 'int')->setDefault(0);
            $table->addColumn('players_max', 'int')->setDefault(0);
            $table->addColumn('motd', 'text')->nullable(true);
            $table->addColumn('detected_version', 'varchar', 100)->setDefault('');
            $table->addColumn('last_ping_error', 'varchar', 500)->setDefault('');
            $table->addColumn('uptime_bp', 'int')->setDefault(0);
            $table->addColumn('vote_count_total', 'int')->setDefault(0);
            $table->addColumn('vote_count_month', 'int')->setDefault(0);
            $table->addColumn('vote_count_today', 'int')->setDefault(0);
            $table->addColumn('view_count', 'int')->setDefault(0);
            $table->addColumn('rating_count', 'int')->setDefault(0);
            $table->addColumn('rating_sum', 'int')->setDefault(0);
            $table->addColumn('created_date', 'int')->setDefault(0);
            $table->addColumn('last_update_date', 'int')->setDefault(0);
            $table->addColumn('last_ping_date', 'int')->setDefault(0);
            $table->addUniqueKey('slug', 'warext_mc_server_slug');
            $table->addKey(['state', 'vote_count_month'], 'warext_mc_server_rank');
            $table->addKey(['is_online', 'players_online'], 'warext_mc_server_online');
            $table->addKey('owner_user_id', 'warext_mc_server_owner');
        });
    }

    public function installStep2(): void
    {
        $this->schemaManager()->createTable('xf_warext_mc_category', function (Create $table)
        {
            $table->addColumn('category_id', 'int')->autoIncrement();
            $table->addColumn('title', 'varchar', 50)->setDefault('');
            $table->addColumn('slug', 'varchar', 50)->setDefault('');
            $table->addColumn('description', 'varchar', 255)->setDefault('');
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('is_active', 'tinyint')->setDefault(1);
            $table->addUniqueKey('slug', 'warext_mc_category_slug');
            $table->addKey(['is_active', 'display_order'], 'warext_mc_category_order');
        });

        $this->db()->insertBulk('xf_warext_mc_category', [
            ['title' => 'Survival', 'slug' => 'survival', 'description' => '', 'display_order' => 10, 'is_active' => 1],
            ['title' => 'SkyBlock', 'slug' => 'skyblock', 'description' => '', 'display_order' => 20, 'is_active' => 1],
            ['title' => 'BoxPvP', 'slug' => 'boxpvp', 'description' => '', 'display_order' => 30, 'is_active' => 1],
            ['title' => 'OneBlock', 'slug' => 'oneblock', 'description' => '', 'display_order' => 40, 'is_active' => 1],
            ['title' => 'Factions', 'slug' => 'factions', 'description' => '', 'display_order' => 50, 'is_active' => 1],
            ['title' => 'Towny', 'slug' => 'towny', 'description' => '', 'display_order' => 60, 'is_active' => 1],
            ['title' => 'Prison', 'slug' => 'prison', 'description' => '', 'display_order' => 70, 'is_active' => 1],
            ['title' => 'SMP', 'slug' => 'smp', 'description' => '', 'display_order' => 80, 'is_active' => 1],
            ['title' => 'Roleplay', 'slug' => 'roleplay', 'description' => '', 'display_order' => 90, 'is_active' => 1],
            ['title' => 'Minigames', 'slug' => 'minigames', 'description' => '', 'display_order' => 100, 'is_active' => 1],
            ['title' => 'Modlu', 'slug' => 'modlu', 'description' => '', 'display_order' => 110, 'is_active' => 1],
            ['title' => 'Vanilla', 'slug' => 'vanilla', 'description' => '', 'display_order' => 120, 'is_active' => 1]
        ]);
    }

    public function installStep3(): void
    {
        $this->schemaManager()->createTable('xf_warext_mc_server_category', function (Create $table)
        {
            $table->addColumn('server_id', 'int')->setDefault(0);
            $table->addColumn('category_id', 'int')->setDefault(0);
            $table->addPrimaryKey(['server_id', 'category_id']);
            $table->addKey('category_id', 'warext_mc_server_category_lookup');
        });
    }

    public function installStep4(): void
    {
        $this->schemaManager()->createTable('xf_warext_mc_vote', function (Create $table)
        {
            $table->addColumn('vote_id', 'bigint')->autoIncrement();
            $table->addColumn('server_id', 'int')->setDefault(0);
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('minecraft_username', 'varchar', 16)->setDefault('');
            $table->addColumn('minecraft_uuid', 'varchar', 36)->setDefault('');
            $table->addColumn('ip_hash', 'varbinary', 32)->nullable(true);
            $table->addColumn('user_agent_hash', 'varbinary', 32)->nullable(true);
            $table->addColumn('vote_date', 'int')->setDefault(0);
            $table->addColumn('status', 'varchar', 20)->setDefault('pending');
            $table->addColumn('attempt_count', 'int')->setDefault(0);
            $table->addColumn('next_attempt_date', 'int')->setDefault(0);
            $table->addColumn('delivered_date', 'int')->setDefault(0);
            $table->addColumn('fraud_score', 'int')->setDefault(0);
            $table->addColumn('source', 'varchar', 20)->setDefault('web');
            $table->addColumn('last_error', 'varchar', 255)->setDefault('');
            $table->addKey(['server_id', 'vote_date'], 'warext_mc_vote_server_date');
            $table->addKey(['user_id', 'server_id', 'vote_date'], 'warext_mc_vote_user_server');
            $table->addKey(['minecraft_uuid', 'server_id', 'vote_date'], 'warext_mc_vote_uuid_server');
            $table->addKey(['status', 'next_attempt_date'], 'warext_mc_vote_queue');
        });
    }

    public function installStep5(): void
    {
        $this->schemaManager()->createTable('xf_warext_mc_server_team', function (Create $table)
        {
            $table->addColumn('server_id', 'int')->setDefault(0);
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('role', 'varchar', 20)->setDefault('member');
            $table->addColumn('permissions', 'mediumblob')->nullable(true);
            $table->addColumn('joined_date', 'int')->setDefault(0);
            $table->addPrimaryKey(['server_id', 'user_id']);
            $table->addKey('user_id', 'warext_mc_server_team_user');
        });
    }

    public function installStep6(): void
    {
        $this->schemaManager()->createTable('xf_warext_mc_ping_history', function (Create $table)
        {
            $table->addColumn('ping_id', 'bigint')->autoIncrement();
            $table->addColumn('server_id', 'int')->setDefault(0);
            $table->addColumn('check_date', 'int')->setDefault(0);
            $table->addColumn('is_online', 'tinyint')->setDefault(0);
            $table->addColumn('ping_ms', 'int')->setDefault(0);
            $table->addColumn('players_online', 'int')->setDefault(0);
            $table->addColumn('players_max', 'int')->setDefault(0);
            $table->addColumn('detected_version', 'varchar', 100)->setDefault('');
            $table->addKey(['server_id', 'check_date'], 'warext_mc_ping_server_date');
        });
    }

    public function installStep7(): void
    {
        $this->createVotifierTable();
    }

    public function installStep8(): void
    {
        $this->createMinecraftAccountTable();
    }

    public function upgrade1000020Step1(): void
    {
        if (!$this->schemaManager()->columnExists('xf_warext_mc_server', 'last_ping_error'))
        {
            $this->schemaManager()->alterTable('xf_warext_mc_server', function (Alter $table)
            {
                $table->addColumn('last_ping_error', 'varchar', 500)->setDefault('')->after('detected_version');
            });
        }
    }

    public function upgrade1000030Step1(): void
    {
        $this->createVotifierTable();
    }

    public function upgrade1000040Step1(): void
    {
        $this->createMinecraftAccountTable();
    }

    protected function createVotifierTable(): void
    {
        $this->schemaManager()->createTable('xf_warext_mc_votifier', function (Create $table)
        {
            $table->addColumn('server_id', 'int');
            $table->addColumn('enabled', 'tinyint')->setDefault(0);
            $table->addColumn('host', 'varchar', 255)->setDefault('');
            $table->addColumn('port', 'int')->setDefault(8192);
            $table->addColumn('protocol', 'varchar', 10)->setDefault('v2');
            $table->addColumn('service_name', 'varchar', 64)->setDefault('Warext');
            $table->addColumn('token_encrypted', 'text')->nullable(true);
            $table->addColumn('last_test_date', 'int')->setDefault(0);
            $table->addColumn('last_success_date', 'int')->setDefault(0);
            $table->addColumn('last_error', 'varchar', 500)->setDefault('');
            $table->addColumn('updated_date', 'int')->setDefault(0);
            $table->addPrimaryKey('server_id');
            $table->addKey(['enabled', 'updated_date'], 'warext_mc_votifier_enabled');
        });
    }

    protected function createMinecraftAccountTable(): void
    {
        $this->schemaManager()->createTable('xf_warext_mc_account', function (Create $table)
        {
            $table->addColumn('account_id', 'bigint')->autoIncrement();
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('minecraft_username', 'varchar', 16)->setDefault('');
            $table->addColumn('minecraft_uuid', 'varchar', 36)->setDefault('');
            $table->addColumn('verification_state', 'varchar', 20)->setDefault('unverified');
            $table->addColumn('verification_method', 'varchar', 30)->setDefault('');
            $table->addColumn('verification_code', 'varchar', 64)->setDefault('');
            $table->addColumn('is_primary', 'tinyint')->setDefault(0);
            $table->addColumn('created_date', 'int')->setDefault(0);
            $table->addColumn('updated_date', 'int')->setDefault(0);
            $table->addColumn('verified_date', 'int')->setDefault(0);
            $table->addUniqueKey(['user_id', 'minecraft_username'], 'warext_mc_account_user_name');
            $table->addKey(['user_id', 'is_primary'], 'warext_mc_account_user_primary');
            $table->addKey(['minecraft_uuid', 'verification_state'], 'warext_mc_account_uuid_state');
        });
    }

    public function uninstallStep1(): void
    {
        $sm = $this->schemaManager();
        $sm->dropTable('xf_warext_mc_account');
        $sm->dropTable('xf_warext_mc_votifier');
        $sm->dropTable('xf_warext_mc_ping_history');
        $sm->dropTable('xf_warext_mc_server_team');
        $sm->dropTable('xf_warext_mc_vote');
        $sm->dropTable('xf_warext_mc_server_category');
        $sm->dropTable('xf_warext_mc_category');
        $sm->dropTable('xf_warext_mc_server');
    }
}
