<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\Server;
use Warext\MinecraftVote\Security\PublicPermissions;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Detail extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $server = $this->assertViewableServer((int)$params->server_id);

        if ($server->state === 'active')
        {
            $this->repository('Warext\MinecraftVote:Server')->incrementViewCount($server);
        }

        $achievements = $this->repository('Warext\MinecraftVote:Achievement')
            ->findForServer($server->server_id)
            ->fetch();
        $visitor = \XF::visitor();
        $allowGuests = (bool)(\XF::options()->warextMcAllowGuestVotes ?? true);

        return $this->view('Warext\MinecraftVote:Server\View', 'warext_mc_server_view', [
            'server' => $server,
            'achievements' => $achievements,
            'canVote' => $server->state === 'active' && PublicPermissions::allows('vote', $allowGuests, true),
            'canFavorite' => $server->state === 'active' && $visitor->user_id && PublicPermissions::allows('favorite', false, true),
            'canReview' => $server->state === 'active' && PublicPermissions::allows('review', true, true),
            'canReport' => $server->state === 'active' && $visitor->user_id && !$server->is_owner && PublicPermissions::allows('report', false, true)
        ]);
    }

    protected function assertViewableServer(int $serverId): Server
    {
        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId, ['Owner']);
        if (!$server)
        {
            throw $this->exception($this->notFound());
        }

        if ($server->state === 'active')
        {
            return $server;
        }

        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            throw $this->exception($this->notFound());
        }

        if ($server->is_owner
            || $server->can_edit
            || $server->can_publish_updates
            || $server->can_view_stats
            || $server->can_manage_votifier
            || $server->can_manage_reviews)
        {
            return $server;
        }

        throw $this->exception($this->notFound());
    }
}
