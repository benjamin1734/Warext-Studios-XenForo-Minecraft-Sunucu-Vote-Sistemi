<?php

namespace Warext\MinecraftVote\Pub\Controller;

use XF\Pub\Controller\AbstractController;

class MyServers extends AbstractController
{
    public function actionIndex()
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            return $this->noPermission();
        }

        $rows = $this->app->db()->fetchAll(
            'SELECT DISTINCT s.server_id
             FROM xf_warext_mc_server AS s
             LEFT JOIN xf_warext_mc_server_team AS t ON (t.server_id = s.server_id)
             WHERE s.owner_user_id = ? OR t.user_id = ?
             ORDER BY s.last_update_date DESC, s.server_id DESC',
            [$visitor->user_id, $visitor->user_id]
        );

        $servers = [];
        foreach ($rows as $row)
        {
            $server = $this->em()->find('Warext\\MinecraftVote:Server', (int)$row['server_id']);
            if ($server)
            {
                $servers[] = $server;
            }
        }

        return $this->view('Warext\\MinecraftVote:Server\\Mine', 'warext_mc_server_mine', [
            'servers' => $servers
        ]);
    }
}
