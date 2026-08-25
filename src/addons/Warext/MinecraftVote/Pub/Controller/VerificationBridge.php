<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Security\VerificationBridgeKey;
use XF\Pub\Controller\AbstractController;

class VerificationBridge extends AbstractController
{
    public function actionIndex()
    {
        $this->assertPostOnly();

        $bridge = new VerificationBridgeKey();
        if (!$bridge->isEnabled())
        {
            return $this->error('Doğrulama köprüsü kapalı.', 403);
        }

        $timestamp = $this->filter('timestamp', 'uint');
        $nonce = strtolower(trim($this->filter('nonce', 'str')));
        $code = strtoupper(trim($this->filter('code', 'str')));
        $uuid = strtolower(trim($this->filter('minecraft_uuid', 'str')));
        $username = trim($this->filter('minecraft_username', 'str'));
        $signature = strtolower(trim($this->filter('signature', 'str')));

        if ($timestamp < \XF::$time - 120 || $timestamp > \XF::$time + 120)
        {
            return $this->error('İstek zamanı geçersiz.', 400);
        }
        if (!preg_match('/^[a-f0-9]{32,64}$/', $nonce))
        {
            return $this->error('Nonce geçersiz.', 400);
        }

        $canonical = implode("\n", [
            (string)$timestamp,
            $nonce,
            $code,
            $uuid,
            $username
        ]);

        if (!$bridge->verify($canonical, $signature))
        {
            return $this->error('Bridge imzası geçersiz.', 403);
        }

        try
        {
            $placeholder = $this->em()->create('Warext\MinecraftVote:MinecraftAccount');
            $verification = $this->service('Warext\MinecraftVote:MinecraftAccount\Verification', $placeholder);
            $account = $verification->complete($code, $uuid, $username);
        }
        catch (\XF\PrintableException $e)
        {
            return $this->error($e->getMessage(), 400);
        }

        return $this->message('verified:' . $account->account_id);
    }
}
