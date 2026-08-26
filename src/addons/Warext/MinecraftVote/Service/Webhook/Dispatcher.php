<?php

namespace Warext\MinecraftVote\Service\Webhook;

use Warext\MinecraftVote\Entity\Vote;
use Warext\MinecraftVote\Network\EndpointResolver;
use XF\App;
use XF\Service\AbstractService;

class Dispatcher extends AbstractService
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function dispatchVote(Vote $vote): bool
    {
        if (!(bool)(\XF::options()->warextMcWebhookEnabled ?? false))
        {
            return true;
        }

        $url = trim((string)(\XF::options()->warextMcWebhookUrl ?? ''));
        if ($url === '')
        {
            return true;
        }

        $this->assertSafeUrl($url);

        $server = $vote->Server;
        $payload = [
            'event' => 'vote.created',
            'event_id' => 'vote-' . (int)$vote->vote_id,
            'created_at' => (int)$vote->vote_date,
            'data' => [
                'vote_id' => (int)$vote->vote_id,
                'server_id' => (int)$vote->server_id,
                'server_title' => $server ? (string)$server->title : '',
                'minecraft_username' => (string)$vote->minecraft_username,
                'minecraft_uuid' => (string)$vote->minecraft_uuid,
                'user_id' => (int)$vote->user_id,
                'fraud_score' => (int)$vote->fraud_score,
                'source' => (string)$vote->source
            ]
        ];

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $secret = trim((string)(\XF::options()->warextMcWebhookSecret ?? ''));
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Warext-MinecraftVote/1.0',
            'X-Warext-Event' => 'vote.created'
        ];
        if ($secret !== '')
        {
            $headers['X-Warext-Signature'] = 'sha256=' . hash_hmac('sha256', $body, $secret);
        }

        $response = $this->app->http()->client()->request('POST', $url, [
            'body' => $body,
            'headers' => $headers,
            'connect_timeout' => 2.0,
            'timeout' => 4.0,
            'http_errors' => false,
            'allow_redirects' => false
        ]);

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300)
        {
            throw new \RuntimeException('Webhook HTTP ' . $status . ' yanıtı döndürdü.');
        }

        return true;
    }

    protected function assertSafeUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL))
        {
            throw new \InvalidArgumentException('Webhook URL geçersiz.');
        }

        $parts = parse_url($url);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = strtolower((string)($parts['host'] ?? ''));
        if ($scheme !== 'https' || $host === '')
        {
            throw new \InvalidArgumentException('Webhook yalnızca geçerli HTTPS adreslerine gönderilebilir.');
        }
        if (isset($parts['user']) || isset($parts['pass']))
        {
            throw new \InvalidArgumentException('Webhook URL içinde kullanıcı adı veya parola kullanılamaz.');
        }

        $port = (int)($parts['port'] ?? 443);
        (new EndpointResolver())->resolveTcp($host, $port);
    }
}
