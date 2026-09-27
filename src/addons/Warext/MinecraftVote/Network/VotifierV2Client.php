<?php

namespace Warext\MinecraftVote\Network;

class VotifierV2Client
{
    protected EndpointResolver $resolver;
    protected float $timeout;

    public function __construct(?EndpointResolver $resolver = null, float $timeout = 3.0)
    {
        $this->resolver = $resolver ?: new EndpointResolver();
        $this->timeout = max(0.5, min(10.0, $timeout));
    }

    public function send(
        string $host,
        int $port,
        string $token,
        string $username,
        string $serviceName,
        string $address = '0.0.0.0',
        ?int $timestamp = null
    ): array
    {
        $token = trim($token);
        if ($token === '')
        {
            throw new \InvalidArgumentException((string)\XF::phrase('warext_mc_dyn_votifier_token_required'));
        }

        if ($port < 1 || $port > 65535)
        {
            throw new \InvalidArgumentException((string)\XF::phrase('warext_mc_dyn_votifier_port_invalid'));
        }

        if (!preg_match('/^[A-Za-z0-9_]{1,16}$/', $username))
        {
            throw new \InvalidArgumentException((string)\XF::phrase('warext_mc_dyn_votifier_username_invalid'));
        }

        $serviceName = trim($serviceName);
        if ($serviceName === '' || mb_strlen($serviceName) > 64)
        {
            throw new \InvalidArgumentException((string)\XF::phrase('warext_mc_dyn_votifier_service_invalid'));
        }

        $endpoint = $this->resolver->resolveTcp($host, $port);
        $socketAddress = 'tcp://' . $endpoint['socket_host'] . ':' . $endpoint['port'];
        $errno = 0;
        $error = '';
        $started = microtime(true);

        $stream = @stream_socket_client(
            $socketAddress,
            $errno,
            $error,
            $this->timeout,
            STREAM_CLIENT_CONNECT
        );

        if (!$stream)
        {
            throw new \RuntimeException($error !== '' ? $error : 'NuVotifier sunucusuna bağlanılamadı.');
        }

        try
        {
            stream_set_timeout($stream, (int)ceil($this->timeout));

            $header = fgets($stream, 256);
            if ($header === false || $header === '')
            {
                throw new \RuntimeException((string)\XF::phrase('warext_mc_dyn_votifier_header_missing'));
            }

            $header = trim($header);
            if (!preg_match('/^VOTIFIER\s+2\s+([A-Za-z0-9+\/_=-]+)$/', $header, $matches))
            {
                throw new \RuntimeException((string)\XF::phrase('warext_mc_dyn_votifier_v2_required'));
            }

            $challenge = $matches[1];
            $payloadJson = json_encode([
                'username' => $username,
                'serviceName' => $serviceName,
                'timestamp' => $timestamp ?? (int)floor(microtime(true) * 1000),
                'address' => $address,
                'challenge' => $challenge
            ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

            $signature = base64_encode(hash_hmac('sha256', $payloadJson, $token, true));
            $messageJson = json_encode([
                'signature' => $signature,
                'payload' => $payloadJson
            ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

            $messageLength = strlen($messageJson);
            if ($messageLength < 1 || $messageLength > 65535)
            {
                throw new \RuntimeException((string)\XF::phrase('warext_mc_dyn_votifier_packet_too_large'));
            }

            $this->writeAll($stream, pack('nn', 0x733a, $messageLength) . $messageJson);

            $response = fread($stream, 1024);
            if ($response === false || trim($response) === '')
            {
                $meta = stream_get_meta_data($stream);
                if (!empty($meta['timed_out']))
                {
                    throw new \RuntimeException((string)\XF::phrase('warext_mc_dyn_votifier_response_timeout'));
                }

                throw new \RuntimeException((string)\XF::phrase('warext_mc_dyn_votifier_response_missing'));
            }

            $result = json_decode(trim($response), true, 16, JSON_THROW_ON_ERROR);
            if (($result['status'] ?? '') !== 'ok')
            {
                $cause = trim((string)($result['cause'] ?? 'server_error'));
                $detail = trim((string)($result['error'] ?? (string)\XF::phrase('warext_mc_dyn_votifier_unknown_error')));
                throw new \RuntimeException($cause . ': ' . $detail);
            }

            return [
                'success' => true,
                'ping_ms' => max(1, (int)round((microtime(true) - $started) * 1000)),
                'resolved_host' => $endpoint['host'],
                'resolved_port' => $endpoint['port']
            ];
        }
        finally
        {
            fclose($stream);
        }
    }

    protected function writeAll($stream, string $data): void
    {
        $offset = 0;
        $length = strlen($data);

        while ($offset < $length)
        {
            $written = fwrite($stream, substr($data, $offset));
            if ($written === false || $written === 0)
            {
                throw new \RuntimeException((string)\XF::phrase('warext_mc_dyn_votifier_send_failed'));
            }

            $offset += $written;
        }
    }
}
