<?php

namespace Warext\MinecraftVote\Cron;

class ServerStatus
{
    public static function run(): void
    {
        \XF::app()->jobManager()->enqueueUnique(
            'warextMinecraftVoteServerPing',
            'Warext\MinecraftVote:ServerPing',
            [],
            false
        );
    }
}
