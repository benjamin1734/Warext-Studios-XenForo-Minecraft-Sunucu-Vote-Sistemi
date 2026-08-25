<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class ServerTeam extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_server_team';
        $structure->shortName = 'Warext\MinecraftVote:ServerTeam';
        $structure->primaryKey = ['server_id', 'user_id'];
        $structure->columns = [
            'server_id' => ['type' => self::UINT, 'required' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'role' => ['type' => self::STR, 'maxLength' => 20, 'default' => 'member'],
            'permissions' => ['type' => self::SERIALIZED_ARRAY, 'default' => []],
            'joined_date' => ['type' => self::UINT, 'default' => 0]
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
        if (!$this->joined_date)
        {
            $this->joined_date = \XF::$time;
        }

        if (!in_array($this->role, ['manager', 'editor', 'analyst', 'support', 'member'], true))
        {
            $this->error('Geçersiz ekip rolü.', 'role');
        }

        $allowed = ['edit_content', 'publish_updates', 'view_stats', 'manage_votifier', 'manage_reviews'];
        $permissions = is_array($this->permissions) ? $this->permissions : [];
        $clean = [];
        foreach ($allowed as $permission)
        {
            $clean[$permission] = !empty($permissions[$permission]);
        }
        $this->permissions = $clean;
    }
}
