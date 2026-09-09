<?php

namespace Warext\MinecraftVote\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Setup extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertAdminPermission('warextMinecraftVote');
    }

    public function actionIndex()
    {
        $options = \XF::options();
        $db = $this->app->db();
        $counts = [
            'categories' => (int)$db->fetchOne('SELECT COUNT(*) FROM xf_warext_mc_category WHERE is_active = 1'),
            'pending' => (int)$db->fetchOne("SELECT COUNT(*) FROM xf_warext_mc_server WHERE state = 'pending'"),
            'active' => (int)$db->fetchOne("SELECT COUNT(*) FROM xf_warext_mc_server WHERE state = 'active'")
        ];

        return $this->view('Warext\MinecraftVote:Setup\Index', 'warext_mc_admin_setup', [
            'counts' => $counts,
            'allowGuests' => (bool)$options->warextMcAllowGuestVotes,
            'requireVerified' => (bool)$options->warextMcRequireVerifiedAccountForVotes,
            'captchaEnabled' => (bool)$options->warextMcVoteCaptcha,
            'sponsorEnabled' => (bool)$options->warextMcSponsorSalesEnabled,
            'publicApiEnabled' => (bool)$options->warextMcPublicApiEnabled,
            'webhookEnabled' => (bool)$options->warextMcWebhookEnabled
        ]);
    }
}
