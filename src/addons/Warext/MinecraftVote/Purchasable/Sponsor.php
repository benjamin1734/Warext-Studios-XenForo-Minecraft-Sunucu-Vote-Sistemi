<?php

namespace Warext\MinecraftVote\Purchasable;

use XF\Entity\PaymentProfile;
use XF\Entity\User;
use XF\Http\Request;
use XF\Payment\CallbackState;
use XF\Purchasable\AbstractPurchasable;
use XF\Purchasable\Purchase;

class Sponsor extends AbstractPurchasable
{
    public function getPurchasableTypeId()
    {
        return 'warext_mc_sponsor';
    }

    public function getTitle()
    {
        return 'Minecraft sunucu sponsorluğu';
    }

    public function getPurchaseFromRequest(Request $request, User $purchaser, &$error = null)
    {
        if (!(bool)(\XF::options()->warextMcSponsorSalesEnabled ?? false))
        {
            $error = 'Sponsor satın alma sistemi şu anda kapalı.';
            return null;
        }

        $serverId = $request->filter('server_id', 'uint');
        $days = $request->filter('package_days', 'uint');
        $profileId = $request->filter('payment_profile_id', 'uint');

        if (!in_array($days, [7, 30], true))
        {
            $error = 'Geçersiz sponsor paketi.';
            return null;
        }

        $server = $this->app->em()->find('Warext\\MinecraftVote:Server', $serverId);
        if (!$server || $server->state !== 'active')
        {
            $error = 'Sponsor yapılacak aktif sunucu bulunamadı.';
            return null;
        }
        if (!$purchaser->user_id || (int)$server->owner_user_id !== (int)$purchaser->user_id)
        {
            $error = 'Yalnızca sunucu sahibi kendi sunucusu için sponsor paketi satın alabilir.';
            return null;
        }

        $paymentProfile = $this->app->em()->find('XF:PaymentProfile', $profileId);
        if (!$paymentProfile)
        {
            $error = 'Geçerli bir ödeme yöntemi seçin.';
            return null;
        }

        $price = $this->getPackagePrice($days);
        if ($price <= 0)
        {
            $error = 'Seçilen sponsor paketinin fiyatı yapılandırılmamış.';
            return null;
        }

        return $this->buildPurchase($paymentProfile, $server, $purchaser, $days, $price);
    }

    public function completePurchase(CallbackState $state)
    {
        $purchaseRequest = $state->getPurchaseRequest();
        if (!$purchaseRequest)
        {
            return;
        }

        [$serverId, $days] = $this->decodePurchasableId((int)$purchaseRequest->purchasable_id);
        if (!$serverId || !in_array($days, [7, 30], true))
        {
            return;
        }

        $server = $this->app->em()->find('Warext\\MinecraftVote:Server', $serverId);
        if (!$server || $server->state !== 'active')
        {
            return;
        }

        $latest = $this->app->finder('Warext\\MinecraftVote:Sponsor')
            ->where('server_id', $serverId)
            ->where('placement', 'list_top')
            ->where('state', 'active')
            ->order('end_date', 'DESC')
            ->order('sponsor_id', 'DESC')
            ->fetchOne();

        $start = \XF::$time;
        if ($latest && $latest->end_date >= $start)
        {
            $start = (int)$latest->end_date + 1;
        }

        $sponsor = $this->app->em()->create('Warext\\MinecraftVote:Sponsor');
        $sponsor->server_id = $serverId;
        $sponsor->label = 'Sponsorlu';
        $sponsor->placement = 'list_top';
        $sponsor->start_date = $start;
        $sponsor->end_date = $start + ($days * 86400);
        $sponsor->state = 'active';
        $sponsor->display_order = 10;
        $sponsor->created_by = (int)$purchaseRequest->user_id;
        $sponsor->save();

        $this->app->service('Warext\\MinecraftVote:Audit\\Logger')->log(
            'sponsor_purchase_completed',
            $serverId,
            (int)$purchaseRequest->user_id,
            (int)$purchaseRequest->user_id,
            [
                'sponsor_id' => (int)$sponsor->sponsor_id,
                'days' => $days,
                'request_key' => (string)$purchaseRequest->request_key
            ]
        );
    }

    public function reversePurchase(CallbackState $state)
    {
        $purchaseRequest = $state->getPurchaseRequest();
        if (!$purchaseRequest)
        {
            return;
        }

        [$serverId, $days] = $this->decodePurchasableId((int)$purchaseRequest->purchasable_id);
        if (!$serverId || !in_array($days, [7, 30], true))
        {
            return;
        }

        $sponsor = $this->app->finder('Warext\\MinecraftVote:Sponsor')
            ->where('server_id', $serverId)
            ->where('created_by', (int)$purchaseRequest->user_id)
            ->where('state', 'active')
            ->order('sponsor_id', 'DESC')
            ->fetchOne();
        if (!$sponsor)
        {
            return;
        }

        $sponsor->state = 'paused';
        $sponsor->save();

        $this->app->service('Warext\\MinecraftVote:Audit\\Logger')->log(
            'sponsor_purchase_reversed',
            $serverId,
            (int)$purchaseRequest->user_id,
            (int)$purchaseRequest->user_id,
            [
                'sponsor_id' => (int)$sponsor->sponsor_id,
                'days' => $days,
                'request_key' => (string)$purchaseRequest->request_key
            ]
        );
    }

    public function getPurchasableFromExtraData(array $extraData)
    {
        $encoded = (int)($extraData['purchasable_id'] ?? $extraData['purchasableId'] ?? 0);
        [$serverId] = $this->decodePurchasableId($encoded);

        return $serverId ? $this->app->em()->find('Warext\\MinecraftVote:Server', $serverId) : null;
    }

    public function getPurchaseFromExtraData(array $extraData, PaymentProfile $paymentProfile, User $purchaser, &$error = null)
    {
        $encoded = (int)($extraData['purchasable_id'] ?? $extraData['purchasableId'] ?? 0);
        [$serverId, $days] = $this->decodePurchasableId($encoded);
        $server = $serverId ? $this->app->em()->find('Warext\\MinecraftVote:Server', $serverId) : null;
        if (!$server || !in_array($days, [7, 30], true))
        {
            $error = 'Sponsor satın alma kaydı bulunamadı.';
            return null;
        }
        if (!$purchaser->user_id || (int)$server->owner_user_id !== (int)$purchaser->user_id)
        {
            $error = 'Sponsor satın alma yetkiniz yok.';
            return null;
        }

        $price = $this->getPackagePrice($days);
        if ($price <= 0)
        {
            $error = 'Sponsor paketi fiyatı geçersiz.';
            return null;
        }

        return $this->buildPurchase($paymentProfile, $server, $purchaser, $days, $price);
    }

    public function getPurchasablesByProfileId($profileId)
    {
        return [];
    }

    protected function buildPurchase(PaymentProfile $paymentProfile, $server, User $purchaser, int $days, float $price): Purchase
    {
        $currency = strtoupper(trim((string)(\XF::options()->warextMcSponsorCurrency ?? 'TRY')));
        if (!preg_match('/^[A-Z]{3}$/', $currency))
        {
            $currency = 'TRY';
        }

        $purchase = new Purchase();
        $purchase->title = $server->title . ' - ' . $days . ' günlük sponsor';
        $purchase->description = $server->title . ' Minecraft sunucusu için ' . $days . ' günlük sponsorlu listeleme.';
        $purchase->cost = $price;
        $purchase->currency = $currency;
        $purchase->recurring = false;
        $purchase->lengthAmount = $days;
        $purchase->lengthUnit = 'day';
        $purchase->purchaser = $purchaser;
        $purchase->paymentProfile = $paymentProfile;
        $purchase->purchasableTypeId = $this->getPurchasableTypeId();
        $purchase->purchasableId = $this->encodePurchasableId((int)$server->server_id, $days);
        $purchase->purchasableTitle = $server->title . ' sponsorluğu';

        $router = $this->app->router('public');
        $returnUrl = $router->buildLink('canonical:sunucular/sponsor', $server);
        $purchase->returnUrl = $returnUrl;
        $purchase->updateUrl = $returnUrl;
        $purchase->cancelUrl = $returnUrl;

        return $purchase;
    }

    protected function getPackagePrice(int $days): float
    {
        $option = $days === 7 ? 'warextMcSponsorPrice7' : 'warextMcSponsorPrice30';
        $raw = str_replace(',', '.', trim((string)(\XF::options()->{$option} ?? '0')));
        if (!is_numeric($raw))
        {
            return 0.0;
        }

        return max(0.0, round((float)$raw, 2));
    }

    protected function encodePurchasableId(int $serverId, int $days): int
    {
        return ($serverId * 100) + $days;
    }

    protected function decodePurchasableId(int $encoded): array
    {
        if ($encoded <= 0)
        {
            return [0, 0];
        }

        return [intdiv($encoded, 100), $encoded % 100];
    }
}
