<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\Server;
use Warext\MinecraftVote\Security\PublicPermissions;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Vote extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $server = $this->assertActiveServer((int)$params->server_id);
        $visitor = \XF::visitor();
        $allowGuests = (bool)(\XF::options()->warextMcAllowGuestVotes ?? true);

        if (!PublicPermissions::allows('vote', $allowGuests, true))
        {
            return $this->noPermission();
        }
        if (!$visitor->user_id && !$allowGuests)
        {
            return $this->noPermission();
        }

        $linkedAccounts = $visitor->user_id
            ? $this->repository('Warext\MinecraftVote:MinecraftAccount')->findForUser($visitor->user_id)->fetch()
            : [];

        if ($this->isPost())
        {
            try
            {
                $this->service('Warext\MinecraftVote:RateLimit\Request')->assertIp(
                    'warextMcVoteIp',
                    (int)$server->server_id,
                    (string)$this->request->getIp(),
                    3
                );
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 429);
            }

            $accountId = $this->filter('minecraft_account_id', 'uint');
            $username = $this->filter('minecraft_username', 'str');
            $uuid = $this->filter('minecraft_uuid', 'str');

            if ($accountId)
            {
                if (!$visitor->user_id)
                {
                    return $this->noPermission();
                }

                $linkedAccount = $this->repository('Warext\MinecraftVote:MinecraftAccount')
                    ->getForUser($accountId, $visitor->user_id);
                if (!$linkedAccount)
                {
                    return $this->error('Seçilen Minecraft hesabı bulunamadı.', 404);
                }

                $username = $linkedAccount->minecraft_username;
                $uuid = $linkedAccount->minecraft_uuid;
            }

            try
            {
                $creator = $this->service('Warext\MinecraftVote:Vote\Creator', $server, $visitor);
                $creator->setIdentity($username, $uuid);
                $creator->setRequestFingerprint(
                    (string)$this->request->getIp(),
                    (string)$this->request->getServer('HTTP_USER_AGENT', '')
                );
                $creator->create();
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            $this->enqueueVoteDelivery();

            return $this->redirect(
                $this->buildLink('sunucular/detay', $server),
                'Oyunuz kaydedildi. Sunucu ödül entegrasyonu aktifse ödül teslimatı kuyruğa alındı.'
            );
        }

        return $this->view('Warext\MinecraftVote:Server\Vote', 'warext_mc_server_vote', [
            'server' => $server,
            'cooldownHours' => min(168, max(1, (int)(\XF::options()->warextMcVoteCooldownHours ?? 24))),
            'allowGuests' => $allowGuests,
            'linkedAccounts' => $linkedAccounts
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

    protected function enqueueVoteDelivery(): void
    {
        $jobManager = $this->app->jobManager();
        $uniqueId = 'warextMinecraftVoteDelivery';

        if (!$jobManager->getUniqueJob($uniqueId))
        {
            $jobManager->enqueueUnique(
                $uniqueId,
                'Warext\MinecraftVote:VoteDelivery',
                [],
                false
            );
        }
    }
}
