<?php

namespace Warext\MinecraftVote\Service\Votifier;

use Warext\MinecraftVote\Entity\Server;
use Warext\MinecraftVote\Entity\VotifierConfig;
use Warext\MinecraftVote\Network\VotifierV2Client;
use Warext\MinecraftVote\Security\SecretCipher;
use XF\App;
use XF\PrintableException;
use XF\Service\AbstractService;

class ConfigWriter extends AbstractService
{
    protected Server $server;
    protected VotifierConfig $config;
    protected SecretCipher $cipher;

    public function __construct(App $app, Server $server)
    {
        parent::__construct($app);
        $this->server = $server;
        $this->cipher = new SecretCipher();

        $config = $this->em()->find('Warext\MinecraftVote:VotifierConfig', $server->server_id);
        if (!$config)
        {
            $config = $this->em()->create('Warext\MinecraftVote:VotifierConfig');
            $config->server_id = $server->server_id;
            $config->host = $server->host;
            $config->port = 8192;
            $config->protocol = 'v2';
            $config->service_name = 'Warext';
        }

        $this->config = $config;
    }

    public function getConfig(): VotifierConfig
    {
        return $this->config;
    }

    public function setData(array $data): void
    {
        $enabled = !empty($data['enabled']);
        $host = strtolower(trim((string)($data['host'] ?? '')));
        $host = trim($host, '[]');
        $port = (int)($data['port'] ?? 8192);
        $serviceName = trim((string)($data['service_name'] ?? 'Warext'));
        $token = trim((string)($data['token'] ?? ''));

        if ($host === '')
        {
            $host = $this->server->host;
        }

        if (!$this->isValidHost($host))
        {
            throw new PrintableException('Geçerli bir NuVotifier host adresi girin.');
        }

        if ($port < 1 || $port > 65535)
        {
            throw new PrintableException('NuVotifier portu 1-65535 arasında olmalıdır.');
        }

        if ($serviceName === '' || mb_strlen($serviceName) > 64)
        {
            throw new PrintableException('NuVotifier servis adı 1-64 karakter arasında olmalıdır.');
        }

        if ($token !== '')
        {
            $this->config->token_encrypted = $this->cipher->encrypt($token);
        }

        if ($enabled && !$this->config->token_encrypted)
        {
            throw new PrintableException('NuVotifier entegrasyonunu açmak için token girmeniz gerekiyor.');
        }

        $this->config->enabled = $enabled;
        $this->config->host = $host;
        $this->config->port = $port;
        $this->config->protocol = 'v2';
        $this->config->service_name = $serviceName;
    }

    public function save(): VotifierConfig
    {
        $this->config->save();
        return $this->config;
    }

    public function testConnection(): array
    {
        if (!$this->config->token_encrypted)
        {
            throw new PrintableException('Bağlantı testi için NuVotifier token kaydedilmelidir.');
        }

        $this->config->last_test_date = \XF::$time;

        try
        {
            $client = new VotifierV2Client(null, 3.0);
            $result = $client->send(
                $this->config->host ?: $this->server->host,
                $this->config->port,
                $this->cipher->decrypt((string)$this->config->token_encrypted),
                'WarextTest',
                $this->config->service_name ?: 'Warext',
                '0.0.0.0'
            );

            $this->config->last_success_date = \XF::$time;
            $this->config->last_error = '';
            $this->config->save();

            return $result;
        }
        catch (\Throwable $e)
        {
            $this->config->last_error = mb_substr($e->getMessage(), 0, 500);
            $this->config->save();
            throw new PrintableException('NuVotifier bağlantı testi başarısız: ' . $e->getMessage());
        }
    }

    protected function isValidHost(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP))
        {
            return true;
        }

        return strlen($host) <= 253 && (bool)preg_match(
            '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i',
            $host
        );
    }
}
