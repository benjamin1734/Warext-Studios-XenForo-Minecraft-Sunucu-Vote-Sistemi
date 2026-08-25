<?php

namespace Warext\MinecraftVote\Repository;

use XF\Mvc\Entity\Repository;

class AuditLog extends Repository
{
    public function findRecent(int $limit = 100)
    {
        return $this->finder('Warext\MinecraftVote:AuditLog')
            ->with(['Server', 'Actor', 'Target'])
            ->order('log_date', 'DESC')
            ->limit(min(max($limit, 1), 500));
    }

    public function findForServer(int $serverId)
    {
        return $this->finder('Warext\MinecraftVote:AuditLog')
            ->where('server_id', $serverId)
            ->with(['Actor', 'Target'])
            ->order('log_date', 'DESC');
    }
}
