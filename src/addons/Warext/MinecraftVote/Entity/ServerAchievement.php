<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class ServerAchievement extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_server_achievement';
        $structure->shortName = 'Warext\MinecraftVote:ServerAchievement';
        $structure->primaryKey = ['server_id', 'achievement_id'];
        $structure->columns = [
            'server_id' => ['type' => self::UINT, 'required' => true],
            'achievement_id' => ['type' => self::UINT, 'required' => true],
            'awarded_date' => ['type' => self::UINT, 'default' => 0],
            'metric_value' => ['type' => self::UINT, 'default' => 0],
            'source' => ['type' => self::STR, 'maxLength' => 30, 'default' => 'automatic']
        ];
        $structure->relations = [
            'Server' => [
                'entity' => 'Warext\MinecraftVote:Server',
                'type' => self::TO_ONE,
                'conditions' => 'server_id',
                'primary' => true
            ],
            'Achievement' => [
                'entity' => 'Warext\MinecraftVote:Achievement',
                'type' => self::TO_ONE,
                'conditions' => 'achievement_id',
                'primary' => true
            ]
        ];

        return $structure;
    }

    protected function _preSave(): void
    {
        if (!$this->awarded_date)
        {
            $this->awarded_date = \XF::$time;
        }
    }
}
