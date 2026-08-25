<?php

namespace Warext\MinecraftVote\Service\Audit;

use XF\App;
use XF\Service\AbstractService;

class Logger extends AbstractService
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function log(
        string $action,
        int $serverId,
        int $actorUserId,
        int $targetUserId = 0,
        array $details = []
    ): void
    {
        $allowed = [
            'team_member_saved',
            'team_member_removed',
            'ownership_transferred',
            'review_hidden',
            'review_restored',
            'server_state_changed',
            'server_deleted',
            'sponsor_created',
            'sponsor_updated',
            'sponsor_deleted',
            'achievement_updated',
            'achievement_rebuild_requested',
            'vote_rejected',
            'vote_restored',
            'report_state_changed'
        ];

        if (!in_array($action, $allowed, true))
        {
            throw new \InvalidArgumentException('Geçersiz audit action.');
        }

        $safeDetails = [];
        foreach ($details as $key => $value)
        {
            if (!is_string($key) || strlen($key) > 50)
            {
                continue;
            }

            if (is_scalar($value) || $value === null)
            {
                $safeDetails[$key] = is_string($value) ? mb_substr($value, 0, 500) : $value;
            }
        }

        $log = $this->em()->create('Warext\MinecraftVote:AuditLog');
        $log->server_id = max(0, $serverId);
        $log->actor_user_id = max(0, $actorUserId);
        $log->target_user_id = max(0, $targetUserId);
        $log->action = $action;
        $log->details = $safeDetails;
        $log->save();
    }
}
