<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class AuditLog extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_audit_log';
        $structure->shortName = 'Warext\MinecraftVote:AuditLog';
        $structure->primaryKey = 'log_id';
        $structure->columns = [
            'log_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'server_id' => ['type' => self::UINT, 'default' => 0],
            'actor_user_id' => ['type' => self::UINT, 'default' => 0],
            'target_user_id' => ['type' => self::UINT, 'default' => 0],
            'action' => ['type' => self::STR, 'maxLength' => 50, 'required' => true],
            'details' => ['type' => self::SERIALIZED_ARRAY, 'default' => []],
            'log_date' => ['type' => self::UINT, 'default' => 0]
        ];
        $structure->relations = [
            'Server' => [
                'entity' => 'Warext\MinecraftVote:Server',
                'type' => self::TO_ONE,
                'conditions' => 'server_id',
                'primary' => true
            ],
            'Actor' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => [['user_id', '=', '$actor_user_id']],
                'primary' => true
            ],
            'Target' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => [['user_id', '=', '$target_user_id']],
                'primary' => true
            ]
        ];

        return $structure;
    }

    protected function _preSave(): void
    {
        $this->action = strtolower(trim($this->action));
        if (!preg_match('/^[a-z0-9_]{2,50}$/', $this->action))
        {
            $this->error('Geçersiz audit işlem anahtarı.', 'action');
        }

        if (!$this->log_date)
        {
            $this->log_date = \XF::$time;
        }
    }
}
