<?php

namespace Warext\MinecraftVote\Admin\Controller;

use Warext\MinecraftVote\Entity\Server as ServerEntity;
use XF\Admin\Controller\AbstractController;

class Server extends AbstractController
{
    protected function preDispatchController($action, $controller, \XF\Mvc\ParameterBag $params): void
    {
        $this->assertAdminPermission('warextMinecraftVote');
    }

    public function actionIndex()
    {
        $state = $this->filter('state', 'str');
        if (!in_array($state, ['pending', 'active', 'rejected', 'suspended', 'all'], true))
        {
            $state = 'pending';
        }

        $finder = $this->finder('Warext\MinecraftVote:Server')
            ->with('Owner')
            ->order('created_date', 'DESC');

        if ($state !== 'all')
        {
            $finder->where('state', $state);
        }

        $servers = $finder->fetch();

        return $this->view('Warext\MinecraftVote:Server\List', 'warext_mc_admin_server_list', [
            'servers' => $servers,
            'state' => $state
        ]);
    }

    public function actionState()
    {
        $this->assertPostOnly();

        $serverId = $this->filter('server_id', 'uint');
        $state = $this->filter('state', 'str');

        if (!in_array($state, ['pending', 'active', 'rejected', 'suspended'], true))
        {
            return $this->error('Geçersiz sunucu durumu.');
        }

        $server = $this->assertServerExists($serverId);
        $server->state = $state;
        $server->save();

        return $this->redirect($this->buildLink('warext-minecraft', null, ['state' => $state === 'pending' ? 'pending' : 'all']));
    }

    public function actionDelete()
    {
        $this->assertPostOnly();

        $serverId = $this->filter('server_id', 'uint');
        $server = $this->assertServerExists($serverId);

        $this->db()->delete('xf_warext_mc_server_category', 'server_id = ?', $server->server_id);
        $this->db()->delete('xf_warext_mc_server_team', 'server_id = ?', $server->server_id);
        $this->db()->delete('xf_warext_mc_ping_history', 'server_id = ?', $server->server_id);
        $server->delete();

        return $this->redirect($this->buildLink('warext-minecraft'));
    }

    protected function assertServerExists(int $serverId): ServerEntity
    {
        /** @var ServerEntity|null $server */
        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId, ['Owner']);
        if (!$server)
        {
            throw $this->exception($this->notFound());
        }

        return $server;
    }
}
