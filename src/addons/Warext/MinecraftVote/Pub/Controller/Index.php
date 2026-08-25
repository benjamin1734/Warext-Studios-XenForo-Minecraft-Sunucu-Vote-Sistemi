<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Security\PublicPermissions;
use XF\Pub\Controller\AbstractController;

class Index extends AbstractController
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
            case 'trend':
                $finder->orderByTrend();
                break;

            case 'votes':
                $finder->orderByVotes();
                break;

            case 'players':
                $finder->orderByPlayers();
                break;

            case 'new':
                $finder->newestFirst();
                break;

            case 'uptime':
                $finder->order('uptime_bp', 'DESC');
                $finder->order('server_id', 'ASC');
                break;

            case 'popular':
            default:
                $sort = 'popular';
                $finder->orderByPopularity();
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

        $sponsors = $this->repository('Warext\MinecraftVote:Sponsor')
            ->findActiveForPlacement('list_top')
            ->limit(6)
            ->fetch();

        $visitor = \XF::visitor();

        return $this->view('Warext\MinecraftVote:Server\Index', 'warext_mc_server_index', [
            'servers' => $servers,
            'sponsors' => $sponsors,
            'categories' => $categories,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'sort' => $sort,
            'type' => $type,
            'online' => $online,
            'canAddServer' => $visitor->user_id && PublicPermissions::allows('addServer', false, true),
            'canUseFavorites' => $visitor->user_id && PublicPermissions::allows('favorite', false, true)
        ]);
    }
}
