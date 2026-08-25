<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class ServerCategory extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_server_category';
        $structure->shortName = 'Warext\MinecraftVote:ServerCategory';
        $structure->primaryKey = ['server_id', 'category_id'];
        $structure->columns = [
            'server_id' => ['type' => self::UINT, 'required' => true],
            'category_id' => ['type' => self::UINT, 'required' => true]
        ];
        $structure->relations = [
            'Server' => [
                'entity' => 'Warext\MinecraftVote:Server',
                'type' => self::TO_ONE,
                'conditions' => 'server_id',
                'primary' => true
            ],
            'Category' => [
                'entity' => 'Warext\MinecraftVote:Category',
                'type' => self::TO_ONE,
                'conditions' => 'category_id',
                'primary' => true
            ]
        ];

        return $structure;
    }
}
