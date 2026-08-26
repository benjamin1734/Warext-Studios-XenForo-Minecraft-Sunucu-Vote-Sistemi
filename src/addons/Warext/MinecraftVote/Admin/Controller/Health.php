<?php

namespace Warext\MinecraftVote\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Health extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertAdminPermission('warextMinecraftVote');
    }

    public function actionIndex()
    {
        $now = \XF::$time;
        $statusRows = $this->db()->fetchAll('SELECT status, COUNT(*) AS total FROM xf_warext_mc_vote GROUP BY status');
        $voteCounts = [];
        foreach ($statusRows as $row)
        {
            $voteCounts[(string)$row['status']] = (int)$row['total'];
        }

        $oldestPending = (int)$this->db()->fetchOne(
            "SELECT MIN(vote_date) FROM xf_warext_mc_vote WHERE status IN ('pending','processing','retry')"
        );
        $staleProcessing = (int)$this->db()->fetchOne(
            "SELECT COUNT(*) FROM xf_warext_mc_vote WHERE status = 'processing' AND next_attempt_date > 0 AND next_attempt_date <= ?",
            [$now]
        );
        $offlineServers = (int)$this->db()->fetchOne(
            "SELECT COUNT(*) FROM xf_warext_mc_server WHERE state = 'active' AND is_online = 0"
        );
        $staleServers = (int)$this->db()->fetchOne(
            "SELECT COUNT(*) FROM xf_warext_mc_server WHERE state = 'active' AND (last_ping_date = 0 OR last_ping_date < ?)",
            [$now - 3600]
        );

        $votifier = $this->db()->fetchAll(
            'SELECT v.server_id, v.enabled, v.host, v.port, v.service_name, v.last_success_date, v.last_error, s.title '
            . 'FROM xf_warext_mc_votifier AS v LEFT JOIN xf_warext_mc_server AS s ON (s.server_id = v.server_id) '
            . 'ORDER BY v.enabled DESC, v.last_success_date DESC LIMIT 100'
        );

        return $this->view('Warext\MinecraftVote:Health\Index', 'warext_mc_admin_health', [
            'voteCounts' => $voteCounts,
            'oldestPending' => $oldestPending,
            'staleProcessing' => $staleProcessing,
            'offlineServers' => $offlineServers,
            'staleServers' => $staleServers,
            'votifier' => $votifier
        ]);
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
        $this->enqueueVoteDelivery();

        return $this->redirect($this->buildLink('warext-minecraft/health'), $count . ' başarısız oy yeniden kuyruğa alındı.');
    }

    public function actionRecoverStale()
    {
        $this->assertPostOnly();
        $count = $this->db()->update(
            'xf_warext_mc_vote',
            [
                'status' => 'retry',
                'next_attempt_date' => \XF::$time,
                'last_error' => 'Süresi dolmuş processing kaydı otomatik kurtarıldı.'
            ],
            "status = 'processing' AND next_attempt_date > 0 AND next_attempt_date <= ?",
            [\XF::$time]
        );
        $this->enqueueVoteDelivery();

        return $this->redirect($this->buildLink('warext-minecraft/health'), $count . ' takılı teslimat kurtarıldı.');
    }

    protected function enqueueVoteDelivery(): void
    {
        $jobManager = $this->app->jobManager();
        $uniqueId = 'warextMinecraftVoteDelivery';
        if (!$jobManager->getUniqueJob($uniqueId))
        {
            $jobManager->enqueueUnique($uniqueId, 'Warext\MinecraftVote:VoteDelivery', [], false);
        }
    }
}
