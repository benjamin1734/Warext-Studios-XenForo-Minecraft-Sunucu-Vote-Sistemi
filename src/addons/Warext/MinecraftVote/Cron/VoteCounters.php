<?php

namespace Warext\MinecraftVote\Cron;

class VoteCounters
{
    public static function run(): void
    {
        $timeZoneId = (string)(\XF::options()->guestTimeZone ?? 'UTC');

        try
        {
            $timeZone = new \DateTimeZone($timeZoneId ?: 'UTC');
        }
        catch (\Throwable)
        {
            $timeZone = new \DateTimeZone('UTC');
        }

        $now = (new \DateTimeImmutable('@' . \XF::$time))->setTimezone($timeZone);
        $dayStart = $now->setTime(0, 0)->getTimestamp();
        $monthStart = $now->modify('first day of this month')->setTime(0, 0)->getTimestamp();
        $db = \XF::db();

        $rows = $db->fetchAll(
            "SELECT
                server_id,
                COUNT(*) AS vote_count_total,
                SUM(vote_date >= ?) AS vote_count_month,
                SUM(vote_date >= ?) AS vote_count_today
            FROM xf_warext_mc_vote
            WHERE status <> 'rejected'
            GROUP BY server_id",
            [$monthStart, $dayStart]
        );

        $db->update('xf_warext_mc_server', [
            'vote_count_total' => 0,
            'vote_count_month' => 0,
            'vote_count_today' => 0
        ]);

        foreach ($rows as $row)
        {
            $db->update('xf_warext_mc_server', [
                'vote_count_total' => (int)$row['vote_count_total'],
                'vote_count_month' => (int)$row['vote_count_month'],
                'vote_count_today' => (int)$row['vote_count_today']
            ], 'server_id = ?', (int)$row['server_id']);
        }

        $pingCutoff = \XF::$time - 35 * 86400;
        $db->delete('xf_warext_mc_ping_history', 'check_date < ?', $pingCutoff);
    }
}
