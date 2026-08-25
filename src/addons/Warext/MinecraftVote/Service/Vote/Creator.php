<?php

namespace Warext\MinecraftVote\Service\Vote;

use Warext\MinecraftVote\Entity\Server;
use Warext\MinecraftVote\Entity\Vote;
use Warext\MinecraftVote\Repository\Vote as VoteRepository;
use XF\App;
use XF\Entity\User;
use XF\PrintableException;
use XF\Service\AbstractService;

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
            throw new PrintableException('Minecraft kullanıcı adı 3-16 karakter olmalı ve yalnızca harf, rakam veya alt çizgi içermelidir.');
        }

        $this->minecraftUsername = $username;
        $this->minecraftUuid = $this->normalizeUuid($uuid);
    }

    public function setRequestFingerprint(string $ip, string $userAgent = ''): void
    {
        $salt = (string)\XF::config('globalSalt');
        if ($salt === '')
        {
            throw new \RuntimeException('XenForo globalSalt yapılandırması bulunamadı.');
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
            throw new PrintableException('Bu sunucu şu anda oy kabul etmiyor.');
        }

        if ($this->minecraftUsername === '')
        {
            throw new PrintableException('Minecraft kullanıcı adı gereklidir.');
        }

        if (!$this->user->user_id && !(bool)(\XF::options()->warextMcAllowGuestVotes ?? true))
        {
            throw new PrintableException('Oy verebilmek için giriş yapmanız gerekiyor.');
        }

        $db = $this->db();
        $db->beginTransaction();

        try
        {
            $db->fetchOne(
                'SELECT server_id FROM xf_warext_mc_server WHERE server_id = ? FOR UPDATE',
                $this->server->server_id
            );

            $cooldownHours = min(168, max(1, (int)(\XF::options()->warextMcVoteCooldownHours ?? 24)));
            $since = \XF::$time - ($cooldownHours * 3600);

            /** @var VoteRepository $voteRepo */
            $voteRepo = $this->repository('Warext\MinecraftVote:Vote');
            $this->assertCooldown($voteRepo, $since, $cooldownHours);

            $fraudScore = $this->calculateFraudScore($voteRepo);

            /** @var Vote $vote */
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

    protected function assertCooldown(VoteRepository $voteRepo, int $since, int $cooldownHours): void
    {
        if ($this->user->user_id && $voteRepo->hasRecentUserVote($this->server->server_id, $this->user->user_id, $since))
        {
            throw new PrintableException("Bu sunucuya son {$cooldownHours} saat içinde zaten oy verdiniz.");
        }

        if ($voteRepo->hasRecentMinecraftUsernameVote($this->server->server_id, $this->minecraftUsername, $since))
        {
            throw new PrintableException("Bu Minecraft kullanıcı adıyla son {$cooldownHours} saat içinde zaten oy verilmiş.");
        }

        if ($this->minecraftUuid !== '' && $voteRepo->hasRecentMinecraftVote($this->server->server_id, $this->minecraftUuid, $since))
        {
            throw new PrintableException("Bu Minecraft hesabıyla son {$cooldownHours} saat içinde zaten oy verilmiş.");
        }

        if (!$this->user->user_id && $this->ipHash !== null
            && $voteRepo->hasRecentIpVote($this->server->server_id, $this->ipHash, $since))
        {
            throw new PrintableException("Bu bağlantı üzerinden son {$cooldownHours} saat içinde zaten oy verilmiş.");
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
            throw new PrintableException('Minecraft UUID biçimi geçersiz.');
        }

        return substr($hex, 0, 8) . '-'
            . substr($hex, 8, 4) . '-'
            . substr($hex, 12, 4) . '-'
            . substr($hex, 16, 4) . '-'
            . substr($hex, 20, 12);
    }
}
