<?php

namespace Warext\MinecraftVote\Job;

use XF\Job\AbstractJob;

class ServerPing extends AbstractJob
{
    protected $defaultData = [
        'server_id' => 0,
        'start' => 0,
        'batch' => 10
    ];

    public function run($maxRunTime)
    {
        $started = microtime(true);

        if (!empty($this->data['server_id']))
        {
            $server = $this->app->em()->find('Warext\MinecraftVote:Server', (int)$this->data['server_id']);
            if ($server)
            {
                $this->pingServer($server);
            }

            return $this->complete();
        }

        $servers = $this->app->finder('Warext\MinecraftVote:Server')
            ->where('state', 'active')
            ->where('server_id', '>', (int)$this->data['start'])
            ->order('server_id', 'ASC')
            ->fetch(min(max((int)$this->data['batch'], 1), 25));

        if (!$servers->count())
        {
            return $this->complete();
        }

        $processed = 0;

        foreach ($servers as $server)
        {
            $this->data['start'] = $server->server_id;
            $this->pingServer($server);
            $processed++;

            if (microtime(true) - $started >= max(1.0, $maxRunTime - 0.5))
            {
                break;
            }
        }

        $this->data['batch'] = $this->calculateOptimalBatch(
            (int)$this->data['batch'],
            $processed,
            $started,
            $maxRunTime,
            25
        );

        return $this->resume();
    }

    protected function pingServer($server): void
    {
        try
        {
            $pinger = $this->app->service('Warext\MinecraftVote:Server\Pinger', $server);
            $result = $pinger->ping();

            $recorder = $this->app->service('Warext\MinecraftVote:Server\PingRecorder', $server);
            $recorder->record($result);
        }
        catch (\Throwable $e)
        {
            $this->app->logException($e, false, 'Warext Minecraft Vote ping: ');
        }
    }

    public function getStatusMessage()
    {
        return 'Minecraft sunucuları kontrol ediliyor... (' . (int)$this->data['start'] . ')';
    }

    public function canCancel()
    {
        return true;
    }

    public function canTriggerByChoice()
    {
        return false;
    }
}
