<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Server extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_server';
        $structure->shortName = 'Warext\MinecraftVote:Server';
        $structure->primaryKey = 'server_id';
        $structure->columns = [
            'server_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'owner_user_id' => ['type' => self::UINT, 'default' => 0],
            'title' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
            'slug' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
            'description' => ['type' => self::STR, 'nullable' => true, 'default' => null],
            'server_type' => ['type' => self::STR, 'maxLength' => 20, 'default' => 'java'],
            'host' => ['type' => self::STR, 'maxLength' => 255, 'required' => true],
            'port' => ['type' => self::UINT, 'default' => 25565],
            'bedrock_host' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'bedrock_port' => ['type' => self::UINT, 'default' => 19132],
            'website_url' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'discord_url' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'store_url' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'game_modes' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'version_min' => ['type' => self::STR, 'maxLength' => 30, 'default' => ''],
            'version_max' => ['type' => self::STR, 'maxLength' => 30, 'default' => ''],
            'country_code' => ['type' => self::STR, 'maxLength' => 2, 'default' => ''],
            'is_premium' => ['type' => self::BOOL, 'default' => false],
            'allow_cracked' => ['type' => self::BOOL, 'default' => false],
            'state' => ['type' => self::STR, 'maxLength' => 20, 'default' => 'pending'],
            'is_verified' => ['type' => self::BOOL, 'default' => false],
            'verification_method' => ['type' => self::STR, 'maxLength' => 20, 'default' => ''],
            'verification_token' => ['type' => self::STR, 'maxLength' => 64, 'default' => ''],
            'verification_token_date' => ['type' => self::UINT, 'default' => 0],
            'verified_date' => ['type' => self::UINT, 'default' => 0],
            'is_online' => ['type' => self::BOOL, 'default' => false],
            'ping_ms' => ['type' => self::UINT, 'default' => 0],
            'players_online' => ['type' => self::UINT, 'default' => 0],
            'players_max' => ['type' => self::UINT, 'default' => 0],
            'motd' => ['type' => self::STR, 'nullable' => true, 'default' => null],
            'detected_version' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'last_ping_error' => ['type' => self::STR, 'maxLength' => 500, 'default' => ''],
            'uptime_bp' => ['type' => self::UINT, 'default' => 0],
            'vote_count_total' => ['type' => self::UINT, 'default' => 0],
            'vote_count_month' => ['type' => self::UINT, 'default' => 0],
            'vote_count_today' => ['type' => self::UINT, 'default' => 0],
            'unique_voters_month' => ['type' => self::UINT, 'default' => 0],
            'votes_24h' => ['type' => self::UINT, 'default' => 0],
            'votes_72h' => ['type' => self::UINT, 'default' => 0],
            'popular_score_bp' => ['type' => self::UINT, 'default' => 0],
            'trend_score_bp' => ['type' => self::UINT, 'default' => 0],
            'rank_popular' => ['type' => self::UINT, 'default' => 0],
            'rank_trending' => ['type' => self::UINT, 'default' => 0],
            'ranking_updated_date' => ['type' => self::UINT, 'default' => 0],
            'view_count' => ['type' => self::UINT, 'default' => 0],
            'rating_count' => ['type' => self::UINT, 'default' => 0],
            'rating_sum' => ['type' => self::UINT, 'default' => 0],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'last_update_date' => ['type' => self::UINT, 'default' => 0],
            'last_ping_date' => ['type' => self::UINT, 'default' => 0]
        ];
        $structure->getters = [
            'uptime_percent' => true,
            'rating_average' => true,
            'popular_score' => true,
            'trend_score' => true,
            'is_owner' => true,
            'can_edit' => true,
            'can_publish_updates' => true,
            'can_view_stats' => true,
            'can_manage_votifier' => true,
            'can_manage_reviews' => true
        ];
        $structure->relations = [
            'Owner' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => [['user_id', '=', '$owner_user_id']],
                'primary' => true
            ]
        ];

        return $structure;
    }

    public function getUptimePercent(): float
    {
        return min(100, max(0, $this->uptime_bp / 100));
    }

    public function getRatingAverage(): float
    {
        if (!$this->rating_count)
        {
            return 0.0;
        }

        return round($this->rating_sum / $this->rating_count, 2);
    }

    public function getPopularScore(): float
    {
        return min(100, max(0, $this->popular_score_bp / 100));
    }

    public function getTrendScore(): float
    {
        return min(100, max(0, $this->trend_score_bp / 100));
    }

    public function getIsOwner(): bool
    {
        $visitor = \XF::visitor();
        return $visitor->user_id > 0 && (int)$this->owner_user_id === (int)$visitor->user_id;
    }

    public function getCanEdit(): bool
    {
        return $this->hasTeamPermission('edit_content');
    }

    public function getCanPublishUpdates(): bool
    {
        return $this->hasTeamPermission('publish_updates');
    }

    public function getCanViewStats(): bool
    {
        return $this->hasTeamPermission('view_stats');
    }

    public function getCanManageVotifier(): bool
    {
        return $this->hasTeamPermission('manage_votifier');
    }

    public function getCanManageReviews(): bool
    {
        return $this->hasTeamPermission('manage_reviews');
    }

    protected function hasTeamPermission(string $permission): bool
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            return false;
        }

        return $this->repository('Warext\MinecraftVote:ServerTeam')
            ->hasPermission($this, (int)$visitor->user_id, $permission);
    }

    protected function _preSave(): void
    {
        $isNew = !$this->server_id;

        if (!$isNew && $this->hasEndpointChanges())
        {
            $this->is_verified = false;
            $this->verification_method = '';
            $this->verification_token = '';
            $this->verification_token_date = 0;
            $this->verified_date = 0;
        }

        if (!$this->created_date)
        {
            $this->created_date = \XF::$time;
        }

        if ($isNew || !$this->last_update_date || $this->hasContentChanges())
        {
            $this->last_update_date = \XF::$time;
        }

        $this->slug = trim(strtolower($this->slug));
        $this->country_code = strtoupper(trim($this->country_code));

        if (!in_array($this->server_type, ['java', 'bedrock', 'crossplay'], true))
        {
            $this->error(\XF::phrase('please_enter_valid_value'), 'server_type');
        }

        if (!in_array($this->state, ['pending', 'active', 'rejected', 'suspended'], true))
        {
            $this->error(\XF::phrase('please_enter_valid_value'), 'state');
        }

        if ($this->port < 1 || $this->port > 65535)
        {
            $this->error(\XF::phrase('please_enter_valid_value'), 'port');
        }

        if ($this->bedrock_port < 1 || $this->bedrock_port > 65535)
        {
            $this->error(\XF::phrase('please_enter_valid_value'), 'bedrock_port');
        }

        if ($this->verification_method !== '' && !in_array($this->verification_method, ['motd', 'dns_txt'], true))
        {
            $this->error('Geçersiz sunucu doğrulama yöntemi.', 'verification_method');
        }
    }

    protected function hasEndpointChanges(): bool
    {
        foreach (['server_type', 'host', 'port', 'bedrock_host', 'bedrock_port'] as $field)
        {
            if ($this->isChanged($field))
            {
                return true;
            }
        }

        return false;
    }

    protected function hasContentChanges(): bool
    {
        foreach ([
            'owner_user_id', 'title', 'slug', 'description', 'server_type', 'host', 'port',
            'bedrock_host', 'bedrock_port', 'website_url', 'discord_url', 'store_url',
            'game_modes', 'version_min', 'version_max', 'country_code', 'is_premium',
            'allow_cracked', 'state', 'is_verified', 'verification_method', 'verification_token',
            'verification_token_date', 'verified_date'
        ] as $field)
        {
            if ($this->isChanged($field))
            {
                return true;
            }
        }

        return false;
    }
}
