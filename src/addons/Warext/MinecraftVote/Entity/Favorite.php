<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Favorite extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_favorite';
        $structure->shortName = 'Warext\MinecraftVote:Favorite';
        $structure->primaryKey = ['server_id', 'user_id'];
        $structure->columns = [
            'server_id' => ['type' => self::UINT, 'required' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'created_date' => ['type' => self::UINT, 'default' => 0]
        ];
        $structure->relations = [
            'Server' => [
                'entity' => 'Warext\MinecraftVote:Server',
                'type' => self::TO_ONE,
                'conditions' => 'server_id',
                'primary' => true
            ],
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => true
            ]
        ];

        return $structure;
    }

    protected function _preSave(): void
    {
        if (!$this->created_date)
        {
            $this->created_date = \XF::$time;
        }
    }
}
