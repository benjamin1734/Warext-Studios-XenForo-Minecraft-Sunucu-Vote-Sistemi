<?php

namespace Warext\MinecraftVote\Service\Vote;

use Warext\MinecraftVote\Entity\Server;
use Warext\MinecraftVote\Entity\Vote;
use Warext\MinecraftVote\Repository\Vote as VoteRepository;
use XF\App;
use XF\Entity\User;
use XF\PrintableException;
use XF\Service\AbstractService;
use XF\Service\FloodCheckService;

class Creator extends AbstractService
{
    protected Server $server;
    protected User $user;
    protected string $minecraftUsername = '';
    protected string $minecraftUuid = '';
    protected ?string $ipHash = null;
    protected ?string $userAgentHash = null;

    public function __construct(App $app, Server $server, User $user)
    {
        parent::__construct($app);
        $this->server = $server;
        $this->user = $user;
    }

    public function setIdentity(string $username, string $uuid = ''): void
    {
        $username = trim($username);
        if (!preg_match('/^[A-Za-z0-9_]{3,16}$/', $username))
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_minecraft_username_format'));
        }

        $this->minecraftUsername = $username;
        $this->minecraftUuid = $this->normalizeUuid($uuid);
    }

    public function setRequestFingerprint(string $ip, string $userAgent = ''): void
    {
        $salt = (string)\XF::config('globalSalt');
        if ($salt === '')
        {
            throw new \RuntimeException((string)\XF::phrase('warext_mc_dyn_globalsalt_missing'));
        }

        $ip = trim($ip);
        if ($ip !== '')
        {
            $this->ipHash = hash_hmac('sha256', $ip, $salt, true);
        }

        $userAgent = trim($userAgent);
        if ($userAgent !== '')
        {
            $this->userAgentHash = hash_hmac('sha256', mb_substr($userAgent, 0, 1000), $salt, true);
        }
    }

    public function create(): Vote
    {
        if ($this->server->state !== 'active')
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_server_not_accepting_votes'));
        }

        if ($this->minecraftUsername === '')
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_minecraft_username_required'));
        }

        if (!$this->user->user_id)
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_vote_login_required'));
        }

        $this->assertRequestRate();

        $db = $this->db();
        $db->beginTransaction();

        try
        {
            $db->fetchOne(
                'SELECT server_id FROM xf_warext_mc_server WHERE server_id = ? FOR UPDATE',
                $this->server->server_id
            );

            $cooldownHours = 24;
            $since = \XF::$time - 86400;

            $voteRepo = $this->repository('Warext\MinecraftVote:Vote');
            $this->assertIpVelocity($voteRepo);
            $this->assertCooldown($voteRepo, $since, $cooldownHours);

            $fraudScore = $this->calculateFraudScore($voteRepo);

            $vote = $this->em()->create('Warext\MinecraftVote:Vote');
            $vote->server_id = $this->server->server_id;
            $vote->user_id = $this->user->user_id ?: 0;
            $vote->minecraft_username = $this->minecraftUsername;
            $vote->minecraft_uuid = $this->minecraftUuid;
            $vote->ip_hash = $this->ipHash;
            $vote->user_agent_hash = $this->userAgentHash;
            $vote->vote_date = \XF::$time;
            $vote->status = 'pending';
            $vote->next_attempt_date = \XF::$time;
            $vote->fraud_score = $fraudScore;
            $vote->source = 'web';
            $vote->save();

            $voteRepo->rebuildServerCounters($this->server);

            $db->commit();
            return $vote;
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }
    }

    protected function assertRequestRate(): void
    {
        if (!$this->user->user_id || $this->user->hasPermission('general', 'bypassFloodCheck'))
        {
            return;
        }

        $floodChecker = $this->service(FloodCheckService::class);
        $remaining = (int)$floodChecker->checkFlooding(
            'warextMinecraftVote',
            $this->user->user_id,
            5
        );

        if ($remaining > 0)
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_vote_rate_seconds', ['seconds' => $remaining]));
        }
    }

    protected function assertIpVelocity(VoteRepository $voteRepo): void
    {
        if ($this->ipHash === null || $this->user->hasPermission('general', 'bypassFloodCheck'))
        {
            return;
        }

        $serverId = (int)$this->server->server_id;
        $lastTenMinutes = $voteRepo->countRecentIpActivity($serverId, $this->ipHash, \XF::$time - 600);
        if ($lastTenMinutes >= 12)
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_vote_ip_burst'));
        }

        $lastHour = $voteRepo->countRecentIpActivity($serverId, $this->ipHash, \XF::$time - 3600);
        if ($lastHour >= 40)
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_vote_ip_hourly'));
        }
    }

    protected function assertCooldown(VoteRepository $voteRepo, int $since, int $cooldownHours): void
    {
        if ($this->user->user_id && $voteRepo->hasRecentUserVote($this->server->server_id, $this->user->user_id, $since))
        {
            throw new PrintableException(\XF::phrase('warext_mc_vote_cooldown_24h_error'));
        }

        if ($voteRepo->hasRecentMinecraftUsernameVote($this->server->server_id, $this->minecraftUsername, $since))
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_vote_username_cooldown', ['hours' => $cooldownHours]));
        }

        if ($this->minecraftUuid !== '' && $voteRepo->hasRecentMinecraftVote($this->server->server_id, $this->minecraftUuid, $since))
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_vote_account_cooldown', ['hours' => $cooldownHours]));
        }

        if (!$this->user->user_id && $this->ipHash !== null
            && $voteRepo->hasRecentIpVote($this->server->server_id, $this->ipHash, $since))
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_vote_ip_cooldown', ['hours' => $cooldownHours]));
        }
    }

    protected function calculateFraudScore(VoteRepository $voteRepo): int
    {
        $score = 0;

        if ($this->ipHash === null)
        {
            $score += 20;
        }
        else
        {
            $recentFromIp = $voteRepo->countRecentIpVotes(
                $this->server->server_id,
                $this->ipHash,
                \XF::$time - 86400
            );
            $score += min(60, $recentFromIp * 20);
        }

        if (!$this->user->user_id)
        {
            $score += 15;
        }
        elseif ($this->user->register_date && (int)$this->user->register_date >= \XF::$time - 86400)
        {
            $score += 10;
        }

        if ($this->minecraftUuid === '')
        {
            $score += 10;
        }

        if ($this->userAgentHash === null)
        {
            $score += 5;
        }

        return min(100, $score);
    }

    protected function normalizeUuid(string $uuid): string
    {
        $uuid = strtolower(trim($uuid));
        if ($uuid === '')
        {
            return '';
        }

        $hex = str_replace('-', '', $uuid);
        if (!preg_match('/^[a-f0-9]{32}$/', $hex))
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_invalid_minecraft_uuid'));
        }

        return substr($hex, 0, 8) . '-'
            . substr($hex, 8, 4) . '-'
            . substr($hex, 12, 4) . '-'
            . substr($hex, 16, 4) . '-'
            . substr($hex, 20, 12);
    }
}
