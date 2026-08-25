<?php

namespace Warext\MinecraftVote\Repository;

use Warext\MinecraftVote\Entity\Server as ServerEntity;
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

    public function getServerBySlug(string $slug): ?ServerEntity
    {
        return $this->finder('Warext\MinecraftVote:Server')
            ->where('slug', $slug)
            ->fetchOne();
    }

    public function incrementViewCount(ServerEntity $server): void
    {
        $this->db()->query(
            'UPDATE xf_warext_mc_server SET view_count = view_count + 1 WHERE server_id = ?',
            $server->server_id
        );
        $server->view_count++;
    }
}
