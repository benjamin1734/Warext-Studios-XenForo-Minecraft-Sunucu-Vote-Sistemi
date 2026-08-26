<?php

namespace Warext\MinecraftVote\Pub\Controller;

use XF\Pub\Controller\AbstractController;

class Sitemap extends AbstractController
{
    public function actionIndex()
    {
        $servers = $this->finder('Warext\\MinecraftVote:Server')
            ->where('state', 'active')
            ->order('server_id', 'ASC')
            ->limit(50000)
            ->fetch();

        $entries = [];
        foreach ($servers as $server)
        {
            $entries[] = [
                'loc' => $this->buildLink('canonical:sunucular/detay', $server),
                'lastmod' => (int)($server->last_update_date ?: $server->created_date)
            ];
        }

        $this->setResponseType('raw');
        return $this->view('Warext\\MinecraftVote:Sitemap\\Index', '', [
            'entries' => $entries
        ]);
    }
}
