<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class SeasonRank extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_season_rank';
        $structure->shortName = 'Warext\MinecraftVote:SeasonRank';
        $structure->primaryKey = ['season_id', 'server_id'];
        $structure->columns = [
            'season_id' => ['type' => self::UINT, 'required' => true],
            'server_id' => ['type' => self::UINT, 'required' => true],
            'rank' => ['type' => self::UINT, 'default' => 0],
            'vote_count' => ['type' => self::UINT, 'default' => 0],
            'unique_voters' => ['type' => self::UINT, 'default' => 0],
            'uptime_bp' => ['type' => self::UINT, 'default' => 0],
            'peak_players' => ['type' => self::UINT, 'default' => 0],
            'season_score_bp' => ['type' => self::UINT, 'default' => 0],
            'snapshot_date' => ['type' => self::UINT, 'default' => 0]
        ];
        $structure->getters = [
            'uptime_percent' => true,
            'season_score' => true
        ];
        $structure->relations = [
            'Season' => [
                'entity' => 'Warext\MinecraftVote:Season',
                'type' => self::TO_ONE,
                'conditions' => 'season_id',
                'primary' => true
            ],
            'Server' => [
                'entity' => 'Warext\MinecraftVote:Server',
                'type' => self::TO_ONE,
                'conditions' => 'server_id',
                'primary' => true
            ]
        ];

        return $structure;
    }

    public function getUptimePercent(): float
    {
        return round(min(100, max(0, $this->uptime_bp / 100)), 2);
    }

    public function getSeasonScore(): float
    {
        return round(min(100, max(0, $this->season_score_bp / 100)), 2);
    }
}
