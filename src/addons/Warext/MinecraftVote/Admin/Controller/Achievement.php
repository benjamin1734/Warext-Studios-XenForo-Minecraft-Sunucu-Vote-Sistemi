<?php

namespace Warext\MinecraftVote\Admin\Controller;

use Warext\MinecraftVote\Entity\Achievement as AchievementEntity;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Achievement extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertAdminPermission('warextMinecraftVote');
    }

    public function actionIndex()
    {
        if ($this->isPost())
        {
            $achievement = $this->em()->create('Warext\MinecraftVote:Achievement');
            $this->applyInput($achievement);
            $achievement->save();

            $this->service('Warext\MinecraftVote:Audit\Logger')->log(
                'achievement_updated',
                0,
                \XF::visitor()->user_id,
                0,
                ['achievement_id' => $achievement->achievement_id, 'operation' => 'created']
            );

            return $this->redirect(
                $this->buildLink('warext-minecraft/achievements'),
                'Başarım tanımı oluşturuldu.'
            );
        }

        $achievements = $this->finder('Warext\MinecraftVote:Achievement')
            ->order('display_order', 'ASC')
            ->order('achievement_id', 'ASC')
            ->fetch();

        return $this->view('Warext\MinecraftVote:Achievement\Index', 'warext_mc_admin_achievement_index', [
            'achievements' => $achievements,
            'metrics' => $this->getMetrics()
        ]);
    }

    public function actionEdit(ParameterBag $params)
    {
        $achievement = $this->assertAchievementExists((int)$params->achievement_id);

        if ($this->isPost())
        {
            $this->applyInput($achievement, false);
            $achievement->save();

            $this->service('Warext\MinecraftVote:Audit\Logger')->log(
                'achievement_updated',
                0,
                \XF::visitor()->user_id,
                0,
                ['achievement_id' => $achievement->achievement_id, 'operation' => 'edited']
            );

            return $this->redirect(
                $this->buildLink('warext-minecraft/achievements'),
                'Başarım güncellendi.'
            );
        }

        return $this->view('Warext\MinecraftVote:Achievement\Edit', 'warext_mc_admin_achievement_edit', [
            'achievement' => $achievement,
            'metrics' => $this->getMetrics()
        ]);
    }

    public function actionToggle(ParameterBag $params)
    {
        $this->assertPostOnly();
        $achievement = $this->assertAchievementExists((int)$params->achievement_id);
        $achievement->is_active = !$achievement->is_active;
        $achievement->save();

        $this->service('Warext\MinecraftVote:Audit\Logger')->log(
            'achievement_updated',
            0,
            \XF::visitor()->user_id,
            0,
            [
                'achievement_id' => $achievement->achievement_id,
                'operation' => 'toggle',
                'is_active' => $achievement->is_active ? 1 : 0
            ]
        );

        return $this->redirect($this->buildLink('warext-minecraft/achievements'));
    }

    public function actionRebuild()
    {
        $this->assertPostOnly();
        $jobManager = $this->app->jobManager();
        $uniqueId = 'warextMinecraftAchievementRebuild';

        if (!$jobManager->getUniqueJob($uniqueId))
        {
            $jobManager->enqueueUnique(
                $uniqueId,
                'Warext\MinecraftVote:AchievementRebuild',
                [],
                false
            );
        }

        $this->service('Warext\MinecraftVote:Audit\Logger')->log(
            'achievement_rebuild_requested',
            0,
            \XF::visitor()->user_id
        );

        return $this->redirect(
            $this->buildLink('warext-minecraft/achievements'),
            'Başarım hesaplama işi kuyruğa alındı.'
        );
    }

    protected function applyInput(AchievementEntity $achievement, bool $allowKey = true): void
    {
        $input = $this->filter([
            'achievement_key' => 'str',
            'title' => 'str',
            'description' => 'str',
            'icon' => 'str',
            'metric' => 'str',
            'threshold' => 'uint',
            'display_order' => 'uint',
            'is_active' => 'bool'
        ]);

        if ($allowKey)
        {
            $achievement->achievement_key = $input['achievement_key'];
        }
        $achievement->title = $input['title'];
        $achievement->description = $input['description'];
        $achievement->icon = $input['icon'];
        $achievement->metric = $input['metric'];
        $achievement->threshold = $input['threshold'];
        $achievement->display_order = $input['display_order'];
        $achievement->is_active = $input['is_active'];
    }

    protected function assertAchievementExists(int $achievementId): AchievementEntity
    {
        $achievement = $this->em()->find('Warext\MinecraftVote:Achievement', $achievementId);
        if (!$achievement)
        {
            throw $this->exception($this->notFound());
        }

        return $achievement;
    }

    protected function getMetrics(): array
    {
        return [
            'vote_total' => 'Toplam oy',
            'uptime_bp' => 'Uptime (basis point, %99 = 9900)',
            'peak_players' => 'Zirve eş zamanlı oyuncu',
            'age_days' => 'Platform yaşı (gün)',
            'verified' => 'Sahiplik doğrulaması (1)',
            'season_wins' => 'Aylık sezon birinciliği',
            'trend_rank_max' => 'Trend sıralaması (en fazla değer)'
        ];
    }
}
