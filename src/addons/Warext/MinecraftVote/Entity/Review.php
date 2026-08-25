<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Review extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_review';
        $structure->shortName = 'Warext\MinecraftVote:Review';
        $structure->primaryKey = 'review_id';
        $structure->columns = [
            'review_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'server_id' => ['type' => self::UINT, 'required' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'rating' => ['type' => self::UINT, 'required' => true],
            'gameplay_rating' => ['type' => self::UINT, 'default' => 0],
            'staff_rating' => ['type' => self::UINT, 'default' => 0],
            'performance_rating' => ['type' => self::UINT, 'default' => 0],
            'community_rating' => ['type' => self::UINT, 'default' => 0],
            'originality_rating' => ['type' => self::UINT, 'default' => 0],
            'message' => ['type' => self::STR, 'maxLength' => 2000, 'default' => ''],
            'is_verified_player' => ['type' => self::BOOL, 'default' => false],
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
        if (!$this->created_date)
        {
            $this->created_date = \XF::$time;
        }
        $this->updated_date = \XF::$time;

        foreach (['rating', 'gameplay_rating', 'staff_rating', 'performance_rating', 'community_rating', 'originality_rating'] as $field)
        {
            $value = (int)$this->$field;
            $minimum = $field === 'rating' ? 1 : 0;
            if ($value < $minimum || $value > 5)
            {
                $this->error('Puan 1-5 arasında olmalıdır.', $field);
            }
        }

        if (!in_array($this->state, ['visible', 'moderated', 'deleted'], true))
        {
            $this->error('Geçersiz değerlendirme durumu.', 'state');
        }

        $this->message = trim($this->message);
    }
}
