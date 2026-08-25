<?php

namespace Warext\MinecraftVote\Repository;

use XF\Mvc\Entity\Repository;

class Achievement extends Repository
{
    public function findActive()
    {
        return $this->finder('Warext\MinecraftVote:Achievement')
            ->where('is_active', 1)
            ->order('display_order', 'ASC')
            ->order('achievement_id', 'ASC');
    }

    public function findForServer(int $serverId)
    {
        return $this->finder('Warext\MinecraftVote:ServerAchievement')
            ->where('server_id', $serverId)
            ->with('Achievement')
            ->where('Achievement.is_active', 1)
            ->order('Achievement.display_order', 'ASC')
            ->order('awarded_date', 'ASC');
    }

    public function getAward(int $serverId, int $achievementId)
    {
        return $this->em->find('Warext\MinecraftVote:ServerAchievement', [
            'server_id' => $serverId,
            'achievement_id' => $achievementId
        ]);
    }
}
