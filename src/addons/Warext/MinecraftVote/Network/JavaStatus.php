<?php

namespace Warext\MinecraftVote\Network;

class JavaStatus
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
        $endpoint = $this->resolver->resolveJava($host, $port);
        $socketAddress = 'tcp://' . $endpoint['socket_host'] . ':' . $endpoint['port'];
        $started = microtime(true);
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
            throw new \RuntimeException($error !== '' ? $error : 'Minecraft Java sunucusuna bağlanılamadı.');
        }

        try
        {
            stream_set_timeout($stream, (int)ceil($this->timeout));

            $handshake = "\x00"
                . $this->encodeVarInt(767)
                . $this->encodeString($host)
                . pack('n', $port)
                . $this->encodeVarInt(1);

            $this->writeAll($stream, $this->encodeVarInt(strlen($handshake)) . $handshake);
            $this->writeAll($stream, "\x01\x00");

            $packetLength = $this->readVarInt($stream);
            if ($packetLength < 1 || $packetLength > 2097152)
            {
                throw new \RuntimeException('Geçersiz Java durum paketi uzunluğu.');
            }

            $packetId = $this->readVarInt($stream);
            if ($packetId !== 0)
            {
                throw new \RuntimeException('Beklenmeyen Java durum paketi alındı.');
            }

            $jsonLength = $this->readVarInt($stream);
            if ($jsonLength < 2 || $jsonLength > 2097152)
            {
                throw new \RuntimeException('Geçersiz Java durum yanıtı.');
            }

            $json = $this->readBytes($stream, $jsonLength);
            $payload = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
            $elapsed = (int)round((microtime(true) - $started) * 1000);

            return [
                'is_online' => true,
                'ping_ms' => max(1, $elapsed),
                'players_online' => max(0, (int)($payload['players']['online'] ?? 0)),
                'players_max' => max(0, (int)($payload['players']['max'] ?? 0)),
                'motd' => $this->extractDescription($payload['description'] ?? ''),
                'detected_version' => trim((string)($payload['version']['name'] ?? '')),
                'protocol' => (int)($payload['version']['protocol'] ?? 0),
                'resolved_host' => $endpoint['host'],
                'resolved_port' => $endpoint['port']
            ];
        }
        finally
        {
            fclose($stream);
        }
    }

    protected function encodeString(string $value): string
    {
        return $this->encodeVarInt(strlen($value)) . $value;
    }

    protected function encodeVarInt(int $value): string
    {
        $output = '';

        do
        {
            $temp = $value & 0x7F;
            $value >>= 7;

            if ($value !== 0)
            {
                $temp |= 0x80;
            }

            $output .= chr($temp);
        }
        while ($value !== 0);

        return $output;
    }

    protected function readVarInt($stream): int
    {
        $value = 0;
        $position = 0;

        while (true)
        {
            $byte = $this->readBytes($stream, 1);
            $current = ord($byte);
            $value |= ($current & 0x7F) << $position;

            if (($current & 0x80) === 0)
            {
                return $value;
            }

            $position += 7;
            if ($position >= 35)
            {
                throw new \RuntimeException('Java VarInt değeri geçersiz.');
            }
        }
    }

    protected function readBytes($stream, int $length): string
    {
        $buffer = '';

        while (strlen($buffer) < $length)
        {
            $chunk = fread($stream, $length - strlen($buffer));
            if ($chunk === false || $chunk === '')
            {
                $meta = stream_get_meta_data($stream);
                if (!empty($meta['timed_out']))
                {
                    throw new \RuntimeException('Java sunucu durum sorgusu zaman aşımına uğradı.');
                }

                throw new \RuntimeException('Java sunucu durum bağlantısı beklenmedik şekilde kapandı.');
            }

            $buffer .= $chunk;
        }

        return $buffer;
    }

    protected function writeAll($stream, string $data): void
    {
        $written = 0;
        $length = strlen($data);

        while ($written < $length)
        {
            $result = fwrite($stream, substr($data, $written));
            if ($result === false || $result === 0)
            {
                throw new \RuntimeException('Java sunucu durum isteği gönderilemedi.');
            }

            $written += $result;
        }
    }

    protected function extractDescription(mixed $description): string
    {
        $text = $this->flattenText($description);
        $text = preg_replace('/§[0-9A-FK-ORX]/iu', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim(mb_substr($text, 0, 500));
    }

    protected function flattenText(mixed $value): string
    {
        if (is_string($value) || is_numeric($value))
        {
            return (string)$value;
        }

        if (!is_array($value))
        {
            return '';
        }

        $parts = [];

        if (isset($value['text']))
        {
            $parts[] = $this->flattenText($value['text']);
        }
        elseif (isset($value['translate']))
        {
            $parts[] = $this->flattenText($value['translate']);
        }

        if (isset($value['extra']) && is_array($value['extra']))
        {
            foreach ($value['extra'] as $extra)
            {
                $parts[] = $this->flattenText($extra);
            }
        }
        elseif (array_is_list($value))
        {
            foreach ($value as $child)
            {
                $parts[] = $this->flattenText($child);
            }
        }

        return implode(' ', array_filter($parts, static fn(string $part): bool => $part !== ''));
    }
}
