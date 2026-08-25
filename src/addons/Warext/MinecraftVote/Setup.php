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
            $table->addColumn('verification_token_date', 'int')->setDefault(0);
            $table->addColumn('verified_date', 'int')->setDefault(0);
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
            $table->addColumn('unique_voters_month', 'int')->setDefault(0);
            $table->addColumn('votes_24h', 'int')->setDefault(0);
            $table->addColumn('votes_72h', 'int')->setDefault(0);
            $table->addColumn('popular_score_bp', 'int')->setDefault(0);
            $table->addColumn('trend_score_bp', 'int')->setDefault(0);
            $table->addColumn('rank_popular', 'int')->setDefault(0);
            $table->addColumn('rank_trending', 'int')->setDefault(0);
            $table->addColumn('ranking_updated_date', 'int')->setDefault(0);
            $table->addColumn('view_count', 'int')->setDefault(0);
            $table->addColumn('rating_count', 'int')->setDefault(0);
            $table->addColumn('rating_sum', 'int')->setDefault(0);
            $table->addColumn('created_date', 'int')->setDefault(0);
            $table->addColumn('last_update_date', 'int')->setDefault(0);
            $table->addColumn('last_ping_date', 'int')->setDefault(0);
            $table->addUniqueKey('slug', 'warext_mc_server_slug');
            $table->addKey(['state', 'vote_count_month'], 'warext_mc_server_rank');
            $table->addKey(['state', 'popular_score_bp'], 'warext_mc_server_popular');
            $table->addKey(['state', 'trend_score_bp'], 'warext_mc_server_trending');
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
            $table->addKey(['server_id', 'minecraft_username', 'vote_date'], 'warext_mc_vote_server_name');
            $table->addKey(['server_id', 'ip_hash', 'vote_date'], 'warext_mc_vote_server_ip');
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

    public function installStep9(): void
    {
        $this->createSeasonTables();
    }

    public function installStep10(): void
    {
        $this->createReviewTable();
    }

    public function installStep11(): void
    {
        $this->createFavoriteTable();
    }

    public function installStep12(): void
    {
        $this->createServerUpdateTable();
    }

    public function installStep13(): void
    {
        $this->createAchievementTables();
    }

    public function installStep14(): void
    {
        $this->createSponsorTable();
    }

    public function installStep15(): void
    {
        $this->createAuditLogTable();
    }

    public function installStep16(): void
    {
        $this->createReportTable();
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

    public function upgrade1000050Step1(): void
    {
        $sm = $this->schemaManager();
        if (!$sm->columnExists('xf_warext_mc_server', 'verification_token_date'))
        {
            $sm->alterTable('xf_warext_mc_server', function (Alter $table)
            {
                $table->addColumn('verification_token_date', 'int')->setDefault(0)->after('verification_token');
            });
        }

        if (!$sm->columnExists('xf_warext_mc_server', 'verified_date'))
        {
            $sm->alterTable('xf_warext_mc_server', function (Alter $table)
            {
                $table->addColumn('verified_date', 'int')->setDefault(0)->after('verification_token_date');
            });
        }
    }

    public function upgrade1000060Step1(): void
    {
        $this->addRankingColumns();
    }

    public function upgrade1000060Step2(): void
    {
        $this->createSeasonTables();
    }

    public function upgrade1000060Step3(): void
    {
        $this->addSeasonSnapshotColumns();
    }

    public function upgrade1000080Step1(): void
    {
        $this->createReviewTable();
    }

    public function upgrade1000080Step2(): void
    {
        $this->createFavoriteTable();
        $this->ensureFavoriteTrackingColumns();
    }

    public function upgrade1000080Step3(): void
    {
        $this->createServerUpdateTable();
    }

    public function upgrade1000100Step1(): void
    {
        $this->createAchievementTables();
    }

    public function upgrade1000100Step2(): void
    {
        $this->createSponsorTable();
    }

    public function upgrade1000100Step3(): void
    {
        $this->createAuditLogTable();
    }

    public function upgrade1000130Step1(): void
    {
        $this->addVoteAbuseIndexes();
    }

    public function upgrade1000130Step2(): void
    {
        $this->createReportTable();
    }

    protected function addRankingColumns(): void
    {
        $sm = $this->schemaManager();
        foreach ([
            'unique_voters_month',
            'votes_24h',
            'votes_72h',
            'popular_score_bp',
            'trend_score_bp',
            'rank_popular',
            'rank_trending',
            'ranking_updated_date'
        ] as $column)
        {
            if (!$sm->columnExists('xf_warext_mc_server', $column))
            {
                $sm->alterTable('xf_warext_mc_server', function (Alter $table) use ($column)
                {
                    $table->addColumn($column, 'int')->setDefault(0);
                });
            }
        }
    }

    protected function addVoteAbuseIndexes(): void
    {
        $sm = $this->schemaManager();
        if (!$sm->tableExists('xf_warext_mc_vote'))
        {
            return;
        }

        $sm->alterTable('xf_warext_mc_vote', function (Alter $table)
        {
            $table->addKey(['server_id', 'minecraft_username', 'vote_date'], 'warext_mc_vote_server_name');
            $table->addKey(['server_id', 'ip_hash', 'vote_date'], 'warext_mc_vote_server_ip');
        });
    }

    protected function addSeasonSnapshotColumns(): void
    {
        $sm = $this->schemaManager();

        if ($sm->tableExists('xf_warext_mc_season') && !$sm->columnExists('xf_warext_mc_season', 'winner_title'))
        {
            $sm->alterTable('xf_warext_mc_season', function (Alter $table)
            {
                $table->addColumn('winner_title', 'varchar', 100)->setDefault('')->after('winner_server_id');
            });
        }

        if ($sm->tableExists('xf_warext_mc_season_rank') && !$sm->columnExists('xf_warext_mc_season_rank', 'server_title'))
        {
            $sm->alterTable('xf_warext_mc_season_rank', function (Alter $table)
            {
                $table->addColumn('server_title', 'varchar', 100)->setDefault('')->after('server_id');
            });
        }
    }

    protected function createVotifierTable(): void
    {
        $sm = $this->schemaManager();
        if ($sm->tableExists('xf_warext_mc_votifier'))
        {
            return;
        }

        $sm->createTable('xf_warext_mc_votifier', function (Create $table)
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
        $sm = $this->schemaManager();
        if ($sm->tableExists('xf_warext_mc_account'))
        {
            return;
        }

        $sm->createTable('xf_warext_mc_account', function (Create $table)
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

    protected function createSeasonTables(): void
    {
        $sm = $this->schemaManager();

        if (!$sm->tableExists('xf_warext_mc_season'))
        {
            $sm->createTable('xf_warext_mc_season', function (Create $table)
            {
                $table->addColumn('season_id', 'int')->autoIncrement();
                $table->addColumn('season_key', 'char', 7)->setDefault('');
                $table->addColumn('start_date', 'int')->setDefault(0);
                $table->addColumn('end_date', 'int')->setDefault(0);
                $table->addColumn('status', 'varchar', 10)->setDefault('open');
                $table->addColumn('winner_server_id', 'int')->setDefault(0);
                $table->addColumn('winner_title', 'varchar', 100)->setDefault('');
                $table->addColumn('total_votes', 'int')->setDefault(0);
                $table->addColumn('unique_voters', 'int')->setDefault(0);
                $table->addColumn('server_count', 'int')->setDefault(0);
                $table->addColumn('created_date', 'int')->setDefault(0);
                $table->addColumn('finalized_date', 'int')->setDefault(0);
                $table->addUniqueKey('season_key', 'warext_mc_season_key');
                $table->addKey(['status', 'end_date'], 'warext_mc_season_status');
            });
        }

        if (!$sm->tableExists('xf_warext_mc_season_rank'))
        {
            $sm->createTable('xf_warext_mc_season_rank', function (Create $table)
            {
                $table->addColumn('season_id', 'int')->setDefault(0);
                $table->addColumn('server_id', 'int')->setDefault(0);
                $table->addColumn('server_title', 'varchar', 100)->setDefault('');
                $table->addColumn('rank', 'int')->setDefault(0);
                $table->addColumn('vote_count', 'int')->setDefault(0);
                $table->addColumn('unique_voters', 'int')->setDefault(0);
                $table->addColumn('uptime_bp', 'int')->setDefault(0);
                $table->addColumn('peak_players', 'int')->setDefault(0);
                $table->addColumn('season_score_bp', 'int')->setDefault(0);
                $table->addColumn('snapshot_date', 'int')->setDefault(0);
                $table->addPrimaryKey(['season_id', 'server_id']);
                $table->addKey(['season_id', 'rank'], 'warext_mc_season_rank_order');
                $table->addKey('server_id', 'warext_mc_season_rank_server');
            });
        }
    }

    protected function createReviewTable(): void
    {
        $sm = $this->schemaManager();
        if ($sm->tableExists('xf_warext_mc_review'))
        {
            return;
        }

        $sm->createTable('xf_warext_mc_review', function (Create $table)
        {
            $table->addColumn('review_id', 'bigint')->autoIncrement();
            $table->addColumn('server_id', 'int')->setDefault(0);
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('rating', 'tinyint')->setDefault(0);
            $table->addColumn('gameplay_rating', 'tinyint')->setDefault(0);
            $table->addColumn('staff_rating', 'tinyint')->setDefault(0);
            $table->addColumn('performance_rating', 'tinyint')->setDefault(0);
            $table->addColumn('community_rating', 'tinyint')->setDefault(0);
            $table->addColumn('originality_rating', 'tinyint')->setDefault(0);
            $table->addColumn('message', 'text')->nullable(true);
            $table->addColumn('is_verified_player', 'tinyint')->setDefault(0);
            $table->addColumn('state', 'varchar', 20)->setDefault('visible');
            $table->addColumn('created_date', 'int')->setDefault(0);
            $table->addColumn('updated_date', 'int')->setDefault(0);
            $table->addUniqueKey(['server_id', 'user_id'], 'warext_mc_review_server_user');
            $table->addKey(['server_id', 'state', 'updated_date'], 'warext_mc_review_server_state');
            $table->addKey(['user_id', 'updated_date'], 'warext_mc_review_user_date');
        });
    }

    protected function createFavoriteTable(): void
    {
        $sm = $this->schemaManager();
        if ($sm->tableExists('xf_warext_mc_favorite'))
        {
            return;
        }

        $sm->createTable('xf_warext_mc_favorite', function (Create $table)
        {
            $table->addColumn('server_id', 'int')->setDefault(0);
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('notify_updates', 'tinyint')->setDefault(1);
            $table->addColumn('last_seen_update_id', 'bigint')->setDefault(0);
            $table->addColumn('created_date', 'int')->setDefault(0);
            $table->addPrimaryKey(['server_id', 'user_id']);
            $table->addKey(['user_id', 'created_date'], 'warext_mc_favorite_user_date');
            $table->addKey(['notify_updates', 'user_id'], 'warext_mc_favorite_notify');
        });
    }

    protected function ensureFavoriteTrackingColumns(): void
    {
        $sm = $this->schemaManager();
        if (!$sm->tableExists('xf_warext_mc_favorite'))
        {
            return;
        }

        if (!$sm->columnExists('xf_warext_mc_favorite', 'notify_updates'))
        {
            $sm->alterTable('xf_warext_mc_favorite', function (Alter $table)
            {
                $table->addColumn('notify_updates', 'tinyint')->setDefault(1);
            });
        }

        if (!$sm->columnExists('xf_warext_mc_favorite', 'last_seen_update_id'))
        {
            $sm->alterTable('xf_warext_mc_favorite', function (Alter $table)
            {
                $table->addColumn('last_seen_update_id', 'bigint')->setDefault(0);
            });
        }
    }

    protected function createServerUpdateTable(): void
    {
        $sm = $this->schemaManager();
        if ($sm->tableExists('xf_warext_mc_server_update'))
        {
            return;
        }

        $sm->createTable('xf_warext_mc_server_update', function (Create $table)
        {
            $table->addColumn('update_id', 'bigint')->autoIncrement();
            $table->addColumn('server_id', 'int')->setDefault(0);
            $table->addColumn('user_id', 'int')->setDefault(0);
            $table->addColumn('title', 'varchar', 100)->setDefault('');
            $table->addColumn('version_label', 'varchar', 50)->setDefault('');
            $table->addColumn('message', 'mediumtext');
            $table->addColumn('state', 'varchar', 20)->setDefault('visible');
            $table->addColumn('created_date', 'int')->setDefault(0);
            $table->addColumn('updated_date', 'int')->setDefault(0);
            $table->addKey(['server_id', 'state', 'created_date'], 'warext_mc_update_server_state');
            $table->addKey(['user_id', 'created_date'], 'warext_mc_update_user_date');
        });
    }

    protected function createAchievementTables(): void
    {
        $sm = $this->schemaManager();
        $created = false;

        if (!$sm->tableExists('xf_warext_mc_achievement'))
        {
            $sm->createTable('xf_warext_mc_achievement', function (Create $table)
            {
                $table->addColumn('achievement_id', 'int')->autoIncrement();
                $table->addColumn('achievement_key', 'varchar', 50)->setDefault('');
                $table->addColumn('title', 'varchar', 100)->setDefault('');
                $table->addColumn('description', 'varchar', 255)->setDefault('');
                $table->addColumn('icon', 'varchar', 50)->setDefault('fa-trophy');
                $table->addColumn('metric', 'varchar', 30)->setDefault('vote_total');
                $table->addColumn('threshold', 'int')->setDefault(0);
                $table->addColumn('display_order', 'int')->setDefault(10);
                $table->addColumn('is_active', 'tinyint')->setDefault(1);
                $table->addColumn('created_date', 'int')->setDefault(0);
                $table->addColumn('updated_date', 'int')->setDefault(0);
                $table->addUniqueKey('achievement_key', 'warext_mc_achievement_key');
                $table->addKey(['is_active', 'display_order'], 'warext_mc_achievement_order');
            });
            $created = true;
        }

        if (!$sm->tableExists('xf_warext_mc_server_achievement'))
        {
            $sm->createTable('xf_warext_mc_server_achievement', function (Create $table)
            {
                $table->addColumn('server_id', 'int')->setDefault(0);
                $table->addColumn('achievement_id', 'int')->setDefault(0);
                $table->addColumn('awarded_date', 'int')->setDefault(0);
                $table->addColumn('metric_value', 'int')->setDefault(0);
                $table->addColumn('source', 'varchar', 30)->setDefault('automatic');
                $table->addPrimaryKey(['server_id', 'achievement_id']);
                $table->addKey(['achievement_id', 'awarded_date'], 'warext_mc_server_achievement_award');
                $table->addKey(['server_id', 'awarded_date'], 'warext_mc_server_achievement_server');
            });
        }

        if ($created)
        {
            $now = \XF::$time;
            $this->db()->insertBulk('xf_warext_mc_achievement', [
                ['achievement_key' => 'votes_100', 'title' => '100 Oy', 'description' => 'Toplam 100 topluluk oyuna ulaştı.', 'icon' => 'fa-check-to-slot', 'metric' => 'vote_total', 'threshold' => 100, 'display_order' => 10, 'is_active' => 1, 'created_date' => $now, 'updated_date' => $now],
                ['achievement_key' => 'votes_1000', 'title' => '1.000 Oy', 'description' => 'Toplam 1.000 topluluk oyuna ulaştı.', 'icon' => 'fa-box-ballot', 'metric' => 'vote_total', 'threshold' => 1000, 'display_order' => 20, 'is_active' => 1, 'created_date' => $now, 'updated_date' => $now],
                ['achievement_key' => 'votes_10000', 'title' => '10.000 Oy', 'description' => 'Toplam 10.000 topluluk oyuna ulaştı.', 'icon' => 'fa-award', 'metric' => 'vote_total', 'threshold' => 10000, 'display_order' => 30, 'is_active' => 1, 'created_date' => $now, 'updated_date' => $now],
                ['achievement_key' => 'uptime_99', 'title' => '%99 Uptime', 'description' => 'İzlenen çalışma süresinde %99 uptime seviyesine ulaştı.', 'icon' => 'fa-signal', 'metric' => 'uptime_bp', 'threshold' => 9900, 'display_order' => 40, 'is_active' => 1, 'created_date' => $now, 'updated_date' => $now],
                ['achievement_key' => 'peak_100', 'title' => '100 Eş Zamanlı Oyuncu', 'description' => 'En az 100 eş zamanlı oyuncu gördü.', 'icon' => 'fa-users', 'metric' => 'peak_players', 'threshold' => 100, 'display_order' => 50, 'is_active' => 1, 'created_date' => $now, 'updated_date' => $now],
                ['achievement_key' => 'peak_500', 'title' => '500 Eş Zamanlı Oyuncu', 'description' => 'En az 500 eş zamanlı oyuncu gördü.', 'icon' => 'fa-users', 'metric' => 'peak_players', 'threshold' => 500, 'display_order' => 60, 'is_active' => 1, 'created_date' => $now, 'updated_date' => $now],
                ['achievement_key' => 'one_year', 'title' => '1 Yıllık Sunucu', 'description' => 'Platformda 365 günü tamamladı.', 'icon' => 'fa-cake-candles', 'metric' => 'age_days', 'threshold' => 365, 'display_order' => 70, 'is_active' => 1, 'created_date' => $now, 'updated_date' => $now],
                ['achievement_key' => 'verified', 'title' => 'Doğrulanmış Sunucu', 'description' => 'Sunucu sahipliği başarıyla doğrulandı.', 'icon' => 'fa-badge-check', 'metric' => 'verified', 'threshold' => 1, 'display_order' => 80, 'is_active' => 1, 'created_date' => $now, 'updated_date' => $now],
                ['achievement_key' => 'month_champion', 'title' => 'Ayın Sunucusu', 'description' => 'Bir aylık oy sezonunu birinci tamamladı.', 'icon' => 'fa-crown', 'metric' => 'season_wins', 'threshold' => 1, 'display_order' => 90, 'is_active' => 1, 'created_date' => $now, 'updated_date' => $now],
                ['achievement_key' => 'rising_star', 'title' => 'Yükselen Yıldız', 'description' => 'Trend sıralamasında ilk 3 içine girdi.', 'icon' => 'fa-arrow-trend-up', 'metric' => 'trend_rank_max', 'threshold' => 3, 'display_order' => 100, 'is_active' => 1, 'created_date' => $now, 'updated_date' => $now]
            ]);
        }
    }

    protected function createSponsorTable(): void
    {
        $sm = $this->schemaManager();
        if ($sm->tableExists('xf_warext_mc_sponsor'))
        {
            return;
        }

        $sm->createTable('xf_warext_mc_sponsor', function (Create $table)
        {
            $table->addColumn('sponsor_id', 'int')->autoIncrement();
            $table->addColumn('server_id', 'int')->setDefault(0);
            $table->addColumn('label', 'varchar', 50)->setDefault('Sponsorlu');
            $table->addColumn('placement', 'varchar', 30)->setDefault('list_top');
            $table->addColumn('start_date', 'int')->setDefault(0);
            $table->addColumn('end_date', 'int')->setDefault(0);
            $table->addColumn('state', 'varchar', 20)->setDefault('active');
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('created_by', 'int')->setDefault(0);
            $table->addColumn('created_date', 'int')->setDefault(0);
            $table->addColumn('updated_date', 'int')->setDefault(0);
            $table->addKey(['state', 'placement', 'display_order'], 'warext_mc_sponsor_active');
            $table->addKey(['server_id', 'start_date', 'end_date'], 'warext_mc_sponsor_server_date');
        });
    }

    protected function createAuditLogTable(): void
    {
        $sm = $this->schemaManager();
        if ($sm->tableExists('xf_warext_mc_audit_log'))
        {
            return;
        }

        $sm->createTable('xf_warext_mc_audit_log', function (Create $table)
        {
            $table->addColumn('log_id', 'bigint')->autoIncrement();
            $table->addColumn('server_id', 'int')->setDefault(0);
            $table->addColumn('actor_user_id', 'int')->setDefault(0);
            $table->addColumn('target_user_id', 'int')->setDefault(0);
            $table->addColumn('action', 'varchar', 50)->setDefault('');
            $table->addColumn('details', 'mediumblob')->nullable(true);
            $table->addColumn('log_date', 'int')->setDefault(0);
            $table->addKey(['server_id', 'log_date'], 'warext_mc_audit_server_date');
            $table->addKey(['actor_user_id', 'log_date'], 'warext_mc_audit_actor_date');
            $table->addKey(['action', 'log_date'], 'warext_mc_audit_action_date');
        });
    }

    protected function createReportTable(): void
    {
        $sm = $this->schemaManager();
        if ($sm->tableExists('xf_warext_mc_report'))
        {
            return;
        }

        $sm->createTable('xf_warext_mc_report', function (Create $table)
        {
            $table->addColumn('report_id', 'bigint')->autoIncrement();
            $table->addColumn('server_id', 'int')->setDefault(0);
            $table->addColumn('reporter_user_id', 'int')->setDefault(0);
            $table->addColumn('reason', 'varchar', 30)->setDefault('other');
            $table->addColumn('message', 'text')->nullable(true);
            $table->addColumn('state', 'varchar', 20)->setDefault('open');
            $table->addColumn('moderator_user_id', 'int')->setDefault(0);
            $table->addColumn('resolution', 'varchar', 255)->setDefault('');
            $table->addColumn('created_date', 'int')->setDefault(0);
            $table->addColumn('updated_date', 'int')->setDefault(0);
            $table->addColumn('resolved_date', 'int')->setDefault(0);
            $table->addKey(['state', 'created_date'], 'warext_mc_report_state_date');
            $table->addKey(['server_id', 'state', 'created_date'], 'warext_mc_report_server_state');
            $table->addKey(['server_id', 'reporter_user_id', 'created_date'], 'warext_mc_report_duplicate');
        });
    }

    public function uninstallStep1(): void
    {
        $sm = $this->schemaManager();
        $sm->dropTable('xf_warext_mc_report');
        $sm->dropTable('xf_warext_mc_audit_log');
        $sm->dropTable('xf_warext_mc_sponsor');
        $sm->dropTable('xf_warext_mc_server_achievement');
        $sm->dropTable('xf_warext_mc_achievement');
        $sm->dropTable('xf_warext_mc_server_update');
        $sm->dropTable('xf_warext_mc_favorite');
        $sm->dropTable('xf_warext_mc_review');
        $sm->dropTable('xf_warext_mc_season_rank');
        $sm->dropTable('xf_warext_mc_season');
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
