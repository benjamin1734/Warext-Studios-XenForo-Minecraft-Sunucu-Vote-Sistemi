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
            $favorite = $this->finder('Warext\MinecraftVote:Favorite')
                ->where('server_id', $server->server_id)
                ->where('user_id', $userId)
                ->fetchOne();

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
                $favorite->save();
                $active = true;
            }

            $count = (int)$this->finder('Warext\MinecraftVote:Favorite')
                ->where('server_id', $server->server_id)
                ->total();

            $server->favorite_count = $count;
            $server->save();

            $db->commit();
            return $active;
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }
    }

    public function findForUser(int $userId)
    {
        return $this->finder('Warext\MinecraftVote:Favorite')
            ->where('user_id', $userId)
            ->with('Server')
            ->order('created_date', 'DESC');
    }
}
