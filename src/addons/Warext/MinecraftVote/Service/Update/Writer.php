<?php

namespace Warext\MinecraftVote\Service\Update;

use Warext\MinecraftVote\Entity\Server;
use Warext\MinecraftVote\Entity\ServerUpdate;
use XF\App;
use XF\Entity\User;
use XF\PrintableException;
use XF\Service\AbstractService;

class Writer extends AbstractService
{
    protected Server $server;
    protected User $user;

    public function __construct(App $app, Server $server, User $user)
    {
        parent::__construct($app);
        $this->server = $server;
        $this->user = $user;
    }

    public function create(array $input): ServerUpdate
    {
        if (!$this->canPublish())
        {
            throw new PrintableException('Bu sunucu için güncelleme yayınlama yetkiniz yok.');
        }

        $title = trim((string)($input['title'] ?? ''));
        $versionLabel = trim((string)($input['version_label'] ?? ''));
        $message = trim((string)($input['message'] ?? ''));

        if (mb_strlen($title) < 3 || mb_strlen($title) > 100)
        {
            throw new PrintableException('Güncelleme başlığı 3-100 karakter arasında olmalıdır.');
        }
        if (mb_strlen($versionLabel) > 50)
        {
            throw new PrintableException('Sürüm etiketi en fazla 50 karakter olabilir.');
        }
        if (mb_strlen($message) < 10 || mb_strlen($message) > 10000)
        {
            throw new PrintableException('Güncelleme açıklaması 10-10000 karakter arasında olmalıdır.');
        }

        $update = $this->em()->create('Warext\MinecraftVote:ServerUpdate');
        $update->server_id = $this->server->server_id;
        $update->user_id = $this->user->user_id;
        $update->title = $title;
        $update->version_label = $versionLabel;
        $update->message = $message;
        $update->state = 'visible';
        $update->save();

        $this->enqueueAlerts($update);

        return $update;
    }

    public function delete(ServerUpdate $update): void
    {
        if (!$this->canPublish())
        {
            throw new PrintableException('Bu güncellemeyi silme yetkiniz yok.');
        }
        if ($update->server_id !== $this->server->server_id)
        {
            throw new PrintableException('Güncelleme bu sunucuya ait değil.');
        }

        $this->repository('XF:UserAlert')
            ->fastDeleteAlertsForContent('warext_mc_server_update', $update->update_id);
        $update->delete();
    }

    public function canPublish(): bool
    {
        return $this->repository('Warext\MinecraftVote:ServerTeam')
            ->hasPermission($this->server, $this->user->user_id, 'publish_updates');
    }

    protected function enqueueAlerts(ServerUpdate $update): void
    {
        $this->app->jobManager()->enqueueUnique(
            'warextMinecraftUpdateAlert' . $update->update_id,
            'Warext\MinecraftVote:UpdateAlert',
            ['update_id' => $update->update_id],
            false
        );
    }
}
