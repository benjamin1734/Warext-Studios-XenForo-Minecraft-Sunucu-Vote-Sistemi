<?php

namespace Warext\MinecraftVote\Admin\Controller;

use Warext\MinecraftVote\Entity\Server as ServerEntity;
use Warext\MinecraftVote\Entity\Vote as VoteEntity;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Server extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
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

    public function actionVotes()
    {
        $status = $this->filter('status', 'str');
        $allowedStatuses = ['all', 'pending', 'retry', 'failed', 'delivered', 'skipped', 'rejected'];
        if (!in_array($status, $allowedStatuses, true))
        {
            $status = 'pending';
        }

        $page = $this->filterPage();
        $perPage = 50;

        $finder = $this->finder('Warext\MinecraftVote:Vote')
            ->with(['Server', 'User'])
            ->order('vote_date', 'DESC');

        if ($status !== 'all')
        {
            $finder->where('status', $status);
        }

        $total = $finder->total();
        $this->assertValidPage($page, $perPage, $total, 'warext-minecraft/votes');

        $votes = $finder
            ->limitByPage($page, $perPage)
            ->fetch();

        $statusRows = $this->db()->fetchAll(
            'SELECT status, COUNT(*) AS total FROM xf_warext_mc_vote GROUP BY status'
        );
        $statusCounts = array_fill_keys($allowedStatuses, 0);
        foreach ($statusRows as $row)
        {
            $statusCounts[(string)$row['status']] = (int)$row['total'];
            $statusCounts['all'] += (int)$row['total'];
        }

        return $this->view('Warext\MinecraftVote:Vote\Queue', 'warext_mc_admin_vote_queue', [
            'votes' => $votes,
            'status' => $status,
            'statusCounts' => $statusCounts,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total
        ]);
    }

    public function actionVoteRetry()
    {
        $this->assertPostOnly();

        $voteId = $this->filter('vote_id', 'uint');
        $vote = $this->assertVoteExists($voteId);

        if (!in_array($vote->status, ['pending', 'retry', 'failed', 'skipped'], true))
        {
            return $this->error('Bu oy teslimat için tekrar kuyruğa alınamaz.');
        }

        $vote->status = 'retry';
        $vote->attempt_count = 0;
        $vote->next_attempt_date = \XF::$time;
        $vote->delivered_date = 0;
        $vote->last_error = '';
        $vote->save();

        $this->enqueueVoteDelivery();

        return $this->redirect(
            $this->buildLink('warext-minecraft/votes', null, ['status' => 'retry']),
            'Oy teslimat kuyruğuna yeniden eklendi.'
        );
    }

    public function actionRunVoteQueue()
    {
        $this->assertPostOnly();
        $this->enqueueVoteDelivery();

        return $this->redirect(
            $this->buildLink('warext-minecraft/votes', null, ['status' => 'all']),
            'Oy teslimat işi kuyruğa alındı.'
        );
    }

    public function actionRanking()
    {
        $this->assertPostOnly();

        $result = $this->service('Warext\MinecraftVote:Ranking\Rebuilder')->rebuild();

        return $this->redirect(
            $this->buildLink('warext-minecraft', null, ['state' => 'all']),
            sprintf('%d aktif sunucu için Popüler ve Trend sıralamaları yeniden hesaplandı.', (int)$result['updated'])
        );
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
        $previousState = (string)$server->state;
        $server->state = $state;
        $server->save();

        if ($previousState !== $state)
        {
            $this->service('Warext\MinecraftVote:Audit\Logger')->log(
                'server_state_changed',
                $server->server_id,
                \XF::visitor()->user_id,
                $server->owner_user_id,
                [
                    'previous_state' => $previousState,
                    'new_state' => $state,
                    'server_title' => $server->title
                ]
            );
        }

        return $this->redirect($this->buildLink('warext-minecraft', null, [
            'state' => $state === 'pending' ? 'pending' : 'all'
        ]));
    }

    public function actionPing()
    {
        $this->assertPostOnly();

        $serverId = $this->filter('server_id', 'uint');
        $server = $this->assertServerExists($serverId);

        $pinger = $this->service('Warext\MinecraftVote:Server\Pinger', $server);
        $result = $pinger->ping();

        $recorder = $this->service('Warext\MinecraftVote:Server\PingRecorder', $server);
        $recorder->record($result);

        if (!empty($result['is_online']))
        {
            $message = sprintf(
                'Sunucu çevrimiçi. Ping: %d ms, Oyuncu: %d/%d, Sürüm: %s',
                (int)$result['ping_ms'],
                (int)$result['players_online'],
                (int)$result['players_max'],
                (string)($result['detected_version'] ?? '-')
            );
        }
        else
        {
            $message = 'Sunucuya ulaşılamadı: ' . (string)($result['error'] ?? 'Bilinmeyen hata');
        }

        return $this->redirect(
            $this->buildLink('warext-minecraft', null, ['state' => 'all']),
            $message
        );
    }

    public function actionDelete()
    {
        $this->assertPostOnly();

        $serverId = $this->filter('server_id', 'uint');
        $server = $this->assertServerExists($serverId);
        $db = $this->db();

        $updateRows = $db->fetchAll(
            'SELECT update_id FROM xf_warext_mc_server_update WHERE server_id = ?',
            [$server->server_id]
        );
        $alertRepo = $this->repository('XF:UserAlert');
        foreach ($updateRows as $row)
        {
            $alertRepo->fastDeleteAlertsForContent(
                'warext_mc_server_update',
                (int)$row['update_id']
            );
        }

        $serverTitle = (string)$server->title;
        $ownerUserId = (int)$server->owner_user_id;
        $this->service('Warext\MinecraftVote:Audit\Logger')->log(
            'server_deleted',
            $server->server_id,
            \XF::visitor()->user_id,
            $ownerUserId,
            [
                'server_title' => $serverTitle,
                'owner_user_id' => $ownerUserId
            ]
        );

        $db->beginTransaction();

        try
        {
            $db->delete('xf_warext_mc_sponsor', 'server_id = ?', $server->server_id);
            $db->delete('xf_warext_mc_server_achievement', 'server_id = ?', $server->server_id);
            $db->delete('xf_warext_mc_server_update', 'server_id = ?', $server->server_id);
            $db->delete('xf_warext_mc_favorite', 'server_id = ?', $server->server_id);
            $db->delete('xf_warext_mc_review', 'server_id = ?', $server->server_id);
            $db->delete('xf_warext_mc_server_category', 'server_id = ?', $server->server_id);
            $db->delete('xf_warext_mc_server_team', 'server_id = ?', $server->server_id);
            $db->delete('xf_warext_mc_ping_history', 'server_id = ?', $server->server_id);
            $db->delete('xf_warext_mc_vote', 'server_id = ?', $server->server_id);
            $db->delete('xf_warext_mc_votifier', 'server_id = ?', $server->server_id);
            $server->delete();
            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }

        return $this->redirect($this->buildLink('warext-minecraft'));
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

    protected function assertServerExists(int $serverId): ServerEntity
    {
        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId, ['Owner']);
        if (!$server)
        {
            throw $this->exception($this->notFound());
        }

        return $server;
    }

    protected function assertVoteExists(int $voteId): VoteEntity
    {
        $vote = $this->em()->find('Warext\MinecraftVote:Vote', $voteId, ['Server', 'User']);
        if (!$vote)
        {
            throw $this->exception($this->notFound());
        }

        return $vote;
    }
}
