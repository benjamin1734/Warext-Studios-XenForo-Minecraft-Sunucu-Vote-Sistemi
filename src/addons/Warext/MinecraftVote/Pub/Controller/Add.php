<?php

namespace Warext\MinecraftVote\Pub\Controller;

use Warext\MinecraftVote\Security\PublicPermissions;
use XF\Pub\Controller\AbstractController;

class Add extends AbstractController
{
    public function actionIndex()
    {
        $visitor = \XF::visitor();
        if (!$visitor->user_id || !PublicPermissions::allows('addServer', false, true))
        {
            return $this->noPermission();
        }

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
                'category_ids' => 'array-uint'
            ]);

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

                $thread = $this->service('Warext\MinecraftVote:Server\ThreadLinker')
                    ->resolve($input['discussion_thread_url'], $visitor);

                $creator = $this->service('Warext\MinecraftVote:Server\Creator');
                $creator->setOwner($visitor);
                $creator->setData($input);
                $creator->setCategoryIds($input['category_ids']);
                $server = $creator->save();
                if ($thread)
                {
                    $server->discussion_thread_id = (int)$thread->thread_id;
                    $server->save();
                }

                foreach ($uploads as $type => $upload)
                {
                    if ($upload)
                    {
                        $media->store($server, $upload, $type);
                    }
                }
            }
            catch (\XF\PrintableException $e)
            {
                return $this->error($e->getMessage(), 400);
            }

            return $this->redirect(
                $this->buildLink('sunucular/detay', $server),
                'Sunucu kaydınız onay için gönderildi.'
            );
        }

        $categories = $this->finder('Warext\MinecraftVote:Category')
            ->where('is_active', 1)
            ->order('display_order')
            ->fetch();

        return $this->view('Warext\MinecraftVote:Server\Add', 'warext_mc_server_add', [
            'categories' => $categories
        ]);
    }
}
