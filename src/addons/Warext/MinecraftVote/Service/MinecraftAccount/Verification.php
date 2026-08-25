<?php

namespace Warext\MinecraftVote\Service\MinecraftAccount;

use Warext\MinecraftVote\Entity\MinecraftAccount;
use Warext\MinecraftVote\Security\VerificationBridgeKey;
use XF\App;
use XF\PrintableException;
use XF\Service\AbstractService;

class Verification extends AbstractService
{
    private const CHALLENGE_PREFIX = 'W';
    private const TIMESTAMP_LENGTH = 7;
    private const RANDOM_LENGTH = 8;
    private const LIFETIME = 600;
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    protected MinecraftAccount $account;

    public function __construct(App $app, MinecraftAccount $account)
    {
        parent::__construct($app);
        $this->account = $account;
    }

    public function start(): array
    {
        $bridge = new VerificationBridgeKey();
        if (!$bridge->isEnabled())
        {
            throw new PrintableException('Minecraft hesap doğrulama köprüsü şu anda kapalı.');
        }

        if ($this->account->verification_state === 'verified')
        {
            throw new PrintableException('Bu Minecraft hesabı zaten doğrulanmış.');
        }

        $code = $this->generateChallenge();
        $this->account->verification_state = 'pending';
        $this->account->verification_method = 'trusted_bridge';
        $this->account->verification_code = $this->hashChallenge($code);
        $this->account->verified_date = 0;
        $this->account->save();

        return [
            'code' => $code,
            'expires' => \XF::$time + self::LIFETIME
        ];
    }

    public function complete(string $code, string $uuid, string $username): MinecraftAccount
    {
        $code = strtoupper(trim($code));
        $uuid = $this->normalizeUuid($uuid);
        $username = trim($username);

        $this->assertFreshChallenge($code);

        if (!preg_match('/^[A-Za-z0-9_]{3,16}$/', $username))
        {
            throw new PrintableException('Minecraft kullanıcı adı geçersiz.');
        }

        $hash = $this->hashChallenge($code);
        $account = $this->finder('Warext\MinecraftVote:MinecraftAccount')
            ->where('verification_state', 'pending')
            ->where('verification_method', 'trusted_bridge')
            ->where('verification_code', $hash)
            ->fetchOne();

        if (!$account)
        {
            throw new PrintableException('Doğrulama kodu geçersiz, kullanılmış veya süresi dolmuş.');
        }

        if (strcasecmp((string)$account->minecraft_username, $username) !== 0)
        {
            throw new PrintableException('Oyundaki kullanıcı adı, doğrulanmak istenen hesapla eşleşmiyor.');
        }

        $lockName = 'warext_mc_uuid_' . substr(hash('sha256', $uuid), 0, 40);
        $locked = (int)$this->db()->fetchOne('SELECT GET_LOCK(?, 3)', $lockName);
        if ($locked !== 1)
        {
            throw new PrintableException('Hesap doğrulama kilidi alınamadı. Birkaç saniye sonra tekrar deneyin.');
        }

        $db = $this->db();
        $db->beginTransaction();

        try
        {
            $db->fetchOne(
                'SELECT account_id FROM xf_warext_mc_account WHERE account_id = ? FOR UPDATE',
                $account->account_id
            );

            $account = $this->em()->find('Warext\MinecraftVote:MinecraftAccount', $account->account_id);
            if (!$account
                || $account->verification_state !== 'pending'
                || $account->verification_method !== 'trusted_bridge'
                || !hash_equals((string)$account->verification_code, $hash))
            {
                throw new PrintableException('Doğrulama kodu artık geçerli değil.');
            }

            $duplicate = $db->fetchOne(
                "SELECT account_id
                 FROM xf_warext_mc_account
                 WHERE minecraft_uuid = ?
                   AND verification_state = 'verified'
                   AND account_id <> ?
                 LIMIT 1
                 FOR UPDATE",
                [$uuid, $account->account_id]
            );

            if ($duplicate)
            {
                throw new PrintableException('Bu Minecraft UUID başka bir XenForo hesabında zaten doğrulanmış.');
            }

            $account->minecraft_uuid = $uuid;
            $account->verification_state = 'verified';
            $account->verification_method = 'trusted_bridge';
            $account->verification_code = '';
            $account->verified_date = \XF::$time;
            $account->save();

            $db->update(
                'xf_warext_mc_review',
                ['is_verified_player' => 1],
                'user_id = ?',
                $account->user_id
            );

            $db->commit();
            return $account;
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }
        finally
        {
            $db->fetchOne('SELECT RELEASE_LOCK(?)', $lockName);
        }
    }

    public function getLifetimeSeconds(): int
    {
        return self::LIFETIME;
    }

    protected function generateChallenge(): string
    {
        $timestamp = strtoupper(base_convert((string)\XF::$time, 10, 36));
        $timestamp = str_pad($timestamp, self::TIMESTAMP_LENGTH, '0', STR_PAD_LEFT);
        if (strlen($timestamp) > self::TIMESTAMP_LENGTH)
        {
            throw new \RuntimeException('Doğrulama zaman kodu sınırı aşıldı.');
        }

        $random = '';
        $max = strlen(self::ALPHABET) - 1;
        for ($i = 0; $i < self::RANDOM_LENGTH; $i++)
        {
            $random .= self::ALPHABET[random_int(0, $max)];
        }

        return self::CHALLENGE_PREFIX . $timestamp . $random;
    }

    protected function assertFreshChallenge(string $code): void
    {
        if (!preg_match('/^W[A-Z0-9]{15}$/', $code))
        {
            throw new PrintableException('Doğrulama kodu biçimi geçersiz.');
        }

        $encoded = substr($code, 1, self::TIMESTAMP_LENGTH);
        $timestamp = (int)base_convert(strtolower($encoded), 36, 10);
        if ($timestamp <= 0 || $timestamp > \XF::$time + 60 || $timestamp < \XF::$time - self::LIFETIME)
        {
            throw new PrintableException('Doğrulama kodunun süresi dolmuş. Yeni bir kod oluşturun.');
        }
    }

    protected function hashChallenge(string $code): string
    {
        $salt = (string)\XF::config('globalSalt');
        if ($salt === '')
        {
            throw new \RuntimeException('XenForo globalSalt yapılandırması bulunamadı.');
        }

        $key = hash_hkdf('sha256', $salt, 32, 'Warext/MinecraftVote:account-challenge', '');
        return hash_hmac('sha256', strtoupper($code), $key);
    }

    protected function normalizeUuid(string $uuid): string
    {
        $hex = strtolower(str_replace('-', '', trim($uuid)));
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
