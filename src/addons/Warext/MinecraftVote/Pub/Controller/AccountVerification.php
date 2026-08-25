<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\MinecraftAccount;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class AccountVerification extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $account = $this->assertOwnedAccount((int)$params->account_id);
        $challenge = null;

        if ($this->isPost())
        {
            $this->assertNotFlooding('warext_mc_account_verify', 5);

            try
            {
                $challenge = $this->service('Warext\MinecraftVote:MinecraftAccount\Verification', $account)->start();
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }
        }

        return $this->view(
            'Warext\MinecraftVote:MinecraftAccount\Verification',
            'warext_mc_account_verification',
            [
                'account' => $account,
                'challenge' => $challenge,
                'bridgeEnabled' => (bool)(\XF::options()->warextMcVerifyBridgeEnabled ?? false),
                'verificationServer' => trim((string)(\XF::options()->warextMcVerifyServerAddress ?? ''))
            ]
        );
    }

    protected function assertOwnedAccount(int $accountId): MinecraftAccount
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            throw $this->exception($this->noPermission());
        }

        $account = $this->repository('Warext\MinecraftVote:MinecraftAccount')
            ->getForUser($accountId, $visitor->user_id);
        if (!$account)
        {
            throw $this->exception($this->notFound());
        }

        return $account;
    }
}
