<?php

namespace Warext\MinecraftVote\Job;

use XF\Job\AbstractJob;

class WebhookDelivery extends AbstractJob
{
    protected $defaultData = [
        'vote_id' => 0,
        'attempt' => 0
    ];

    public function run($maxRunTime)
    {
        $voteId = (int)$this->data['vote_id'];
        if ($voteId <= 0)
        {
            return $this->complete();
        }

        $vote = $this->app->em()->find('Warext\\MinecraftVote:Vote', $voteId, ['Server']);
        if (!$vote)
        {
            return $this->complete();
        }

        try
        {
            $this->app->service('Warext\\MinecraftVote:Webhook\\Dispatcher')->dispatchVote($vote);
            return $this->complete();
        }
        catch (\Throwable $e)
        {
            $this->data['attempt'] = (int)$this->data['attempt'] + 1;
            $this->app->logException($e, false, 'Warext Minecraft Vote webhook: ');

            if ($this->data['attempt'] >= 3)
            {
                return $this->complete();
            }

            return $this->resume();
        }
    }

    public function getStatusMessage()
    {
        return 'Minecraft vote webhook teslimatı yapılıyor...';
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
