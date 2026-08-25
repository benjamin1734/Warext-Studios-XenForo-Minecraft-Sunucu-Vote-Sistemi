<?php

namespace Warext\MinecraftVote\Repository;

use Warext\MinecraftVote\Finder\Server as ServerFinder;
use XF\Mvc\Entity\Repository;

class Server extends Repository
{
    public function findServersForList(): ServerFinder
    {
        return $this->finder('Warext\MinecraftVote:Server')
            ->activeOnly();
    }

    public function findPopularServers(): ServerFinder
    {
        return $this->findServersForList()
            ->orderByVotes();
    }

    public function findOnlineServers(): ServerFinder
    {
        return $this->findServersForList()
            ->onlineOnly()
            ->orderByPlayers();
    }

    public function findNewestServers(): ServerFinder
    {
        return $this->findServersForList()
            ->newestFirst();
    }

    public function findServersByOwner(int $userId): ServerFinder
    {
        return $this->finder('Warext\MinecraftVote:Server')
            ->ownedBy($userId)
            ->order('last_update_date', 'DESC');
    }

    public function getServerBySlug(string $slug): ?\Warext\MinecraftVote\Entity\Server
    {
        return $this->finder('Warext\MinecraftVote:Server')
            ->where('slug', $slug)
            ->fetchOne();
    }
}
