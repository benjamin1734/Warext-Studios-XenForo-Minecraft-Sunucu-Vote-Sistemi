<?php

namespace Warext\MinecraftVote\Admin\Controller;

use Warext\MinecraftVote\Entity\Sponsor as SponsorEntity;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;
use XF\PrintableException;

class Sponsor extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertAdminPermission('warextMinecraftVote');
    }

    public function actionIndex()
    {
        if ($this->isPost())
        {
            $input = $this->filter([
                'server_id' => 'uint',
                'label' => 'str',
                'start_date' => 'str',
                'end_date' => 'str',
                'display_order' => 'uint'
            ]);

            $server = $this->em()->find('Warext\MinecraftVote:Server', $input['server_id']);
            if (!$server || $server->state !== 'active')
            {
                return $this->error('Sponsor yalnızca aktif bir sunucuya atanabilir.', 400);
            }

            try
            {
                $startDate = $this->parseDate($input['start_date'], false);
                $endDate = $this->parseDate($input['end_date'], true);
            }
            catch (PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            if ($endDate && $endDate <= $startDate)
            {
                return $this->error('Sponsor bitiş tarihi başlangıç tarihinden sonra olmalıdır.', 400);
            }

            $sponsor = $this->em()->create('Warext\MinecraftVote:Sponsor');
            $sponsor->server_id = $server->server_id;
            $sponsor->label = trim($input['label']) ?: 'Sponsorlu';
            $sponsor->placement = 'list_top';
            $sponsor->start_date = $startDate;
            $sponsor->end_date = $endDate;
            $sponsor->state = 'active';
            $sponsor->display_order = max(1, $input['display_order'] ?: 10);
            $sponsor->created_by = \XF::visitor()->user_id;
            $sponsor->save();

            $this->service('Warext\MinecraftVote:Audit\Logger')->log(
                'sponsor_created',
                $server->server_id,
                \XF::visitor()->user_id,
                0,
                [
                    'sponsor_id' => $sponsor->sponsor_id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'label' => $sponsor->label
                ]
            );

            return $this->redirect(
                $this->buildLink('warext-minecraft/sponsors'),
                'Sponsorlu gösterim oluşturuldu.'
            );
        }

        $sponsors = $this->repository('Warext\MinecraftVote:Sponsor')
            ->findForAdmin()
            ->fetch();
        $servers = $this->finder('Warext\MinecraftVote:Server')
            ->where('state', 'active')
            ->order('title', 'ASC')
            ->fetch();

        return $this->view('Warext\MinecraftVote:Sponsor\Index', 'warext_mc_admin_sponsor_index', [
            'sponsors' => $sponsors,
            'servers' => $servers
        ]);
    }

    public function actionToggle(ParameterBag $params)
    {
        $this->assertPostOnly();
        $sponsor = $this->assertSponsorExists((int)$params->sponsor_id);
        $sponsor->state = $sponsor->state === 'active' ? 'paused' : 'active';
        $sponsor->save();

        $this->service('Warext\MinecraftVote:Audit\Logger')->log(
            'sponsor_updated',
            $sponsor->server_id,
            \XF::visitor()->user_id,
            0,
            ['sponsor_id' => $sponsor->sponsor_id, 'state' => $sponsor->state]
        );

        return $this->redirect($this->buildLink('warext-minecraft/sponsors'));
    }

    public function actionDelete(ParameterBag $params)
    {
        $this->assertPostOnly();
        $sponsor = $this->assertSponsorExists((int)$params->sponsor_id);
        $serverId = $sponsor->server_id;
        $sponsorId = $sponsor->sponsor_id;
        $sponsor->delete();

        $this->service('Warext\MinecraftVote:Audit\Logger')->log(
            'sponsor_deleted',
            $serverId,
            \XF::visitor()->user_id,
            0,
            ['sponsor_id' => $sponsorId]
        );

        return $this->redirect(
            $this->buildLink('warext-minecraft/sponsors'),
            'Sponsor kaydı silindi.'
        );
    }

    protected function assertSponsorExists(int $sponsorId): SponsorEntity
    {
        $sponsor = $this->em()->find('Warext\MinecraftVote:Sponsor', $sponsorId, ['Server']);
        if (!$sponsor)
        {
            throw $this->exception($this->notFound());
        }

        return $sponsor;
    }

    protected function parseDate(string $value, bool $allowEmpty): int
    {
        $value = trim($value);
        if ($value === '')
        {
            return $allowEmpty ? 0 : \XF::$time;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false || $timestamp <= 0)
        {
            throw new PrintableException('Geçerli bir tarih girin. Örnek: 2026-09-01 18:00');
        }

        return $timestamp;
    }
}
