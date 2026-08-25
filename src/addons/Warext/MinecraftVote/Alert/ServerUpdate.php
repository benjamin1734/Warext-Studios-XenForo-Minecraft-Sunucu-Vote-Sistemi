<?php

namespace Warext\MinecraftVote\Alert;

use XF\Alert\AbstractHandler;
use XF\Entity\UserAlert;
use XF\Mvc\Entity\Entity;

class ServerUpdate extends AbstractHandler
{
    public function getEntityWith()
    {
        return ['Server', 'User'];
    }

    public function canViewContent(Entity $entity, &$error = null)
    {
        return $entity->state === 'visible'
            && $entity->Server
            && $entity->Server->state === 'active';
    }

    public function canViewAlert(UserAlert $alert, &$error = null)
    {
        $content = $alert->Content;
        return $content ? $this->canViewContent($content, $error) : false;
    }

    public function getOptOutActions()
    {
        return ['publish'];
    }

    public function getOptOutDisplayOrder()
    {
        return 500;
    }
}
