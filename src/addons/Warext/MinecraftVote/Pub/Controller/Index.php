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
        $query = trim($this->filter('q', 'str'));
        $categoryId = $this->filter('category', 'uint');
        $countryCode = strtoupper(trim($this->filter('country', 'str')));
        $version = trim($this->filter('version', 'str'));
        $gameMode = trim($this->filter('game_mode', 'str'));
        $minPlayers = min(1000000, $this->filter('min_players', 'uint'));
        $premium = $this->filter('premium', 'str');
        $cracked = $this->filter('cracked', 'str');
        $verified = $this->filter('verified', 'bool');

        if (!in_array($type, ['', 'java', 'bedrock', 'crossplay'], true))
        {
            $type = '';
        }
        if (!in_array($premium, ['', 'yes', 'no'], true))
        {
            $premium = '';
        }
        if (!in_array($cracked, ['', 'yes', 'no'], true))
        {
            $cracked = '';
        }
        if ($countryCode !== '' && !preg_match('/^[A-Z]{2}$/', $countryCode))
        {
            $countryCode = '';
        }

        $query = substr($query, 0, 80);
        $version = substr($version, 0, 30);
        $gameMode = substr($gameMode, 0, 50);

        $finder = $this->finder('Warext\MinecraftVote:Server')->activeOnly();

        if ($type !== '')
        {
            $finder->where('server_type', $type);
        }
        if ($online)
        {
            $finder->onlineOnly();
        }
        if ($verified)
        {
            $finder->verifiedOnly();
        }

        $finder
            ->searchText($query)
            ->inCategory($categoryId)
            ->inCountry($countryCode)
            ->matchingVersion($version)
            ->matchingGameMode($gameMode)
            ->minimumPlayers($minPlayers)
            ->premiumMode($premium)
            ->crackedMode($cracked);

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

        $filterParams = [
            'q' => $query,
            'sort' => $sort,
            'type' => $type,
            'online' => $online ? 1 : 0,
            'category' => $categoryId,
            'country' => $countryCode,
            'version' => $version,
            'game_mode' => $gameMode,
            'min_players' => $minPlayers,
            'premium' => $premium,
            'cracked' => $cracked,
            'verified' => $verified ? 1 : 0
        ];
        $filterParams = array_filter($filterParams, static function ($value)
        {
            return $value !== '' && $value !== 0 && $value !== false && $value !== null;
        });

        $this->assertValidPage($page, $perPage, $total, 'sunucular');

        $servers = $finder
            ->limitByPage($page, $perPage)
            ->fetch();

        $categories = $this->finder('Warext\MinecraftVote:Category')
            ->where('is_active', 1)
            ->order('display_order')
            ->fetch();

        $countryRows = $this->db()->fetchAll(
            "SELECT DISTINCT country_code
             FROM xf_warext_mc_server
             WHERE state = 'active' AND country_code <> ''
             ORDER BY country_code"
        );
        $countryOptions = [];
        foreach ($countryRows as $row)
        {
            $code = strtoupper(trim((string)$row['country_code']));
            if (preg_match('/^[A-Z]{2}$/', $code))
            {
                $countryOptions[$code] = $code;
            }
        }

        $sponsors = $this->repository('Warext\MinecraftVote:Sponsor')
            ->findActiveForPlacement('list_top')
            ->limit(6)
            ->fetch();

        $visitor = \XF::visitor();

        return $this->view('Warext\MinecraftVote:Server\Index', 'warext_mc_server_index', [
            'servers' => $servers,
            'sponsors' => $sponsors,
            'categories' => $categories,
            'countryOptions' => $countryOptions,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'sort' => $sort,
            'type' => $type,
            'online' => $online,
            'query' => $query,
            'categoryId' => $categoryId,
            'countryCode' => $countryCode,
            'version' => $version,
            'gameMode' => $gameMode,
            'minPlayers' => $minPlayers,
            'premium' => $premium,
            'cracked' => $cracked,
            'verified' => $verified,
            'filterParams' => $filterParams,
            'canAddServer' => $visitor->user_id && PublicPermissions::allows('addServer', false, true),
            'canUseFavorites' => $visitor->user_id && PublicPermissions::allows('favorite', false, true)
        ]);
    }
}
