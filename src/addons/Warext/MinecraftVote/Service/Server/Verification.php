<?php

namespace Warext\MinecraftVote\Service\Server;

use Warext\MinecraftVote\Entity\Server;
use XF\App;
use XF\PrintableException;
use XF\Service\AbstractService;

class Verification extends AbstractService
{
    private const TOKEN_LIFETIME = 86400;

    protected Server $server;

    public function __construct(App $app, Server $server)
    {
        parent::__construct($app);
        $this->server = $server;
    }

    public function start(string $method): Server
    {
        if (!in_array($method, ['motd', 'dns_txt'], true))
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_invalid_verification_method'));
        }

        if ($method === 'dns_txt' && !$this->canUseDnsVerification())
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_dns_domain_required'));
        }

        $this->server->is_verified = false;
        $this->server->verification_method = $method;
        $this->server->verification_token = 'WAREXT-' . strtoupper(bin2hex(random_bytes(8)));
        $this->server->verification_token_date = \XF::$time;
        $this->server->verified_date = 0;
        $this->server->save();

        return $this->server;
    }

    public function verify(): array
    {
        $token = trim($this->server->verification_token);
        $method = $this->server->verification_method;

        if ($token === '' || !in_array($method, ['motd', 'dns_txt'], true))
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_verification_start_first'));
        }

        if (!$this->server->verification_token_date
            || $this->server->verification_token_date < \XF::$time - self::TOKEN_LIFETIME)
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_verification_expired'));
        }

        if ($method === 'motd')
        {
            $result = $this->verifyMotd($token);
        }
        else
        {
            $result = $this->verifyDnsTxt($token);
        }

        if (!$result['success'])
        {
            throw new PrintableException($result['message']);
        }

        $this->server->is_verified = true;
        $this->server->verification_token = '';
        $this->server->verification_token_date = 0;
        $this->server->verified_date = \XF::$time;
        $this->server->save();

        return $result;
    }

    public function getDnsRecordName(): string
    {
        return '_warext-minecraft.' . $this->normalizeDnsHost($this->server->host);
    }

    public function getDnsRecordValue(): string
    {
        return 'warext-verification=' . $this->server->verification_token;
    }

    public function getTokenLifetimeHours(): int
    {
        return (int)(self::TOKEN_LIFETIME / 3600);
    }

    protected function verifyMotd(string $token): array
    {
        $pinger = $this->service('Warext\MinecraftVote:Server\Pinger', $this->server);
        $result = $pinger->ping();

        if (empty($result['is_online']))
        {
            return [
                'success' => false,
                'message' => (string)\XF::phrase('warext_mc_dyn_server_unreachable', ['error' => (string)($result['error'] ?? \XF::phrase('warext_mc_dyn_unknown_connection_error'))])
            ];
        }

        $motd = (string)($result['motd'] ?? '');
        if (stripos($motd, $token) === false)
        {
            return [
                'success' => false,
                'message' => (string)\XF::phrase('warext_mc_dyn_motd_code_missing')
            ];
        }

        return [
            'success' => true,
            'method' => 'motd',
            'message' => (string)\XF::phrase('warext_mc_dyn_ownership_motd_verified')
        ];
    }

    protected function verifyDnsTxt(string $token): array
    {
        if (!$this->canUseDnsVerification())
        {
            return [
                'success' => false,
                'message' => (string)\XF::phrase('warext_mc_dyn_dns_domain_required')
            ];
        }

        if (!function_exists('dns_get_record'))
        {
            return [
                'success' => false,
                'message' => (string)\XF::phrase('warext_mc_dyn_dns_support_missing')
            ];
        }

        $recordName = $this->getDnsRecordName();
        $expected = 'warext-verification=' . $token;
        $records = @dns_get_record($recordName, DNS_TXT);

        if (!$records)
        {
            return [
                'success' => false,
                'message' => (string)\XF::phrase('warext_mc_dyn_dns_record_pending')
            ];
        }

        foreach ($records as $record)
        {
            $values = [];
            if (isset($record['txt']))
            {
                $values[] = (string)$record['txt'];
            }
            if (!empty($record['entries']) && is_array($record['entries']))
            {
                $values[] = implode('', array_map('strval', $record['entries']));
            }

            foreach ($values as $value)
            {
                if (hash_equals($expected, trim($value)))
                {
                    return [
                        'success' => true,
                        'method' => 'dns_txt',
                        'message' => (string)\XF::phrase('warext_mc_dyn_ownership_dns_verified')
                    ];
                }
            }
        }

        return [
            'success' => false,
            'message' => (string)\XF::phrase('warext_mc_dyn_dns_value_mismatch')
        ];
    }

    protected function canUseDnsVerification(): bool
    {
        $host = $this->normalizeDnsHost($this->server->host);
        if ($host === '' || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP))
        {
            return false;
        }

        return (bool)filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    }

    protected function normalizeDnsHost(string $host): string
    {
        return strtolower(rtrim(trim($host), '.'));
    }
}
