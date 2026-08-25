<?php

namespace Warext\MinecraftVote\Finder;

use XF\Mvc\Entity\Finder;

class Server extends Finder
{
    public function activeOnly(): self
    {
        $this->where('state', 'active');
        return $this;
    }

    public function verifiedOnly(): self
    {
        $this->where('is_verified', 1);
        return $this;
    }

    public function onlineOnly(): self
    {
        $this->where('is_online', 1);
        return $this;
    }

    public function ownedBy(int $userId): self
    {
        $this->where('owner_user_id', $userId);
        return $this;
    }

    public function searchText(string $query): self
    {
        $query = trim($query);
        if ($query === '')
        {
            return $this;
        }

        $needle = '%' . $query . '%';
        $this->whereOr([
            ['title', 'LIKE', $needle],
            ['host', 'LIKE', $needle],
            ['game_modes', 'LIKE', $needle],
            ['description', 'LIKE', $needle]
        ]);

        return $this;
    }

    public function inCategory(int $categoryId): self
    {
        if ($categoryId <= 0)
        {
            return $this;
        }

        $serverIdColumn = $this->columnSqlName('server_id');
        $this->whereSql(
            'EXISTS (SELECT 1 FROM xf_warext_mc_server_category AS warext_mc_sc'
            . ' WHERE warext_mc_sc.server_id = ' . $serverIdColumn
            . ' AND warext_mc_sc.category_id = ' . $categoryId . ')'
        );

        return $this;
    }

    public function inCountry(string $countryCode): self
    {
        $countryCode = strtoupper(trim($countryCode));
        if ($countryCode !== '')
        {
            $this->where('country_code', $countryCode);
        }

        return $this;
    }

    public function matchingVersion(string $version): self
    {
        $version = trim($version);
        if ($version === '')
        {
            return $this;
        }

        $needle = '%' . $version . '%';
        $this->whereOr([
            ['detected_version', 'LIKE', $needle],
            ['version_min', 'LIKE', $needle],
            ['version_max', 'LIKE', $needle]
        ]);

        return $this;
    }

    public function matchingGameMode(string $gameMode): self
    {
        $gameMode = trim($gameMode);
        if ($gameMode !== '')
        {
            $this->where('game_modes', 'LIKE', '%' . $gameMode . '%');
        }

        return $this;
    }

    public function minimumPlayers(int $minimum): self
    {
        if ($minimum > 0)
        {
            $this->where('players_online', '>=', $minimum);
        }

        return $this;
    }

    public function premiumMode(string $mode): self
    {
        if ($mode === 'yes')
        {
            $this->where('is_premium', 1);
        }
        elseif ($mode === 'no')
        {
            $this->where('is_premium', 0);
        }

        return $this;
    }

    public function crackedMode(string $mode): self
    {
        if ($mode === 'yes')
        {
            $this->where('allow_cracked', 1);
        }
        elseif ($mode === 'no')
        {
            $this->where('allow_cracked', 0);
        }

        return $this;
    }

    public function orderByPopularity(): self
    {
        $this->order('popular_score_bp', 'DESC');
        $this->order('vote_count_month', 'DESC');
        $this->order('server_id', 'ASC');
        return $this;
    }

    public function orderByTrend(): self
    {
        $this->order('trend_score_bp', 'DESC');
        $this->order('votes_24h', 'DESC');
        $this->order('server_id', 'ASC');
        return $this;
    }

    public function orderByVotes(): self
    {
        $this->order('vote_count_month', 'DESC');
        $this->order('unique_voters_month', 'DESC');
        $this->order('server_id', 'ASC');
        return $this;
    }

    public function orderByPlayers(): self
    {
        $this->order('players_online', 'DESC');
        $this->order('server_id', 'ASC');
        return $this;
    }

    public function newestFirst(): self
    {
        $this->order('created_date', 'DESC');
        return $this;
    }
}
