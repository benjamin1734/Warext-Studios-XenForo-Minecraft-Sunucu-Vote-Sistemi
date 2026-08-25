<?php

namespace Warext\MinecraftVote\Service\Server;

use Warext\MinecraftVote\Entity\Server;
use XF\App;
use XF\Entity\User;
use XF\Service\AbstractService;

class Creator extends AbstractService
{
    protected Server $server;
    protected array $categoryIds = [];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->server = $app->em()->create('Warext\MinecraftVote:Server');
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function setOwner(User $user): void
    {
        $this->server->owner_user_id = $user->user_id;
    }

    public function setData(array $data): void
    {
        $data['title'] = trim($data['title'] ?? '');
        $data['host'] = $this->normalizeHost($data['host'] ?? '');
        $data['bedrock_host'] = $this->normalizeHost($data['bedrock_host'] ?? '');
        $data['country_code'] = strtoupper(trim($data['country_code'] ?? ''));
        $data['port'] = (int)($data['port'] ?? 0) ?: 25565;
        $data['bedrock_port'] = (int)($data['bedrock_port'] ?? 0) ?: 19132;

        foreach (['website_url', 'discord_url', 'store_url'] as $urlField)
        {
            $data[$urlField] = trim($data[$urlField] ?? '');
            if ($data[$urlField] !== '' && !$this->isValidHttpUrl($data[$urlField]))
            {
                $this->server->error('Yalnızca geçerli http veya https bağlantıları kullanılabilir.', $urlField);
            }
        }

        if ($data['title'] === '')
        {
            $this->server->error('Sunucu adı boş bırakılamaz.', 'title');
        }

        if (!$this->isValidHost($data['host']))
        {
            $this->server->error('Geçerli bir Java sunucu adresi girin.', 'host');
        }

        $serverType = $data['server_type'] ?? 'java';
        if (in_array($serverType, ['bedrock', 'crossplay'], true) && !$this->isValidHost($data['bedrock_host']))
        {
            $this->server->error('Bedrock veya Crossplay sunucuları için geçerli bir Bedrock adresi girin.', 'bedrock_host');
        }

        if ($data['country_code'] !== '' && !preg_match('/^[A-Z]{2}$/', $data['country_code']))
        {
            $this->server->error('Ülke kodu iki harfli ISO kodu olmalıdır.', 'country_code');
        }

        $this->server->bulkSet($data, [
            'title',
            'description',
            'server_type',
            'host',
            'port',
            'bedrock_host',
            'bedrock_port',
            'website_url',
            'discord_url',
            'store_url',
            'version_min',
            'version_max',
            'country_code',
            'is_premium',
            'allow_cracked'
        ]);

        $this->server->slug = $this->generateUniqueSlug($data['title']);
        $this->server->state = 'pending';
        $this->assertServerAddressIsUnique();
    }

    public function setCategoryIds(array $categoryIds): void
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));

        if (count($categoryIds) > 5)
        {
            $this->server->error('Bir sunucu en fazla 5 kategoriye eklenebilir.', 'category_ids');
            return;
        }

        if (!$categoryIds)
        {
            $this->categoryIds = [];
            return;
        }

        $validIds = $this->finder('Warext\MinecraftVote:Category')
            ->whereIds($categoryIds)
            ->where('is_active', 1)
            ->fetch()
            ->keys();

        $this->categoryIds = array_map('intval', $validIds);

        if (count($this->categoryIds) !== count($categoryIds))
        {
            $this->server->error('Geçersiz bir sunucu kategorisi seçildi.', 'category_ids');
        }
    }

    public function save(): Server
    {
        $db = $this->db();
        $db->beginTransaction();

        try
        {
            $this->server->save(true, false);

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

    protected function generateUniqueSlug(string $title): string
    {
        $slug = trim($title);
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        if (is_string($transliterated) && $transliterated !== '')
        {
            $slug = $transliterated;
        }

        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        $slug = substr($slug ?: 'server', 0, 80);

        $base = $slug;
        $suffix = 2;
        $repo = $this->repository('Warext\MinecraftVote:Server');

        while ($repo->getServerBySlug($slug))
        {
            $candidateSuffix = '-' . $suffix++;
            $slug = substr($base, 0, 100 - strlen($candidateSuffix)) . $candidateSuffix;
        }

        return $slug;
    }

    protected function normalizeHost(string $host): string
    {
        $host = trim($host);
        if ($host === '')
        {
            return '';
        }

        if (str_contains($host, '://'))
        {
            $parsedHost = parse_url($host, PHP_URL_HOST);
            if (is_string($parsedHost) && $parsedHost !== '')
            {
                $host = $parsedHost;
            }
        }

        $host = trim($host, " \t\n\r\0\x0B./");
        return strtolower($host);
    }

    protected function isValidHost(string $host): bool
    {
        if ($host === '')
        {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP))
        {
            return true;
        }

        if (strlen($host) > 253)
        {
            return false;
        }

        return (bool)preg_match(
            '/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i',
            $host
        );
    }

    protected function isValidHttpUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL))
        {
            return false;
        }

        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }

    protected function assertServerAddressIsUnique(): void
    {
        if ($this->finder('Warext\MinecraftVote:Server')
            ->where('host', $this->server->host)
            ->where('port', $this->server->port)
            ->fetchOne())
        {
            $this->server->error('Bu Java sunucusu daha önce platforma eklenmiş.', 'host');
        }

        if ($this->server->bedrock_host !== '' && $this->finder('Warext\MinecraftVote:Server')
            ->where('bedrock_host', $this->server->bedrock_host)
            ->where('bedrock_port', $this->server->bedrock_port)
            ->fetchOne())
        {
            $this->server->error('Bu Bedrock sunucusu daha önce platforma eklenmiş.', 'bedrock_host');
        }
    }
}
