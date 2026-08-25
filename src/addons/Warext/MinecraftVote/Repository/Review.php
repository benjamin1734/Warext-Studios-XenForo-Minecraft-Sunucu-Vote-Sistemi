<?php

namespace Warext\MinecraftVote\Repository;

use Warext\MinecraftVote\Entity\Review as ReviewEntity;
use Warext\MinecraftVote\Entity\Server;
use XF\Mvc\Entity\Repository;

class Review extends Repository
{
    public function getUserReview(int $serverId, int $userId): ?ReviewEntity
    {
        if ($userId <= 0)
        {
            return null;
        }

        return $this->finder('Warext\MinecraftVote:Review')
            ->where('server_id', $serverId)
            ->where('user_id', $userId)
            ->fetchOne();
    }

    public function findVisibleForServer(int $serverId)
    {
        return $this->finder('Warext\MinecraftVote:Review')
            ->where('server_id', $serverId)
            ->where('state', 'visible')
            ->with('User')
            ->order('updated_date', 'DESC');
    }

    public function rebuildServerRating(Server $server): void
    {
        $row = $this->db()->fetchRow(
            "SELECT COUNT(*) AS rating_count, COALESCE(SUM(rating), 0) AS rating_sum
             FROM xf_warext_mc_review
             WHERE server_id = ? AND state = 'visible'",
            [$server->server_id]
        );

        $server->rating_count = (int)($row['rating_count'] ?? 0);
        $server->rating_sum = (int)($row['rating_sum'] ?? 0);
        $server->save();
    }
}
