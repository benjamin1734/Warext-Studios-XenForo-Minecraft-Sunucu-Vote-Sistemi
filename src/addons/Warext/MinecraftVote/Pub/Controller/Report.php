<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Security\PublicPermissions;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Report extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id || !PublicPermissions::allows('report', false, true))
        {
            return $this->noPermission();
        }

        $server = $this->em()->find('Warext\MinecraftVote:Server', (int)$params->server_id);
        if (!$server || $server->state !== 'active')
        {
            throw $this->exception($this->notFound());
        }

        if ($server->owner_user_id === $visitor->user_id)
        {
            return $this->noPermission();
        }

        if ($this->isPost())
        {
            $this->assertPostOnly();
            $reason = $this->filter('reason', 'str');
            $message = $this->filter('message', 'str');

            try
            {
                $creator = $this->service('Warext\MinecraftVote:Report\Creator', $server, $visitor);
                $creator->setData($reason, $message);
                $creator->save();
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            return $this->redirect(
                $this->buildLink('sunucular/detay', $server),
                'Raporunuz moderasyon ekibine gönderildi.'
            );
        }

        return $this->view('Warext\MinecraftVote:Report\Create', 'warext_mc_report_create', [
            'server' => $server
        ]);
    }
}
