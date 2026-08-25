<?php

namespace Warext\MinecraftVote\Service\Ranking;

use XF\Service\AbstractService;

class Rebuilder extends AbstractService
{
    public function rebuild(): array
    {
        $now = \XF::$time;
        [$dayStart, $monthStart] = $this->getCounterBoundaries();
        $since24h = $now - 86400;
        $since72h = $now - 259200;
        $previous72h = $now - 518400;
        $scanStart = min($monthStart, $previous72h, $dayStart);
        $db = $this->db();

        $servers = $db->fetchAll(
            "SELECT server_id, players_online, uptime_bp, view_count
             FROM xf_warext_mc_server
             WHERE state = 'active'"
        );

        if (!$servers)
        {
            $db->query(
                "UPDATE xf_warext_mc_server
                 SET unique_voters_month = 0, votes_24h = 0, votes_72h = 0,
                     popular_score_bp = 0, trend_score_bp = 0,
                     rank_popular = 0, rank_trending = 0, ranking_updated_date = ?",
                $now
            );

            $this->app->service('Warext\MinecraftVote:Season\Manager')->maintain();

            return ['servers' => 0, 'updated' => 0];
        }

        $voteRows = $db->fetchAll(
            "SELECT server_id,
                    SUM(CASE WHEN vote_date >= ? THEN 1 ELSE 0 END) AS vote_month,
                    SUM(CASE WHEN vote_date >= ? THEN 1 ELSE 0 END) AS vote_today,
                    SUM(CASE WHEN vote_date >= ? THEN 1 ELSE 0 END) AS votes_24h,
                    SUM(CASE WHEN vote_date >= ? THEN 1 ELSE 0 END) AS votes_72h,
                    SUM(CASE WHEN vote_date >= ? AND vote_date < ? THEN 1 ELSE 0 END) AS votes_previous_72h,
                    COUNT(DISTINCT CASE WHEN vote_date >= ? THEN
                        CASE
                            WHEN minecraft_uuid <> '' THEN CONCAT('m:', LOWER(minecraft_uuid))
                            WHEN user_id > 0 THEN CONCAT('u:', user_id)
                            ELSE CONCAT('n:', LOWER(minecraft_username))
                        END
                    END) AS unique_voters_month
             FROM xf_warext_mc_vote
             WHERE status <> 'rejected' AND vote_date >= ?
             GROUP BY server_id",
            [
                $monthStart,
                $dayStart,
                $since24h,
                $since72h,
                $previous72h,
                $since72h,
                $monthStart,
                $scanStart
            ]
        );

        $votesByServer = [];
        foreach ($voteRows as $row)
        {
            $votesByServer[(int)$row['server_id']] = [
                'vote_month' => (int)$row['vote_month'],
                'vote_today' => (int)$row['vote_today'],
                'votes_24h' => (int)$row['votes_24h'],
                'votes_72h' => (int)$row['votes_72h'],
                'votes_previous_72h' => (int)$row['votes_previous_72h'],
                'unique_voters_month' => (int)$row['unique_voters_month']
            ];
        }

        $metrics = [];
        $maxVotes = 1;
        $maxUnique = 1;
        $maxPlayers = 1;
        $maxVotes24h = 1;
        $maxLogViews = 1.0;

        foreach ($servers as $server)
        {
            $serverId = (int)$server['server_id'];
            $vote = $votesByServer[$serverId] ?? [
                'vote_month' => 0,
                'vote_today' => 0,
                'votes_24h' => 0,
                'votes_72h' => 0,
                'votes_previous_72h' => 0,
                'unique_voters_month' => 0
            ];

            $metrics[$serverId] = [
                'server_id' => $serverId,
                'players_online' => max(0, (int)$server['players_online']),
                'uptime_bp' => min(10000, max(0, (int)$server['uptime_bp'])),
                'view_count' => max(0, (int)$server['view_count']),
                ...$vote
            ];

            $maxVotes = max($maxVotes, $vote['vote_month']);
            $maxUnique = max($maxUnique, $vote['unique_voters_month']);
            $maxPlayers = max($maxPlayers, (int)$server['players_online']);
            $maxVotes24h = max($maxVotes24h, $vote['votes_24h']);
            $maxLogViews = max($maxLogViews, log1p(max(0, (int)$server['view_count'])));
        }

        foreach ($metrics as &$metric)
        {
            $votesNorm = $metric['vote_month'] / $maxVotes;
            $uniqueNorm = $metric['unique_voters_month'] / $maxUnique;
            $playersNorm = $metric['players_online'] / $maxPlayers;
            $uptimeNorm = $metric['uptime_bp'] / 10000;
            $engagementNorm = log1p($metric['view_count']) / $maxLogViews;

            $metric['popular_score_bp'] = (int)round(
                ($votesNorm * 4500)
                + ($uniqueNorm * 2000)
                + ($playersNorm * 1500)
                + ($uptimeNorm * 1000)
                + ($engagementNorm * 1000)
            );

            $previous = $metric['votes_previous_72h'];
            $current = $metric['votes_72h'];
            if ($current <= 0)
            {
                $growthNorm = 0.0;
            }
            elseif ($previous <= 0)
            {
                $growthNorm = min(1.0, $current / 5);
            }
            else
            {
                $growthNorm = min(1.0, max(0.0, ($current - $previous) / max(3, $previous)));
            }

            $votes24Norm = $metric['votes_24h'] / $maxVotes24h;
            $metric['trend_score_bp'] = (int)round(
                ($growthNorm * 5000)
                + ($votes24Norm * 2500)
                + ($playersNorm * 1500)
                + ($uptimeNorm * 1000)
            );
        }
        unset($metric);

        $popular = array_values($metrics);
        usort($popular, static function (array $a, array $b): int
        {
            return ($b['popular_score_bp'] <=> $a['popular_score_bp'])
                ?: ($b['vote_month'] <=> $a['vote_month'])
                ?: ($b['unique_voters_month'] <=> $a['unique_voters_month'])
                ?: ($a['server_id'] <=> $b['server_id']);
        });

        $trending = array_values($metrics);
        usort($trending, static function (array $a, array $b): int
        {
            return ($b['trend_score_bp'] <=> $a['trend_score_bp'])
                ?: ($b['votes_24h'] <=> $a['votes_24h'])
                ?: ($b['votes_72h'] <=> $a['votes_72h'])
                ?: ($a['server_id'] <=> $b['server_id']);
        });

        $popularRanks = [];
        foreach ($popular as $index => $row)
        {
            $popularRanks[$row['server_id']] = $index + 1;
        }

        $trendRanks = [];
        foreach ($trending as $index => $row)
        {
            $trendRanks[$row['server_id']] = $index + 1;
        }

        $db->beginTransaction();
        try
        {
            $db->query(
                "UPDATE xf_warext_mc_server
                 SET unique_voters_month = 0, votes_24h = 0, votes_72h = 0,
                     popular_score_bp = 0, trend_score_bp = 0,
                     rank_popular = 0, rank_trending = 0, ranking_updated_date = ?
                 WHERE state <> 'active'",
                $now
            );

            foreach ($metrics as $serverId => $metric)
            {
                $db->update('xf_warext_mc_server', [
                    'vote_count_month' => $metric['vote_month'],
                    'vote_count_today' => $metric['vote_today'],
                    'unique_voters_month' => $metric['unique_voters_month'],
                    'votes_24h' => $metric['votes_24h'],
                    'votes_72h' => $metric['votes_72h'],
                    'popular_score_bp' => $metric['popular_score_bp'],
                    'trend_score_bp' => $metric['trend_score_bp'],
                    'rank_popular' => $popularRanks[$serverId] ?? 0,
                    'rank_trending' => $trendRanks[$serverId] ?? 0,
                    'ranking_updated_date' => $now
                ], 'server_id = ?', $serverId);
            }

            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }

        $this->app->service('Warext\MinecraftVote:Season\Manager')->maintain();

        return ['servers' => count($servers), 'updated' => count($metrics)];
    }

    protected function getCounterBoundaries(): array
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

        return [
            $now->setTime(0, 0)->getTimestamp(),
            $now->modify('first day of this month')->setTime(0, 0)->getTimestamp()
        ];
    }
}
