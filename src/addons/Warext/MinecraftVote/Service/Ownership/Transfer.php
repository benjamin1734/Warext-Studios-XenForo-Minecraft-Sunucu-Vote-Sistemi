<?php

namespace Warext\MinecraftVote\Service\Ownership;

use Warext\MinecraftVote\Entity\Server;
use XF\App;
use XF\Entity\User;
use XF\PrintableException;
use XF\Service\AbstractService;

class Transfer extends AbstractService
{
    protected Server $server;
    protected User $actor;

    public function __construct(App $app, Server $server, User $actor)
    {
        parent::__construct($app);
        $this->server = $server;
        $this->actor = $actor;
    }

    public function transfer(string $targetUsername, string $confirmTitle): User
    {
        if (!$this->actor->user_id || (int)$this->actor->user_id !== (int)$this->server->owner_user_id)
        {
            throw new PrintableException('Sunucu sahipliğini yalnızca mevcut sunucu sahibi devredebilir.');
        }

        if (trim($confirmTitle) !== (string)$this->server->title)
        {
            throw new PrintableException('Onay için sunucu adını birebir yazmalısınız.');
        }

        $targetUsername = trim($targetUsername);
        if ($targetUsername === '')
        {
            throw new PrintableException('Yeni sahibin XenForo kullanıcı adını girin.');
        }

        $target = $this->finder('XF:User')
            ->where('username', $targetUsername)
            ->fetchOne();
        if (!$target)
        {
            throw new PrintableException('Belirtilen XenForo kullanıcısı bulunamadı.');
        }
        if ((int)$target->user_id === (int)$this->actor->user_id)
        {
            throw new PrintableException('Sunucu zaten bu kullanıcıya ait.');
        }

        $oldOwnerId = (int)$this->server->owner_user_id;
        $db = $this->db();
        $db->beginTransaction();

        try
        {
            $existingTeamMember = $this->repository('Warext\MinecraftVote:ServerTeam')
                ->getMember($this->server->server_id, $target->user_id);
            if ($existingTeamMember)
            {
                $existingTeamMember->delete();
            }

            $this->server->owner_user_id = $target->user_id;
            $this->server->is_verified = false;
            $this->server->verification_method = '';
            $this->server->verification_token = '';
            $this->server->verification_token_date = 0;
            $this->server->verified_date = 0;
            $this->server->save();

            $this->service('Warext\MinecraftVote:Audit\Logger')->log(
                'ownership_transferred',
                $this->server->server_id,
                $oldOwnerId,
                $target->user_id,
                [
                    'old_owner_user_id' => $oldOwnerId,
                    'new_owner_user_id' => $target->user_id,
                    'verification_reset' => 1
                ]
            );

            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }

        return $target;
    }
}
