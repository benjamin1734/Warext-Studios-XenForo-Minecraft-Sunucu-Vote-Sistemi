<?php

namespace Warext\MinecraftVote\Service\Achievement;

use Warext\MinecraftVote\Entity\Server;
use XF\App;
use XF\Service\AbstractService;

class Evaluator extends AbstractService
{
    protected Server $server;

    public function __construct(App $app, Server $server)
    {
        parent::__construct($app);
        $this->server = $server;
    }

    public function evaluate(): int
    {
        if (!$this->server->server_id || $this->server->state !== 'active')
        {
            return 0;
        }

        $achievementRepo = $this->repository('Warext\MinecraftVote:Achievement');
        $achievements = $achievementRepo->findActive()->fetch();
        if (!$achievements->count())
        {
            return 0;
        }

        $metricCache = [];
        $awarded = 0;

        foreach ($achievements as $achievement)
        {
            if ($achievementRepo->getAward($this->server->server_id, $achievement->achievement_id))
            {
                continue;
            }

            $metric = $achievement->metric;
            if (!array_key_exists($metric, $metricCache))
            {
                $metricCache[$metric] = $this->getMetricValue($metric);
            }

            $value = (int)$metricCache[$metric];
            if (!$this->meetsThreshold($metric, $value, (int)$achievement->threshold))
            {
                continue;
            }

            $award = $this->em()->create('Warext\MinecraftVote:ServerAchievement');
            $award->server_id = $this->server->server_id;
            $award->achievement_id = $achievement->achievement_id;
            $award->metric_value = max(0, $value);
            $award->source = 'automatic';
            $award->save();
            $awarded++;
        }

        return $awarded;
    }

    protected function getMetricValue(string $metric): int
    {
        switch ($metric)
        {
            case 'vote_total':
                return (int)$this->server->vote_count_total;

            case 'uptime_bp':
                return (int)$this->server->uptime_bp;

            case 'peak_players':
                return (int)$this->db()->fetchOne(
                    'SELECT COALESCE(MAX(players_online), 0) FROM xf_warext_mc_ping_history WHERE server_id = ?',
                    $this->server->server_id
                );

            case 'age_days':
                if (!$this->server->created_date)
                {
                    return 0;
                }
                return intdiv(max(0, \XF::$time - (int)$this->server->created_date), 86400);

            case 'verified':
                return $this->server->is_verified ? 1 : 0;

            case 'season_wins':
                return (int)$this->db()->fetchOne(
                    'SELECT COUNT(*) FROM xf_warext_mc_season_rank WHERE server_id = ? AND rank = 1',
                    $this->server->server_id
                );

            case 'trend_rank_max':
                return (int)$this->server->rank_trending;
        }

        return 0;
    }

    protected function meetsThreshold(string $metric, int $value, int $threshold): bool
    {
        if ($metric === 'trend_rank_max')
        {
            return $value > 0 && $value <= $threshold;
        }

        return $value >= $threshold;
    }
}
