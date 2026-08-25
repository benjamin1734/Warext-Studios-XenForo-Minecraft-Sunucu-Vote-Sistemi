<?php

namespace Warext\MinecraftVote\Service\Server;

use Warext\MinecraftVote\Entity\Server;
use XF\App;
use XF\Entity\User;
use XF\Service\AbstractService;

class Creator extends AbstractService
{
    protected Server $server;

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
        $this->server->bulkSet($data, [
            'title',
            'slug',
            'description',
            'server_type',
            'host',
            'port',
            'bedrock_host',
            'bedrock_port',
            'website_url',
            'discord_url',
            'store_url',
            'game_modes',
            'version_min',
            'version_max',
            'country_code',
            'is_premium',
            'allow_cracked'
        ]);
    }

    public function save(): Server
    {
        $this->server->save();
        return $this->server;
    }
}
