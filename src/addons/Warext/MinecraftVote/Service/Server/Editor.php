<?php

namespace Warext\MinecraftVote\Service\Server;

use Warext\MinecraftVote\Entity\Server;
use XF\App;
use XF\PrintableException;
use XF\Service\AbstractService;

class Editor extends AbstractService
{
    protected Server $server;
    protected int $actorUserId;
    protected array $categoryIds = [];

    public function __construct(App $app, Server $server, int $actorUserId)
    {
        parent::__construct($app);
        $this->server = $server;
        $this->actorUserId = $actorUserId;
    }

    public function setData(array $data): void
    {
        if (!$this->repository('Warext\MinecraftVote:ServerTeam')
            ->hasPermission($this->server, $this->actorUserId, 'edit_content'))
        {
            throw new PrintableException('Bu sunucuyu düzenleme yetkiniz yok.');
        }

        $data['title'] = trim((string)($data['title'] ?? ''));
        $data['host'] = $this->normalizeHost((string)($data['host'] ?? ''));
        $data['bedrock_host'] = $this->normalizeHost((string)($data['bedrock_host'] ?? ''));
        $data['country_code'] = strtoupper(trim((string)($data['country_code'] ?? '')));
        $data['port'] = (int)($data['port'] ?? 0) ?: 25565;
        $data['bedrock_port'] = (int)($data['bedrock_port'] ?? 0) ?: 19132;
        $serverType = (string)($data['server_type'] ?? 'java');

        if (in_array($serverType, ['bedrock', 'crossplay'], true) && $data['bedrock_host'] === '')
        {
            $data['bedrock_host'] = $data['host'];
        }

        if ($data['title'] === '')
        {
            throw new PrintableException('Sunucu adı boş bırakılamaz.');
        }
        if (!$this->isValidHost($data['host']))
        {
            throw new PrintableException('Geçerli bir Java sunucu adresi girin.');
        }
        if (in_array($serverType, ['bedrock', 'crossplay'], true) && !$this->isValidHost($data['bedrock_host']))
        {
            throw new PrintableException('Bedrock veya Crossplay sunucuları için geçerli bir Bedrock adresi girin.');
        }
        if ($data['country_code'] !== '' && !preg_match('/^[A-Z]{2}$/', $data['country_code']))
        {
            throw new PrintableException('Ülke kodu iki harfli ISO kodu olmalıdır.');
        }

        foreach (['website_url', 'discord_url', 'store_url'] as $field)
        {
            $data[$field] = trim((string)($data[$field] ?? ''));
            if ($data[$field] !== '' && !$this->isValidHttpUrl($data[$field]))
            {
                throw new PrintableException('Yalnızca geçerli http veya https bağlantıları kullanılabilir.');
            }
        }

        $endpointChanged = $this->server->server_type !== $serverType
            || $this->server->host !== $data['host']
            || (int)$this->server->port !== $data['port']
            || $this->server->bedrock_host !== $data['bedrock_host']
            || (int)$this->server->bedrock_port !== $data['bedrock_port'];

        $this->assertAddressIsUnique($data);

        $this->server->bulkSet($data, [
            'title', 'description', 'server_type', 'host', 'port', 'bedrock_host', 'bedrock_port',
            'website_url', 'discord_url', 'store_url', 'version_min', 'version_max', 'country_code',
            'is_premium', 'allow_cracked'
        ]);

        if ($endpointChanged)
        {
            $this->server->state = 'pending';
        }
    }

    public function setCategoryIds(array $categoryIds): void
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        if (count($categoryIds) > 5)
        {
            throw new PrintableException('Bir sunucu en fazla 5 kategoriye eklenebilir.');
        }

        if (!$categoryIds)
        {
            $this->categoryIds = [];
            return;
        }

        $valid = $this->finder('Warext\MinecraftVote:Category')
            ->whereIds($categoryIds)
            ->where('is_active', 1)
            ->fetch()
            ->keys();
        $this->categoryIds = array_map('intval', $valid);

        if (count($this->categoryIds) !== count($categoryIds))
        {
            throw new PrintableException('Geçersiz bir sunucu kategorisi seçildi.');
        }
    }

    public function save(): Server
    {
        $db = $this->db();
        $db->beginTransaction();

        try
        {
            $this->server->save(true, false);
            $db->delete('xf_warext_mc_server_category', 'server_id = ?', $this->server->server_id);

            foreach ($this->categoryIds as $categoryId)
            {
                $link = $this->em()->create('Warext\MinecraftVote:ServerCategory');
                $link->server_id = $this->server->server_id;
                $link->category_id = $categoryId;
                $link->save();
            }

            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }

        return $this->server;
    }

    protected function assertAddressIsUnique(array $data): void
    {
        $java = $this->finder('Warext\MinecraftVote:Server')
            ->where('host', $data['host'])
            ->where('port', $data['port'])
            ->where('server_id', '!=', $this->server->server_id)
            ->fetchOne();
        if ($java)
        {
            throw new PrintableException('Bu Java sunucusu başka bir kayıt tarafından kullanılıyor.');
        }

        if ($data['bedrock_host'] !== '')
        {
            $bedrock = $this->finder('Warext\MinecraftVote:Server')
                ->where('bedrock_host', $data['bedrock_host'])
                ->where('bedrock_port', $data['bedrock_port'])
                ->where('server_id', '!=', $this->server->server_id)
                ->fetchOne();
            if ($bedrock)
            {
                throw new PrintableException('Bu Bedrock sunucusu başka bir kayıt tarafından kullanılıyor.');
            }
        }
    }

    protected function normalizeHost(string $host): string
    {
        $host = trim($host);
        if (str_contains($host, '://'))
        {
            $parsed = parse_url($host, PHP_URL_HOST);
            if (is_string($parsed) && $parsed !== '')
            {
                $host = $parsed;
            }
        }

        return strtolower(trim($host, " \t\n\r\0\x0B./"));
    }

    protected function isValidHost(string $host): bool
    {
        if ($host === '' || strlen($host) > 253)
        {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP))
        {
            return true;
        }

        return (bool)preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i', $host);
    }

    protected function isValidHttpUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL))
        {
            return false;
        }

        return in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
