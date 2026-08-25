<?php

namespace Warext\MinecraftVote\Service\Team;

use Warext\MinecraftVote\Entity\Server;
use XF\App;
use XF\Entity\User;
use XF\PrintableException;
use XF\Service\AbstractService;

class Manager extends AbstractService
{
    protected Server $server;
    protected User $actor;

    public function __construct(App $app, Server $server, User $actor)
    {
        parent::__construct($app);
        $this->server = $server;
        $this->actor = $actor;
    }

    public function addOrUpdate(string $username, string $role, array $permissions)
    {
        $this->assertCanManage();

        $username = trim($username);
        if ($username === '')
        {
            throw new PrintableException('Ekip üyesi kullanıcı adı gereklidir.');
        }

        $user = $this->finder('XF:User')
            ->where('username', $username)
            ->fetchOne();
        if (!$user)
        {
            throw new PrintableException('Belirtilen XenForo kullanıcısı bulunamadı.');
        }

        if ((int)$user->user_id === (int)$this->server->owner_user_id)
        {
            throw new PrintableException('Sunucu sahibi ekip üyesi olarak eklenemez.');
        }

        $role = trim($role);
        if (!in_array($role, ['manager', 'editor', 'analyst', 'support', 'member'], true))
        {
            $role = 'member';
        }

        $repo = $this->repository('Warext\MinecraftVote:ServerTeam');
        $member = $repo->getMember($this->server->server_id, $user->user_id);
        $operation = $member ? 'updated' : 'created';
        if (!$member)
        {
            $member = $this->em()->create('Warext\MinecraftVote:ServerTeam');
            $member->server_id = $this->server->server_id;
            $member->user_id = $user->user_id;
        }

        $member->role = $role;
        $member->permissions = $repo->sanitizePermissions($permissions);
        $member->save();

        $enabledPermissions = [];
        foreach ($member->permissions as $permission => $enabled)
        {
            if ($enabled)
            {
                $enabledPermissions[] = $permission;
            }
        }

        $this->service('Warext\MinecraftVote:Audit\Logger')->log(
            'team_member_saved',
            $this->server->server_id,
            $this->actor->user_id,
            $user->user_id,
            [
                'operation' => $operation,
                'role' => $member->role,
                'permissions' => implode(',', $enabledPermissions)
            ]
        );

        return $member;
    }

    public function remove(int $userId): bool
    {
        $this->assertCanManage();

        $member = $this->repository('Warext\MinecraftVote:ServerTeam')
            ->getMember($this->server->server_id, $userId);
        if (!$member)
        {
            return false;
        }

        $role = $member->role;
        $member->delete();

        $this->service('Warext\MinecraftVote:Audit\Logger')->log(
            'team_member_removed',
            $this->server->server_id,
            $this->actor->user_id,
            $userId,
            ['role' => $role]
        );

        return true;
    }

    protected function assertCanManage(): void
    {
        if (!$this->actor->user_id || (int)$this->actor->user_id !== (int)$this->server->owner_user_id)
        {
            throw new PrintableException('Sunucu ekibini yalnızca sunucu sahibi yönetebilir.');
        }
    }
}
