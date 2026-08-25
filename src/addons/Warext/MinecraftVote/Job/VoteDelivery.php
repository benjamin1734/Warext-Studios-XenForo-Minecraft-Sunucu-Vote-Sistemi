<?php

namespace Warext\MinecraftVote\Job;

use XF\Job\AbstractJob;

class VoteDelivery extends AbstractJob
{
    protected $defaultData = [
        'start' => 0,
        'batch' => 25
    ];

    public function run($maxRunTime)
    {
        $started = microtime(true);
        $finder = $this->app->finder('Warext\MinecraftVote:Vote')
            ->pendingDelivery(\XF::$time)
            ->where('vote_id', '>', (int)$this->data['start'])
            ->limit(min(max((int)$this->data['batch'], 1), 100));

        $votes = $finder->fetch();
        if (!$votes->count())
        {
            return $this->complete();
        }

        $processed = 0;

        foreach ($votes as $vote)
        {
            $this->data['start'] = $vote->vote_id;

            try
            {
                $delivery = $this->app->service('Warext\MinecraftVote:Votifier\Delivery', $vote);
                $delivery->deliver();
            }
            catch (\Throwable $e)
            {
                $this->app->logException($e, false, 'Warext Minecraft Vote delivery: ');
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
        return 'Minecraft oy ödülleri teslim ediliyor... (' . (int)$this->data['start'] . ')';
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
