<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Achievement extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_achievement';
        $structure->shortName = 'Warext\MinecraftVote:Achievement';
        $structure->primaryKey = 'achievement_id';
        $structure->columns = [
            'achievement_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'achievement_key' => ['type' => self::STR, 'maxLength' => 50, 'required' => true],
            'title' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
            'description' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'icon' => ['type' => self::STR, 'maxLength' => 50, 'default' => 'fa-trophy'],
            'metric' => ['type' => self::STR, 'maxLength' => 30, 'required' => true],
            'threshold' => ['type' => self::UINT, 'default' => 0],
            'display_order' => ['type' => self::UINT, 'default' => 10],
            'is_active' => ['type' => self::BOOL, 'default' => true],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'updated_date' => ['type' => self::UINT, 'default' => 0]
        ];

        return $structure;
    }

    protected function _preSave(): void
    {
        $this->achievement_key = strtolower(trim($this->achievement_key));
        $this->title = trim($this->title);
        $this->description = trim($this->description);
        $this->icon = trim($this->icon) ?: 'fa-trophy';
        $this->metric = strtolower(trim($this->metric));

        if (!preg_match('/^[a-z0-9_]{2,50}$/', $this->achievement_key))
        {
            $this->error('Başarım anahtarı yalnızca küçük harf, sayı ve alt çizgi içerebilir.', 'achievement_key');
        }

        if (!in_array($this->metric, [
            'vote_total', 'uptime_bp', 'peak_players', 'age_days',
            'verified', 'season_wins', 'trend_rank_max'
        ], true))
        {
            $this->error('Geçersiz başarım metriği.', 'metric');
        }

        if (!$this->created_date)
        {
            $this->created_date = \XF::$time;
        }
        $this->updated_date = \XF::$time;
    }
}
