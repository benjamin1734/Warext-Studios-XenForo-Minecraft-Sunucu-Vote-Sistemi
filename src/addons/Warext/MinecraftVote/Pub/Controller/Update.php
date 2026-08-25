<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\Server;
use Warext\MinecraftVote\Entity\ServerUpdate;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Update extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $server = $this->assertViewableServer((int)$params->server_id);
        $visitor = \XF::visitor();
        $teamRepo = $this->repository('Warext\MinecraftVote:ServerTeam');
        $canPublish = $visitor->user_id && $teamRepo
            ->hasPermission($server, $visitor->user_id, 'publish_updates');
        $canManageVotifier = $visitor->user_id && $teamRepo
            ->hasPermission($server, $visitor->user_id, 'manage_votifier');
        $canViewStats = $visitor->user_id && $teamRepo
            ->hasPermission($server, $visitor->user_id, 'view_stats');

        if ($this->isPost())
        {
            if (!$canPublish)
            {
                return $this->noPermission();
            }

            $input = $this->filter([
                'title' => 'str',
                'version_label' => 'str',
                'message' => 'str'
            ]);

            try
            {
                $writer = $this->service('Warext\MinecraftVote:Update\Writer', $server, $visitor);
                $writer->create($input);
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            return $this->redirect(
                $this->buildLink('sunucular/guncellemeler', $server),
                'Sunucu güncellemesi yayınlandı.'
            );
        }

        $page = $this->filterPage();
        $perPage = 15;
        $repo = $this->repository('Warext\MinecraftVote:ServerUpdate');
        $finder = $repo->findVisibleForServer($server->server_id);
        $total = $finder->total();
        $this->assertValidPage($page, $perPage, $total, 'sunucular/guncellemeler', $server);

        $updates = $finder
            ->limitByPage($page, $perPage)
            ->fetch();

        if ($visitor->user_id)
        {
            $repo->markSeen($server->server_id, $visitor->user_id);
        }

        return $this->view('Warext\MinecraftVote:Update\Index', 'warext_mc_update_index', [
            'server' => $server,
            'updates' => $updates,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'canPublish' => $canPublish,
            'canManageVotifier' => $canManageVotifier,
            'canViewStats' => $canViewStats
        ]);
    }

    public function actionDelete(ParameterBag $params)
    {
        $this->assertPostOnly();
        $update = $this->assertUpdateExists((int)$params->update_id);
        $server = $update->Server;
        $visitor = \XF::visitor();

        if (!$visitor->user_id || !$server)
        {
            return $this->noPermission();
        }

        try
        {
            $writer = $this->service('Warext\MinecraftVote:Update\Writer', $server, $visitor);
            $writer->delete($update);
        }
        catch (\XF\PrintableException $e)
        {
            return $this->error($e->getMessage(), 400);
        }

        return $this->redirect(
            $this->buildLink('sunucular/guncellemeler', $server),
            'Sunucu güncellemesi silindi.'
        );
    }

    protected function assertViewableServer(int $serverId): Server
    {
        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId);
        if (!$server)
        {
            throw $this->exception($this->notFound());
        }

        $visitor = \XF::visitor();
        $canManageUpdates = $visitor->user_id && $this->repository('Warext\MinecraftVote:ServerTeam')
            ->hasPermission($server, $visitor->user_id, 'publish_updates');

        if ($server->state !== 'active' && !$canManageUpdates)
        {
            throw $this->exception($this->notFound());
        }

        return $server;
    }

    protected function assertUpdateExists(int $updateId): ServerUpdate
    {
        $update = $this->em()->find('Warext\MinecraftVote:ServerUpdate', $updateId, ['Server']);
        if (!$update)
        {
            throw $this->exception($this->notFound());
        }

        return $update;
    }
}
