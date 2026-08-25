<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\MinecraftAccount;
use Warext\MinecraftVote\Entity\Server as ServerEntity;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Server extends AbstractController
{
    public function actionSezonlar()
    {
        $currentSeason = $this->service('Warext\MinecraftVote:Season\Manager')->ensureCurrentSeason();
        $currentTop = $this->finder('Warext\MinecraftVote:Server')
            ->activeOnly()
            ->orderByVotes()
            ->limit(10)
            ->fetch();

        $closedSeasons = $this->finder('Warext\MinecraftVote:Season')
            ->where('status', 'closed')
            ->order('start_date', 'DESC')
            ->limit(12)
            ->fetch();

        return $this->view('Warext\MinecraftVote:Season\Index', 'warext_mc_season_index', [
            'currentSeason' => $currentSeason,
            'currentTop' => $currentTop,
            'closedSeasons' => $closedSeasons
        ]);
    }

    public function actionSezon(ParameterBag $params)
    {
        $season = $this->em()->find('Warext\MinecraftVote:Season', (int)$params->season_id);
        if (!$season || $season->status !== 'closed')
        {
            throw $this->exception($this->notFound());
        }

        $ranks = $this->finder('Warext\MinecraftVote:SeasonRank')
            ->where('season_id', $season->season_id)
            ->order('rank', 'ASC')
            ->fetch();

        return $this->view('Warext\MinecraftVote:Season\View', 'warext_mc_season_view', [
            'season' => $season,
            'ranks' => $ranks
        ]);
    }

    public function actionHesaplar()
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            return $this->noPermission();
        }

        if ($this->isPost())
        {
            $this->assertNotFlooding('warext_mc_account_add', 10);

            $username = $this->filter('minecraft_username', 'str');
            $uuid = $this->filter('minecraft_uuid', 'str');

            try
            {
                $creator = $this->service('Warext\MinecraftVote:MinecraftAccount\Creator', $visitor);
                $creator->setData($username, $uuid);
                $creator->save();
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            return $this->redirect(
                $this->buildLink('sunucular/hesaplar'),
                'Minecraft hesabı profilinize eklendi.'
            );
        }

        $accounts = $this->repository('Warext\MinecraftVote:MinecraftAccount')
            ->findForUser($visitor->user_id)
            ->fetch();

        return $this->view('Warext\MinecraftVote:MinecraftAccount\List', 'warext_mc_account_list', [
            'accounts' => $accounts
        ]);
    }

    public function actionHesapSil(ParameterBag $params)
    {
        $this->assertPostOnly();

        $account = $this->assertOwnedMinecraftAccount((int)$params->account_id);
        $userId = $account->user_id;
        $wasPrimary = $account->is_primary;
        $wasVerified = $account->verification_state === 'verified';
        $account->delete();

        if ($wasPrimary)
        {
            $this->repository('Warext\MinecraftVote:MinecraftAccount')->promotePrimaryIfNeeded($userId);
        }

        if ($wasVerified)
        {
            $hasVerifiedAccount = (bool)$this->finder('Warext\MinecraftVote:MinecraftAccount')
                ->where('user_id', $userId)
                ->where('verification_state', 'verified')
                ->fetchOne();

            if (!$hasVerifiedAccount)
            {
                $this->db()->update(
                    'xf_warext_mc_review',
                    ['is_verified_player' => 0],
                    'user_id = ?',
                    $userId
                );
            }
        }

        return $this->redirect(
            $this->buildLink('sunucular/hesaplar'),
            'Minecraft hesabı bağlantısı kaldırıldı.'
        );
    }

    public function actionHesapBirincil(ParameterBag $params)
    {
        $this->assertPostOnly();

        $account = $this->assertOwnedMinecraftAccount((int)$params->account_id);
        $this->repository('Warext\MinecraftVote:MinecraftAccount')->makePrimary($account);

        return $this->redirect(
            $this->buildLink('sunucular/hesaplar'),
            'Birincil Minecraft hesabınız güncellendi.'
        );
    }

    public function actionDogrula(ParameterBag $params)
    {
        $server = $this->assertOwnedServer((int)$params->server_id);
        $verification = $this->service('Warext\MinecraftVote:Server\Verification', $server);

        if ($this->isPost())
        {
            $operation = $this->filter('operation', 'str');

            try
            {
                if ($operation === 'start')
                {
                    $method = $this->filter('method', 'str');
                    $verification->start($method);

                    return $this->redirect(
                        $this->buildLink('sunucular/dogrula', $server),
                        'Yeni sunucu doğrulama kodu oluşturuldu.'
                    );
                }

                if ($operation === 'verify')
                {
                    $result = $verification->verify();

                    return $this->redirect(
                        $this->buildLink('sunucular/detay', $server),
                        (string)$result['message']
                    );
                }
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            return $this->error('Geçersiz doğrulama işlemi.', 400);
        }

        return $this->view('Warext\MinecraftVote:Server\Verification', 'warext_mc_server_verification', [
            'server' => $server,
            'dnsRecordName' => $server->verification_method === 'dns_txt' && $server->verification_token
                ? $verification->getDnsRecordName()
                : '',
            'dnsRecordValue' => $server->verification_method === 'dns_txt' && $server->verification_token
                ? $verification->getDnsRecordValue()
                : '',
            'tokenLifetimeHours' => $verification->getTokenLifetimeHours()
        ]);
    }

    protected function assertOwnedMinecraftAccount(int $accountId): MinecraftAccount
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            throw $this->exception($this->noPermission());
        }

        $account = $this->repository('Warext\MinecraftVote:MinecraftAccount')
            ->getForUser($accountId, $visitor->user_id);
        if (!$account)
        {
            throw $this->exception($this->notFound());
        }

        return $account;
    }

    protected function assertOwnedServer(int $serverId): ServerEntity
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            throw $this->exception($this->noPermission());
        }

        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId, ['Owner']);
        if (!$server)
        {
            throw $this->exception($this->notFound());
        }

        if ($server->owner_user_id !== $visitor->user_id)
        {
            throw $this->exception($this->noPermission());
        }

        return $server;
    }
}
