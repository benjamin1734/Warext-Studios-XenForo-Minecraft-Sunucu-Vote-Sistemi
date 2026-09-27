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
            throw new \RuntimeException((string)\XF::phrase('warext_mc_dyn_openssl_encrypt_required'));
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
            throw new \RuntimeException((string)\XF::phrase('warext_mc_dyn_token_encrypt_failed'));
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
            throw new \RuntimeException((string)\XF::phrase('warext_mc_dyn_openssl_decrypt_required'));
        }

        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 30 || ord($raw[0]) !== 1)
        {
            throw new \RuntimeException((string)\XF::phrase('warext_mc_dyn_token_data_invalid'));
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
            throw new \RuntimeException((string)\XF::phrase('warext_mc_dyn_token_decrypt_failed'));
        }

        return $plaintext;
    }

    protected function getKey(): string
    {
        $salt = (string)\XF::config('globalSalt');
        if ($salt === '')
        {
            throw new \RuntimeException((string)\XF::phrase('warext_mc_dyn_globalsalt_missing'));
        }

        return hash_hkdf('sha256', $salt, 32, self::AAD, '');
    }
}
