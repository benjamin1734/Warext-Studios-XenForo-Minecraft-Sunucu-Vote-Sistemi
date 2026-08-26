<?php

namespace Warext\MinecraftVote\Pub\Controller;

use XF\Pub\Controller\AbstractController;

class Api extends AbstractController
{
    public function actionIndex()
    {
        if (!(bool)(\XF::options()->warextMcPublicApiEnabled ?? false))
        {
            return $this->notFound();
        }

        try
        {
            $this->service('Warext\\MinecraftVote:RateLimit\\Request')->assertIp(
                'warextMcApi',
                0,
                (string)$this->request->getIp(),
                2
            );
        }
        catch (\XF\PrintableException $e)
        {
            return $this->error($e->getMessage(), 429);
        }

        $serverId = $this->filter('server_id', 'uint');
        $limit = min(100, max(1, (int)($this->filter('limit', 'uint') ?: 25)));

        $this->setResponseType('json');
        $view = $this->view('Warext\\MinecraftVote:Api\\Index', '');

        if ($serverId)
        {
            $server = $this->finder('Warext\\MinecraftVote:Server')
                ->where('server_id', $serverId)
                ->where('state', 'active')
                ->fetchOne();

            if (!$server)
            {
                return $this->notFound();
            }

            $view->setJsonParams([
                'server' => $this->serializeServer($server, true)
            ]);
            return $view;
        }

        $servers = $this->finder('Warext\\MinecraftVote:Server')
            ->where('state', 'active')
            ->order('popular_score_bp', 'DESC')
            ->order('server_id', 'ASC')
            ->limit($limit)
            ->fetch();

        $payload = [];
        foreach ($servers as $server)
        {
            $payload[] = $this->serializeServer($server, false);
        }

        $view->setJsonParams([
            'generated_at' => \XF::$time,
            'count' => count($payload),
            'servers' => $payload
        ]);

        return $view;
    }

    protected function serializeServer($server, bool $detail): array
    {
        $data = [
            'server_id' => (int)$server->server_id,
            'title' => (string)$server->title,
            'slug' => (string)$server->slug,
            'type' => (string)$server->server_type,
            'host' => (string)$server->host,
            'port' => (int)$server->port,
            'online' => (bool)$server->is_online,
            'players_online' => (int)$server->players_online,
            'players_max' => (int)$server->players_max,
            'ping_ms' => (int)$server->ping_ms,
            'version' => (string)$server->detected_version,
            'game_modes' => (string)$server->game_modes,
            'country' => (string)$server->country_code,
            'verified' => (bool)$server->is_verified,
            'votes_month' => (int)$server->vote_count_month,
            'votes_total' => (int)$server->vote_count_total,
            'rating' => (float)$server->rating_average,
            'uptime_percent' => (float)$server->uptime_percent,
            'last_ping_date' => (int)$server->last_ping_date
        ];

        if ($detail)
        {
            $data['bedrock_host'] = (string)$server->bedrock_host;
            $data['bedrock_port'] = (int)$server->bedrock_port;
            $data['premium'] = (bool)$server->is_premium;
            $data['cracked'] = (bool)$server->allow_cracked;
            $data['created_date'] = (int)$server->created_date;
            $data['last_update_date'] = (int)$server->last_update_date;
        }

        return $data;
    }
}
