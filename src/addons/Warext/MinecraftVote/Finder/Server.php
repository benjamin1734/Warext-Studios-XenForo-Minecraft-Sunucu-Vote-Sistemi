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

    public function orderByVotes(): self
    {
        $this->order('vote_count_month', 'DESC');
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
