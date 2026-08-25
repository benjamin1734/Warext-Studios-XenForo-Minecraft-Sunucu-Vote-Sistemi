<?php

namespace Warext\MinecraftVote\Cron;

class VoteDelivery
{
    public static function run(): void
    {
        $jobManager = \XF::app()->jobManager();
        $uniqueId = 'warextMinecraftVoteDelivery';

        if ($jobManager->getUniqueJob($uniqueId))
        {
            return;
        }

        $jobManager->enqueueUnique(
            $uniqueId,
            'Warext\MinecraftVote:VoteDelivery',
            [],
            false
        );
    }
}
