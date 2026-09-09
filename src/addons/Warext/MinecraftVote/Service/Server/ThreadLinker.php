<?php

namespace Warext\MinecraftVote\Service\Server;

use Warext\MinecraftVote\Entity\Category;
use Warext\MinecraftVote\Entity\Server;
use XF\App;
use XF\Entity\Thread;
use XF\Entity\User;
use XF\PrintableException;
use XF\Service\AbstractService;

class ThreadLinker extends AbstractService
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function resolve(string $url, User $actor, ?Server $server = null): ?Thread
    {
        $url = trim($url);
        if ($url === '')
        {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $boardHost = parse_url((string)$this->app->options()->boardUrl, PHP_URL_HOST);
        if ($host && $boardHost && strcasecmp((string)$host, (string)$boardHost) !== 0)
        {
            throw new PrintableException('Tanıtım konusu bu XenForo sitesine ait olmalıdır.');
        }

        if (!preg_match('~(?:^|[/?&])threads/(?:[^/?#]*\.)?(\d+)(?:/|$|[?#&])~i', $url, $match))
        {
            throw new PrintableException('Geçerli bir XenForo tanıtım konusu bağlantısı girin.');
        }

        $thread = $this->em()->find('XF:Thread', (int)$match[1], ['Forum', 'User']);
        if (!$thread)
        {
            throw new PrintableException('Tanıtım konusu bulunamadı.');
        }

        if (!$thread->canView($error))
        {
            throw new PrintableException('Bu tanıtım konusunu görüntüleme yetkiniz yok.');
        }

        $visitor = \XF::visitor();
        if ((int)$thread->user_id !== (int)$actor->user_id && !$visitor->is_moderator && !$visitor->is_admin)
        {
            throw new PrintableException('Yalnızca kendi tanıtım konunuzu sunucuya bağlayabilirsiniz.');
        }

        if (!$this->getMappedCategory($thread))
        {
            throw new PrintableException('Bu konu, sunucu tanıtım entegrasyonu açık bir foruma ait değil.');
        }

        $linked = $this->finder('Warext\MinecraftVote:Server')
            ->where('discussion_thread_id', $thread->thread_id)
            ->fetchOne();
        if ($linked && (!$server || (int)$linked->server_id !== (int)$server->server_id))
        {
            throw new PrintableException('Bu tanıtım konusu başka bir sunucu kaydına bağlı.');
        }

        return $thread;
    }

    public function getMappedCategory(Thread $thread): ?Category
    {
        $category = $this->finder('Warext\MinecraftVote:Category')
            ->where('forum_node_id', (int)$thread->node_id)
            ->where('thread_integration_enabled', 1)
            ->where('is_active', 1)
            ->fetchOne();

        return $category instanceof Category ? $category : null;
    }

    public function link(Server $server, ?Thread $thread): void
    {
        $server->discussion_thread_id = $thread ? (int)$thread->thread_id : 0;
        $server->save();
    }
}
