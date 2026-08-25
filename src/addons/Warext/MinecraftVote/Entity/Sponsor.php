<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Sponsor extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_sponsor';
        $structure->shortName = 'Warext\MinecraftVote:Sponsor';
        $structure->primaryKey = 'sponsor_id';
        $structure->columns = [
            'sponsor_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'server_id' => ['type' => self::UINT, 'required' => true],
            'label' => ['type' => self::STR, 'maxLength' => 50, 'default' => 'Sponsorlu'],
            'placement' => ['type' => self::STR, 'maxLength' => 30, 'default' => 'list_top'],
            'start_date' => ['type' => self::UINT, 'default' => 0],
            'end_date' => ['type' => self::UINT, 'default' => 0],
            'state' => ['type' => self::STR, 'maxLength' => 20, 'default' => 'active'],
            'display_order' => ['type' => self::UINT, 'default' => 10],
            'created_by' => ['type' => self::UINT, 'default' => 0],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'updated_date' => ['type' => self::UINT, 'default' => 0]
        ];
        $structure->getters = [
            'is_current' => true
        ];
        $structure->relations = [
            'Server' => [
                'entity' => 'Warext\MinecraftVote:Server',
                'type' => self::TO_ONE,
                'conditions' => 'server_id',
                'primary' => true
            ],
            'Creator' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => [['user_id', '=', '$created_by']],
                'primary' => true
            ]
        ];

        return $structure;
    }

    public function getIsCurrent(): bool
    {
        if ($this->state !== 'active')
        {
            return false;
        }

        $now = \XF::$time;
        return $this->start_date <= $now && (!$this->end_date || $this->end_date >= $now);
    }

    protected function _preSave(): void
    {
        $this->label = trim($this->label) ?: 'Sponsorlu';
        $this->placement = strtolower(trim($this->placement));

        if (!in_array($this->placement, ['list_top'], true))
        {
            $this->error('Geçersiz sponsor alanı.', 'placement');
        }
        if (!in_array($this->state, ['active', 'paused'], true))
        {
            $this->error('Geçersiz sponsor durumu.', 'state');
        }
        if ($this->end_date && $this->end_date <= $this->start_date)
        {
            $this->error('Sponsor bitiş tarihi başlangıç tarihinden sonra olmalıdır.', 'end_date');
        }

        if (!$this->created_date)
        {
            $this->created_date = \XF::$time;
        }
        $this->updated_date = \XF::$time;
    }
}
