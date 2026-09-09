<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Category extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_category';
        $structure->shortName = 'Warext\MinecraftVote:Category';
        $structure->primaryKey = 'category_id';
        $structure->columns = [
            'category_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'title' => ['type' => self::STR, 'maxLength' => 50, 'required' => true],
            'slug' => ['type' => self::STR, 'maxLength' => 50, 'required' => true],
            'description' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'display_order' => ['type' => self::UINT, 'default' => 10],
            'is_active' => ['type' => self::BOOL, 'default' => true],
            'forum_node_id' => ['type' => self::UINT, 'default' => 0],
            'thread_integration_enabled' => ['type' => self::BOOL, 'default' => false],
            'thread_default_server_type' => ['type' => self::STR, 'maxLength' => 20, 'default' => 'java']
        ];
        $structure->relations = [
            'Forum' => [
                'entity' => 'XF:Forum',
                'type' => self::TO_ONE,
                'conditions' => [['node_id', '=', '$forum_node_id']],
                'primary' => true
            ]
        ];

        return $structure;
    }
}
