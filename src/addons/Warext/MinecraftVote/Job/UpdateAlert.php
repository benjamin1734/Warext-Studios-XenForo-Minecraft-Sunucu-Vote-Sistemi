<?php

namespace Warext\MinecraftVote\Job;

use XF\Job\AbstractJob;

class UpdateAlert extends AbstractJob
{
    protected $defaultData = [
        'update_id' => 0,
        'start_user_id' => 0,
        'batch' => 100
    ];

    public function run($maxRunTime)
    {
        $updateId = (int)$this->data['update_id'];
        if ($updateId <= 0)
        {
            return $this->complete();
        }

        $update = $this->app->em()->find('Warext\MinecraftVote:ServerUpdate', $updateId, ['Server', 'User']);
        if (!$update || $update->state !== 'visible' || !$update->Server || $update->Server->state !== 'active')
        {
            return $this->complete();
        }

        $started = microtime(true);
        $favorites = $this->app->finder('Warext\MinecraftVote:Favorite')
            ->where('server_id', $update->server_id)
            ->where('notify_updates', 1)
            ->where('user_id', '>', (int)$this->data['start_user_id'])
            ->with('User')
            ->order('user_id', 'ASC')
            ->fetch(min(max((int)$this->data['batch'], 10), 250));

        if (!$favorites->count())
        {
            return $this->complete();
        }

        $alertRepo = $this->app->repository('XF:UserAlert');
        $processed = 0;

        foreach ($favorites as $favorite)
        {
            $this->data['start_user_id'] = $favorite->user_id;
            $receiver = $favorite->User;

            if ($receiver && $receiver->user_id && (int)$receiver->user_id !== (int)$update->user_id)
            {
                $alertRepo->fastDeleteAlertsToUser(
                    $receiver->user_id,
                    'warext_mc_server_update',
                    $update->update_id,
                    'publish'
                );
                $alertRepo->alertFromUser(
                    $receiver,
                    $update->User,
                    'warext_mc_server_update',
                    $update->update_id,
                    'publish',
                    [],
                    ['dependsOnAddOnId' => 'Warext/MinecraftVote']
                );
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
            250
        );

        return $this->resume();
    }

    public function getStatusMessage()
    {
        return 'Minecraft sunucu güncelleme bildirimleri gönderiliyor...';
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
