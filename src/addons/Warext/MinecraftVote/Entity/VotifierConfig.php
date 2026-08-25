<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class VotifierConfig extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_votifier';
        $structure->shortName = 'Warext\MinecraftVote:VotifierConfig';
        $structure->primaryKey = 'server_id';
        $structure->columns = [
            'server_id' => ['type' => self::UINT, 'required' => true],
            'enabled' => ['type' => self::BOOL, 'default' => false],
            'host' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'port' => ['type' => self::UINT, 'default' => 8192],
            'protocol' => ['type' => self::STR, 'maxLength' => 10, 'default' => 'v2'],
            'service_name' => ['type' => self::STR, 'maxLength' => 64, 'default' => 'Warext'],
            'token_encrypted' => ['type' => self::STR, 'nullable' => true, 'default' => null],
            'last_test_date' => ['type' => self::UINT, 'default' => 0],
            'last_success_date' => ['type' => self::UINT, 'default' => 0],
            'last_error' => ['type' => self::STR, 'maxLength' => 500, 'default' => ''],
            'updated_date' => ['type' => self::UINT, 'default' => 0]
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
        $this->host = strtolower(trim($this->host));
        $this->service_name = trim($this->service_name) ?: 'Warext';
        $this->updated_date = \XF::$time;

        if ($this->port < 1 || $this->port > 65535)
        {
            $this->error('NuVotifier portu 1-65535 arasında olmalıdır.', 'port');
        }

        if (!in_array($this->protocol, ['v2'], true))
        {
            $this->error('Şu anda yalnızca güvenli NuVotifier V2 protokolü desteklenmektedir.', 'protocol');
        }
    }
}
