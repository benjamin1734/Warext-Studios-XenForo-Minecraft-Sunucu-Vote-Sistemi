<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\Server;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Analytics extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $server = $this->assertCanView((int)$params->server_id);
        $now = \XF::$time;
        $day = 86400;

        $voteStats = $this->db()->fetchRow(
            "SELECT
                COUNT(*) AS votes_30d,
                COUNT(DISTINCT CASE WHEN minecraft_uuid <> '' THEN minecraft_uuid ELSE CONCAT('u:', user_id, ':', minecraft_username) END) AS unique_voters_30d,
                SUM(status = 'delivered') AS delivered_30d,
                SUM(status = 'failed') AS failed_30d,
                SUM(status = 'retry') AS retry_30d,
                SUM(vote_date >= ?) AS votes_7d,
                SUM(vote_date >= ?) AS votes_24h
             FROM xf_warext_mc_vote
             WHERE server_id = ? AND vote_date >= ? AND status <> 'rejected'",
            [$now - 7 * $day, $now - $day, $server->server_id, $now - 30 * $day]
        );

        $pingStats = $this->db()->fetchRow(
            'SELECT
                COUNT(*) AS checks_7d,
                SUM(is_online = 1) AS online_checks_7d,
                MAX(players_online) AS peak_players_7d,
                ROUND(AVG(CASE WHEN is_online = 1 THEN players_online END), 1) AS avg_players_7d,
                ROUND(AVG(CASE WHEN is_online = 1 THEN ping_ms END), 1) AS avg_ping_7d
             FROM xf_warext_mc_ping_history
             WHERE server_id = ? AND check_date >= ?',
            [$server->server_id, $now - 7 * $day]
        );

        $dailyVotes = $this->db()->fetchAll(
            "SELECT DATE(FROM_UNIXTIME(vote_date)) AS vote_day, COUNT(*) AS total
             FROM xf_warext_mc_vote
             WHERE server_id = ? AND vote_date >= ? AND status <> 'rejected'
             GROUP BY DATE(FROM_UNIXTIME(vote_date))
             ORDER BY vote_day ASC",
            [$server->server_id, $now - 14 * $day]
        );

        $favorites = $this->db()->fetchOne(
            'SELECT COUNT(*) FROM xf_warext_mc_favorite WHERE server_id = ?',
            [$server->server_id]
        );
        $reviews = $this->db()->fetchOne(
            "SELECT COUNT(*) FROM xf_warext_mc_review WHERE server_id = ? AND state = 'visible'",
            [$server->server_id]
        );

        $delivered = (int)($voteStats['delivered_30d'] ?? 0);
        $failed = (int)($voteStats['failed_30d'] ?? 0);
        $retry = (int)($voteStats['retry_30d'] ?? 0);
        $deliveryTotal = $delivered + $failed + $retry;
        $deliveryRate = $deliveryTotal > 0 ? round($delivered / $deliveryTotal * 100, 1) : 0.0;

        $checks = (int)($pingStats['checks_7d'] ?? 0);
        $onlineChecks = (int)($pingStats['online_checks_7d'] ?? 0);
        $uptime7d = $checks > 0 ? round($onlineChecks / $checks * 100, 2) : 0.0;

        return $this->view('Warext\MinecraftVote:Analytics\Index', 'warext_mc_analytics_index', [
            'server' => $server,
            'voteStats' => $voteStats,
            'pingStats' => $pingStats,
            'dailyVotes' => $dailyVotes,
            'favorites' => (int)$favorites,
            'reviews' => (int)$reviews,
            'deliveryRate' => $deliveryRate,
            'uptime7d' => $uptime7d
        ]);
    }

    protected function assertCanView(int $serverId): Server
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            throw $this->exception($this->noPermission());
        }

        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId);
        if (!$server)
        {
            throw $this->exception($this->notFound());
        }

        if (!$this->repository('Warext\MinecraftVote:ServerTeam')
            ->hasPermission($server, $visitor->user_id, 'view_stats'))
        {
            throw $this->exception($this->noPermission());
        }

        return $server;
    }
}
