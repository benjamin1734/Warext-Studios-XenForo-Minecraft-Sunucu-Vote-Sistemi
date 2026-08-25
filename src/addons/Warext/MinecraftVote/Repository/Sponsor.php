<?php

namespace Warext\MinecraftVote\Repository;

use XF\Mvc\Entity\Repository;

class Sponsor extends Repository
{
    public function findActiveForPlacement(string $placement = 'list_top', ?int $now = null)
    {
        $now ??= \XF::$time;

        return $this->finder('Warext\MinecraftVote:Sponsor')
            ->where('state', 'active')
            ->where('placement', $placement)
            ->where('start_date', '<=', $now)
            ->whereOr(
                ['end_date', '=', 0],
                ['end_date', '>=', $now]
            )
            ->with('Server')
            ->where('Server.state', 'active')
            ->order('display_order', 'ASC')
            ->order('sponsor_id', 'ASC');
    }

    public function findForAdmin()
    {
        return $this->finder('Warext\MinecraftVote:Sponsor')
            ->with(['Server', 'Creator'])
            ->order('updated_date', 'DESC')
            ->order('sponsor_id', 'DESC');
    }
}
