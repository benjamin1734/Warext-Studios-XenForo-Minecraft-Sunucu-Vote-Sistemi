<?php

namespace Warext\MinecraftVote\Job;

use XF\Job\AbstractJob;

class AchievementRebuild extends AbstractJob
{
    protected $defaultData = [
        'start' => 0,
        'batch' => 25,
        'awarded' => 0
    ];

    public function run($maxRunTime)
    {
        $started = microtime(true);
        $servers = $this->app->finder('Warext\MinecraftVote:Server')
            ->where('state', 'active')
            ->where('server_id', '>', (int)$this->data['start'])
            ->order('server_id', 'ASC')
            ->fetch(min(max((int)$this->data['batch'], 5), 100));

        if (!$servers->count())
        {
            return $this->complete();
        }

        $processed = 0;
        foreach ($servers as $server)
        {
            $this->data['start'] = $server->server_id;

            try
            {
                $evaluator = $this->app->service('Warext\MinecraftVote:Achievement\Evaluator', $server);
                $this->data['awarded'] += $evaluator->evaluate();
            }
            catch (\Throwable $e)
            {
                $this->app->logException($e, false, 'Warext Minecraft Vote achievement: ');
            }

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
            100
        );

        return $this->resume();
    }

    public function getStatusMessage()
    {
        return 'Minecraft sunucu başarımları hesaplanıyor... (' . (int)$this->data['start'] . ')';
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
