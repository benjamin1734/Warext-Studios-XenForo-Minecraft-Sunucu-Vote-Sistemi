<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Achievement extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_achievement';
        $structure->shortName = 'Warext\MinecraftVote:Achievement';
        $structure->primaryKey = 'achievement_id';
        $structure->columns = [
            'achievement_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'achievement_key' => ['type' => self::STR, 'maxLength' => 50, 'required' => true],
            'title' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
            'description' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'icon' => ['type' => self::STR, 'maxLength' => 50, 'default' => 'fa-trophy'],
            'metric' => ['type' => self::STR, 'maxLength' => 30, 'required' => true],
            'threshold' => ['type' => self::UINT, 'default' => 0],
            'display_order' => ['type' => self::UINT, 'default' => 10],
            'is_active' => ['type' => self::BOOL, 'default' => true],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'updated_date' => ['type' => self::UINT, 'default' => 0]
        ];

        $structure->getters = [
            'display_title' => true,
            'display_description' => true
        ];

        return $structure;
    }

    public function getDisplayTitle(): string
    {
        $stock = [
            'votes_100' => ['100 Oy', '100 Votes'],
            'votes_1000' => ['1.000 Oy', '1,000 Votes'],
            'votes_10000' => ['10.000 Oy', '10,000 Votes'],
            'uptime_99' => ['%99 Uptime', '99% Uptime'],
            'peak_100' => ['100 Eş Zamanlı Oyuncu', '100 Concurrent Players'],
            'peak_500' => ['500 Eş Zamanlı Oyuncu', '500 Concurrent Players'],
            'one_year' => ['1 Yıllık Sunucu', 'One-Year Server'],
            'verified' => ['Doğrulanmış Sunucu', 'Verified Server'],
            'month_champion' => ['Ayın Sunucusu', 'Server of the Month'],
            'rising_star' => ['Yükselen Yıldız', 'Rising Star']
        ];

        if (isset($stock[$this->achievement_key]) && in_array($this->title, $stock[$this->achievement_key], true))
        {
            return (string)\XF::phrase('warext_mc_achievement_' . $this->achievement_key . '_title');
        }

        return (string)$this->title;
    }

    public function getDisplayDescription(): string
    {
        $stock = [
            'votes_100' => ['Toplam 100 topluluk oyuna ulaştı.', 'Reached 100 total community votes.'],
            'votes_1000' => ['Toplam 1.000 topluluk oyuna ulaştı.', 'Reached 1,000 total community votes.'],
            'votes_10000' => ['Toplam 10.000 topluluk oyuna ulaştı.', 'Reached 10,000 total community votes.'],
            'uptime_99' => ['İzlenen çalışma süresinde %99 uptime seviyesine ulaştı.', 'Reached 99% uptime across monitored availability.'],
            'peak_100' => ['En az 100 eş zamanlı oyuncu gördü.', 'Reached at least 100 concurrent players.'],
            'peak_500' => ['En az 500 eş zamanlı oyuncu gördü.', 'Reached at least 500 concurrent players.'],
            'one_year' => ['Platformda 365 günü tamamladı.', 'Completed 365 days on the platform.'],
            'verified' => ['Sunucu sahipliği başarıyla doğrulandı.', 'Server ownership was successfully verified.'],
            'month_champion' => ['Bir aylık oy sezonunu birinci tamamladı.', 'Finished first in a monthly vote season.'],
            'rising_star' => ['Trend sıralamasında ilk 3 içine girdi.', 'Reached the top 3 in the trending ranking.']
        ];

        if (isset($stock[$this->achievement_key]) && in_array($this->description, $stock[$this->achievement_key], true))
        {
            return (string)\XF::phrase('warext_mc_achievement_' . $this->achievement_key . '_desc');
        }

        return (string)$this->description;
    }

    protected function _preSave(): void
    {
        $this->achievement_key = strtolower(trim($this->achievement_key));
        $this->title = trim($this->title);
        $this->description = trim($this->description);
        $this->icon = trim($this->icon) ?: 'fa-trophy';
        $this->metric = strtolower(trim($this->metric));

        if (!preg_match('/^[a-z0-9_]{2,50}$/', $this->achievement_key))
        {
            $this->error((string)\XF::phrase('warext_mc_dyn_achievement_key_format'), 'achievement_key');
        }

        if (!in_array($this->metric, [
            'vote_total', 'uptime_bp', 'peak_players', 'age_days',
            'verified', 'season_wins', 'trend_rank_max'
        ], true))
        {
            $this->error((string)\XF::phrase('warext_mc_dyn_invalid_achievement_metric'), 'metric');
        }

        if (!$this->created_date)
        {
            $this->created_date = \XF::$time;
        }
        $this->updated_date = \XF::$time;
    }
}
