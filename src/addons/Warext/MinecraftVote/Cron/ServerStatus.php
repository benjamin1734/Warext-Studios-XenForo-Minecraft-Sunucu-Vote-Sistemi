<?php

namespace Warext\MinecraftVote\Cron;

class ServerStatus
{
    public static function run(): void
    {
        $jobManager = \XF::app()->jobManager();
        $uniqueId = 'warextMinecraftVoteServerPing';

        if ($jobManager->getUniqueJob($uniqueId))
        {
            return;
        }

        $jobManager->enqueueUnique(
            $uniqueId,
            'Warext\MinecraftVote:ServerPing',
            [],
            false
        );
    }
}
