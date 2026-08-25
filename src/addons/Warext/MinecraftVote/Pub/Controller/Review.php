<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\Server;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Review extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $server = $this->assertActiveServer((int)$params->server_id);
        $visitor = \XF::visitor();
        $repo = $this->repository('Warext\MinecraftVote:Review');

        if ($this->isPost())
        {
            if (!$visitor->user_id)
            {
                return $this->noPermission();
            }

            $input = $this->filter([
                'rating' => 'uint',
                'gameplay_rating' => 'uint',
                'staff_rating' => 'uint',
                'performance_rating' => 'uint',
                'community_rating' => 'uint',
                'originality_rating' => 'uint',
                'message' => 'str'
            ]);

            try
            {
                $writer = $this->service('Warext\MinecraftVote:Review\Writer', $server, $visitor);
                $writer->save($input);
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            return $this->redirect(
                $this->buildLink('sunucular/degerlendir', $server),
                'Değerlendirmeniz kaydedildi.'
            );
        }

        $page = $this->filterPage();
        $perPage = 20;
        $finder = $repo->findVisibleForServer($server->server_id);
        $total = $finder->total();
        $this->assertValidPage($page, $perPage, $total, 'sunucular/degerlendir', $server);

        $reviews = $finder
            ->limitByPage($page, $perPage)
            ->fetch();

        $userReview = $visitor->user_id
            ? $repo->getUserReview($server->server_id, $visitor->user_id)
            : null;

        return $this->view('Warext\MinecraftVote:Review\Index', 'warext_mc_review_index', [
            'server' => $server,
            'reviews' => $reviews,
            'userReview' => $userReview,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total
        ]);
    }

    public function actionDelete(ParameterBag $params)
    {
        $this->assertPostOnly();
        $server = $this->assertActiveServer((int)$params->server_id);
        $visitor = \XF::visitor();

        if (!$visitor->user_id)
        {
            return $this->noPermission();
        }

        $writer = $this->service('Warext\MinecraftVote:Review\Writer', $server, $visitor);
        $writer->delete();

        return $this->redirect(
            $this->buildLink('sunucular/degerlendir', $server),
            'Değerlendirmeniz silindi.'
        );
    }

    protected function assertActiveServer(int $serverId): Server
    {
        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId);
        if (!$server || $server->state !== 'active')
        {
            throw $this->exception($this->notFound());
        }

        return $server;
    }
}
