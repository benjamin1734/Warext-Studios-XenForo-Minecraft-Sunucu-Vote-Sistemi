<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Vote extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_vote';
        $structure->shortName = 'Warext\MinecraftVote:Vote';
        $structure->primaryKey = 'vote_id';
        $structure->columns = [
            'vote_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'server_id' => ['type' => self::UINT, 'required' => true],
            'user_id' => ['type' => self::UINT, 'default' => 0],
            'minecraft_username' => ['type' => self::STR, 'maxLength' => 16, 'required' => true],
            'minecraft_uuid' => ['type' => self::STR, 'maxLength' => 36, 'default' => ''],
            'ip_hash' => ['type' => self::BINARY, 'nullable' => true, 'default' => null],
            'user_agent_hash' => ['type' => self::BINARY, 'nullable' => true, 'default' => null],
            'vote_date' => ['type' => self::UINT, 'default' => 0],
            'status' => ['type' => self::STR, 'maxLength' => 20, 'default' => 'pending'],
            'attempt_count' => ['type' => self::UINT, 'default' => 0],
            'next_attempt_date' => ['type' => self::UINT, 'default' => 0],
            'delivered_date' => ['type' => self::UINT, 'default' => 0],
            'fraud_score' => ['type' => self::UINT, 'default' => 0],
            'source' => ['type' => self::STR, 'maxLength' => 20, 'default' => 'web'],
            'last_error' => ['type' => self::STR, 'maxLength' => 255, 'default' => '']
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
        if (!$this->vote_date)
        {
            $this->vote_date = \XF::$time;
        }

        if (!in_array($this->status, ['pending', 'delivered', 'retry', 'failed', 'rejected', 'skipped'], true))
        {
            $this->error(\XF::phrase('please_enter_valid_value'), 'status');
        }
    }
}
