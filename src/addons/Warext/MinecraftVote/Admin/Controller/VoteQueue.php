<?php

namespace Warext\MinecraftVote\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class VoteQueue extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertAdminPermission('warextMinecraftVote');
    }

    public function actionIndex()
    {
        $page = $this->filterPage();
        $perPage = 50;
        $status = strtolower(trim($this->filter('status', 'str')));
        $serverId = $this->filter('server_id', 'uint');
        $allowedStatuses = ['', 'pending', 'processing', 'retry', 'failed', 'delivered', 'rejected', 'skipped'];
        if (!in_array($status, $allowedStatuses, true))
        {
            $status = '';
        }

        $finder = $this->finder('Warext\MinecraftVote:Vote')
            ->with(['Server', 'User'])
            ->order('vote_id', 'DESC');
        if ($status !== '')
        {
            $finder->where('status', $status);
        }
        if ($serverId)
        {
            $finder->where('server_id', $serverId);
        }

        $total = $finder->total();
        $this->assertValidPage($page, $perPage, $total, 'warext-minecraft/vote-queue');
        $votes = $finder->limitByPage($page, $perPage)->fetch();

        $statsRows = $this->db()->fetchAll(
            'SELECT status, COUNT(*) AS total FROM xf_warext_mc_vote GROUP BY status'
        );
        $stats = [];
        foreach ($statsRows as $row)
        {
            $stats[(string)$row['status']] = (int)$row['total'];
        }

        $oldestPending = (int)$this->db()->fetchOne(
            "SELECT MIN(vote_date) FROM xf_warext_mc_vote WHERE status IN ('pending','processing','retry')"
        );

        return $this->view('Warext\MinecraftVote:VoteQueue\Index', 'warext_mc_admin_vote_queue', [
            'votes' => $votes,
            'stats' => $stats,
            'oldestPending' => $oldestPending,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'status' => $status,
            'serverId' => $serverId
        ]);
    }

    public function actionRetry(ParameterBag $params)
    {
        $this->assertPostOnly();
        $vote = $this->em()->find('Warext\MinecraftVote:Vote', (int)$params->vote_id);
        if (!$vote)
        {
            return $this->notFound();
        }

        if (!in_array($vote->status, ['failed', 'retry', 'processing', 'pending'], true))
        {
            return $this->error('Bu oy yeniden teslimat için uygun durumda değil.');
        }

        $vote->status = 'retry';
        $vote->attempt_count = 0;
        $vote->next_attempt_date = \XF::$time;
        $vote->last_error = '';
        $vote->save();
        $this->enqueueDelivery();

        return $this->redirect($this->buildLink('warext-minecraft/vote-queue'), 'Oy yeniden teslimat kuyruğuna alındı.');
    }

    public function actionRetryFailed()
    {
        $this->assertPostOnly();
        $count = $this->db()->update(
            'xf_warext_mc_vote',
            [
                'status' => 'retry',
                'attempt_count' => 0,
                'next_attempt_date' => \XF::$time,
                'last_error' => ''
            ],
            "status = 'failed'"
        );
        $this->enqueueDelivery();

        return $this->redirect($this->buildLink('warext-minecraft/vote-queue'), $count . ' başarısız oy yeniden kuyruğa alındı.');
    }

    protected function enqueueDelivery(): void
    {
        $jobManager = $this->app->jobManager();
        $uniqueId = 'warextMinecraftVoteDelivery';
        if (!$jobManager->getUniqueJob($uniqueId))
        {
            $jobManager->enqueueUnique($uniqueId, 'Warext\MinecraftVote:VoteDelivery', [], false);
        }
    }
}
