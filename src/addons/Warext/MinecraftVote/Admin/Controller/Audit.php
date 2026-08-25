<?php

namespace Warext\MinecraftVote\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Audit extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertAdminPermission('warextMinecraftVote');
    }

    public function actionIndex()
    {
        $page = $this->filterPage();
        $perPage = 50;
        $serverId = $this->filter('server_id', 'uint');
        $actionKey = strtolower(trim($this->filter('action_key', 'str')));

        $finder = $this->finder('Warext\MinecraftVote:AuditLog')
            ->with(['Server', 'Actor', 'Target'])
            ->order('log_date', 'DESC');

        if ($serverId)
        {
            $finder->where('server_id', $serverId);
        }
        if ($actionKey !== '')
        {
            $finder->where('action', $actionKey);
        }

        $total = $finder->total();
        $this->assertValidPage($page, $perPage, $total, 'warext-minecraft/audit');
        $logs = $finder->limitByPage($page, $perPage)->fetch();

        return $this->view('Warext\MinecraftVote:Audit\Index', 'warext_mc_admin_audit_index', [
            'logs' => $logs,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'serverId' => $serverId,
            'actionKey' => $actionKey
        ]);
    }
}
