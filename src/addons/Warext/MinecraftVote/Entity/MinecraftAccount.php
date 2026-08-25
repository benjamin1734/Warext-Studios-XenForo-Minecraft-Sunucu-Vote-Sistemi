<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class MinecraftAccount extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_account';
        $structure->shortName = 'Warext\MinecraftVote:MinecraftAccount';
        $structure->primaryKey = 'account_id';
        $structure->columns = [
            'account_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'minecraft_username' => ['type' => self::STR, 'maxLength' => 16, 'required' => true],
            'minecraft_uuid' => ['type' => self::STR, 'maxLength' => 36, 'default' => ''],
            'verification_state' => ['type' => self::STR, 'maxLength' => 20, 'default' => 'unverified'],
            'verification_method' => ['type' => self::STR, 'maxLength' => 30, 'default' => ''],
            'verification_code' => ['type' => self::STR, 'maxLength' => 64, 'default' => ''],
            'is_primary' => ['type' => self::BOOL, 'default' => false],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'updated_date' => ['type' => self::UINT, 'default' => 0],
            'verified_date' => ['type' => self::UINT, 'default' => 0]
        ];
        $structure->relations = [
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
        $this->minecraft_username = trim($this->minecraft_username);
        if (!preg_match('/^[A-Za-z0-9_]{3,16}$/', $this->minecraft_username))
        {
            $this->error('Minecraft kullanıcı adı 3-16 karakter olmalı ve yalnızca harf, rakam veya alt çizgi içermelidir.', 'minecraft_username');
        }

        $this->minecraft_uuid = $this->normalizeUuid($this->minecraft_uuid);

        if (!in_array($this->verification_state, ['unverified', 'pending', 'verified', 'revoked'], true))
        {
            $this->error('Geçersiz Minecraft hesap doğrulama durumu.', 'verification_state');
        }

        if (!$this->created_date)
        {
            $this->created_date = \XF::$time;
        }

        $this->updated_date = \XF::$time;

        if ($this->verification_state === 'verified' && !$this->verified_date)
        {
            $this->verified_date = \XF::$time;
        }
        elseif ($this->verification_state !== 'verified')
        {
            $this->verified_date = 0;
        }
    }

    protected function normalizeUuid(string $uuid): string
    {
        $uuid = strtolower(trim($uuid));
        if ($uuid === '')
        {
            return '';
        }

        $hex = str_replace('-', '', $uuid);
        if (!preg_match('/^[a-f0-9]{32}$/', $hex))
        {
            $this->error('Minecraft UUID biçimi geçersiz.', 'minecraft_uuid');
            return '';
        }

        return substr($hex, 0, 8) . '-'
            . substr($hex, 8, 4) . '-'
            . substr($hex, 12, 4) . '-'
            . substr($hex, 16, 4) . '-'
            . substr($hex, 20, 12);
    }
}
