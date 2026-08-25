<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class ServerUpdate extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_server_update';
        $structure->shortName = 'Warext\MinecraftVote:ServerUpdate';
        $structure->contentType = 'warext_mc_server_update';
        $structure->primaryKey = 'update_id';
        $structure->columns = [
            'update_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'server_id' => ['type' => self::UINT, 'required' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'title' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
            'version_label' => ['type' => self::STR, 'maxLength' => 50, 'default' => ''],
            'message' => ['type' => self::STR, 'required' => true],
            'state' => ['type' => self::STR, 'maxLength' => 20, 'default' => 'visible'],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'updated_date' => ['type' => self::UINT, 'default' => 0]
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
        $this->title = trim($this->title);
        $this->version_label = trim($this->version_label);
        $this->message = trim($this->message);

        if (!$this->created_date)
        {
            $this->created_date = \XF::$time;
        }
        $this->updated_date = \XF::$time;

        if (!in_array($this->state, ['visible', 'deleted'], true))
        {
            $this->error('Geçersiz güncelleme durumu.', 'state');
        }
    }
}
