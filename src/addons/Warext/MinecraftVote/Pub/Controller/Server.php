<?php

namespace Warext\MinecraftVote\Pub\Controller;

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

            /** @var \Warext\MinecraftVote\Service\Server\Creator $creator */
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

    public function actionDetay(ParameterBag $params)
    {
        $server = $this->assertViewableServer($params->server_id);

        if ($server->state === 'active')
        {
            $this->repository('Warext\MinecraftVote:Server')->incrementViewCount($server);
        }

        return $this->view('Warext\MinecraftVote:Server\View', 'warext_mc_server_view', [
            'server' => $server
        ]);
    }

    protected function assertViewableServer(int $serverId): ServerEntity
    {
        /** @var ServerEntity|null $server */
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
