<?php

namespace Warext\MinecraftVote\Pub\Controller;

use XF\Pub\Controller\AbstractController;

class Compare extends AbstractController
{
    public function actionIndex()
    {
        $serverIds = $this->filter('server_ids', 'array-uint');
        $serverIds = array_values(array_unique(array_filter($serverIds)));

        if (count($serverIds) > 4)
        {
            $serverIds = array_slice($serverIds, 0, 4);
        }

        $servers = [];
        if ($serverIds)
        {
            $servers = $this->finder('Warext\MinecraftVote:Server')
                ->where('state', 'active')
                ->where('server_id', $serverIds)
                ->fetch();
        }

        $availableServers = $this->finder('Warext\MinecraftVote:Server')
            ->where('state', 'active')
            ->order('popular_score_bp', 'DESC')
            ->order('server_id', 'ASC')
            ->limit(200)
            ->fetch();

        return $this->view('Warext\MinecraftVote:Server\Compare', 'warext_mc_server_compare', [
            'servers' => $servers,
            'availableServers' => $availableServers,
            'selectedIds' => $serverIds
        ]);
    }
}
