<?php

namespace Warext\MinecraftVote\Service\RateLimit;

use XF\App;
use XF\PrintableException;
use XF\Service\AbstractService;
use XF\Service\FloodCheckService;

class Request extends AbstractService
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function assertIp(string $action, int $scopeId, string $ip, int $seconds): void
    {
        $visitor = \XF::visitor();
        if ($visitor->user_id && $visitor->hasPermission('general', 'bypassFloodCheck'))
        {
            return;
        }

        $action = preg_replace('/[^A-Za-z0-9_]/', '', $action) ?? '';
        if ($action === '' || strlen($action) > 25)
        {
            throw new \InvalidArgumentException('Geçersiz rate-limit action.');
        }

        $ip = trim($ip);
        if ($ip === '' || $seconds <= 0)
        {
            throw new PrintableException('İstek doğrulaması yapılamadı. Lütfen tekrar deneyin.');
        }

        $salt = (string)\XF::config('globalSalt');
        if ($salt === '')
        {
            throw new \RuntimeException('XenForo globalSalt yapılandırması bulunamadı.');
        }

        $digest = hash_hmac('sha256', $action . '|' . max(0, $scopeId) . '|' . $ip, $salt, true);
        $part = unpack('Nvalue', substr($digest, 0, 4));
        $syntheticId = ((int)($part['value'] ?? 0) & 0x7FFFFFFF) + 1;

        $flood = $this->service(FloodCheckService::class);
        $remaining = (int)$flood->checkFlooding($action, $syntheticId, $seconds);
        if ($remaining > 0)
        {
            throw new PrintableException("Çok hızlı istek gönderiyorsunuz. {$remaining} saniye sonra tekrar deneyin.");
        }
    }
}
