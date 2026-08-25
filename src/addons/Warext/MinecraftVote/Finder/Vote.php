<?php

namespace Warext\MinecraftVote\Finder;

use XF\Mvc\Entity\Finder;

class Vote extends Finder
{
    public function forServer(int $serverId): self
    {
        $this->where('server_id', $serverId);
        return $this;
    }

    public function forUser(int $userId): self
    {
        $this->where('user_id', $userId);
        return $this;
    }

    public function pendingDelivery(int $now): self
    {
        $this->where('status', ['pending', 'retry', 'processing']);
        $this->where('next_attempt_date', '<=', $now);
        $this->order('vote_id', 'ASC');
        return $this;
    }

    public function since(int $timestamp): self
    {
        $this->where('vote_date', '>=', $timestamp);
        return $this;
    }
}
