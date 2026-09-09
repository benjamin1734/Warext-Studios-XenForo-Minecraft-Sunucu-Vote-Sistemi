<?php

namespace Warext\MinecraftVote\ApprovalQueue;

use Warext\MinecraftVote\Entity\Server as ServerEntity;
use XF\ApprovalQueue\AbstractHandler;
use XF\Mvc\Entity\Entity;

class Server extends AbstractHandler
{
    protected function canActionContent(Entity $content, &$error = null)
    {
        return $content instanceof ServerEntity && $content->canApproveUnapprove($error);
    }

    public function actionApprove(ServerEntity $server): void
    {
        if ($server->state !== 'active')
        {
            $server->state = 'active';
            $server->save();
        }
    }

    public function actionDelete(ServerEntity $server): void
    {
        if ($server->state !== 'rejected')
        {
            $server->state = 'rejected';
            $server->save();
        }
    }
}
