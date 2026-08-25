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
            'is_online' => ['type' => self::BOOL, 'default' => false],
            'ping_ms' => ['type' => self::UINT, 'default' => 0],
            'players_online' => ['type' => self::UINT, 'default' => 0],
            'players_max' => ['type' => self::UINT, 'default' => 0],
            'motd' => ['type' => self::STR, 'nullable' => true, 'default' => null],
            'detected_version' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'uptime_bp' => ['type' => self::UINT, 'default' => 0],
            'vote_count_total' => ['type' => self::UINT, 'default' => 0],
            'vote_count_month' => ['type' => self::UINT, 'default' => 0],
            'vote_count_today' => ['type' => self::UINT, 'default' => 0],
            'view_count' => ['type' => self::UINT, 'default' => 0],
            'rating_count' => ['type' => self::UINT, 'default' => 0],
            'rating_sum' => ['type' => self::UINT, 'default' => 0],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'last_update_date' => ['type' => self::UINT, 'default' => 0],
            'last_ping_date' => ['type' => self::UINT, 'default' => 0]
        ];
        $structure->getters = [
            'uptime_percent' => true,
            'rating_average' => true
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

    protected function _preSave(): void
    {
        if (!$this->created_date)
        {
            $this->created_date = \XF::$time;
        }

        $this->last_update_date = \XF::$time;
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
    }
}
