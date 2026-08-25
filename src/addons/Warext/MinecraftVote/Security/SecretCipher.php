<?php

namespace Warext\MinecraftVote\Security;

class SecretCipher
{
    private const CIPHER = 'aes-256-gcm';
    private const AAD = 'Warext/MinecraftVote:votifier';

    public function encrypt(string $plaintext): string
    {
        if ($plaintext === '')
        {
            return '';
        }

        if (!function_exists('openssl_encrypt'))
        {
            throw new \RuntimeException('OpenSSL PHP uzantısı NuVotifier token şifrelemesi için gereklidir.');
        }

        $iv = random_bytes(12);
        $tag = '';
        $encrypted = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->getKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            self::AAD,
            16
        );

        if ($encrypted === false || strlen($tag) !== 16)
        {
            throw new \RuntimeException('NuVotifier token şifrelenemedi.');
        }

        return base64_encode("\x01" . $iv . $tag . $encrypted);
    }

    public function decrypt(string $encoded): string
    {
        if ($encoded === '')
        {
            return '';
        }

        if (!function_exists('openssl_decrypt'))
        {
            throw new \RuntimeException('OpenSSL PHP uzantısı NuVotifier token çözümlemesi için gereklidir.');
        }

        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 30 || ord($raw[0]) !== 1)
        {
            throw new \RuntimeException('NuVotifier token verisi geçersiz.');
        }

        $iv = substr($raw, 1, 12);
        $tag = substr($raw, 13, 16);
        $ciphertext = substr($raw, 29);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->getKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            self::AAD
        );

        if ($plaintext === false)
        {
            throw new \RuntimeException('NuVotifier token çözümlenemedi. globalSalt değişmiş olabilir.');
        }

        return $plaintext;
    }

    protected function getKey(): string
    {
        $salt = (string)\XF::config('globalSalt');
        if ($salt === '')
        {
            throw new \RuntimeException('XenForo globalSalt yapılandırması bulunamadı.');
        }

        return hash_hkdf('sha256', $salt, 32, self::AAD, '');
    }
}
