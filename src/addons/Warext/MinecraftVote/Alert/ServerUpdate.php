<?php

namespace Warext\MinecraftVote\Alert;

use XF\Alert\AbstractHandler;
use XF\Entity\UserAlert;
use XF\Mvc\Entity\Entity;

class ServerUpdate extends AbstractHandler
{
    public function getEntityWith(): array
    {
        return ['Server', 'User'];
    }

    public function canViewContent(Entity $entity, &$error = null): bool
    {
        return $entity->state === 'visible'
            && $entity->Server
            && $entity->Server->state === 'active';
    }

    public function canViewAlert(UserAlert $alert, &$error = null): bool
    {
        $content = $alert->Content;
        return $content ? $this->canViewContent($content, $error) : false;
    }

    public function getOptOutActions(): array
    {
        return ['publish'];
    }

    public function getOptOutDisplayOrder(): int
    {
        return 500;
    }
}
