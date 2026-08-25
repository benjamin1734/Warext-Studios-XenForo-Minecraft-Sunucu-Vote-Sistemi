<?php

namespace Warext\MinecraftVote\Repository;

use Warext\MinecraftVote\Finder\Vote as VoteFinder;
use XF\Mvc\Entity\Repository;

class Vote extends Repository
{
    public function findVotesForServer(int $serverId): VoteFinder
    {
        return $this->finder('Warext\MinecraftVote:Vote')
            ->forServer($serverId)
            ->order('vote_date', 'DESC');
    }

    public function findVotesForUser(int $userId): VoteFinder
    {
        return $this->finder('Warext\MinecraftVote:Vote')
            ->forUser($userId)
            ->order('vote_date', 'DESC');
    }

    public function findPendingDelivery(int $limit = 100): VoteFinder
    {
        return $this->finder('Warext\MinecraftVote:Vote')
            ->pendingDelivery(\XF::$time)
            ->limit($limit);
    }

    public function hasRecentUserVote(int $serverId, int $userId, int $since): bool
    {
        if ($userId <= 0)
        {
            return false;
        }

        return (bool)$this->finder('Warext\MinecraftVote:Vote')
            ->forServer($serverId)
            ->forUser($userId)
            ->since($since)
            ->fetchOne();
    }

    public function hasRecentMinecraftVote(int $serverId, string $minecraftUuid, int $since): bool
    {
        if ($minecraftUuid === '')
        {
            return false;
        }

        return (bool)$this->finder('Warext\MinecraftVote:Vote')
            ->forServer($serverId)
            ->where('minecraft_uuid', $minecraftUuid)
            ->since($since)
            ->fetchOne();
    }
}
