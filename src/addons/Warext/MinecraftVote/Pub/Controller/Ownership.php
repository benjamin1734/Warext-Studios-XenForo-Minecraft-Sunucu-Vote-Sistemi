<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\Server;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Ownership extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $server = $this->assertOwnedServer((int)$params->server_id);
        $visitor = \XF::visitor();

        if ($this->isPost())
        {
            $targetUsername = $this->filter('target_username', 'str');
            $confirmTitle = $this->filter('confirm_title', 'str');

            try
            {
                $transfer = $this->service('Warext\MinecraftVote:Ownership\Transfer', $server, $visitor);
                $newOwner = $transfer->transfer($targetUsername, $confirmTitle);
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            return $this->redirect(
                $this->buildLink('sunucular'),
                'Sunucu sahipliği ' . $newOwner->username . ' kullanıcısına devredildi. Sahiplik doğrulaması güvenlik nedeniyle sıfırlandı.'
            );
        }

        return $this->view('Warext\MinecraftVote:Ownership\Transfer', 'warext_mc_ownership_transfer', [
            'server' => $server
        ]);
    }

    protected function assertOwnedServer(int $serverId): Server
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            throw $this->exception($this->noPermission());
        }

        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId, ['Owner']);
        if (!$server)
        {
            throw $this->exception($this->notFound());
        }
        if ((int)$server->owner_user_id !== (int)$visitor->user_id)
        {
            throw $this->exception($this->noPermission());
        }

        return $server;
    }
}
