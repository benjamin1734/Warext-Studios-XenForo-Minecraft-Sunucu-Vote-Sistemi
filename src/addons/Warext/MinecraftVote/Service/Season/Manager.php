<?php

namespace Warext\MinecraftVote\Service\Season;

use Warext\MinecraftVote\Entity\Season;
use XF\Service\AbstractService;

class Manager extends AbstractService
{
    public function maintain(): void
    {
        $this->ensureCurrentSeason();

        $expired = $this->finder('Warext\MinecraftVote:Season')
            ->where('status', 'open')
            ->where('end_date', '<=', \XF::$time)
            ->order('end_date', 'ASC')
            ->fetch();

        foreach ($expired as $season)
        {
            $this->finalizeSeason($season);
        }
    }

    public function ensureCurrentSeason(): Season
    {
        [$key, $start, $end] = $this->getMonthWindow(\XF::$time);

        $season = $this->finder('Warext\MinecraftVote:Season')
            ->where('season_key', $key)
            ->fetchOne();

        if ($season)
        {
            return $season;
        }

        $season = $this->em()->create('Warext\MinecraftVote:Season');
        $season->season_key = $key;
        $season->start_date = $start;
        $season->end_date = $end;
        $season->status = 'open';
        $season->save();

        return $season;
    }

    public function finalizeSeason(Season $season): void
    {
        if ($season->status === 'closed')
        {
            return;
        }

        $db = $this->db();
        $db->beginTransaction();

        try
        {
            $lockedStatus = $db->fetchOne(
                'SELECT status FROM xf_warext_mc_season WHERE season_id = ? FOR UPDATE',
                $season->season_id
            );

            if ($lockedStatus === 'closed')
            {
                $db->commit();
                return;
            }

            $voteRows = $db->fetchAll(
                "SELECT v.server_id,
                        COUNT(*) AS vote_count,
                        COUNT(DISTINCT CASE
                            WHEN v.minecraft_uuid <> '' THEN CONCAT('m:', LOWER(v.minecraft_uuid))
                            WHEN v.user_id > 0 THEN CONCAT('u:', v.user_id)
                            ELSE CONCAT('n:', LOWER(v.minecraft_username))
                        END) AS unique_voters
                 FROM xf_warext_mc_vote AS v
                 INNER JOIN xf_warext_mc_server AS s ON s.server_id = v.server_id
                 WHERE v.status <> 'rejected'
                   AND v.vote_date >= ?
                   AND v.vote_date < ?
                   AND s.state <> 'rejected'
                 GROUP BY v.server_id",
                [$season->start_date, $season->end_date]
            );

            $pingRows = $db->fetchAll(
                "SELECT server_id,
                        MAX(players_online) AS peak_players,
                        ROUND((SUM(is_online) / COUNT(*)) * 10000) AS uptime_bp
                 FROM xf_warext_mc_ping_history
                 WHERE check_date >= ? AND check_date < ?
                 GROUP BY server_id",
                [$season->start_date, $season->end_date]
            );

            $pingByServer = [];
            foreach ($pingRows as $row)
            {
                $pingByServer[(int)$row['server_id']] = [
                    'peak_players' => max(0, (int)$row['peak_players']),
                    'uptime_bp' => min(10000, max(0, (int)$row['uptime_bp']))
                ];
            }

            $rows = [];
            $maxVotes = 1;
            $maxUnique = 1;
            $maxPeak = 1;

            foreach ($voteRows as $row)
            {
                $serverId = (int)$row['server_id'];
                $ping = $pingByServer[$serverId] ?? ['peak_players' => 0, 'uptime_bp' => 0];
                $record = [
                    'server_id' => $serverId,
                    'vote_count' => max(0, (int)$row['vote_count']),
                    'unique_voters' => max(0, (int)$row['unique_voters']),
                    'peak_players' => $ping['peak_players'],
                    'uptime_bp' => $ping['uptime_bp']
                ];

                $rows[] = $record;
                $maxVotes = max($maxVotes, $record['vote_count']);
                $maxUnique = max($maxUnique, $record['unique_voters']);
                $maxPeak = max($maxPeak, $record['peak_players']);
            }

            foreach ($rows as &$row)
            {
                $row['season_score_bp'] = (int)round(
                    (($row['vote_count'] / $maxVotes) * 6000)
                    + (($row['unique_voters'] / $maxUnique) * 2500)
                    + (($row['uptime_bp'] / 10000) * 1000)
                    + (($row['peak_players'] / $maxPeak) * 500)
                );
            }
            unset($row);

            usort($rows, static function (array $a, array $b): int
            {
                return ($b['vote_count'] <=> $a['vote_count'])
                    ?: ($b['unique_voters'] <=> $a['unique_voters'])
                    ?: ($b['uptime_bp'] <=> $a['uptime_bp'])
                    ?: ($b['peak_players'] <=> $a['peak_players'])
                    ?: ($a['server_id'] <=> $b['server_id']);
            });

            $db->delete('xf_warext_mc_season_rank', 'season_id = ?', $season->season_id);

            foreach ($rows as $index => $row)
            {
                $rank = $this->em()->create('Warext\MinecraftVote:SeasonRank');
                $rank->season_id = $season->season_id;
                $rank->server_id = $row['server_id'];
                $rank->rank = $index + 1;
                $rank->vote_count = $row['vote_count'];
                $rank->unique_voters = $row['unique_voters'];
                $rank->uptime_bp = $row['uptime_bp'];
                $rank->peak_players = $row['peak_players'];
                $rank->season_score_bp = $row['season_score_bp'];
                $rank->snapshot_date = \XF::$time;
                $rank->save();
            }

            $platformUnique = (int)$db->fetchOne(
                "SELECT COUNT(DISTINCT CASE
                    WHEN minecraft_uuid <> '' THEN CONCAT('m:', LOWER(minecraft_uuid))
                    WHEN user_id > 0 THEN CONCAT('u:', user_id)
                    ELSE CONCAT('n:', LOWER(minecraft_username))
                 END)
                 FROM xf_warext_mc_vote
                 WHERE status <> 'rejected' AND vote_date >= ? AND vote_date < ?",
                [$season->start_date, $season->end_date]
            );

            $season->winner_server_id = $rows[0]['server_id'] ?? 0;
            $season->total_votes = array_sum(array_column($rows, 'vote_count'));
            $season->unique_voters = $platformUnique;
            $season->server_count = count($rows);
            $season->status = 'closed';
            $season->finalized_date = \XF::$time;
            $season->save();

            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }
    }

    public function getMonthWindow(int $timestamp): array
    {
        $timeZone = $this->getTimeZone();
        $now = (new \DateTimeImmutable('@' . $timestamp))->setTimezone($timeZone);
        $start = $now->modify('first day of this month')->setTime(0, 0);
        $end = $start->modify('first day of next month');

        return [
            $start->format('Y-m'),
            $start->getTimestamp(),
            $end->getTimestamp()
        ];
    }

    protected function getTimeZone(): \DateTimeZone
    {
        $timeZoneId = (string)(\XF::options()->guestTimeZone ?? 'UTC');

        try
        {
            return new \DateTimeZone($timeZoneId ?: 'UTC');
        }
        catch (\Throwable)
        {
            return new \DateTimeZone('UTC');
        }
    }
}
