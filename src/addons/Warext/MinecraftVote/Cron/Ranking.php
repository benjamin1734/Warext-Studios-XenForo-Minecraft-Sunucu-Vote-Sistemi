<?php

namespace Warext\MinecraftVote\Cron;

class Ranking
{
    public static function run(): void
    {
        \XF::app()->service('Warext\MinecraftVote:Ranking\Rebuilder')->rebuild();
    }
}
