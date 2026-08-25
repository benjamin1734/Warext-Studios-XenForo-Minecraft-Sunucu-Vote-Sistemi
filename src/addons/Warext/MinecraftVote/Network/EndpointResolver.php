<?php

namespace Warext\MinecraftVote\Network;

class EndpointResolver
{
    public function resolveJava(string $host, int $port): array
    {
        return $this->resolve($host, $port, 'tcp', 25565);
    }

    public function resolveBedrock(string $host, int $port): array
    {
        return $this->resolve($host, $port, 'udp', 19132);
    }

    protected function resolve(string $host, int $port, string $transport, int $defaultPort): array
    {
        $host = $this->normalizeHost($host);

        if ($port === $defaultPort && !filter_var($host, FILTER_VALIDATE_IP))
        {
            $srv = $this->resolveSrv($host, $transport);
            if ($srv)
            {
                $host = $srv['host'];
                $port = $srv['port'];
            }
        }

        $this->assertHostAllowed($host);

        return [
            'host' => $host,
            'port' => $port,
            'socket_host' => $this->formatSocketHost($host)
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
            if (!\XF::$developmentMode)
            {
                throw new \RuntimeException('Yerel adresler yalnızca XenForo geliştirme modunda kullanılabilir.');
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

    protected function assertHostAllowed(string $host): void
    {
        if (\XF::$developmentMode)
        {
            return;
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
