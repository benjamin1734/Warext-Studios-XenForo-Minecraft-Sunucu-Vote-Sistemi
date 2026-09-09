from pathlib import Path
import json
import re

ROOT = Path('src/addons/Warext/MinecraftVote')


def write(path, content):
    target = Path(path)
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(content, encoding='utf-8')


def replace(path, old, new):
    target = Path(path)
    text = target.read_text(encoding='utf-8')
    if old not in text:
        raise SystemExit(f'Pattern not found in {path}: {old[:100]}')
    target.write_text(text.replace(old, new, 1), encoding='utf-8')


addon_path = ROOT / 'addon.json'
addon = json.loads(addon_path.read_text(encoding='utf-8'))
addon['version_id'] = 1011010
addon['version_string'] = '1.1.1'
addon_path.write_text(json.dumps(addon, ensure_ascii=False, indent=4) + '\n', encoding='utf-8')

write(ROOT / 'Entity/Category.php', r'''<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Category extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_category';
        $structure->shortName = 'Warext\MinecraftVote:Category';
        $structure->primaryKey = 'category_id';
        $structure->columns = [
            'category_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'title' => ['type' => self::STR, 'maxLength' => 50, 'required' => true],
            'slug' => ['type' => self::STR, 'maxLength' => 50, 'required' => true],
            'description' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'display_order' => ['type' => self::UINT, 'default' => 10],
            'is_active' => ['type' => self::BOOL, 'default' => true],
            'forum_node_id' => ['type' => self::UINT, 'default' => 0],
            'thread_integration_enabled' => ['type' => self::BOOL, 'default' => false],
            'thread_default_server_type' => ['type' => self::STR, 'maxLength' => 20, 'default' => 'java']
        ];
        $structure->relations = [
            'Forum' => [
                'entity' => 'XF:Forum',
                'type' => self::TO_ONE,
                'conditions' => [['node_id', '=', '$forum_node_id']],
                'primary' => true
            ]
        ];

        return $structure;
    }
}
''')

write(ROOT / 'Entity/Server.php', r'''<?php

namespace Warext\MinecraftVote\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Server extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_warext_mc_server';
        $structure->shortName = 'Warext\MinecraftVote:Server';
        $structure->primaryKey = 'server_id';
        $structure->contentType = 'warext_mc_server';
        $structure->columns = [
            'server_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'owner_user_id' => ['type' => self::UINT, 'default' => 0],
            'title' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
            'slug' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
            'description' => ['type' => self::STR, 'nullable' => true, 'default' => null],
            'server_type' => ['type' => self::STR, 'maxLength' => 20, 'default' => 'java'],
            'host' => ['type' => self::STR, 'maxLength' => 255, 'required' => true],
            'port' => ['type' => self::UINT, 'default' => 25565],
            'bedrock_host' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'bedrock_port' => ['type' => self::UINT, 'default' => 19132],
            'website_url' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'discord_url' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'store_url' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'trailer_url' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'banner_path' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'animated_banner_path' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'cover_path' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'discussion_thread_id' => ['type' => self::UINT, 'default' => 0],
            'source_type' => ['type' => self::STR, 'maxLength' => 20, 'default' => 'manual'],
            'game_modes' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'version_min' => ['type' => self::STR, 'maxLength' => 30, 'default' => ''],
            'version_max' => ['type' => self::STR, 'maxLength' => 30, 'default' => ''],
            'country_code' => ['type' => self::STR, 'maxLength' => 2, 'default' => ''],
            'is_premium' => ['type' => self::BOOL, 'default' => false],
            'allow_cracked' => ['type' => self::BOOL, 'default' => false],
            'state' => ['type' => self::STR, 'maxLength' => 20, 'default' => 'pending'],
            'is_verified' => ['type' => self::BOOL, 'default' => false],
            'verification_method' => ['type' => self::STR, 'maxLength' => 20, 'default' => ''],
            'verification_token' => ['type' => self::STR, 'maxLength' => 64, 'default' => ''],
            'verification_token_date' => ['type' => self::UINT, 'default' => 0],
            'verified_date' => ['type' => self::UINT, 'default' => 0],
            'is_online' => ['type' => self::BOOL, 'default' => false],
            'ping_ms' => ['type' => self::UINT, 'default' => 0],
            'players_online' => ['type' => self::UINT, 'default' => 0],
            'players_max' => ['type' => self::UINT, 'default' => 0],
            'motd' => ['type' => self::STR, 'nullable' => true, 'default' => null],
            'detected_version' => ['type' => self::STR, 'maxLength' => 100, 'default' => ''],
            'last_ping_error' => ['type' => self::STR, 'maxLength' => 500, 'default' => ''],
            'uptime_bp' => ['type' => self::UINT, 'default' => 0],
            'vote_count_total' => ['type' => self::UINT, 'default' => 0],
            'vote_count_month' => ['type' => self::UINT, 'default' => 0],
            'vote_count_today' => ['type' => self::UINT, 'default' => 0],
            'unique_voters_month' => ['type' => self::UINT, 'default' => 0],
            'votes_24h' => ['type' => self::UINT, 'default' => 0],
            'votes_72h' => ['type' => self::UINT, 'default' => 0],
            'popular_score_bp' => ['type' => self::UINT, 'default' => 0],
            'trend_score_bp' => ['type' => self::UINT, 'default' => 0],
            'rank_popular' => ['type' => self::UINT, 'default' => 0],
            'rank_trending' => ['type' => self::UINT, 'default' => 0],
            'ranking_updated_date' => ['type' => self::UINT, 'default' => 0],
            'view_count' => ['type' => self::UINT, 'default' => 0],
            'rating_count' => ['type' => self::UINT, 'default' => 0],
            'rating_sum' => ['type' => self::UINT, 'default' => 0],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'last_update_date' => ['type' => self::UINT, 'default' => 0],
            'last_ping_date' => ['type' => self::UINT, 'default' => 0]
        ];
        $structure->getters = [
            'uptime_percent' => true,
            'rating_average' => true,
            'popular_score' => true,
            'trend_score' => true,
            'is_owner' => true,
            'can_edit' => true,
            'can_publish_updates' => true,
            'can_view_stats' => true,
            'can_manage_votifier' => true,
            'can_manage_reviews' => true
        ];
        $structure->relations = [
            'Owner' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => [['user_id', '=', '$owner_user_id']],
                'primary' => true
            ],
            'DiscussionThread' => [
                'entity' => 'XF:Thread',
                'type' => self::TO_ONE,
                'conditions' => [['thread_id', '=', '$discussion_thread_id']],
                'primary' => true
            ],
            'ApprovalQueue' => [
                'entity' => 'XF:ApprovalQueue',
                'type' => self::TO_ONE,
                'conditions' => [
                    ['content_type', '=', 'warext_mc_server'],
                    ['content_id', '=', '$server_id']
                ],
                'primary' => true
            ]
        ];

        return $structure;
    }

    public function getUptimePercent(): float
    {
        return min(100, max(0, $this->uptime_bp / 100));
    }

    public function getRatingAverage(): float
    {
        if (!$this->rating_count)
        {
            return 0.0;
        }

        return round($this->rating_sum / $this->rating_count, 2);
    }

    public function getPopularScore(): float
    {
        return min(100, max(0, $this->popular_score_bp / 100));
    }

    public function getTrendScore(): float
    {
        return min(100, max(0, $this->trend_score_bp / 100));
    }

    public function getIsOwner(): bool
    {
        $visitor = \XF::visitor();
        return $visitor->user_id > 0 && (int)$this->owner_user_id === (int)$visitor->user_id;
    }

    public function getCanEdit(): bool
    {
        return $this->hasTeamPermission('edit_content');
    }

    public function getCanPublishUpdates(): bool
    {
        return $this->hasTeamPermission('publish_updates');
    }

    public function getCanViewStats(): bool
    {
        return $this->hasTeamPermission('view_stats');
    }

    public function getCanManageVotifier(): bool
    {
        return $this->hasTeamPermission('manage_votifier');
    }

    public function getCanManageReviews(): bool
    {
        return $this->hasTeamPermission('manage_reviews');
    }

    public function canApproveUnapprove(&$error = null): bool
    {
        $visitor = \XF::visitor();
        return (bool)($visitor->is_moderator || $visitor->is_admin);
    }

    protected function hasTeamPermission(string $permission): bool
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            return false;
        }

        return $this->repository('Warext\MinecraftVote:ServerTeam')
            ->hasPermission($this, (int)$visitor->user_id, $permission);
    }

    protected function _preSave(): void
    {
        $isNew = !$this->server_id;

        if (!$isNew && $this->hasEndpointChanges())
        {
            $this->is_verified = false;
            $this->verification_method = '';
            $this->verification_token = '';
            $this->verification_token_date = 0;
            $this->verified_date = 0;
        }

        if (!$this->created_date)
        {
            $this->created_date = \XF::$time;
        }

        if ($isNew || !$this->last_update_date || $this->hasContentChanges())
        {
            $this->last_update_date = \XF::$time;
        }

        $this->slug = trim(strtolower($this->slug));
        $this->country_code = strtoupper(trim($this->country_code));

        if (!in_array($this->server_type, ['java', 'bedrock', 'crossplay'], true))
        {
            $this->error(\XF::phrase('please_enter_valid_value'), 'server_type');
        }

        if (!in_array($this->source_type, ['manual', 'thread'], true))
        {
            $this->error(\XF::phrase('please_enter_valid_value'), 'source_type');
        }

        if (!in_array($this->state, ['pending', 'active', 'rejected', 'suspended'], true))
        {
            $this->error(\XF::phrase('please_enter_valid_value'), 'state');
        }

        if ($this->port < 1 || $this->port > 65535)
        {
            $this->error(\XF::phrase('please_enter_valid_value'), 'port');
        }

        if ($this->bedrock_port < 1 || $this->bedrock_port > 65535)
        {
            $this->error(\XF::phrase('please_enter_valid_value'), 'bedrock_port');
        }

        if ($this->verification_method !== '' && !in_array($this->verification_method, ['motd', 'dns_txt'], true))
        {
            $this->error('Geçersiz sunucu doğrulama yöntemi.', 'verification_method');
        }
    }

    protected function _postSave(): void
    {
        if ($this->state === 'pending')
        {
            $approvalQueue = $this->getRelationOrDefault('ApprovalQueue', false);
            $approvalQueue->content_date = $this->created_date ?: \XF::$time;
            $approvalQueue->save();
        }
        elseif ($this->ApprovalQueue)
        {
            $this->ApprovalQueue->delete();
        }
    }

    protected function _postDelete(): void
    {
        if ($this->ApprovalQueue)
        {
            $this->ApprovalQueue->delete();
        }

        foreach ([$this->banner_path, $this->animated_banner_path, $this->cover_path] as $path)
        {
            if ($path !== '')
            {
                \XF\Util\File::deleteFromAbstractedPath('data://' . ltrim($path, '/'));
            }
        }

        $db = $this->db();
        $serverId = (int)$this->server_id;

        $updateRows = $db->fetchAll(
            'SELECT update_id FROM xf_warext_mc_server_update WHERE server_id = ?',
            [$serverId]
        );
        $alertRepo = $this->repository('XF:UserAlert');
        foreach ($updateRows as $row)
        {
            $alertRepo->fastDeleteAlertsForContent('warext_mc_server_update', (int)$row['update_id']);
        }

        foreach ([
            'xf_warext_mc_sponsor',
            'xf_warext_mc_server_achievement',
            'xf_warext_mc_server_update',
            'xf_warext_mc_favorite',
            'xf_warext_mc_review',
            'xf_warext_mc_report',
            'xf_warext_mc_server_category',
            'xf_warext_mc_server_team',
            'xf_warext_mc_ping_history',
            'xf_warext_mc_vote',
            'xf_warext_mc_votifier'
        ] as $table)
        {
            $db->delete($table, 'server_id = ?', $serverId);
        }
    }

    protected function hasEndpointChanges(): bool
    {
        foreach (['server_type', 'host', 'port', 'bedrock_host', 'bedrock_port'] as $field)
        {
            if ($this->isChanged($field))
            {
                return true;
            }
        }

        return false;
    }

    protected function hasContentChanges(): bool
    {
        foreach ([
            'owner_user_id', 'title', 'slug', 'description', 'server_type', 'host', 'port',
            'bedrock_host', 'bedrock_port', 'website_url', 'discord_url', 'store_url', 'trailer_url',
            'banner_path', 'animated_banner_path', 'cover_path', 'discussion_thread_id', 'source_type',
            'game_modes', 'version_min', 'version_max', 'country_code', 'is_premium', 'allow_cracked',
            'state', 'is_verified', 'verification_method', 'verification_token', 'verification_token_date',
            'verified_date'
        ] as $field)
        {
            if ($this->isChanged($field))
            {
                return true;
            }
        }

        return false;
    }
}
''')

write(ROOT / 'Service/Server/Media.php', r'''<?php

namespace Warext\MinecraftVote\Service\Server;

use Warext\MinecraftVote\Entity\Server;
use XF\App;
use XF\Http\Upload;
use XF\PrintableException;
use XF\Service\AbstractService;

class Media extends AbstractService
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    public function validateUpload(?Upload $upload, string $type): void
    {
        if (!$upload)
        {
            return;
        }

        $config = $this->getConfig($type);
        $tempFile = $upload->getTempFile();
        $fileSize = @filesize($tempFile);
        if ($fileSize === false || $fileSize < 1 || $fileSize > $config['max'])
        {
            throw new PrintableException($config['size_error']);
        }

        $imageInfo = @getimagesize($tempFile);
        if (!$imageInfo || empty($imageInfo[0]) || empty($imageInfo[1]) || empty($imageInfo[2]))
        {
            throw new PrintableException('Yüklenen dosya geçerli bir görsel değil.');
        }

        $extension = $this->extensionFromImageType((int)$imageInfo[2]);
        if (!in_array($extension, $config['extensions'], true))
        {
            throw new PrintableException($config['format_error']);
        }

        $width = (int)$imageInfo[0];
        $height = (int)$imageInfo[1];
        if ($type === 'banner' || $type === 'animated_banner')
        {
            if ($width !== 468 || $height !== 60)
            {
                throw new PrintableException('Liste bannerı tam 468×60 piksel olmalıdır.');
            }
        }
        elseif ($width < 800 || $height < 300)
        {
            throw new PrintableException('Kapak görseli en az 800×300 piksel olmalıdır.');
        }
    }

    public function store(Server $server, Upload $upload, string $type): void
    {
        $this->validateUpload($upload, $type);
        $config = $this->getConfig($type);
        $imageInfo = getimagesize($upload->getTempFile());
        $extension = $this->extensionFromImageType((int)$imageInfo[2]);
        $field = $config['field'];
        $oldPath = (string)$server->{$field};
        $relativePath = 'warext-minecraft/server/' . $server->server_id . '/' . $type . '.' . $extension;

        \XF\Util\File::copyFileToAbstractedPath(
            $upload->getTempFile(),
            'data://' . $relativePath
        );

        if ($oldPath !== '' && $oldPath !== $relativePath)
        {
            \XF\Util\File::deleteFromAbstractedPath('data://' . ltrim($oldPath, '/'));
        }

        $server->{$field} = $relativePath;
        $server->save();
    }

    public function remove(Server $server, string $type): void
    {
        $config = $this->getConfig($type);
        $field = $config['field'];
        $path = (string)$server->{$field};
        if ($path !== '')
        {
            \XF\Util\File::deleteFromAbstractedPath('data://' . ltrim($path, '/'));
            $server->{$field} = '';
            $server->save();
        }
    }

    protected function getConfig(string $type): array
    {
        $configs = [
            'banner' => [
                'field' => 'banner_path',
                'max' => 10 * 1024 * 1024,
                'extensions' => ['jpg', 'png', 'webp'],
                'size_error' => 'Statik banner en fazla 10 MB olabilir.',
                'format_error' => 'Statik banner JPG, PNG veya WebP olmalıdır.'
            ],
            'animated_banner' => [
                'field' => 'animated_banner_path',
                'max' => 15 * 1024 * 1024,
                'extensions' => ['gif'],
                'size_error' => 'Hareketli banner en fazla 15 MB olabilir.',
                'format_error' => 'Hareketli banner GIF olmalıdır.'
            ],
            'cover' => [
                'field' => 'cover_path',
                'max' => 12 * 1024 * 1024,
                'extensions' => ['jpg', 'png', 'webp'],
                'size_error' => 'Kapak görseli en fazla 12 MB olabilir.',
                'format_error' => 'Kapak görseli JPG, PNG veya WebP olmalıdır.'
            ]
        ];

        if (!isset($configs[$type]))
        {
            throw new PrintableException('Geçersiz sunucu medya türü.');
        }

        return $configs[$type];
    }

    protected function extensionFromImageType(int $imageType): string
    {
        if ($imageType === IMAGETYPE_JPEG)
        {
            return 'jpg';
        }
        if ($imageType === IMAGETYPE_PNG)
        {
            return 'png';
        }
        if ($imageType === IMAGETYPE_GIF)
        {
            return 'gif';
        }
        if (defined('IMAGETYPE_WEBP') && $imageType === IMAGETYPE_WEBP)
        {
            return 'webp';
        }

        return '';
    }
}
''')

write(ROOT / 'Service/Server/ThreadLinker.php', r'''<?php

namespace Warext\MinecraftVote\Service\Server;

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

        if (!preg_match('~(?:^|/)threads/(?:[^/?#]*\.)?(\d+)(?:/|$|[?#])~i', $url, $match))
        {
            throw new PrintableException('Geçerli bir XenForo tanıtım konusu bağlantısı girin.');
        }

        $thread = $this->em()->find('XF:Thread', (int)$match[1]);
        if (!$thread)
        {
            throw new PrintableException('Tanıtım konusu bulunamadı.');
        }

        $visitor = \XF::visitor();
        if ((int)$thread->user_id !== (int)$actor->user_id && !$visitor->is_moderator && !$visitor->is_admin)
        {
            throw new PrintableException('Yalnızca kendi tanıtım konunuzu sunucuya bağlayabilirsiniz.');
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

    public function link(Server $server, ?Thread $thread): void
    {
        $server->discussion_thread_id = $thread ? (int)$thread->thread_id : 0;
        $server->save();
    }
}
''')

write(ROOT / 'ApprovalQueue/Server.php', r'''<?php

namespace Warext\MinecraftVote\ApprovalQueue;

use Warext\MinecraftVote\Entity\Server as ServerEntity;
use XF\ApprovalQueue\AbstractHandler;
use XF\Mvc\Entity\Entity;

class Server extends AbstractHandler
{
    protected function canActionContent(Entity $content, &$error = null)
    {
        return $content instanceof ServerEntity && $content->canApproveUnapprove($error);
    }

    public function actionApprove(ServerEntity $server): void
    {
        if ($server->state !== 'active')
        {
            $server->state = 'active';
            $server->save();
        }
    }

    public function actionDelete(ServerEntity $server): void
    {
        if ($server->state !== 'rejected')
        {
            $server->state = 'rejected';
            $server->save();
        }
    }
}
''')

write(ROOT / 'Listener.php', r'''<?php

namespace Warext\MinecraftVote;

use XF\Mvc\Entity\Entity;
use XF\Template\Templater;

class Listener
{
    public static function threadFormPreRender(Templater $templater, &$type, &$template, array &$params): void
    {
        if (empty($params['forum']) || !\XF::visitor()->user_id)
        {
            return;
        }

        $category = \XF::finder('Warext\MinecraftVote:Category')
            ->where('forum_node_id', (int)$params['forum']->node_id)
            ->where('thread_integration_enabled', 1)
            ->where('is_active', 1)
            ->fetchOne();
        if (!$category)
        {
            return;
        }

        $params['warextMcThreadCategory'] = $category;
    }

    public static function threadViewPreRender(Templater $templater, &$type, &$template, array &$params): void
    {
        if (empty($params['thread']))
        {
            return;
        }

        $server = \XF::finder('Warext\MinecraftVote:Server')
            ->where('discussion_thread_id', (int)$params['thread']->thread_id)
            ->fetchOne();
        if (!$server)
        {
            return;
        }

        $visitor = \XF::visitor();
        if ($server->state !== 'active' && (int)$server->owner_user_id !== (int)$visitor->user_id && !$visitor->is_moderator && !$visitor->is_admin)
        {
            return;
        }

        $params['warextMcLinkedServer'] = $server;
    }

    public static function postEntityPostSave(Entity $entity): void
    {
        if (!$entity instanceof \XF\Entity\Post || !$entity->isInsert() || (int)$entity->position !== 0)
        {
            return;
        }

        $request = \XF::app()->request();
        $host = trim((string)$request->filter('warext_mc_host', 'str'));
        if ($host === '')
        {
            return;
        }

        try
        {
            $thread = $entity->Thread;
            if (!$thread || !$thread->Forum || !$thread->User)
            {
                return;
            }

            $category = \XF::finder('Warext\MinecraftVote:Category')
                ->where('forum_node_id', (int)$thread->node_id)
                ->where('thread_integration_enabled', 1)
                ->where('is_active', 1)
                ->fetchOne();
            if (!$category)
            {
                return;
            }

            $serverType = strtolower(trim((string)$request->filter('warext_mc_server_type', 'str')));
            if (!in_array($serverType, ['java', 'bedrock', 'crossplay'], true))
            {
                $serverType = (string)$category->thread_default_server_type;
            }

            $data = [
                'title' => (string)$thread->title,
                'description' => (string)$entity->message,
                'server_type' => $serverType,
                'host' => $host,
                'port' => (int)$request->filter('warext_mc_port', 'uint') ?: 25565,
                'bedrock_host' => (string)$request->filter('warext_mc_bedrock_host', 'str'),
                'bedrock_port' => (int)$request->filter('warext_mc_bedrock_port', 'uint') ?: 19132,
                'website_url' => (string)$request->filter('warext_mc_website_url', 'str'),
                'discord_url' => (string)$request->filter('warext_mc_discord_url', 'str'),
                'store_url' => '',
                'trailer_url' => '',
                'game_modes' => (string)$category->title,
                'version_min' => '',
                'version_max' => '',
                'country_code' => 'TR',
                'is_premium' => (bool)$request->filter('warext_mc_is_premium', 'bool'),
                'allow_cracked' => (bool)$request->filter('warext_mc_allow_cracked', 'bool')
            ];

            $existingThread = \XF::finder('Warext\MinecraftVote:Server')
                ->where('discussion_thread_id', (int)$thread->thread_id)
                ->fetchOne();
            if ($existingThread)
            {
                return;
            }

            $creator = \XF::service('Warext\MinecraftVote:Server\Creator');
            $creator->setOwner($thread->User);
            $creator->setData($data);
            $creator->setCategoryIds([(int)$category->category_id]);
            $server = $creator->save(false);
            $server->discussion_thread_id = (int)$thread->thread_id;
            $server->source_type = 'thread';
            $server->save();
        }
        catch (\Throwable $e)
        {
            \XF::logException($e, false, 'Warext MinecraftVote thread integration: ');
        }
    }

    public static function threadEntityPostDelete(Entity $entity): void
    {
        if (!$entity instanceof \XF\Entity\Thread)
        {
            return;
        }

        $servers = \XF::finder('Warext\MinecraftVote:Server')
            ->where('discussion_thread_id', (int)$entity->thread_id)
            ->fetch();
        foreach ($servers as $server)
        {
            $server->discussion_thread_id = 0;
            $server->save();
        }
    }
}
''')

write(ROOT / 'Pub/Controller/Add.php', r'''<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Security\PublicPermissions;
use XF\Pub\Controller\AbstractController;

class Add extends AbstractController
{
    public function actionIndex()
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id || !PublicPermissions::allows('addServer', false, true))
        {
            return $this->noPermission();
        }

        if ($this->isPost())
        {
            $input = $this->filter([
                'title' => 'str',
                'description' => 'str',
                'server_type' => 'str',
                'host' => 'str',
                'port' => 'uint',
                'bedrock_host' => 'str',
                'bedrock_port' => 'uint',
                'website_url' => 'str',
                'discord_url' => 'str',
                'store_url' => 'str',
                'trailer_url' => 'str',
                'discussion_thread_url' => 'str',
                'game_modes' => 'str',
                'version_min' => 'str',
                'version_max' => 'str',
                'country_code' => 'str',
                'is_premium' => 'bool',
                'allow_cracked' => 'bool',
                'category_ids' => 'array-uint'
            ]);

            $media = $this->service('Warext\MinecraftVote:Server\Media');
            $uploads = [
                'banner' => $this->request->getFile('banner'),
                'animated_banner' => $this->request->getFile('animated_banner'),
                'cover' => $this->request->getFile('cover')
            ];

            try
            {
                foreach ($uploads as $type => $upload)
                {
                    $media->validateUpload($upload, $type);
                }

                $thread = $this->service('Warext\MinecraftVote:Server\ThreadLinker')
                    ->resolve($input['discussion_thread_url'], $visitor);

                $creator = $this->service('Warext\MinecraftVote:Server\Creator');
                $creator->setOwner($visitor);
                $creator->setData($input);
                $creator->setCategoryIds($input['category_ids']);
                $server = $creator->save();
                if ($thread)
                {
                    $server->discussion_thread_id = (int)$thread->thread_id;
                    $server->save();
                }

                foreach ($uploads as $type => $upload)
                {
                    if ($upload)
                    {
                        $media->store($server, $upload, $type);
                    }
                }
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            return $this->redirect(
                $this->buildLink('sunucular/detay', $server),
                'Sunucu kaydınız onay için gönderildi.'
            );
        }

        $categories = $this->finder('Warext\MinecraftVote:Category')
            ->where('is_active', 1)
            ->order('display_order')
            ->fetch();

        return $this->view('Warext\MinecraftVote:Server\Add', 'warext_mc_server_add', [
            'categories' => $categories
        ]);
    }
}
''')

write(ROOT / 'Pub/Controller/Edit.php', r'''<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\Server;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Edit extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $server = $this->assertCanEdit((int)$params->server_id);

        if ($this->isPost())
        {
            $input = $this->filter([
                'title' => 'str',
                'description' => 'str',
                'server_type' => 'str',
                'host' => 'str',
                'port' => 'uint',
                'bedrock_host' => 'str',
                'bedrock_port' => 'uint',
                'website_url' => 'str',
                'discord_url' => 'str',
                'store_url' => 'str',
                'trailer_url' => 'str',
                'discussion_thread_url' => 'str',
                'game_modes' => 'str',
                'version_min' => 'str',
                'version_max' => 'str',
                'country_code' => 'str',
                'is_premium' => 'bool',
                'allow_cracked' => 'bool',
                'category_ids' => 'array-uint',
                'remove_banner' => 'bool',
                'remove_animated_banner' => 'bool',
                'remove_cover' => 'bool'
            ]);

            $wasActive = $server->state === 'active';
            $media = $this->service('Warext\MinecraftVote:Server\Media');
            $uploads = [
                'banner' => $this->request->getFile('banner'),
                'animated_banner' => $this->request->getFile('animated_banner'),
                'cover' => $this->request->getFile('cover')
            ];

            try
            {
                foreach ($uploads as $type => $upload)
                {
                    $media->validateUpload($upload, $type);
                }

                $thread = $this->service('Warext\MinecraftVote:Server\ThreadLinker')
                    ->resolve($input['discussion_thread_url'], \XF::visitor(), $server);

                $editor = $this->service(
                    'Warext\MinecraftVote:Server\Editor',
                    $server,
                    (int)\XF::visitor()->user_id
                );
                $editor->setData($input);
                $editor->setCategoryIds($input['category_ids']);
                $editor->save();

                $server->discussion_thread_id = $thread ? (int)$thread->thread_id : 0;
                $server->save();

                foreach (['banner', 'animated_banner', 'cover'] as $type)
                {
                    $removeKey = 'remove_' . $type;
                    if (!empty($input[$removeKey]))
                    {
                        $media->remove($server, $type);
                    }
                    if ($uploads[$type])
                    {
                        $media->store($server, $uploads[$type], $type);
                    }
                }
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            $message = $wasActive && $server->state === 'pending'
                ? 'Sunucu bağlantı bilgileri değişti. Sahiplik doğrulaması sıfırlandı ve kayıt yeniden yönetici onayına gönderildi.'
                : 'Sunucu bilgileri güncellendi.';

            return $this->redirect(
                $this->buildLink('sunucular/detay', $server),
                $message
            );
        }

        $categories = $this->finder('Warext\MinecraftVote:Category')
            ->where('is_active', 1)
            ->order('display_order')
            ->fetch();

        $rows = $this->app->db()->fetchAll(
            'SELECT category_id FROM xf_warext_mc_server_category WHERE server_id = ?',
            [$server->server_id]
        );
        $selectedCategoryIds = array_map('intval', array_column($rows, 'category_id'));

        return $this->view('Warext\MinecraftVote:Server\Edit', 'warext_mc_server_edit', [
            'server' => $server,
            'categories' => $categories,
            'selectedCategoryIds' => $selectedCategoryIds
        ]);
    }

    protected function assertCanEdit(int $serverId): Server
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            throw $this->exception($this->noPermission());
        }

        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId, ['DiscussionThread']);
        if (!$server)
        {
            throw $this->exception($this->notFound());
        }

        if (!$this->repository('Warext\MinecraftVote:ServerTeam')
            ->hasPermission($server, $visitor->user_id, 'edit_content'))
        {
            throw $this->exception($this->noPermission());
        }

        return $server;
    }
}
''')

write(ROOT / 'Admin/Controller/Category.php', r'''<?php

namespace Warext\MinecraftVote\Admin\Controller;

use Warext\MinecraftVote\Entity\Category as CategoryEntity;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Category extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertAdminPermission('warextMinecraftVote');
    }

    public function actionIndex()
    {
        if ($this->isPost())
        {
            $category = $this->em()->create('Warext\MinecraftVote:Category');
            $this->applyInput($category);
            $category->save();

            return $this->redirect(
                $this->buildLink('warext-minecraft/categories'),
                'Kategori oluşturuldu.'
            );
        }

        $categories = $this->finder('Warext\MinecraftVote:Category')
            ->with('Forum')
            ->order('display_order', 'ASC')
            ->order('category_id', 'ASC')
            ->fetch();

        $usageCounts = $this->app->db()->fetchPairs(
            'SELECT category_id, COUNT(*) FROM xf_warext_mc_server_category GROUP BY category_id'
        );

        $categoryRows = [];
        foreach ($categories as $category)
        {
            $categoryRows[] = [
                'category' => $category,
                'usageCount' => (int)($usageCounts[$category->category_id] ?? 0)
            ];
        }

        return $this->view('Warext\MinecraftVote:Category\Index', 'warext_mc_admin_category_index', [
            'categoryRows' => $categoryRows,
            'forumOptions' => $this->getForumOptions()
        ]);
    }

    public function actionEdit(ParameterBag $params)
    {
        $category = $this->assertCategoryExists((int)$params->category_id);

        if ($this->isPost())
        {
            $this->applyInput($category);
            $category->save();

            return $this->redirect(
                $this->buildLink('warext-minecraft/categories'),
                'Kategori güncellendi.'
            );
        }

        return $this->view('Warext\MinecraftVote:Category\Edit', 'warext_mc_admin_category_edit', [
            'category' => $category,
            'forumOptions' => $this->getForumOptions()
        ]);
    }

    public function actionToggle(ParameterBag $params)
    {
        if (!$this->isPost())
        {
            return $this->redirect($this->buildLink('warext-minecraft/categories'));
        }

        $category = $this->assertCategoryExists((int)$params->category_id);
        $category->is_active = !$category->is_active;
        $category->save();

        return $this->redirect($this->buildLink('warext-minecraft/categories'));
    }

    public function actionDelete(ParameterBag $params)
    {
        if (!$this->isPost())
        {
            return $this->redirect($this->buildLink('warext-minecraft/categories'));
        }

        $category = $this->assertCategoryExists((int)$params->category_id);
        $this->app->db()->delete('xf_warext_mc_server_category', 'category_id = ?', $category->category_id);
        $category->delete();

        return $this->redirect(
            $this->buildLink('warext-minecraft/categories'),
            'Kategori silindi.'
        );
    }

    protected function applyInput(CategoryEntity $category): void
    {
        $input = $this->filter([
            'title' => 'str',
            'slug' => 'str',
            'description' => 'str',
            'display_order' => 'uint',
            'is_active' => 'bool',
            'forum_node_id' => 'uint',
            'thread_integration_enabled' => 'bool',
            'thread_default_server_type' => 'str'
        ]);

        $title = trim($input['title']);
        if ($title === '')
        {
            $category->error('Kategori adı boş bırakılamaz.', 'title');
        }

        $slug = trim($input['slug']);
        $slug = $this->slugify($slug === '' ? $title : $slug);
        $existing = $this->finder('Warext\MinecraftVote:Category')
            ->where('slug', $slug)
            ->fetchOne();
        if ($existing && (int)$existing->category_id !== (int)$category->category_id)
        {
            $category->error('Bu kategori kısa adı zaten kullanılıyor.', 'slug');
        }

        $serverType = strtolower(trim($input['thread_default_server_type']));
        if (!in_array($serverType, ['java', 'bedrock', 'crossplay'], true))
        {
            $serverType = 'java';
        }

        if ($input['thread_integration_enabled'] && !$input['forum_node_id'])
        {
            $category->error('Konu entegrasyonu için bir XenForo forumu seçin.', 'forum_node_id');
        }

        if ($input['forum_node_id'])
        {
            $forum = $this->em()->find('XF:Forum', (int)$input['forum_node_id']);
            if (!$forum)
            {
                $category->error('Seçilen XenForo forumu bulunamadı.', 'forum_node_id');
            }

            $mapped = $this->finder('Warext\MinecraftVote:Category')
                ->where('forum_node_id', (int)$input['forum_node_id'])
                ->fetchOne();
            if ($mapped && (int)$mapped->category_id !== (int)$category->category_id)
            {
                $category->error('Bu XenForo forumu başka bir Minecraft kategorisine bağlı.', 'forum_node_id');
            }
        }

        $category->title = mb_substr($title, 0, 50);
        $category->slug = $slug;
        $category->description = mb_substr(trim($input['description']), 0, 255);
        $category->display_order = max(1, (int)$input['display_order']);
        $category->is_active = (bool)$input['is_active'];
        $category->forum_node_id = (int)$input['forum_node_id'];
        $category->thread_integration_enabled = (bool)$input['thread_integration_enabled'];
        $category->thread_default_server_type = $serverType;
    }

    protected function getForumOptions(): array
    {
        $options = [0 => 'Bağlantı yok'];
        $forums = $this->finder('XF:Forum')->order('title')->fetch();
        foreach ($forums as $forum)
        {
            $options[(int)$forum->node_id] = (string)$forum->title;
        }

        return $options;
    }

    protected function slugify(string $value): string
    {
        $value = trim($value);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($ascii) && $ascii !== '')
        {
            $value = $ascii;
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return substr($value ?: 'kategori', 0, 50);
    }

    protected function assertCategoryExists(int $categoryId): CategoryEntity
    {
        $category = $this->em()->find('Warext\MinecraftVote:Category', $categoryId);
        if (!$category)
        {
            throw $this->exception($this->notFound());
        }

        return $category;
    }
}
''')

creator = ROOT / 'Service/Server/Creator.php'
text = creator.read_text(encoding='utf-8')
text = text.replace("foreach (['website_url', 'discord_url', 'store_url'] as $field)", "foreach (['website_url', 'discord_url', 'store_url', 'trailer_url'] as $field)")
text = text.replace("'website_url', 'discord_url', 'store_url', 'game_modes'", "'website_url', 'discord_url', 'store_url', 'trailer_url', 'game_modes'")
text = text.replace('public function save(): Server', 'public function save(bool $manageTransaction = true): Server')
text = text.replace("        $db = $this->db();\n        $db->beginTransaction();", "        $db = $this->db();\n        if ($manageTransaction)\n        {\n            $db->beginTransaction();\n        }")
text = text.replace("            $db->commit();", "            if ($manageTransaction)\n            {\n                $db->commit();\n            }")
text = text.replace("            $db->rollback();", "            if ($manageTransaction)\n            {\n                $db->rollback();\n            }")
creator.write_text(text, encoding='utf-8')

editor = ROOT / 'Service/Server/Editor.php'
text = editor.read_text(encoding='utf-8')
text = text.replace("foreach (['website_url', 'discord_url', 'store_url'] as $field)", "foreach (['website_url', 'discord_url', 'store_url', 'trailer_url'] as $field)")
text = text.replace("'website_url', 'discord_url', 'store_url', 'game_modes'", "'website_url', 'discord_url', 'store_url', 'trailer_url', 'game_modes'")
editor.write_text(text, encoding='utf-8')

setup = ROOT / 'Setup.php'
text = setup.read_text(encoding='utf-8')
text = text.replace("    public function installStep17(): void\n    {\n        $this->ensureSponsorPurchaseSupport();\n    }\n", "    public function installStep17(): void\n    {\n        $this->ensureSponsorPurchaseSupport();\n    }\n\n    public function installStep18(): void\n    {\n        $this->ensureThreadMediaIntegration();\n    }\n")
text = text.replace("    public function upgrade1010040Step2(): void\n    {\n        $this->ensureSponsorPurchaseSupport();\n    }\n", "    public function upgrade1010040Step2(): void\n    {\n        $this->ensureSponsorPurchaseSupport();\n    }\n\n    public function upgrade1011010Step1(): void\n    {\n        $this->ensureThreadMediaIntegration();\n    }\n")
marker = "    protected function addRankingColumns(): void\n"
method = r'''    protected function ensureThreadMediaIntegration(): void
    {
        $sm = $this->schemaManager();
        foreach ([
            'trailer_url' => ['varchar', 255, 'store_url'],
            'banner_path' => ['varchar', 255, 'trailer_url'],
            'animated_banner_path' => ['varchar', 255, 'banner_path'],
            'cover_path' => ['varchar', 255, 'animated_banner_path'],
            'discussion_thread_id' => ['int', null, 'cover_path'],
            'source_type' => ['varchar', 20, 'discussion_thread_id']
        ] as $column => $spec)
        {
            if (!$sm->columnExists('xf_warext_mc_server', $column))
            {
                $sm->alterTable('xf_warext_mc_server', function (Alter $table) use ($column, $spec)
                {
                    $definition = $spec[1] ? $table->addColumn($column, $spec[0], $spec[1]) : $table->addColumn($column, $spec[0]);
                    $definition->setDefault($column === 'discussion_thread_id' ? 0 : ($column === 'source_type' ? 'manual' : ''))->after($spec[2]);
                    if ($column === 'discussion_thread_id')
                    {
                        $table->addKey('discussion_thread_id', 'warext_mc_server_thread');
                    }
                });
            }
        }

        foreach ([
            'forum_node_id' => ['int', null, 'is_active'],
            'thread_integration_enabled' => ['tinyint', null, 'forum_node_id'],
            'thread_default_server_type' => ['varchar', 20, 'thread_integration_enabled']
        ] as $column => $spec)
        {
            if (!$sm->columnExists('xf_warext_mc_category', $column))
            {
                $sm->alterTable('xf_warext_mc_category', function (Alter $table) use ($column, $spec)
                {
                    $definition = $spec[1] ? $table->addColumn($column, $spec[0], $spec[1]) : $table->addColumn($column, $spec[0]);
                    $definition->setDefault($column === 'thread_default_server_type' ? 'java' : 0)->after($spec[2]);
                    if ($column === 'forum_node_id')
                    {
                        $table->addKey('forum_node_id', 'warext_mc_category_forum');
                    }
                });
            }
        }

        $db = $this->db();
        $db->query(
            'INSERT INTO xf_content_type (content_type, addon_id, fields) VALUES (?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE addon_id = VALUES(addon_id)',
            ['warext_mc_server', 'Warext/MinecraftVote', '']
        );
        foreach ([
            'entity' => 'Warext\\MinecraftVote:Server',
            'approval_queue_handler_class' => 'Warext\\MinecraftVote\\ApprovalQueue\\Server'
        ] as $fieldName => $fieldValue)
        {
            $db->query(
                'INSERT INTO xf_content_type_field (content_type, field_name, field_value, addon_id) VALUES (?, ?, ?, ?) '
                . 'ON DUPLICATE KEY UPDATE field_value = VALUES(field_value), addon_id = VALUES(addon_id)',
                ['warext_mc_server', $fieldName, $fieldValue, 'Warext/MinecraftVote']
            );
        }
        $db->delete('xf_data_registry', 'data_key = ?', 'contentTypes');
    }

'''
if marker not in text:
    raise SystemExit('Setup insert marker missing')
text = text.replace(marker, method + marker, 1)
text = text.replace("        $this->db()->delete('xf_purchasable', 'purchasable_type_id = ?', 'warext_mc_sponsor');", "        $this->db()->delete('xf_purchasable', 'purchasable_type_id = ?', 'warext_mc_sponsor');\n        $this->db()->delete('xf_approval_queue', 'content_type = ?', 'warext_mc_server');\n        $this->db()->delete('xf_content_type_field', 'content_type = ?', 'warext_mc_server');\n        $this->db()->delete('xf_content_type', 'content_type = ?', 'warext_mc_server');\n        $this->db()->delete('xf_data_registry', 'data_key = ?', 'contentTypes');")
setup.write_text(text, encoding='utf-8')

write(ROOT / '_output/content_type_fields/warext_mc_server-entity.json', json.dumps({
    'content_type': 'warext_mc_server',
    'field_name': 'entity',
    'field_value': 'Warext\\MinecraftVote:Server'
}, indent=4) + '\n')
write(ROOT / '_output/content_type_fields/warext_mc_server-approval_queue_handler_class.json', json.dumps({
    'content_type': 'warext_mc_server',
    'field_name': 'approval_queue_handler_class',
    'field_value': 'Warext\\MinecraftVote\\ApprovalQueue\\Server'
}, indent=4) + '\n')

listeners = {
    'entity_post_save_Warext-MinecraftVote-Listener_postEntityPostSave_XF-Entity-Post.json': {
        'event_id': 'entity_post_save', 'execute_order': 10, 'callback_class': 'Warext\\MinecraftVote\\Listener',
        'callback_method': 'postEntityPostSave', 'active': True, 'hint': 'XF\\Entity\\Post',
        'description': 'Create linked Minecraft server from configured promotion forums.'
    },
    'entity_post_delete_Warext-MinecraftVote-Listener_threadEntityPostDelete_XF-Entity-Thread.json': {
        'event_id': 'entity_post_delete', 'execute_order': 10, 'callback_class': 'Warext\\MinecraftVote\\Listener',
        'callback_method': 'threadEntityPostDelete', 'active': True, 'hint': 'XF\\Entity\\Thread',
        'description': 'Detach Minecraft server when linked promotion thread is deleted.'
    },
    'templater_template_pre_render_Warext-MinecraftVote-Listener_threadFormPreRender_public-forum_post_thread.json': {
        'event_id': 'templater_template_pre_render', 'execute_order': 10, 'callback_class': 'Warext\\MinecraftVote\\Listener',
        'callback_method': 'threadFormPreRender', 'active': True, 'hint': 'public:forum_post_thread',
        'description': 'Expose Minecraft server fields in configured promotion forums.'
    },
    'templater_template_pre_render_Warext-MinecraftVote-Listener_threadViewPreRender_public-thread_view.json': {
        'event_id': 'templater_template_pre_render', 'execute_order': 10, 'callback_class': 'Warext\\MinecraftVote\\Listener',
        'callback_method': 'threadViewPreRender', 'active': True, 'hint': 'public:thread_view',
        'description': 'Expose linked Minecraft server on promotion threads.'
    }
}
for name, data in listeners.items():
    write(ROOT / '_output/code_event_listeners' / name, json.dumps(data, ensure_ascii=False, indent=4) + '\n')

mods = {
    'warext_mc_thread_fields.json': {
        'template': 'forum_post_thread',
        'description': 'Minecraft server directory integration fields',
        'execution_order': 10,
        'enabled': True,
        'action': 'preg_replace',
        'find': '#</xf:form>\\s*$#s',
        'replace': '<xf:include template="warext_mc_thread_integration_fields" />\\n</xf:form>'
    },
    'warext_mc_thread_card.json': {
        'template': 'thread_view',
        'description': 'Linked Minecraft server card',
        'execution_order': 10,
        'enabled': True,
        'action': 'preg_replace',
        'find': '#^#',
        'replace': '<xf:include template="warext_mc_thread_server_card" />\\n'
    }
}
for name, data in mods.items():
    write(ROOT / '_output/template_modifications/public' / name, json.dumps(data, ensure_ascii=False, indent=4) + '\n')

write(ROOT / '_output/templates/public/warext_mc_thread_integration_fields.html', r'''<xf:if is="$warextMcThreadCategory">
    <h2 class="block-formSectionHeader"><span class="block-formSectionHeader-aligner">Sunucu dizininde de yayınla</span></h2>
    <div class="block-body">
        <div class="blockMessage blockMessage--important">Sunucu adresini boş bırakırsanız yalnızca normal tanıtım konusu oluşturulur. Adres girerseniz konu başlığı ve ilk mesaj kullanılarak {$warextMcThreadCategory.title} kategorisinde onay bekleyen bir vote kaydı oluşturulur.</div>
        <xf:textboxrow name="warext_mc_host" label="Sunucu adresi" placeholder="play.sunucum.com" maxlength="255" />
        <xf:selectrow name="warext_mc_server_type" value="{$warextMcThreadCategory.thread_default_server_type}" label="Sunucu türü">
            <xf:option value="java">Java</xf:option>
            <xf:option value="bedrock">Bedrock</xf:option>
            <xf:option value="crossplay">Java + Bedrock / Crossplay</xf:option>
        </xf:selectrow>
        <xf:numberboxrow name="warext_mc_port" value="25565" min="1" max="65535" label="Java / ana port" />
        <xf:textboxrow name="warext_mc_bedrock_host" label="Bedrock adresi" maxlength="255" explain="Yalnız Bedrock/Crossplay kullanıyorsanız doldurun. Ana adres ile aynıysa boş bırakabilirsiniz." />
        <xf:numberboxrow name="warext_mc_bedrock_port" value="19132" min="1" max="65535" label="Bedrock portu" />
        <xf:textboxrow name="warext_mc_website_url" label="Web sitesi" maxlength="255" />
        <xf:textboxrow name="warext_mc_discord_url" label="Discord" maxlength="255" />
        <xf:checkboxrow>
            <xf:option name="warext_mc_is_premium" value="1">Premium hesap zorunlu</xf:option>
            <xf:option name="warext_mc_allow_cracked" value="1">Crack giriş destekleniyor</xf:option>
        </xf:checkboxrow>
        <div class="formRow"><div class="formRow-labelWrapper"><label class="formRow-label">Görseller</label></div><div class="formRow-main"><div class="formRow-explain">Tanıtım konusundan otomatik oluşan sunucunun banner, hareketli GIF banner ve kapak görselini daha sonra Sunucularım → Düzenle bölümünden ekleyebilirsiniz. Konu formuna ikinci bir görsel yükleme sistemi eklenmez.</div></div></div>
    </div>
</xf:if>
''')

write(ROOT / '_output/templates/public/warext_mc_thread_server_card.html', r'''<xf:if is="$warextMcLinkedServer">
    <xf:css src="warext_mc_servers.less" />
    <div class="block warextMcThreadServerCard">
        <div class="block-container">
            <div class="block-row">
                <div class="contentRow">
                    <div class="contentRow-main">
                        <div class="contentRow-title"><strong>{$warextMcLinkedServer.title}</strong> <span class="label label--primary">Minecraft Sunucusu</span></div>
                        <div class="contentRow-minor">{$warextMcLinkedServer.host}:{$warextMcLinkedServer.port} · {$warextMcLinkedServer.players_online}/{$warextMcLinkedServer.players_max} oyuncu · {$warextMcLinkedServer.vote_count_month} aylık oy</div>
                    </div>
                    <div class="contentRow-extra">
                        <xf:button href="{{ link('sunucular/detay', $warextMcLinkedServer) }}">Sunucu Sayfası</xf:button>
                        <xf:if is="$warextMcLinkedServer.state == 'active'"><xf:button href="{{ link('sunucular/oy', $warextMcLinkedServer) }}" class="button--cta">Oy Ver</xf:button></xf:if>
                    </div>
                </div>
            </div>
        </div>
    </div>
</xf:if>
''')

write(ROOT / '_output/templates/public/approval_item_warext_mc_server.html', r'''<xf:macro template="approval_queue_macros" name="item_message_type"
    arg-content="{$content}"
    arg-user="{$content.Owner}"
    arg-messageHtml="{$content.description}"
    arg-typePhraseHtml="Minecraft Sunucusu"
    arg-spamDetails="{$spamDetails}"
    arg-unapprovedItem="{$unapprovedItem}"
    arg-handler="{$handler}"
    arg-headerPhraseHtml="{$content.title}" />
''')

write(ROOT / '_output/templates/public/warext_mc_server_add.html', r'''<xf:title>Sunucu Ekle</xf:title>
<xf:description>Minecraft sunucunuzu platforma ekleyin. Yeni kayıtlar moderasyon onayından sonra listelenir.</xf:description>

<div class="blockMessage blockMessage--important">Kategoriler sınıflandırma içindir. Tanıtım konusu bağlantısı ve tüm görseller isteğe bağlıdır; yalnızca sunucu adresi temel kayıt için zorunludur.</div>

<xf:form action="{{ link('sunucular/ekle') }}" ajax="true" upload="true" class="block">
    <div class="block-container">
        <h2 class="block-header">Temel Bilgiler</h2>
        <div class="block-body">
            <xf:textboxrow name="title" label="Sunucu adı" required="true" maxlength="100" />
            <xf:selectrow name="server_type" label="Sunucu türü">
                <xf:option value="java">Java</xf:option>
                <xf:option value="bedrock">Bedrock</xf:option>
                <xf:option value="crossplay">Java + Bedrock / Crossplay</xf:option>
            </xf:selectrow>
            <xf:textboxrow name="host" label="Ana sunucu adresi" required="true" maxlength="255" placeholder="play.sunucum.com" />
            <xf:numberboxrow name="port" value="25565" min="1" max="65535" label="Ana / Java portu" />
            <xf:textboxrow name="bedrock_host" label="Bedrock adresi" maxlength="255" explain="Bedrock/Crossplay içindir. Ana adres ile aynıysa boş bırakabilirsiniz." />
            <xf:numberboxrow name="bedrock_port" value="19132" min="1" max="65535" label="Bedrock portu" />
            <xf:textarearow name="description" label="Sunucu açıklaması" rows="8" />
        </div>

        <h2 class="block-header">Görsel Kimlik</h2>
        <div class="block-body">
            <div class="blockMessage">Liste bannerı klasik vote standardı olan 468×60 kullanır. Hareketli GIF yüklerseniz listede statik bannerın yerine o gösterilir. Kapak görseli yalnız sunucu detay sayfasının geniş üst alanında kullanılır.</div>
            <xf:uploadrow name="banner" accept=".jpg,.jpeg,.png,.webp" label="Liste bannerı" explain="468×60 JPG, PNG veya WebP · en fazla 10 MB" />
            <xf:uploadrow name="animated_banner" accept=".gif" label="Hareketli banner" explain="468×60 GIF · en fazla 15 MB · varsa statik bannerı geçersiz kılar" />
            <xf:uploadrow name="cover" accept=".jpg,.jpeg,.png,.webp" label="Detay kapağı" explain="En az 800×300 · JPG, PNG veya WebP · en fazla 12 MB" />
            <xf:textboxrow name="trailer_url" label="Tanıtım videosu" placeholder="https://" maxlength="255" explain="İsteğe bağlı video/trailer bağlantısı." />
        </div>

        <h2 class="block-header">Kategori, Mod ve Sürüm</h2>
        <div class="block-body">
            <xf:checkboxrow name="category_ids" label="Kategoriler" explain="En fazla 5 kategori seçebilirsiniz.">
                <xf:foreach loop="$categories" value="$category"><xf:option value="{$category.category_id}">{$category.title}</xf:option></xf:foreach>
            </xf:checkboxrow>
            <xf:textboxrow name="game_modes" label="Oyun modları" placeholder="Survival, SkyBlock, PvP" maxlength="255" />
            <xf:textboxrow name="version_min" label="Minimum sürüm" maxlength="30" />
            <xf:textboxrow name="version_max" label="Maksimum sürüm" maxlength="30" />
            <xf:textboxrow name="country_code" label="Ülke kodu" value="TR" maxlength="2" />
            <xf:checkboxrow>
                <xf:option name="is_premium" value="1">Premium hesap zorunlu</xf:option>
                <xf:option name="allow_cracked" value="1">Crack giriş destekleniyor</xf:option>
            </xf:checkboxrow>
        </div>

        <h2 class="block-header">Bağlantılar</h2>
        <div class="block-body">
            <xf:textboxrow name="website_url" label="Web sitesi" placeholder="https://" maxlength="255" />
            <xf:textboxrow name="discord_url" label="Discord" placeholder="https://discord.gg/..." maxlength="255" />
            <xf:textboxrow name="store_url" label="Mağaza" placeholder="https://" maxlength="255" />
            <xf:textboxrow name="discussion_thread_url" label="Sunucu tanıtım konusu" placeholder="https://forum.site.com/threads/..." maxlength="500" explain="İsteğe bağlı. Kendi XenForo tanıtım konunuzun bağlantısını girerseniz vote kaydı ile konu çift yönlü bağlanır." />
        </div>

        <xf:submitrow submit="Sunucuyu Gönder" />
    </div>
</xf:form>
''')

write(ROOT / '_output/templates/public/warext_mc_server_edit.html', r'''<xf:title>{$server.title} - Sunucuyu Düzenle</xf:title>
<xf:description>Sunucu bilgilerini, görsellerini, kategorilerini ve forum bağlantısını güncelleyin.</xf:description>
<xf:breadcrumb href="{{ link('sunucular') }}">Minecraft Sunucuları</xf:breadcrumb>
<xf:breadcrumb href="{{ link('sunucular/detay', $server) }}">{$server.title}</xf:breadcrumb>

<div class="blockMessage blockMessage--warning">Sunucu türü, IP/adres veya port bilgileri değiştirilirse sahiplik doğrulaması sıfırlanır ve sunucu yeniden moderasyon onayına gönderilir.</div>

<xf:form action="{{ link('sunucular/duzenle', $server) }}" ajax="true" upload="true" class="block">
    <div class="block-container">
        <h2 class="block-header">Temel Bilgiler</h2>
        <div class="block-body">
            <xf:textboxrow name="title" value="{$server.title}" label="Sunucu adı" required="true" maxlength="100" />
            <xf:selectrow name="server_type" value="{$server.server_type}" label="Sunucu türü">
                <xf:option value="java">Java</xf:option><xf:option value="bedrock">Bedrock</xf:option><xf:option value="crossplay">Java + Bedrock / Crossplay</xf:option>
            </xf:selectrow>
            <xf:textboxrow name="host" value="{$server.host}" label="Ana sunucu adresi" required="true" maxlength="255" />
            <xf:numberboxrow name="port" value="{$server.port}" min="1" max="65535" label="Ana / Java portu" />
            <xf:textboxrow name="bedrock_host" value="{$server.bedrock_host}" label="Bedrock adresi" maxlength="255" />
            <xf:numberboxrow name="bedrock_port" value="{$server.bedrock_port}" min="1" max="65535" label="Bedrock portu" />
            <xf:textarearow name="description" value="{$server.description}" label="Sunucu açıklaması" rows="8" />
        </div>

        <h2 class="block-header">Görsel Kimlik</h2>
        <div class="block-body">
            <xf:if is="$server.banner_path"><div class="formRow"><div class="formRow-labelWrapper"><label class="formRow-label">Mevcut statik banner</label></div><div class="formRow-main"><img src="{$xf.app.config.externalDataUrl}/{$server.banner_path}" style="max-width:468px;width:100%;height:auto" alt="" /></div></div></xf:if>
            <xf:uploadrow name="banner" accept=".jpg,.jpeg,.png,.webp" label="Liste bannerı" explain="468×60 · yeni dosya mevcut olanın yerine geçer" />
            <xf:if is="$server.banner_path"><xf:checkboxrow><xf:option name="remove_banner" value="1">Statik bannerı kaldır</xf:option></xf:checkboxrow></xf:if>
            <xf:if is="$server.animated_banner_path"><div class="formRow"><div class="formRow-labelWrapper"><label class="formRow-label">Mevcut hareketli banner</label></div><div class="formRow-main"><img src="{$xf.app.config.externalDataUrl}/{$server.animated_banner_path}" style="max-width:468px;width:100%;height:auto" alt="" /></div></div></xf:if>
            <xf:uploadrow name="animated_banner" accept=".gif" label="Hareketli banner" explain="468×60 GIF · listede statik bannerı geçersiz kılar" />
            <xf:if is="$server.animated_banner_path"><xf:checkboxrow><xf:option name="remove_animated_banner" value="1">Hareketli bannerı kaldır</xf:option></xf:checkboxrow></xf:if>
            <xf:if is="$server.cover_path"><div class="formRow"><div class="formRow-labelWrapper"><label class="formRow-label">Mevcut kapak</label></div><div class="formRow-main"><img src="{$xf.app.config.externalDataUrl}/{$server.cover_path}" style="max-width:640px;width:100%;height:auto" alt="" /></div></div></xf:if>
            <xf:uploadrow name="cover" accept=".jpg,.jpeg,.png,.webp" label="Detay kapağı" explain="En az 800×300" />
            <xf:if is="$server.cover_path"><xf:checkboxrow><xf:option name="remove_cover" value="1">Kapak görselini kaldır</xf:option></xf:checkboxrow></xf:if>
            <xf:textboxrow name="trailer_url" value="{$server.trailer_url}" label="Tanıtım videosu" maxlength="255" />
        </div>

        <h2 class="block-header">Kategori, Mod ve Sürüm</h2>
        <div class="block-body">
            <xf:checkboxrow label="Kategoriler">
                <xf:foreach loop="$categories" value="$category"><xf:option name="category_ids[]" value="{$category.category_id}" selected="{{ in_array($category.category_id, $selectedCategoryIds) }}">{$category.title}</xf:option></xf:foreach>
            </xf:checkboxrow>
            <xf:textboxrow name="game_modes" value="{$server.game_modes}" label="Oyun modları" maxlength="255" />
            <xf:textboxrow name="version_min" value="{$server.version_min}" label="Minimum sürüm" maxlength="30" />
            <xf:textboxrow name="version_max" value="{$server.version_max}" label="Maksimum sürüm" maxlength="30" />
            <xf:textboxrow name="country_code" value="{$server.country_code}" label="Ülke kodu" maxlength="2" />
            <xf:checkboxrow>
                <xf:option name="is_premium" value="1" selected="$server.is_premium">Premium hesap zorunlu</xf:option>
                <xf:option name="allow_cracked" value="1" selected="$server.allow_cracked">Crack giriş destekleniyor</xf:option>
            </xf:checkboxrow>
        </div>

        <h2 class="block-header">Bağlantılar ve Forum</h2>
        <div class="block-body">
            <xf:textboxrow name="website_url" value="{$server.website_url}" label="Web sitesi" maxlength="255" />
            <xf:textboxrow name="discord_url" value="{$server.discord_url}" label="Discord" maxlength="255" />
            <xf:textboxrow name="store_url" value="{$server.store_url}" label="Mağaza" maxlength="255" />
            <xf:textboxrow name="discussion_thread_url" value="{{ $server.DiscussionThread ? link('canonical:threads', $server.DiscussionThread) : '' }}" label="Sunucu tanıtım konusu" maxlength="500" explain="Boş bırakırsanız mevcut konu bağlantısı kaldırılır." />
        </div>
        <xf:submitrow submit="Değişiklikleri Kaydet" />
    </div>
</xf:form>
''')

write(ROOT / '_output/templates/admin/warext_mc_admin_category_index.html', r'''<xf:title>Minecraft Sunucu Kategorileri</xf:title>
<div class="blockMessage blockMessage--important">Kategori bir XenForo forumuna bağlanırsa o forumda yeni konu açarken isteğe bağlı sunucu dizini alanları görünür. Sunucu adresi boşsa konu normal şekilde açılır ve vote kaydı oluşturulmaz.</div>
<xf:form action="{{ link('warext-minecraft/categories') }}" class="block">
    <div class="block-container">
        <h2 class="block-header">Yeni Kategori</h2>
        <div class="block-body">
            <xf:textboxrow name="title" label="Kategori adı" required="true" maxlength="50" />
            <xf:textboxrow name="slug" label="Kısa ad" maxlength="50" />
            <xf:textboxrow name="description" label="Açıklama" maxlength="255" />
            <xf:numberboxrow name="display_order" value="10" min="1" label="Sıralama" />
            <xf:selectrow name="forum_node_id" label="Bağlı tanıtım forumu">
                <xf:foreach loop="$forumOptions" key="$forumId" value="$forumTitle"><xf:option value="{$forumId}">{$forumTitle}</xf:option></xf:foreach>
            </xf:selectrow>
            <xf:selectrow name="thread_default_server_type" label="Konu formu varsayılan türü"><xf:option value="java">Java</xf:option><xf:option value="bedrock">Bedrock</xf:option><xf:option value="crossplay">Crossplay</xf:option></xf:selectrow>
            <xf:checkboxrow>
                <xf:option name="is_active" value="1" selected="true">Aktif</xf:option>
                <xf:option name="thread_integration_enabled" value="1">Bu forumda konu → vote entegrasyonunu aç</xf:option>
            </xf:checkboxrow>
        </div>
        <xf:submitrow submit="Kategori Ekle" />
    </div>
</xf:form>
<div class="block"><div class="block-container"><h2 class="block-header">Mevcut Kategoriler</h2><div class="block-body">
    <xf:if is="$categoryRows is not empty">
        <xf:foreach loop="$categoryRows" value="$row">
            <div class="block-row block-row--separated"><div class="contentRow"><div class="contentRow-main">
                <div class="contentRow-title"><strong>{$row.category.title}</strong> <xf:if is="$row.category.is_active"><span class="label label--green">Aktif</span><xf:else /><span class="label label--red">Pasif</span></xf:if> <xf:if is="$row.category.thread_integration_enabled"><span class="label label--primary">Konu entegrasyonu</span></xf:if></div>
                <div class="contentRow-minor">Kısa ad: {$row.category.slug} · Kullanım: {$row.usageCount} sunucu<xf:if is="$row.category.Forum"> · Forum: {$row.category.Forum.title}</xf:if></div>
                <div class="buttonGroup" style="margin-top:8px"><xf:button href="{{ link('warext-minecraft/category-edit', $row.category) }}">Düzenle</xf:button>
                    <xf:form action="{{ link('warext-minecraft/category-toggle', $row.category) }}" ajax="true" class="buttonGroup"><xf:button type="submit"><xf:if is="$row.category.is_active">Pasifleştir<xf:else />Aktifleştir</xf:if></xf:button></xf:form>
                    <xf:form action="{{ link('warext-minecraft/category-delete', $row.category) }}" ajax="true" data-xf-init="delete" class="buttonGroup"><xf:button type="submit" class="button--link">Sil</xf:button></xf:form>
                </div>
            </div></div></div>
        </xf:foreach>
    <xf:else /><div class="blockMessage">Henüz kategori yok.</div></xf:if>
</div></div></div>
''')

write(ROOT / '_output/templates/admin/warext_mc_admin_category_edit.html', r'''<xf:title>Kategori Düzenle: {$category.title}</xf:title>
<xf:form action="{{ link('warext-minecraft/category-edit', $category) }}" class="block">
    <div class="block-container"><div class="block-body">
        <xf:textboxrow name="title" value="{$category.title}" label="Kategori adı" required="true" maxlength="50" />
        <xf:textboxrow name="slug" value="{$category.slug}" label="Kısa ad" required="true" maxlength="50" />
        <xf:textboxrow name="description" value="{$category.description}" label="Açıklama" maxlength="255" />
        <xf:numberboxrow name="display_order" value="{$category.display_order}" min="1" label="Sıralama" />
        <xf:selectrow name="forum_node_id" value="{$category.forum_node_id}" label="Bağlı tanıtım forumu">
            <xf:foreach loop="$forumOptions" key="$forumId" value="$forumTitle"><xf:option value="{$forumId}">{$forumTitle}</xf:option></xf:foreach>
        </xf:selectrow>
        <xf:selectrow name="thread_default_server_type" value="{$category.thread_default_server_type}" label="Konu formu varsayılan türü"><xf:option value="java">Java</xf:option><xf:option value="bedrock">Bedrock</xf:option><xf:option value="crossplay">Crossplay</xf:option></xf:selectrow>
        <xf:checkboxrow>
            <xf:option name="is_active" value="1" selected="{$category.is_active}">Aktif</xf:option>
            <xf:option name="thread_integration_enabled" value="1" selected="{$category.thread_integration_enabled}">Bu forumda konu → vote entegrasyonunu aç</xf:option>
        </xf:checkboxrow>
    </div><xf:submitrow submit="Kaydet" /></div>
</xf:form>
''')

view = ROOT / '_output/templates/public/warext_mc_server_view.html'
text = view.read_text(encoding='utf-8')
hero_marker = '<div class="block warextMcServerHero">'
hero_media = r'''<xf:if is="$server.cover_path">
    <div class="warextMcServerCover"><img src="{$xf.app.config.externalDataUrl}/{$server.cover_path}" alt="{$server.title}" /></div>
</xf:if>

'''
if hero_marker not in text:
    raise SystemExit('server view hero marker missing')
text = text.replace(hero_marker, hero_media + hero_marker, 1)
links_old = '<xf:if is="$server.website_url || $server.discord_url || $server.store_url">'
links_new = '<xf:if is="$server.website_url || $server.discord_url || $server.store_url || $server.trailer_url || $server.DiscussionThread">'
text = text.replace(links_old, links_new, 1)
text = text.replace('<xf:if is="$server.store_url"><a class="button" href="{$server.store_url}" target="_blank" rel="nofollow noopener">Mağaza</a></xf:if>', '<xf:if is="$server.store_url"><a class="button" href="{$server.store_url}" target="_blank" rel="nofollow noopener">Mağaza</a></xf:if>\n                <xf:if is="$server.trailer_url"><a class="button" href="{$server.trailer_url}" target="_blank" rel="nofollow noopener">Tanıtım Videosu</a></xf:if>\n                <xf:if is="$server.DiscussionThread"><a class="button" href="{{ link(\'threads\', $server.DiscussionThread) }}">Tanıtım Konusu</a></xf:if>', 1)
view.write_text(text, encoding='utf-8')

index = ROOT / '_output/templates/public/warext_mc_server_index.html'
text = index.read_text(encoding='utf-8')
needle = '<div class="warextMcVoteRow-main">'
replacement = r'''<div class="warextMcVoteRow-media">
                                    <xf:if is="$server.animated_banner_path">
                                        <img src="{$xf.app.config.externalDataUrl}/{$server.animated_banner_path}" alt="{$server.title}" />
                                    <xf:elseif is="$server.banner_path" />
                                        <img src="{$xf.app.config.externalDataUrl}/{$server.banner_path}" alt="{$server.title}" />
                                    <xf:else />
                                        <div class="warextMcVoteRow-mediaFallback"><i class="fa--xf far fa-server" aria-hidden="true"></i></div>
                                    </xf:if>
                                </div>
                                <div class="warextMcVoteRow-main">'''
if needle not in text:
    raise SystemExit('index vote row marker missing')
text = text.replace(needle, replacement, 1)
index.write_text(text, encoding='utf-8')

less = ROOT / '_output/templates/public/warext_mc_servers.less'
text = less.read_text(encoding='utf-8')
text += r'''

.warextMcVoteRow-media {
    flex: 0 0 234px;
    display: flex;
    align-items: center;
}
.warextMcVoteRow-media img {
    display: block;
    width: 234px;
    height: 30px;
    object-fit: cover;
    border-radius: 4px;
}
.warextMcVoteRow-mediaFallback {
    width: 234px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid @xf-borderColor;
    border-radius: 4px;
    color: @xf-textColorMuted;
    background: @xf-contentAltBg;
}
.warextMcServerCover {
    margin-bottom: 12px;
    overflow: hidden;
    border-radius: @xf-borderRadiusLarge;
    background: @xf-contentAltBg;
}
.warextMcServerCover img {
    display: block;
    width: 100%;
    max-height: 460px;
    object-fit: cover;
}
.warextMcThreadServerCard {
    margin-bottom: 12px;
}
@media (max-width: @xf-responsiveNarrow) {
    .warextMcVoteRow-media {
        flex-basis: 100%;
    }
    .warextMcVoteRow-media img,
    .warextMcVoteRow-mediaFallback {
        width: 100%;
        height: auto;
        min-height: 38px;
    }
}
'''
less.write_text(text, encoding='utf-8')

write('.github/build_xf_integrations.py', r'''#!/usr/bin/env python3
import json
import sys
from pathlib import Path
import xml.etree.ElementTree as ET


def load(path):
    return json.loads(path.read_text(encoding='utf-8-sig'))


def write_xml(root, path):
    ET.ElementTree(root).write(path, encoding='utf-8', xml_declaration=True)


def build_listeners(addon):
    root = ET.Element('code_event_listeners')
    source = addon / '_output' / 'code_event_listeners'
    for path in sorted(source.glob('*.json')):
        data = load(path)
        node = ET.SubElement(root, 'listener')
        for key in ['event_id', 'execute_order', 'callback_class', 'callback_method', 'active', 'hint', 'description']:
            value = data.get(key, '')
            if isinstance(value, bool):
                value = '1' if value else '0'
            node.set(key, str(value))
    write_xml(root, addon / '_data' / 'code_event_listeners.xml')


def build_modifications(addon):
    root = ET.Element('template_modifications')
    source = addon / '_output' / 'template_modifications'
    for style in ['public', 'admin', 'email']:
        folder = source / style
        if not folder.exists():
            continue
        for path in sorted(folder.glob('*.json')):
            data = load(path)
            node = ET.SubElement(root, 'modification')
            node.set('type', style)
            node.set('template', str(data.get('template', '')))
            node.set('modification_key', path.stem)
            node.set('description', str(data.get('description', '')))
            node.set('execution_order', str(data.get('execution_order', 10)))
            node.set('enabled', '1' if data.get('enabled', True) else '0')
            node.set('action', str(data.get('action', 'str_replace')))
            find = ET.SubElement(node, 'find')
            find.text = str(data.get('find', ''))
            replace = ET.SubElement(node, 'replace')
            replace.text = str(data.get('replace', ''))
    write_xml(root, addon / '_data' / 'template_modifications.xml')


def main():
    addon = Path(sys.argv[1] if len(sys.argv) > 1 else 'src/addons/Warext/MinecraftVote')
    (addon / '_data').mkdir(parents=True, exist_ok=True)
    build_listeners(addon)
    build_modifications(addon)


if __name__ == '__main__':
    main()
''')

for workflow_path in ['.github/workflows/php-lint.yml', '.github/workflows/build-addon.yml']:
    wf = Path(workflow_path)
    text = wf.read_text(encoding='utf-8')
    nav_call = 'python .github/build_xf_navigation.py src/addons/Warext/MinecraftVote' if 'php-lint' in workflow_path else 'python3 .github/build_xf_navigation.py src/addons/Warext/MinecraftVote'
    prefix = 'python ' if 'php-lint' in workflow_path else 'python3 '
    if '.github/build_xf_integrations.py' not in text:
        text = text.replace(nav_call, nav_call + '\n          ' + prefix + '.github/build_xf_integrations.py src/addons/Warext/MinecraftVote')
    if "'code_event_listeners.xml'" not in text:
        text = text.replace("{'permissions.xml', 'permission_interface_groups.xml', 'navigation.xml'}", "{'permissions.xml', 'permission_interface_groups.xml', 'navigation.xml', 'code_event_listeners.xml', 'template_modifications.xml'}")
    wf.write_text(text, encoding='utf-8')

readme = Path('README.md')
text = readme.read_text(encoding='utf-8')
text = text.replace('Warext-MinecraftVote-1.1.0.zip', 'Warext-MinecraftVote-1.1.1.zip')
text = text.replace('- Sponsorlu listeleme', '- Statik 468×60 banner, hareketli GIF banner, detay kapağı ve trailer\n- Forum tanıtım konusu ↔ vote kaydı çift yönlü bağlantı\n- Belirlenen forum kategorilerinde konu açarken isteğe bağlı otomatik vote kaydı\n- XenForo yerleşik Approval Queue ile moderatör onayı\n- Sponsorlu listeleme')
readme.write_text(text, encoding='utf-8')

security = Path('.github/security_regression.py')
text = security.read_text(encoding='utf-8')
text = text.replace("if version_string != '1.1.0' or int(addon.get('version_id', 0)) < 1011000:\n    raise SystemExit('Sürüm numarası 1.1.0 değil.')", "if version_string != '1.1.1' or int(addon.get('version_id', 0)) < 1011010:\n    raise SystemExit('Sürüm numarası 1.1.1 değil.')")
anchor = "require('Entity/Sponsor.php', ['purchase_request_key'])\n"
extra = r'''require('Entity/Server.php', ["$structure->contentType = 'warext_mc_server'", 'ApprovalQueue', 'discussion_thread_id', 'animated_banner_path', 'cover_path'])
require('Service/Server/Media.php', ['468', 'animated_banner', 'copyFileToAbstractedPath', 'IMAGETYPE_GIF'])
require('Service/Server/ThreadLinker.php', ['discussion_thread_id', 'threads/', 'boardUrl'])
require('ApprovalQueue/Server.php', ['XF\\ApprovalQueue\\AbstractHandler', 'actionApprove', 'actionDelete'])
require('Listener.php', ['threadFormPreRender', 'postEntityPostSave', 'threadViewPreRender', 'threadEntityPostDelete'])
require('_output/templates/public/warext_mc_server_add.html', ['upload="true"', 'animated_banner', 'discussion_thread_url', '468×60'])
require('_output/templates/public/warext_mc_thread_integration_fields.html', ['Sunucu dizininde de yayınla', 'warext_mc_host'])
require('_output/templates/public/approval_item_warext_mc_server.html', ['approval_queue_macros'])
require('_output/content_type_fields/warext_mc_server-approval_queue_handler_class.json', ['approval_queue_handler_class'])
require('_output/code_event_listeners/entity_post_save_Warext-MinecraftVote-Listener_postEntityPostSave_XF-Entity-Post.json', ['entity_post_save', 'XF\\\\Entity\\\\Post'])
require('_output/template_modifications/public/warext_mc_thread_fields.json', ['forum_post_thread', 'preg_replace'])
require('Setup.php', ['installStep18', 'upgrade1011010Step1', 'ensureThreadMediaIntegration', 'warext_mc_server', 'contentTypes'])
'''
if extra not in text:
    text = text.replace(anchor, anchor + extra)
security.write_text(text, encoding='utf-8')

validator = Path('.github/validate_xf_source.py')
text = validator.read_text(encoding='utf-8')
text = text.replace("class_fields = {'entity', 'alert_handler_class', 'attachment_handler_class'}", "class_fields = {'entity', 'alert_handler_class', 'attachment_handler_class', 'approval_queue_handler_class'}")
validator.write_text(text, encoding='utf-8')

print('v1.1.1 patch applied')
