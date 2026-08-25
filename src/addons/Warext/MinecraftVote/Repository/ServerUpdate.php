<?php

namespace Warext\MinecraftVote\Repository;

use XF\Mvc\Entity\Repository;

class ServerUpdate extends Repository
{
    public function findVisibleForServer(int $serverId)
    {
        return $this->finder('Warext\MinecraftVote:ServerUpdate')
            ->where('server_id', $serverId)
            ->where('state', 'visible')
            ->with('User')
            ->order('created_date', 'DESC');
    }

    public function getLatestVisibleId(int $serverId): int
    {
        $update = $this->finder('Warext\MinecraftVote:ServerUpdate')
            ->where('server_id', $serverId)
            ->where('state', 'visible')
            ->order('update_id', 'DESC')
            ->fetchOne();

        return $update ? (int)$update->update_id : 0;
    }

    public function countUnreadForUser(int $serverId, int $userId): int
    {
        if ($userId <= 0)
        {
            return 0;
        }

        $favorite = $this->finder('Warext\MinecraftVote:Favorite')
            ->where('server_id', $serverId)
            ->where('user_id', $userId)
            ->fetchOne();
        if (!$favorite || !$favorite->notify_updates)
        {
            return 0;
        }

        return $this->finder('Warext\MinecraftVote:ServerUpdate')
            ->where('server_id', $serverId)
            ->where('state', 'visible')
            ->where('update_id', '>', (int)$favorite->last_seen_update_id)
            ->total();
    }

    public function markSeen(int $serverId, int $userId): void
    {
        if ($userId <= 0)
        {
            return;
        }

        $favorite = $this->finder('Warext\MinecraftVote:Favorite')
            ->where('server_id', $serverId)
            ->where('user_id', $userId)
            ->fetchOne();
        if (!$favorite)
        {
            return;
        }

        $latestId = $this->getLatestVisibleId($serverId);
        if ($latestId > (int)$favorite->last_seen_update_id)
        {
            $favorite->last_seen_update_id = $latestId;
            $favorite->save();
        }
    }
}
