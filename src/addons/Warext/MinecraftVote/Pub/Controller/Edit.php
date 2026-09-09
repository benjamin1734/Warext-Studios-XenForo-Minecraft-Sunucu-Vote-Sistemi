<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Entity\Server;
use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Edit extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
        $server = $this->assertCanEdit((int)$params->server_id);

        if ($this->isPost())
        {
            $input = $this->filter([
                'title' => 'str',
                'description' => 'str',
                'server_type' => 'str',
                'host' => 'str',
                'port' => 'uint',
                'bedrock_host' => 'str',
                'bedrock_port' => 'uint',
                'website_url' => 'str',
                'discord_url' => 'str',
                'store_url' => 'str',
                'trailer_url' => 'str',
                'discussion_thread_url' => 'str',
                'game_modes' => 'str',
                'version_min' => 'str',
                'version_max' => 'str',
                'country_code' => 'str',
                'is_premium' => 'bool',
                'allow_cracked' => 'bool',
                'category_ids' => 'array-uint',
                'remove_banner' => 'bool',
                'remove_animated_banner' => 'bool',
                'remove_cover' => 'bool'
            ]);

            $wasActive = $server->state === 'active';
            $media = $this->service('Warext\MinecraftVote:Server\Media');
            $uploads = [
                'banner' => $this->request->getFile('banner'),
                'animated_banner' => $this->request->getFile('animated_banner'),
                'cover' => $this->request->getFile('cover')
            ];

            try
            {
                foreach ($uploads as $type => $upload)
                {
                    $media->validateUpload($upload, $type);
                }

                $threadLinker = $this->service('Warext\MinecraftVote:Server\ThreadLinker');
                $thread = $threadLinker->resolve($input['discussion_thread_url'], \XF::visitor(), $server);
                $categoryIds = $input['category_ids'];
                if ($thread)
                {
                    $mappedCategory = $threadLinker->getMappedCategory($thread);
                    if ($mappedCategory)
                    {
                        $categoryIds[] = (int)$mappedCategory->category_id;
                    }
                }

                $editor = $this->service(
                    'Warext\MinecraftVote:Server\Editor',
                    $server,
                    (int)\XF::visitor()->user_id
                );
                $editor->setData($input);
                $editor->setCategoryIds($categoryIds);
                $editor->save();
                $threadLinker->link($server, $thread);

                foreach (['banner', 'animated_banner', 'cover'] as $type)
                {
                    $removeKey = 'remove_' . $type;
                    if (!empty($input[$removeKey]))
                    {
                        $media->remove($server, $type);
                    }
                    if ($uploads[$type])
                    {
                        $media->store($server, $uploads[$type], $type);
                    }
                }
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            $message = $wasActive && $server->state === 'pending'
                ? 'Sunucu bağlantı bilgileri değişti. Sahiplik doğrulaması sıfırlandı ve kayıt yeniden yönetici onayına gönderildi.'
                : 'Sunucu bilgileri güncellendi.';

            return $this->redirect(
                $this->buildLink('sunucular/detay', $server),
                $message
            );
        }

        $categories = $this->finder('Warext\MinecraftVote:Category')
            ->where('is_active', 1)
            ->order('display_order')
            ->fetch();

        $rows = $this->app->db()->fetchAll(
            'SELECT category_id FROM xf_warext_mc_server_category WHERE server_id = ?',
            [$server->server_id]
        );
        $selectedCategoryIds = array_map('intval', array_column($rows, 'category_id'));

        return $this->view('Warext\MinecraftVote:Server\Edit', 'warext_mc_server_edit', [
            'server' => $server,
            'categories' => $categories,
            'selectedCategoryIds' => $selectedCategoryIds
        ]);
    }

    protected function assertCanEdit(int $serverId): Server
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id)
        {
            throw $this->exception($this->noPermission());
        }

        $server = $this->em()->find('Warext\MinecraftVote:Server', $serverId, ['DiscussionThread']);
        if (!$server)
        {
            throw $this->exception($this->notFound());
        }

        if (!$this->repository('Warext\MinecraftVote:ServerTeam')
            ->hasPermission($server, $visitor->user_id, 'edit_content'))
        {
            throw $this->exception($this->noPermission());
        }

        return $server;
    }
}
