<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\Server;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Votifier extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $server = $this->assertCanManage((int)$params->server_id);
        $writer = $this->service('Warext\MinecraftVote:Votifier\ConfigWriter', $server);
        $config = $writer->getConfig();

        if ($this->isPost())
        {
            $input = $this->filter([
                'enabled' => 'bool',
                'host' => 'str',
                'port' => 'uint',
                'service_name' => 'str',
                'token' => 'str',
                'test' => 'bool'
            ]);

            try
            {
                $writer->setData($input);
                $config = $writer->save();

                if ($input['test'])
                {
                    $result = $writer->testConnection();
                    return $this->redirect(
                        $this->buildLink('sunucular/votifier', $server),
                        \XF::phrase('warext_mc_dyn_votifier_test_success', ['connection' => (int)$result['ping_ms'] . ' ms'])
                    );
                }
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            return $this->redirect(
                $this->buildLink('sunucular/votifier', $server),
                \XF::phrase('warext_mc_dyn_votifier_saved')
            );
        }

        return $this->view('Warext\MinecraftVote:Server\Votifier', 'warext_mc_votifier_config', [
            'server' => $server,
            'config' => $config,
            'tokenExplain' => $config->token_encrypted
                ? \XF::phrase('warext_mc_dyn_votifier_token_saved_explain')
                : \XF::phrase('warext_mc_dyn_votifier_token_help')
        ]);
    }

    protected function assertCanManage(int $serverId): Server
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            throw $this->exception($this->noPermission());
        }

        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId);
        if (!$server)
        {
            throw $this->exception($this->notFound());
        }

        if (!$this->repository('Warext\MinecraftVote:ServerTeam')
            ->hasPermission($server, $visitor->user_id, 'manage_votifier'))
        {
            throw $this->exception($this->noPermission());
        }

        return $server;
    }
}
