<?php

namespace Warext\MinecraftVote\Admin\Controller;

use Warext\MinecraftVote\Security\VerificationBridgeKey;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class VerificationBridge extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params): void
    {
        $this->assertAdminPermission('warextMinecraftVote');
    }

    public function actionIndex()
    {
        $optionRepo = $this->repository('XF:Option');

        if ($this->isPost())
        {
            $operation = $this->filter('operation', 'str');

            if ($operation === 'save')
            {
                $enabled = $this->filter('enabled', 'bool');
                $address = trim($this->filter('server_address', 'str'));
                if (mb_strlen($address) > 255)
                {
                    return $this->error('Doğrulama sunucusu adresi en fazla 255 karakter olabilir.', 400);
                }

                $optionRepo->updateOptions([
                    'warextMcVerifyBridgeEnabled' => $enabled ? 1 : 0,
                    'warextMcVerifyServerAddress' => $address
                ]);
                $optionRepo->rebuildOptionCache();

                return $this->redirect(
                    $this->buildLink('warext-minecraft/verification-bridge'),
                    'Minecraft hesap doğrulama köprüsü ayarları kaydedildi.'
                );
            }

            if ($operation === 'rotate')
            {
                $generation = max(1, (int)(\XF::options()->warextMcVerifyBridgeGeneration ?? 1));
                if ($generation >= 2147483647)
                {
                    return $this->error('Bridge generation sınırına ulaşıldı.', 400);
                }

                $optionRepo->updateOption('warextMcVerifyBridgeGeneration', $generation + 1);
                $optionRepo->rebuildOptionCache();

                return $this->redirect(
                    $this->buildLink('warext-minecraft/verification-bridge'),
                    'Bridge anahtarı yenilendi. Minecraft doğrulama eklentisindeki secret değerini güncelleyin.'
                );
            }

            return $this->error('Geçersiz bridge işlemi.', 400);
        }

        $bridge = new VerificationBridgeKey();
        $endpoint = $this->app->router('public')->buildLink('canonical:warext-minecraft-verify');

        return $this->view(
            'Warext\MinecraftVote:VerificationBridge\Index',
            'warext_mc_admin_verification_bridge',
            [
                'enabled' => $bridge->isEnabled(),
                'generation' => $bridge->getGeneration(),
                'secret' => $bridge->getSecret(),
                'endpoint' => $endpoint,
                'serverAddress' => trim((string)(\XF::options()->warextMcVerifyServerAddress ?? ''))
            ]
        );
    }
}
