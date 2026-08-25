<?php

namespace Warext\MinecraftVote\Repository;

use Warext\MinecraftVote\Entity\Server;
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
            ->where('status', '<>', 'rejected')
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
            ->where('status', '<>', 'rejected')
            ->since($since)
            ->fetchOne();
    }

    public function hasRecentMinecraftUsernameVote(int $serverId, string $username, int $since): bool
    {
        if ($username === '')
        {
            return false;
        }

        return (bool)$this->finder('Warext\MinecraftVote:Vote')
            ->forServer($serverId)
            ->where('minecraft_username', $username)
            ->where('status', '<>', 'rejected')
            ->since($since)
            ->fetchOne();
    }

    public function hasRecentIpVote(int $serverId, string $ipHash, int $since): bool
    {
        if ($ipHash === '')
        {
            return false;
        }

        return (bool)$this->finder('Warext\MinecraftVote:Vote')
            ->forServer($serverId)
            ->where('ip_hash', $ipHash)
            ->where('status', '<>', 'rejected')
            ->since($since)
            ->fetchOne();
    }

    public function countRecentIpVotes(int $serverId, string $ipHash, int $since): int
    {
        if ($ipHash === '')
        {
            return 0;
        }

        return $this->finder('Warext\MinecraftVote:Vote')
            ->forServer($serverId)
            ->where('ip_hash', $ipHash)
            ->where('status', '<>', 'rejected')
            ->since($since)
            ->total();
    }

    public function countRecentIpActivity(int $serverId, string $ipHash, int $since): int
    {
        if ($serverId <= 0 || $ipHash === '')
        {
            return 0;
        }

        return $this->finder('Warext\MinecraftVote:Vote')
            ->forServer($serverId)
            ->where('ip_hash', $ipHash)
            ->since($since)
            ->total();
    }

    public function getLatestVoteForUsername(int $serverId, string $username): ?\Warext\MinecraftVote\Entity\Vote
    {
        return $this->finder('Warext\MinecraftVote:Vote')
            ->forServer($serverId)
            ->where('minecraft_username', $username)
            ->where('status', '<>', 'rejected')
            ->order('vote_date', 'DESC')
            ->fetchOne();
    }

    public function rebuildServerCounters(Server $server): void
    {
        [$dayStart, $monthStart] = $this->getCounterBoundaries();

        $row = $this->db()->fetchRow(
            "SELECT
                COUNT(*) AS vote_count_total,
                SUM(vote_date >= ?) AS vote_count_month,
                SUM(vote_date >= ?) AS vote_count_today
             FROM xf_warext_mc_vote
             WHERE server_id = ? AND status <> 'rejected'",
            [$monthStart, $dayStart, $server->server_id]
        );

        $server->vote_count_total = (int)($row['vote_count_total'] ?? 0);
        $server->vote_count_month = (int)($row['vote_count_month'] ?? 0);
        $server->vote_count_today = (int)($row['vote_count_today'] ?? 0);
        $server->save();
    }

    protected function getCounterBoundaries(): array
    {
        $timeZoneId = (string)(\XF::options()->guestTimeZone ?? 'UTC');

        try
        {
            $timeZone = new \DateTimeZone($timeZoneId ?: 'UTC');
        }
        catch (\Throwable)
        {
            $timeZone = new \DateTimeZone('UTC');
        }

        $now = new \DateTimeImmutable('@' . \XF::$time);
        $now = $now->setTimezone($timeZone);

        return [
            $now->setTime(0, 0)->getTimestamp(),
            $now->modify('first day of this month')->setTime(0, 0)->getTimestamp()
        ];
    }
}
