<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Season extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_season';
        $structure->shortName = 'Warext\MinecraftVote:Season';
        $structure->primaryKey = 'season_id';
        $structure->columns = [
            'season_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'season_key' => ['type' => self::STR, 'maxLength' => 7, 'required' => true],
            'start_date' => ['type' => self::UINT, 'required' => true],
            'end_date' => ['type' => self::UINT, 'required' => true],
            'status' => ['type' => self::STR, 'maxLength' => 10, 'default' => 'open'],
            'winner_server_id' => ['type' => self::UINT, 'default' => 0],
            'total_votes' => ['type' => self::UINT, 'default' => 0],
            'unique_voters' => ['type' => self::UINT, 'default' => 0],
            'server_count' => ['type' => self::UINT, 'default' => 0],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'finalized_date' => ['type' => self::UINT, 'default' => 0]
        ];
        $structure->relations = [
            'Winner' => [
                'entity' => 'Warext\MinecraftVote:Server',
                'type' => self::TO_ONE,
                'conditions' => [['server_id', '=', '$winner_server_id']],
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

        if (!preg_match('/^\d{4}-\d{2}$/', $this->season_key))
        {
            $this->error('Geçersiz sezon anahtarı.', 'season_key');
        }

        if (!in_array($this->status, ['open', 'closed'], true))
        {
            $this->error('Geçersiz sezon durumu.', 'status');
        }

        if ($this->end_date <= $this->start_date)
        {
            $this->error('Sezon bitiş tarihi başlangıç tarihinden sonra olmalıdır.', 'end_date');
        }
    }
}
