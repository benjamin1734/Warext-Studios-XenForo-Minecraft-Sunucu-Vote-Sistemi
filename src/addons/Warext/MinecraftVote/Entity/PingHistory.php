<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class PingHistory extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_ping_history';
        $structure->shortName = 'Warext\MinecraftVote:PingHistory';
        $structure->primaryKey = 'ping_id';
        $structure->columns = [
            'ping_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'server_id' => ['type' => self::UINT, 'required' => true],
            'check_date' => ['type' => self::UINT, 'default' => 0],
            'is_online' => ['type' => self::BOOL, 'default' => false],
            'ping_ms' => ['type' => self::UINT, 'default' => 0],
            'players_online' => ['type' => self::UINT, 'default' => 0],
            'players_max' => ['type' => self::UINT, 'default' => 0],
            'detected_version' => ['type' => self::STR, 'maxLength' => 100, 'default' => '']
        ];
        $structure->relations = [
            'Server' => [
                'entity' => 'Warext\MinecraftVote:Server',
                'type' => self::TO_ONE,
                'conditions' => 'server_id',
                'primary' => true
            ]
        ];

        return $structure;
    }

    protected function _preSave(): void
    {
        if (!$this->check_date)
        {
            $this->check_date = \XF::$time;
        }
    }
}
