<?php

namespace Warext\MinecraftVote\Network;

class BedrockStatus
{
    protected EndpointResolver $resolver;
    protected float $timeout;

    public function __construct(?EndpointResolver $resolver = null, float $timeout = 3.0)
    {
        $this->resolver = $resolver ?: new EndpointResolver();
        $this->timeout = max(0.5, min(10.0, $timeout));
    }

    public function query(string $host, int $port): array
    {
        $endpoint = $this->resolver->resolveBedrock($host, $port);
        $socketAddress = 'udp://' . $endpoint['socket_host'] . ':' . $endpoint['port'];
        $errno = 0;
        $error = '';

        $stream = @stream_socket_client(
            $socketAddress,
            $errno,
            $error,
            $this->timeout,
            STREAM_CLIENT_CONNECT
        );

        if (!$stream)
        {
            throw new \RuntimeException($error !== '' ? $error : 'Minecraft Bedrock sunucusuna bağlanılamadı.');
        }

        try
        {
            stream_set_timeout($stream, (int)ceil($this->timeout));

            $magic = hex2bin('00ffff00fefefefefdfdfdfd12345678');
            if ($magic === false)
            {
                throw new \RuntimeException('RakNet magic değeri oluşturulamadı.');
            }

            $timestamp = (int)floor(microtime(true) * 1000);
            $guid = random_int(1, PHP_INT_MAX);
            $packet = "\x01" . $this->packLong($timestamp) . $magic . $this->packLong($guid);

            $started = microtime(true);
            $written = fwrite($stream, $packet);
            if ($written === false || $written !== strlen($packet))
            {
                throw new \RuntimeException('Bedrock durum isteği gönderilemedi.');
            }

            $response = fread($stream, 4096);
            if ($response === false || $response === '')
            {
                $meta = stream_get_meta_data($stream);
                if (!empty($meta['timed_out']))
                {
                    throw new \RuntimeException('Bedrock sunucu durum sorgusu zaman aşımına uğradı.');
                }

                throw new \RuntimeException('Bedrock sunucusundan durum yanıtı alınamadı.');
            }

            if (strlen($response) < 35 || ord($response[0]) !== 0x1C)
            {
                throw new \RuntimeException('Geçersiz Bedrock RakNet yanıtı.');
            }

            $lengthData = unpack('nlength', substr($response, 33, 2));
            $serverIdLength = (int)($lengthData['length'] ?? 0);
            if ($serverIdLength < 1 || $serverIdLength > 2048 || strlen($response) < 35 + $serverIdLength)
            {
                throw new \RuntimeException('Geçersiz Bedrock sunucu kimliği uzunluğu.');
            }

            $serverId = substr($response, 35, $serverIdLength);
            $fields = explode(';', $serverId);

            if (($fields[0] ?? '') !== 'MCPE')
            {
                throw new \RuntimeException('Beklenmeyen Bedrock durum biçimi.');
            }

            $motdParts = [];
            if (!empty($fields[1]))
            {
                $motdParts[] = $fields[1];
            }
            if (!empty($fields[7]))
            {
                $motdParts[] = $fields[7];
            }

            $motd = trim(implode(' - ', array_unique($motdParts)));
            $motd = preg_replace('/§[0-9A-FK-ORX]/iu', '', $motd) ?? $motd;
            $motd = preg_replace('/\s+/u', ' ', $motd) ?? $motd;
            $elapsed = (int)round((microtime(true) - $started) * 1000);

            return [
                'is_online' => true,
                'ping_ms' => max(1, $elapsed),
                'players_online' => max(0, (int)($fields[4] ?? 0)),
                'players_max' => max(0, (int)($fields[5] ?? 0)),
                'motd' => trim(mb_substr($motd, 0, 500)),
                'detected_version' => trim((string)($fields[3] ?? '')),
                'protocol' => (int)($fields[2] ?? 0),
                'game_mode' => trim((string)($fields[8] ?? '')),
                'resolved_host' => $endpoint['host'],
                'resolved_port' => $endpoint['port']
            ];
        }
        finally
        {
            fclose($stream);
        }
    }

    protected function packLong(int $value): string
    {
        $high = intdiv($value, 4294967296);
        $low = $value % 4294967296;

        return pack('NN', $high, $low);
    }
}
