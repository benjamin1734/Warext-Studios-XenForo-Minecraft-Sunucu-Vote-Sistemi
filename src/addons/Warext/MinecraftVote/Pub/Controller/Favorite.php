<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\Server;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Favorite extends AbstractController
{
    public function actionToggle(ParameterBag $params)
    {
        $this->assertPostOnly();
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            return $this->noPermission();
        }

        $server = $this->assertActiveServer((int)$params->server_id);
        $active = $this->repository('Warext\MinecraftVote:Favorite')
            ->toggle($server, $visitor->user_id);

        return $this->redirect(
            $this->buildLink('sunucular/detay', $server),
            $active ? 'Sunucu favorilerinize eklendi.' : 'Sunucu favorilerinizden çıkarıldı.'
        );
    }

    public function actionIndex()
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            return $this->noPermission();
        }

        $favorites = $this->repository('Warext\MinecraftVote:Favorite')
            ->findForUser($visitor->user_id)
            ->fetch();

        return $this->view('Warext\MinecraftVote:Favorite\Index', 'warext_mc_favorite_index', [
            'favorites' => $favorites
        ]);
    }

    protected function assertActiveServer(int $serverId): Server
    {
        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId);
        if (!$server || $server->state !== 'active')
        {
            throw $this->exception($this->notFound());
        }

        return $server;
    }
}
