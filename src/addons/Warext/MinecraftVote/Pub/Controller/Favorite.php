<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\Server;
use Warext\MinecraftVote\Security\PublicPermissions;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Favorite extends AbstractController
{
    protected function canUseFavorites(): bool
    {
        return \XF::visitor()->user_id > 0 && PublicPermissions::allows('favorite', false, true);
    }

    public function actionToggle(ParameterBag $params)
    {
        $this->assertPostOnly();
        if (!$this->canUseFavorites())
        {
            return $this->noPermission();
        }

        $visitor = \XF::visitor();
        $server = $this->assertActiveServer((int)$params->server_id);
        $active = $this->repository('Warext\MinecraftVote:Favorite')
            ->toggle($server, $visitor->user_id);

        return $this->redirect(
            $this->buildLink('sunucular/detay', $server),
            $active ? 'Sunucu favorilerinize eklendi.' : 'Sunucu favorilerinizden çıkarıldı.'
        );
    }

    public function actionNotify(ParameterBag $params)
    {
        $this->assertPostOnly();
        if (!$this->canUseFavorites())
        {
            return $this->noPermission();
        }

        $visitor = \XF::visitor();
        $server = $this->assertActiveServer((int)$params->server_id);
        $enabled = $this->filter('enabled', 'bool');
        $updated = $this->repository('Warext\MinecraftVote:Favorite')
            ->setUpdateNotifications($server->server_id, $visitor->user_id, $enabled);

        if (!$updated)
        {
            return $this->error('Bildirim ayarını değiştirmek için sunucu favorilerinizde olmalıdır.', 400);
        }

        return $this->redirect(
            $this->buildLink('sunucular/favoriler'),
            $enabled ? 'Sunucu güncelleme bildirimleri açıldı.' : 'Sunucu güncelleme bildirimleri kapatıldı.'
        );
    }

    public function actionIndex()
    {
        if (!$this->canUseFavorites())
        {
            return $this->noPermission();
        }

        $visitor = \XF::visitor();
        $repo = $this->repository('Warext\MinecraftVote:Favorite');
        $favorites = $repo->findForUser($visitor->user_id)->fetch();
        $unreadCounts = $repo->getUnreadUpdateCounts($visitor->user_id);
        $entries = [];

        foreach ($favorites as $favorite)
        {
            $entries[] = [
                'favorite' => $favorite,
                'unread_count' => (int)($unreadCounts[$favorite->server_id] ?? 0)
            ];
        }

        return $this->view('Warext\MinecraftVote:Favorite\Index', 'warext_mc_favorite_index', [
            'entries' => $entries
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
