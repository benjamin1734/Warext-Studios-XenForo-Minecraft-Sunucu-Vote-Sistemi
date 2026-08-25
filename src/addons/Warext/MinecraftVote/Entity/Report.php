<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Report extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_report';
        $structure->shortName = 'Warext\MinecraftVote:Report';
        $structure->primaryKey = 'report_id';
        $structure->columns = [
            'report_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'server_id' => ['type' => self::UINT, 'required' => true],
            'reporter_user_id' => ['type' => self::UINT, 'required' => true],
            'reason' => ['type' => self::STR, 'maxLength' => 30, 'default' => 'other'],
            'message' => ['type' => self::STR, 'nullable' => true, 'default' => null],
            'state' => ['type' => self::STR, 'maxLength' => 20, 'default' => 'open'],
            'moderator_user_id' => ['type' => self::UINT, 'default' => 0],
            'resolution' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'updated_date' => ['type' => self::UINT, 'default' => 0],
            'resolved_date' => ['type' => self::UINT, 'default' => 0]
        ];
        $structure->relations = [
            'Server' => [
                'entity' => 'Warext\MinecraftVote:Server',
                'type' => self::TO_ONE,
                'conditions' => 'server_id',
                'primary' => true
            ],
            'Reporter' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => [['user_id', '=', '$reporter_user_id']]
            ],
            'Moderator' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => [['user_id', '=', '$moderator_user_id']]
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
        $this->updated_date = \XF::$time;

        if (!in_array($this->reason, ['fake', 'malicious', 'scam', 'offline', 'inappropriate', 'other'], true))
        {
            $this->error('Geçersiz rapor nedeni.', 'reason');
        }
        if (!in_array($this->state, ['open', 'resolved', 'rejected'], true))
        {
            $this->error('Geçersiz rapor durumu.', 'state');
        }
    }
}
