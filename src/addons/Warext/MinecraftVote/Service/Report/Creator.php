<?php

namespace Warext\MinecraftVote\Service\Report;

use Warext\MinecraftVote\Entity\Report;
use Warext\MinecraftVote\Entity\Server;
use XF\App;
use XF\Entity\User;
use XF\PrintableException;
use XF\Service\AbstractService;
use XF\Service\FloodCheckService;

class Creator extends AbstractService
{
    protected Server $server;
    protected User $user;
    protected string $reason = 'other';
    protected string $message = '';

    public function __construct(App $app, Server $server, User $user)
    {
        parent::__construct($app);
        $this->server = $server;
        $this->user = $user;
    }

    public function setData(string $reason, string $message): void
    {
        $reason = trim($reason);
        $message = trim($message);

        if (!in_array($reason, ['fake', 'malicious', 'scam', 'offline', 'inappropriate', 'other'], true))
        {
            throw new PrintableException('Geçerli bir rapor nedeni seçin.');
        }
        if (mb_strlen($message) < 10 || mb_strlen($message) > 1000)
        {
            throw new PrintableException('Rapor açıklaması 10 ile 1000 karakter arasında olmalıdır.');
        }

        $this->reason = $reason;
        $this->message = $message;
    }

    public function save(): Report
    {
        if (!$this->user->user_id)
        {
            throw new PrintableException('Rapor göndermek için giriş yapmanız gerekiyor.');
        }
        if ($this->server->state !== 'active')
        {
            throw new PrintableException('Bu sunucu şu anda raporlanamaz.');
        }
        if ($this->server->owner_user_id === $this->user->user_id)
        {
            throw new PrintableException('Kendi sunucunuzu raporlayamazsınız.');
        }

        $this->assertFloodRate();

        $repo = $this->repository('Warext\MinecraftVote:Report');
        if ($repo->hasRecentReport($this->server->server_id, $this->user->user_id, \XF::$time - 86400))
        {
            throw new PrintableException('Bu sunucuyu son 24 saat içinde zaten raporladınız.');
        }

        $report = $this->em()->create('Warext\MinecraftVote:Report');
        $report->server_id = $this->server->server_id;
        $report->reporter_user_id = $this->user->user_id;
        $report->reason = $this->reason;
        $report->message = $this->message;
        $report->state = 'open';
        $report->save();

        return $report;
    }

    protected function assertFloodRate(): void
    {
        if ($this->user->hasPermission('general', 'bypassFloodCheck'))
        {
            return;
        }

        $flood = $this->service(FloodCheckService::class);
        $remaining = (int)$flood->checkFlooding('warextMinecraftReport', $this->user->user_id, 30);
        if ($remaining > 0)
        {
            throw new PrintableException("Çok hızlı rapor gönderiyorsunuz. {$remaining} saniye sonra tekrar deneyin.");
        }
    }
}
