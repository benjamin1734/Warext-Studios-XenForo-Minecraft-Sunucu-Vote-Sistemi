<?php

namespace Warext\MinecraftVote\Cron;

class Achievement
{
    public static function run(): void
    {
        $jobManager = \XF::app()->jobManager();
        $uniqueId = 'warextMinecraftAchievementRebuild';

        if (!$jobManager->getUniqueJob($uniqueId))
        {
            $jobManager->enqueueUnique(
                $uniqueId,
                'Warext\MinecraftVote:AchievementRebuild',
                [],
                false
            );
        }
    }
}
