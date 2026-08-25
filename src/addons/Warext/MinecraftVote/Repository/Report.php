<?php

namespace Warext\MinecraftVote\Repository;

use XF\Mvc\Entity\Repository;

class Report extends Repository
{
    public function findReports(string $state = '', int $serverId = 0)
    {
        $finder = $this->finder('Warext\MinecraftVote:Report')
            ->with(['Server', 'Reporter', 'Moderator'])
            ->order('created_date', 'DESC');

        if (in_array($state, ['open', 'resolved', 'rejected'], true))
        {
            $finder->where('state', $state);
        }
        if ($serverId > 0)
        {
            $finder->where('server_id', $serverId);
        }

        return $finder;
    }

    public function hasRecentReport(int $serverId, int $userId, int $since): bool
    {
        if ($serverId <= 0 || $userId <= 0)
        {
            return false;
        }

        return (bool)$this->finder('Warext\MinecraftVote:Report')
            ->where('server_id', $serverId)
            ->where('reporter_user_id', $userId)
            ->where('created_date', '>=', $since)
            ->fetchOne();
    }
}
