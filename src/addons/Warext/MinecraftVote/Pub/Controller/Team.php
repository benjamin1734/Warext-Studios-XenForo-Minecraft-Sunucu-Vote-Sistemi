<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\Server;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Team extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $server = $this->assertOwnedServer((int)$params->server_id);
        $visitor = \XF::visitor();

        if ($this->isPost())
        {
            $input = $this->filter([
                'username' => 'str',
                'role' => 'str',
                'edit_content' => 'bool',
                'publish_updates' => 'bool',
                'view_stats' => 'bool',
                'manage_votifier' => 'bool',
                'manage_reviews' => 'bool'
            ]);

            $permissions = [
                'edit_content' => $input['edit_content'],
                'publish_updates' => $input['publish_updates'],
                'view_stats' => $input['view_stats'],
                'manage_votifier' => $input['manage_votifier'],
                'manage_reviews' => $input['manage_reviews']
            ];

            try
            {
                $manager = $this->service('Warext\MinecraftVote:Team\Manager', $server, $visitor);
                $manager->addOrUpdate($input['username'], $input['role'], $permissions);
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            return $this->redirect(
                $this->buildLink('sunucular/ekip', $server),
                'Sunucu ekip üyesi kaydedildi.'
            );
        }

        $members = $this->repository('Warext\MinecraftVote:ServerTeam')
            ->findForServer($server->server_id)
            ->fetch();

        return $this->view('Warext\MinecraftVote:Team\Index', 'warext_mc_team_index', [
            'server' => $server,
            'members' => $members
        ]);
    }

    public function actionRemove(ParameterBag $params)
    {
        $this->assertPostOnly();
        $server = $this->assertOwnedServer((int)$params->server_id);
        $userId = $this->filter('user_id', 'uint');

        try
        {
            $manager = $this->service('Warext\MinecraftVote:Team\Manager', $server, \XF::visitor());
            $manager->remove($userId);
        }
        catch (\XF\PrintableException $e)
        {
            return $this->error($e->getMessage(), 400);
        }

        return $this->redirect(
            $this->buildLink('sunucular/ekip', $server),
            'Ekip üyesi kaldırıldı.'
        );
    }

    protected function assertOwnedServer(int $serverId): Server
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            throw $this->exception($this->noPermission());
        }

        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId);
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
