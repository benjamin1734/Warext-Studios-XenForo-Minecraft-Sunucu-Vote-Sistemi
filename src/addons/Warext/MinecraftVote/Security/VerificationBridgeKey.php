<?php

namespace Warext\MinecraftVote\Security;

class VerificationBridgeKey
{
    private const INFO = 'Warext/MinecraftVote:account-verification-bridge';

    public function isEnabled(): bool
    {
        return (bool)(\XF::options()->warextMcVerifyBridgeEnabled ?? false);
    }

    public function getGeneration(): int
    {
        return max(1, min(2147483647, (int)(\XF::options()->warextMcVerifyBridgeGeneration ?? 1)));
    }

    public function getSecret(?int $generation = null): string
    {
        $salt = (string)\XF::config('globalSalt');
        if ($salt === '')
        {
            throw new \RuntimeException('XenForo globalSalt yapılandırması bulunamadı.');
        }

        $generation = $generation ?? $this->getGeneration();
        $raw = hash_hkdf('sha256', $salt, 32, self::INFO . ':v' . $generation, '');

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public function verify(string $canonical, string $signature): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/i', $signature))
        {
            return false;
        }

        $expected = hash_hmac('sha256', $canonical, $this->getSecret());
        return hash_equals($expected, strtolower($signature));
    }
}
