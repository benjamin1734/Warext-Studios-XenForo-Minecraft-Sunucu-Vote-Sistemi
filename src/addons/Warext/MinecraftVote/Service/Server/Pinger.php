<?php

namespace Warext\MinecraftVote\Service\Server;

use Warext\MinecraftVote\Entity\Server;
use Warext\MinecraftVote\Network\BedrockStatus;
use Warext\MinecraftVote\Network\JavaStatus;
use XF\App;
use XF\Service\AbstractService;

class Pinger extends AbstractService
{
    protected Server $server;

    public function __construct(App $app, Server $server)
    {
        parent::__construct($app);
        $this->server = $server;
    }

    public function ping(): array
    {
        try
        {
            return match ($this->server->server_type)
            {
                'bedrock' => $this->pingBedrock(),
                'crossplay' => $this->pingCrossplay(),
                default => $this->pingJava()
            };
        }
        catch (\Throwable $e)
        {
            return $this->offlineResult($e->getMessage());
        }
    }

    protected function pingJava(): array
    {
        $query = new JavaStatus(null, 3.0);
        return $query->query($this->server->host, $this->server->port);
    }

    protected function pingBedrock(): array
    {
        $host = $this->server->bedrock_host !== ''
            ? $this->server->bedrock_host
            : $this->server->host;

        $query = new BedrockStatus(null, 3.0);
        return $query->query($host, $this->server->bedrock_port);
    }

    protected function pingCrossplay(): array
    {
        try
        {
            return $this->pingJava();
        }
        catch (\Throwable $javaError)
        {
            try
            {
                return $this->pingBedrock();
            }
            catch (\Throwable $bedrockError)
            {
                throw new \RuntimeException(
                    'Java: ' . $javaError->getMessage() . ' | Bedrock: ' . $bedrockError->getMessage()
                );
            }
        }
    }

    protected function offlineResult(string $error): array
    {
        return [
            'is_online' => false,
            'ping_ms' => 0,
            'players_online' => 0,
            'players_max' => 0,
            'motd' => '',
            'detected_version' => '',
            'protocol' => 0,
            'error' => mb_substr(trim($error), 0, 500)
        ];
    }
}
