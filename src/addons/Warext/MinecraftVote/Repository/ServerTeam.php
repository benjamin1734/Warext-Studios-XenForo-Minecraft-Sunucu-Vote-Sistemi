<?php

namespace Warext\MinecraftVote\Repository;

use XF\Mvc\Entity\Repository;

class ServerTeam extends Repository
{
    public const PERMISSIONS = [
        'edit_content',
        'publish_updates',
        'view_stats',
        'manage_votifier',
        'manage_reviews'
    ];

    public function findForServer(int $serverId)
    {
        return $this->finder('Warext\MinecraftVote:ServerTeam')
            ->where('server_id', $serverId)
            ->with('User')
            ->order('joined_date', 'ASC');
    }

    public function getMember(int $serverId, int $userId)
    {
        if ($serverId <= 0 || $userId <= 0)
        {
            return null;
        }

        return $this->finder('Warext\MinecraftVote:ServerTeam')
            ->where('server_id', $serverId)
            ->where('user_id', $userId)
            ->fetchOne();
    }

    public function hasPermission($server, int $userId, string $permission): bool
    {
        if ($userId <= 0 || !in_array($permission, self::PERMISSIONS, true))
        {
            return false;
        }

        if ((int)$server->owner_user_id === $userId)
        {
            return true;
        }

        $member = $this->getMember((int)$server->server_id, $userId);
        if (!$member)
        {
            return false;
        }

        $permissions = is_array($member->permissions) ? $member->permissions : [];
        return !empty($permissions[$permission]);
    }

    public function sanitizePermissions(array $permissions): array
    {
        $clean = [];
        foreach (self::PERMISSIONS as $permission)
        {
            $clean[$permission] = !empty($permissions[$permission]);
        }

        return $clean;
    }
}
