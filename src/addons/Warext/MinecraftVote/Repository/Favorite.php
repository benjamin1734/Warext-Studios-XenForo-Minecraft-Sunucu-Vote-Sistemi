<?php

namespace Warext\MinecraftVote\Repository;

use Warext\MinecraftVote\Entity\Server;
use XF\Mvc\Entity\Repository;

class Favorite extends Repository
{
    public function isFavorited(int $serverId, int $userId): bool
    {
        if ($userId <= 0)
        {
            return false;
        }

        return (bool)$this->finder('Warext\MinecraftVote:Favorite')
            ->where('server_id', $serverId)
            ->where('user_id', $userId)
            ->fetchOne();
    }

    public function countForServer(int $serverId): int
    {
        return $this->finder('Warext\MinecraftVote:Favorite')
            ->where('server_id', $serverId)
            ->total();
    }

    public function toggle(Server $server, int $userId): bool
    {
        if ($userId <= 0)
        {
            throw new \InvalidArgumentException('Favori işlemi için kullanıcı gereklidir.');
        }

        $db = $this->db();
        $db->beginTransaction();

        try
        {
            $favorite = $this->getForUser($server->server_id, $userId);

            if ($favorite)
            {
                $favorite->delete();
                $active = false;
            }
            else
            {
                $favorite = $this->em->create('Warext\MinecraftVote:Favorite');
                $favorite->server_id = $server->server_id;
                $favorite->user_id = $userId;
                $favorite->notify_updates = true;
                $favorite->last_seen_update_id = $this->repository('Warext\MinecraftVote:ServerUpdate')
                    ->getLatestVisibleId($server->server_id);
                $favorite->save();
                $active = true;
            }

            $db->commit();
            return $active;
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }
    }

    public function getForUser(int $serverId, int $userId)
    {
        if ($userId <= 0)
        {
            return null;
        }

        return $this->finder('Warext\MinecraftVote:Favorite')
            ->where('server_id', $serverId)
            ->where('user_id', $userId)
            ->fetchOne();
    }

    public function setUpdateNotifications(int $serverId, int $userId, bool $enabled): bool
    {
        $favorite = $this->getForUser($serverId, $userId);
        if (!$favorite)
        {
            return false;
        }

        $favorite->notify_updates = $enabled;
        if ($enabled)
        {
            $favorite->last_seen_update_id = $this->repository('Warext\MinecraftVote:ServerUpdate')
                ->getLatestVisibleId($serverId);
        }
        $favorite->save();

        return true;
    }

    public function findForUser(int $userId)
    {
        return $this->finder('Warext\MinecraftVote:Favorite')
            ->where('user_id', $userId)
            ->with('Server')
            ->order('created_date', 'DESC');
    }

    public function getUnreadUpdateCounts(int $userId): array
    {
        if ($userId <= 0)
        {
            return [];
        }

        $rows = $this->db()->fetchAll(
            "SELECT f.server_id, COUNT(u.update_id) AS unread_count
             FROM xf_warext_mc_favorite AS f
             LEFT JOIN xf_warext_mc_server_update AS u
               ON u.server_id = f.server_id
              AND u.state = 'visible'
              AND u.update_id > f.last_seen_update_id
             WHERE f.user_id = ?
               AND f.notify_updates = 1
             GROUP BY f.server_id",
            [$userId]
        );

        $counts = [];
        foreach ($rows as $row)
        {
            $counts[(int)$row['server_id']] = (int)$row['unread_count'];
        }

        return $counts;
    }
}
