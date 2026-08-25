<?php

namespace Warext\MinecraftVote\Service\Server;

use Warext\MinecraftVote\Entity\Server;
use XF\App;
use XF\Service\AbstractService;

class PingRecorder extends AbstractService
{
    protected Server $server;

    public function __construct(App $app, Server $server)
    {
        parent::__construct($app);
        $this->server = $server;
    }

    public function record(array $result): void
    {
        $db = $this->db();
        $db->beginTransaction();

        try
        {
            $isOnline = !empty($result['is_online']);

            $history = $this->em()->create('Warext\MinecraftVote:PingHistory');
            $history->server_id = $this->server->server_id;
            $history->check_date = \XF::$time;
            $history->is_online = $isOnline;
            $history->ping_ms = $isOnline ? max(0, (int)($result['ping_ms'] ?? 0)) : 0;
            $history->players_online = $isOnline ? max(0, (int)($result['players_online'] ?? 0)) : 0;
            $history->players_max = $isOnline ? max(0, (int)($result['players_max'] ?? 0)) : 0;
            $history->detected_version = $isOnline
                ? mb_substr(trim((string)($result['detected_version'] ?? '')), 0, 100)
                : '';
            $history->save();

            $this->server->is_online = $isOnline;
            $this->server->ping_ms = $history->ping_ms;
            $this->server->players_online = $history->players_online;
            $this->server->players_max = $history->players_max;
            $this->server->last_ping_date = \XF::$time;

            if ($isOnline)
            {
                $motd = trim((string)($result['motd'] ?? ''));
                $version = trim((string)($result['detected_version'] ?? ''));

                if ($motd !== '')
                {
                    $this->server->motd = mb_substr($motd, 0, 1000);
                }

                if ($version !== '')
                {
                    $this->server->detected_version = mb_substr($version, 0, 100);
                }
            }

            $cutoff = \XF::$time - 30 * 86400;
            $uptime = $db->fetchOne(
                'SELECT COALESCE(ROUND(AVG(is_online) * 10000), 0) FROM xf_warext_mc_ping_history WHERE server_id = ? AND check_date >= ?',
                [$this->server->server_id, $cutoff]
            );

            $this->server->uptime_bp = min(10000, max(0, (int)$uptime));
            $this->server->save();

            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }
    }
}
