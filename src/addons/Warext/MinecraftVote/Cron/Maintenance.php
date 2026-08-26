<?php

namespace Warext\MinecraftVote\Cron;

class Maintenance
{
    public static function run(): void
    {
        $app = \XF::app();
        $db = $app->db();
        $now = \XF::$time;

        $retentionDays = min(365, max(7, (int)(\XF::options()->warextMcPingHistoryRetentionDays ?? 30)));
        $cutoff = $now - ($retentionDays * 86400);

        $db->query(
            'DELETE FROM xf_warext_mc_ping_history WHERE check_date < ? LIMIT 10000',
            [$cutoff]
        );

        $recovered = $db->update(
            'xf_warext_mc_vote',
            [
                'status' => 'retry',
                'next_attempt_date' => $now,
                'last_error' => 'Süresi dolmuş teslimat lease kaydı otomatik kurtarıldı.'
            ],
            "status = 'processing' AND next_attempt_date > 0 AND next_attempt_date <= ?",
            [$now]
        );

        if ($recovered > 0)
        {
            $jobManager = $app->jobManager();
            $uniqueId = 'warextMinecraftVoteDelivery';
            if (!$jobManager->getUniqueJob($uniqueId))
            {
                $jobManager->enqueueUnique(
                    $uniqueId,
                    'Warext\\MinecraftVote:VoteDelivery',
                    [],
                    false
                );
            }
        }
    }
}
