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
        $db->beginTransaction();

        try
        {
            $db->delete('xf_warext_mc_server_category', 'server_id = ?', $server->server_id);
            $db->delete('xf_warext_mc_server_team', 'server_id = ?', $server->server_id);
            $db->delete('xf_warext_mc_ping_history', 'server_id = ?', $server->server_id);
            $db->delete('xf_warext_mc_vote', 'server_id = ?', $server->server_id);
            $db->delete('xf_warext_mc_votifier_config', 'server_id = ?', $server->server_id);
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
