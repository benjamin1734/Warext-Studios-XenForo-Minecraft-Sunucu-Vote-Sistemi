<?php

namespace Warext\MinecraftVote\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Report extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertAdminPermission('warextMinecraftVote');
    }

    public function actionIndex()
    {
        $page = $this->filterPage();
        $perPage = 50;
        $state = strtolower(trim($this->filter('state', 'str')));
        $serverId = $this->filter('server_id', 'uint');

        $finder = $this->repository('Warext\MinecraftVote:Report')->findReports($state, $serverId);
        $total = $finder->total();
        $this->assertValidPage($page, $perPage, $total, 'warext-minecraft/reports');

        return $this->view('Warext\MinecraftVote:Report\Index', 'warext_mc_admin_report_index', [
            'reports' => $finder->limitByPage($page, $perPage)->fetch(),
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'state' => $state,
            'serverId' => $serverId
        ]);
    }

    public function actionUpdate(ParameterBag $params)
    {
        $this->assertPostOnly();

        $report = $this->em()->find('Warext\MinecraftVote:Report', (int)$params->report_id);
        if (!$report)
        {
            throw $this->exception($this->notFound());
        }

        $newState = strtolower(trim($this->filter('state', 'str')));
        $resolution = trim($this->filter('resolution', 'str'));
        if (!in_array($newState, ['open', 'resolved', 'rejected'], true))
        {
            return $this->error('Geçersiz rapor durumu.', 400);
        }
        if (mb_strlen($resolution) > 255)
        {
            return $this->error('Moderasyon notu en fazla 255 karakter olabilir.', 400);
        }

        $oldState = $report->state;
        $db = $this->app->db();
        $db->beginTransaction();
        try
        {
            $report->state = $newState;
            $report->moderator_user_id = \XF::visitor()->user_id;
            $report->resolution = $resolution;
            $report->resolved_date = $newState === 'open' ? 0 : \XF::$time;
            $report->save();

            $this->service('Warext\MinecraftVote:Audit\Logger')->log(
                'report_state_changed',
                $report->server_id,
                \XF::visitor()->user_id,
                $report->reporter_user_id,
                [
                    'report_id' => $report->report_id,
                    'old_state' => $oldState,
                    'new_state' => $newState,
                    'reason' => $report->reason
                ]
            );

            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }

        return $this->redirect($this->buildLink('warext-minecraft/reports'), 'Rapor durumu güncellendi.');
    }
}
