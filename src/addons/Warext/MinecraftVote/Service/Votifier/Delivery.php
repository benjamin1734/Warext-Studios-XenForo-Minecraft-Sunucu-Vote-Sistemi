<?php

namespace Warext\MinecraftVote\Service\Votifier;

use Warext\MinecraftVote\Entity\Vote;
use Warext\MinecraftVote\Entity\VotifierConfig;
use Warext\MinecraftVote\Network\VotifierV2Client;
use Warext\MinecraftVote\Security\SecretCipher;
use XF\App;
use XF\Service\AbstractService;

class Delivery extends AbstractService
{
    protected Vote $vote;
    protected int $leaseSeconds = 300;

    public function __construct(App $app, Vote $vote)
    {
        parent::__construct($app);
        $this->vote = $vote;
    }

    public function deliver(): string
    {
        if (!$this->claimForDelivery())
        {
            return (string)$this->vote->status;
        }

        $server = $this->vote->Server;
        if (!$server)
        {
            return $this->markFailed('Oy kaydına bağlı sunucu bulunamadı.');
        }

        $config = $this->em()->find('Warext\MinecraftVote:VotifierConfig', $server->server_id);
        if (!$config || !$config->enabled)
        {
            $this->vote->status = 'skipped';
            $this->vote->last_error = '';
            $this->vote->next_attempt_date = 0;
            $this->vote->save();
            return 'skipped';
        }

        if (!$config->token_encrypted)
        {
            return $this->handleFailure($config, 'NuVotifier token yapılandırılmamış.');
        }

        try
        {
            $cipher = new SecretCipher();
            $client = new VotifierV2Client(null, 3.0);
            $client->send(
                $config->host ?: $server->host,
                $config->port,
                $cipher->decrypt((string)$config->token_encrypted),
                $this->vote->minecraft_username,
                $config->service_name ?: 'Warext',
                '0.0.0.0',
                $this->vote->vote_date * 1000
            );

            $this->vote->status = 'delivered';
            $this->vote->delivered_date = \XF::$time;
            $this->vote->next_attempt_date = 0;
            $this->vote->last_error = '';
            $this->vote->save();

            $config->last_success_date = \XF::$time;
            $config->last_error = '';
            $config->save();

            return 'delivered';
        }
        catch (\Throwable $e)
        {
            return $this->handleFailure($config, $e->getMessage());
        }
    }

    protected function claimForDelivery(): bool
    {
        $db = $this->db();
        $now = \XF::$time;
        $db->beginTransaction();

        try
        {
            $row = $db->fetchRow(
                'SELECT status, attempt_count, next_attempt_date FROM xf_warext_mc_vote WHERE vote_id = ? FOR UPDATE',
                $this->vote->vote_id
            );

            if (!$row)
            {
                $db->commit();
                return false;
            }

            $status = (string)$row['status'];
            $nextAttempt = (int)$row['next_attempt_date'];
            $eligible = in_array($status, ['pending', 'retry'], true)
                || ($status === 'processing' && $nextAttempt <= $now);

            if (!$eligible || $nextAttempt > $now)
            {
                $this->vote->status = $status;
                $this->vote->attempt_count = (int)$row['attempt_count'];
                $this->vote->next_attempt_date = $nextAttempt;
                $db->commit();
                return false;
            }

            $this->vote->status = 'processing';
            $this->vote->attempt_count = (int)$row['attempt_count'] + 1;
            $this->vote->next_attempt_date = $now + $this->leaseSeconds;
            $this->vote->last_error = '';
            $this->vote->save();

            $db->commit();
            return true;
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }
    }

    protected function handleFailure(VotifierConfig $config, string $message): string
    {
        $message = trim($message) ?: 'Bilinmeyen NuVotifier teslimat hatası.';
        $maxAttempts = min(10, max(1, (int)(\XF::options()->warextMcVotifierMaxAttempts ?? 5)));

        $config->last_error = mb_substr($message, 0, 500);
        $config->save();

        if ($this->vote->attempt_count >= $maxAttempts)
        {
            return $this->markFailed($message);
        }

        $delays = [60, 300, 900, 3600, 10800, 21600, 43200, 86400, 86400, 86400];
        $delayIndex = min(max($this->vote->attempt_count - 1, 0), count($delays) - 1);

        $this->vote->status = 'retry';
        $this->vote->next_attempt_date = \XF::$time + $delays[$delayIndex];
        $this->vote->last_error = mb_substr($message, 0, 255);
        $this->vote->save();

        return 'retry';
    }

    protected function markFailed(string $message): string
    {
        $this->vote->status = 'failed';
        $this->vote->next_attempt_date = 0;
        $this->vote->last_error = mb_substr(trim($message), 0, 255);
        $this->vote->save();

        return 'failed';
    }
}
