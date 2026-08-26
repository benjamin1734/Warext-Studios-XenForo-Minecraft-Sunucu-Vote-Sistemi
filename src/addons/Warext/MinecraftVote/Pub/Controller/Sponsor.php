<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\Server;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Sponsor extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            return $this->noPermission();
        }

        $server = $this->assertPurchasableServer((int)$params->server_id);
        if (!(bool)(\XF::options()->warextMcSponsorSalesEnabled ?? false))
        {
            return $this->error('Sponsor satın alma sistemi şu anda kapalı.');
        }

        $this->ensurePurchasableRegistered();

        $profiles = $this->repository('XF:Payment')
            ->findPaymentProfilesForList()
            ->fetch();

        $currency = strtoupper(trim((string)(\XF::options()->warextMcSponsorCurrency ?? 'TRY')));
        if (!preg_match('/^[A-Z]{3}$/', $currency))
        {
            $currency = 'TRY';
        }

        $currentSponsor = $this->finder('Warext\MinecraftVote:Sponsor')
            ->where('server_id', $server->server_id)
            ->where('placement', 'list_top')
            ->where('state', 'active')
            ->where('end_date', '>=', \XF::$time)
            ->order('end_date', 'DESC')
            ->fetchOne();

        return $this->view('Warext\MinecraftVote:Sponsor\Purchase', 'warext_mc_sponsor_purchase', [
            'server' => $server,
            'profiles' => $profiles,
            'price7' => $this->normalizePrice((string)(\XF::options()->warextMcSponsorPrice7 ?? '0')),
            'price30' => $this->normalizePrice((string)(\XF::options()->warextMcSponsorPrice30 ?? '0')),
            'currency' => $currency,
            'currentSponsor' => $currentSponsor
        ]);
    }

    protected function assertPurchasableServer(int $serverId): Server
    {
        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId);
        if (!$server || $server->state !== 'active')
        {
            throw $this->exception($this->notFound());
        }

        if ((int)$server->owner_user_id !== (int)\XF::visitor()->user_id)
        {
            throw $this->exception($this->noPermission());
        }

        return $server;
    }

    protected function ensurePurchasableRegistered(): void
    {
        $class = 'Warext\\MinecraftVote:Sponsor';
        $purchasable = $this->em()->find('XF:Purchasable', 'warext_mc_sponsor');
        if (!$purchasable)
        {
            $purchasable = $this->em()->create('XF:Purchasable');
            $purchasable->purchasable_type_id = 'warext_mc_sponsor';
        }

        if ($purchasable->purchasable_class !== $class || $purchasable->addon_id !== 'Warext/MinecraftVote')
        {
            $purchasable->purchasable_class = $class;
            $purchasable->addon_id = 'Warext/MinecraftVote';
            $purchasable->save();
        }
    }

    protected function normalizePrice(string $raw): float
    {
        $raw = str_replace(',', '.', trim($raw));
        return is_numeric($raw) ? max(0.0, round((float)$raw, 2)) : 0.0;
    }
}
