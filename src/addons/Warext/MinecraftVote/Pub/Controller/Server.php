<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\MinecraftAccount;
use Warext\MinecraftVote\Entity\Server as ServerEntity;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Server extends AbstractController
{
    public function actionIndex()
    {
        $page = $this->filterPage();
        $perPage = 20;
        $sort = $this->filter('sort', 'str');
        $type = $this->filter('type', 'str');
        $online = $this->filter('online', 'bool');

        $finder = $this->finder('Warext\MinecraftVote:Server')
            ->where('state', 'active');

        if (in_array($type, ['java', 'bedrock', 'crossplay'], true))
        {
            $finder->where('server_type', $type);
        }

        if ($online)
        {
            $finder->where('is_online', 1);
        }

        switch ($sort)
        {
            case 'players':
                $finder->order('players_online', 'DESC');
                break;

            case 'new':
                $finder->order('created_date', 'DESC');
                break;

            case 'uptime':
                $finder->order('uptime_bp', 'DESC');
                break;

            case 'votes':
            default:
                $sort = 'votes';
                $finder->order('vote_count_month', 'DESC');
                break;
        }

        $total = $finder->total();
        $this->assertValidPage($page, $perPage, $total, 'sunucular');

        $servers = $finder
            ->limitByPage($page, $perPage)
            ->fetch();

        $categories = $this->finder('Warext\MinecraftVote:Category')
            ->where('is_active', 1)
            ->order('display_order')
            ->fetch();

        return $this->view('Warext\MinecraftVote:Server\Index', 'warext_mc_server_index', [
            'servers' => $servers,
            'categories' => $categories,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'sort' => $sort,
            'type' => $type,
            'online' => $online
        ]);
    }

    public function actionEkle()
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
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
                'version_min' => 'str',
                'version_max' => 'str',
                'country_code' => 'str',
                'is_premium' => 'bool',
                'allow_cracked' => 'bool',
                'category_ids' => 'array-uint'
            ]);

            $creator = $this->service('Warext\MinecraftVote:Server\Creator');
            $creator->setOwner($visitor);
            $creator->setData($input);
            $creator->setCategoryIds($input['category_ids']);
            $server = $creator->save();

            return $this->redirect(
                $this->buildLink('sunucular/detay', $server),
                'Sunucu kaydınız oluşturuldu ve yönetici onayına gönderildi.'
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

    public function actionHesaplar()
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            return $this->noPermission();
        }

        if ($this->isPost())
        {
            $username = $this->filter('minecraft_username', 'str');
            $uuid = $this->filter('minecraft_uuid', 'str');

            try
            {
                $creator = $this->service('Warext\MinecraftVote:MinecraftAccount\Creator', $visitor);
                $creator->setData($username, $uuid);
                $creator->save();
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            return $this->redirect(
                $this->buildLink('sunucular/hesaplar'),
                'Minecraft hesabı profilinize eklendi.'
            );
        }

        $accounts = $this->repository('Warext\MinecraftVote:MinecraftAccount')
            ->findForUser($visitor->user_id)
            ->fetch();

        return $this->view('Warext\MinecraftVote:MinecraftAccount\List', 'warext_mc_account_list', [
            'accounts' => $accounts
        ]);
    }

    public function actionHesapSil(ParameterBag $params)
    {
        $this->assertPostOnly();

        $account = $this->assertOwnedMinecraftAccount((int)$params->account_id);
        $userId = $account->user_id;
        $wasPrimary = $account->is_primary;
        $account->delete();

        if ($wasPrimary)
        {
            $this->repository('Warext\MinecraftVote:MinecraftAccount')->promotePrimaryIfNeeded($userId);
        }

        return $this->redirect(
            $this->buildLink('sunucular/hesaplar'),
            'Minecraft hesabı bağlantısı kaldırıldı.'
        );
    }

    public function actionHesapBirincil(ParameterBag $params)
    {
        $this->assertPostOnly();

        $account = $this->assertOwnedMinecraftAccount((int)$params->account_id);
        $this->repository('Warext\MinecraftVote:MinecraftAccount')->makePrimary($account);

        return $this->redirect(
            $this->buildLink('sunucular/hesaplar'),
            'Birincil Minecraft hesabınız güncellendi.'
        );
    }

    public function actionOy(ParameterBag $params)
    {
        $server = $this->assertViewableServer((int)$params->server_id);
        if ($server->state !== 'active')
        {
            throw $this->exception($this->notFound());
        }

        $visitor = \XF::visitor();
        $allowGuests = (bool)(\XF::options()->warextMcAllowGuestVotes ?? true);

        if (!$visitor->user_id && !$allowGuests)
        {
            return $this->noPermission();
        }

        $linkedAccounts = $visitor->user_id
            ? $this->repository('Warext\MinecraftVote:MinecraftAccount')->findForUser($visitor->user_id)->fetch()
            : [];

        if ($this->isPost())
        {
            $accountId = $this->filter('minecraft_account_id', 'uint');
            $username = $this->filter('minecraft_username', 'str');
            $uuid = $this->filter('minecraft_uuid', 'str');

            if ($accountId)
            {
                if (!$visitor->user_id)
                {
                    return $this->noPermission();
                }

                $linkedAccount = $this->repository('Warext\MinecraftVote:MinecraftAccount')
                    ->getForUser($accountId, $visitor->user_id);
                if (!$linkedAccount)
                {
                    return $this->error('Seçilen Minecraft hesabı bulunamadı.', 404);
                }

                $username = $linkedAccount->minecraft_username;
                $uuid = $linkedAccount->minecraft_uuid;
            }

            try
            {
                $creator = $this->service('Warext\MinecraftVote:Vote\Creator', $server, $visitor);
                $creator->setIdentity($username, $uuid);
                $creator->setRequestFingerprint(
                    (string)$this->request->getIp(),
                    (string)$this->request->getServer('HTTP_USER_AGENT', '')
                );
                $creator->create();
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            $this->enqueueVoteDelivery();

            return $this->redirect(
                $this->buildLink('sunucular/detay', $server),
                'Oyunuz kaydedildi. Sunucu ödül entegrasyonu aktifse ödül teslimatı kuyruğa alındı.'
            );
        }

        return $this->view('Warext\MinecraftVote:Server\Vote', 'warext_mc_server_vote', [
            'server' => $server,
            'cooldownHours' => min(168, max(1, (int)(\XF::options()->warextMcVoteCooldownHours ?? 24))),
            'allowGuests' => $allowGuests,
            'linkedAccounts' => $linkedAccounts
        ]);
    }

    public function actionVotifier(ParameterBag $params)
    {
        $server = $this->assertOwnedServer((int)$params->server_id);
        $writer = $this->service('Warext\MinecraftVote:Votifier\ConfigWriter', $server);
        $config = $writer->getConfig();

        if ($this->isPost())
        {
            $input = $this->filter([
                'enabled' => 'bool',
                'host' => 'str',
                'port' => 'uint',
                'service_name' => 'str',
                'token' => 'str',
                'test' => 'bool'
            ]);

            try
            {
                $writer->setData($input);
                $config = $writer->save();

                if ($input['test'])
                {
                    $result = $writer->testConnection();
                    return $this->redirect(
                        $this->buildLink('sunucular/votifier', $server),
                        'NuVotifier V2 test oyu başarıyla gönderildi. Bağlantı: ' . (int)$result['ping_ms'] . ' ms.'
                    );
                }
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            return $this->redirect(
                $this->buildLink('sunucular/votifier', $server),
                'NuVotifier ayarları kaydedildi.'
            );
        }

        return $this->view('Warext\MinecraftVote:Server\Votifier', 'warext_mc_votifier_config', [
            'server' => $server,
            'config' => $config,
            'tokenExplain' => $config->token_encrypted
                ? 'Token kayıtlı ve şifrelenmiş durumda. Değiştirmek istemiyorsanız bu alanı boş bırakın.'
                : 'NuVotifier config dosyanızdaki default veya Warext servis tokenını girin.'
        ]);
    }

    public function actionDetay(ParameterBag $params)
    {
        $server = $this->assertViewableServer((int)$params->server_id);

        if ($server->state === 'active')
        {
            $this->repository('Warext\MinecraftVote:Server')->incrementViewCount($server);
        }

        return $this->view('Warext\MinecraftVote:Server\View', 'warext_mc_server_view', [
            'server' => $server
        ]);
    }

    protected function enqueueVoteDelivery(): void
    {
        $jobManager = $this->app->jobManager();
        $uniqueId = 'warextMinecraftVoteDelivery';

        if (!$jobManager->getUniqueJob($uniqueId))
        {
            $jobManager->enqueueUnique(
                $uniqueId,
                'Warext\MinecraftVote:VoteDelivery',
                [],
                false
            );
        }
    }

    protected function assertOwnedMinecraftAccount(int $accountId): MinecraftAccount
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            throw $this->exception($this->noPermission());
        }

        $account = $this->repository('Warext\MinecraftVote:MinecraftAccount')
            ->getForUser($accountId, $visitor->user_id);
        if (!$account)
        {
            throw $this->exception($this->notFound());
        }

        return $account;
    }

    protected function assertOwnedServer(int $serverId): ServerEntity
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            throw $this->exception($this->noPermission());
        }

        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId, ['Owner']);
        if (!$server)
        {
            throw $this->exception($this->notFound());
        }

        if ($server->owner_user_id !== $visitor->user_id)
        {
            throw $this->exception($this->noPermission());
        }

        return $server;
    }

    protected function assertViewableServer(int $serverId): ServerEntity
    {
        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId, ['Owner']);
        if (!$server)
        {
            throw $this->exception($this->notFound());
        }

        $visitor = \XF::visitor();
        if ($server->state !== 'active' && $server->owner_user_id !== $visitor->user_id)
        {
            throw $this->exception($this->notFound());
        }

        return $server;
    }
}
