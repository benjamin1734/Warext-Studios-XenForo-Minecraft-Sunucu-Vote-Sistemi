<?php

namespace Warext\MinecraftVote\Network;

class EndpointResolver
{
    public function resolveJava(string $host, int $port): array
    {
        return $this->resolve($host, $port, 'tcp', 25565, true);
    }

    public function resolveBedrock(string $host, int $port): array
    {
        return $this->resolve($host, $port, 'udp', 19132, true);
    }

    public function resolveTcp(string $host, int $port): array
    {
        return $this->resolve($host, $port, 'tcp', 0, false);
    }

    protected function resolve(
        string $host,
        int $port,
        string $transport,
        int $defaultPort,
        bool $allowMinecraftSrv
    ): array
    {
        if ($port < 1 || $port > 65535)
        {
            throw new \InvalidArgumentException('Geçersiz sunucu portu.');
        }

        $originalHost = $this->normalizeHost($host);
        $resolvedHost = $originalHost;

        if (
            $allowMinecraftSrv
            && $defaultPort > 0
            && $port === $defaultPort
            && !filter_var($resolvedHost, FILTER_VALIDATE_IP)
        )
        {
            $srv = $this->resolveSrv($resolvedHost, $transport);
            if ($srv)
            {
                $resolvedHost = $srv['host'];
                $port = $srv['port'];
            }
        }

        $connectHost = $this->resolveConnectHost($resolvedHost);

        return [
            'host' => $resolvedHost,
            'original_host' => $originalHost,
            'connect_host' => $connectHost,
            'port' => $port,
            'socket_host' => $this->formatSocketHost($connectHost)
        ];
    }

    protected function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = trim($host, '[]');

        if ($host === '' || strlen($host) > 253)
        {
            throw new \InvalidArgumentException('Geçersiz sunucu adresi.');
        }

        if ($host === 'localhost')
        {
            if (!$this->privateHostsAllowed())
            {
                throw new \RuntimeException('Yerel adreslere bağlantı kapalı. ACP ayarlarından lokal test iznini açın.');
            }

            return $host;
        }

        if (filter_var($host, FILTER_VALIDATE_IP))
        {
            return $host;
        }

        if (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
        {
            throw new \InvalidArgumentException('Geçersiz sunucu alan adı.');
        }

        return $host;
    }

    protected function resolveSrv(string $host, string $transport): ?array
    {
        if (!function_exists('dns_get_record'))
        {
            return null;
        }

        $recordName = '_minecraft._' . $transport . '.' . $host;
        $records = @dns_get_record($recordName, DNS_SRV);

        if (!$records)
        {
            return null;
        }

        usort($records, static function (array $a, array $b): int
        {
            $priorityCompare = ((int)($a['pri'] ?? 0)) <=> ((int)($b['pri'] ?? 0));
            if ($priorityCompare !== 0)
            {
                return $priorityCompare;
            }

            return ((int)($b['weight'] ?? 0)) <=> ((int)($a['weight'] ?? 0));
        });

        $record = $records[0];
        $target = rtrim((string)($record['target'] ?? ''), '.');
        $port = (int)($record['port'] ?? 0);

        if ($target === '' || $port < 1 || $port > 65535)
        {
            return null;
        }

        return [
            'host' => $this->normalizeHost($target),
            'port' => $port
        ];
    }

    protected function resolveConnectHost(string $host): string
    {
        if ($this->privateHostsAllowed())
        {
            return $host;
        }

        $addresses = $this->resolveAddresses($host);
        if (!$addresses)
        {
            throw new \RuntimeException('Sunucu adresi çözümlenemedi.');
        }

        foreach ($addresses as $address)
        {
            if (!filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ))
            {
                throw new \RuntimeException('Özel veya ayrılmış ağ adreslerine bağlantı engellendi.');
            }
        }

        return $addresses[0];
    }

    protected function privateHostsAllowed(): bool
    {
        return (bool)(\XF::options()->warextMcAllowPrivateHosts ?? false);
    }

    protected function resolveAddresses(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP))
        {
            return [$host];
        }

        $addresses = [];
        $ipv4 = @gethostbynamel($host);
        if ($ipv4)
        {
            $addresses = array_merge($addresses, $ipv4);
        }

        if (function_exists('dns_get_record'))
        {
            $ipv6Records = @dns_get_record($host, DNS_AAAA);
            if ($ipv6Records)
            {
                foreach ($ipv6Records as $record)
                {
                    if (!empty($record['ipv6']))
                    {
                        $addresses[] = $record['ipv6'];
                    }
                }
            }
        }

        return array_values(array_unique($addresses));
    }

    protected function formatSocketHost(string $host): string
    {
        return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            ? '[' . $host . ']'
            : $host;
    }
}
