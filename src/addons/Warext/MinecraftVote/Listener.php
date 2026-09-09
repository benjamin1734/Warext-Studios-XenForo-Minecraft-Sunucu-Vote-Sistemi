<?php

namespace Warext\MinecraftVote;

use XF\Mvc\Entity\Entity;
use XF\Template\Templater;

class Listener
{
    public static function threadFormPreRender(Templater $templater, &$type, &$template, array &$params): void
    {
        if (empty($params['forum']) || !\XF::visitor()->user_id)
        {
            return;
        }

        $category = \XF::finder('Warext\MinecraftVote:Category')
            ->where('forum_node_id', (int)$params['forum']->node_id)
            ->where('thread_integration_enabled', 1)
            ->where('is_active', 1)
            ->fetchOne();
        if (!$category)
        {
            return;
        }

        $params['warextMcThreadCategory'] = $category;
    }

    public static function threadViewPreRender(Templater $templater, &$type, &$template, array &$params): void
    {
        if (empty($params['thread']))
        {
            return;
        }

        $server = \XF::finder('Warext\MinecraftVote:Server')
            ->where('discussion_thread_id', (int)$params['thread']->thread_id)
            ->fetchOne();
        if (!$server)
        {
            return;
        }

        $visitor = \XF::visitor();
        if ($server->state !== 'active' && (int)$server->owner_user_id !== (int)$visitor->user_id && !$visitor->is_moderator && !$visitor->is_admin)
        {
            return;
        }

        $params['warextMcLinkedServer'] = $server;
    }

    public static function postEntityPostSave(Entity $entity): void
    {
        if (!$entity instanceof \XF\Entity\Post || !$entity->isInsert() || (int)$entity->position !== 0)
        {
            return;
        }

        $request = \XF::app()->request();
        $host = trim((string)$request->filter('warext_mc_host', 'str'));
        if ($host === '')
        {
            return;
        }

        try
        {
            $thread = $entity->Thread;
            if (!$thread || !$thread->Forum || !$thread->User)
            {
                return;
            }

            $category = \XF::finder('Warext\MinecraftVote:Category')
                ->where('forum_node_id', (int)$thread->node_id)
                ->where('thread_integration_enabled', 1)
                ->where('is_active', 1)
                ->fetchOne();
            if (!$category)
            {
                return;
            }

            $serverType = strtolower(trim((string)$request->filter('warext_mc_server_type', 'str')));
            if (!in_array($serverType, ['java', 'bedrock', 'crossplay'], true))
            {
                $serverType = (string)$category->thread_default_server_type;
            }

            $data = [
                'title' => (string)$thread->title,
                'description' => (string)$entity->message,
                'server_type' => $serverType,
                'host' => $host,
                'port' => (int)$request->filter('warext_mc_port', 'uint') ?: 25565,
                'bedrock_host' => (string)$request->filter('warext_mc_bedrock_host', 'str'),
                'bedrock_port' => (int)$request->filter('warext_mc_bedrock_port', 'uint') ?: 19132,
                'website_url' => (string)$request->filter('warext_mc_website_url', 'str'),
                'discord_url' => (string)$request->filter('warext_mc_discord_url', 'str'),
                'store_url' => '',
                'trailer_url' => '',
                'game_modes' => (string)$category->title,
                'version_min' => '',
                'version_max' => '',
                'country_code' => 'TR',
                'is_premium' => (bool)$request->filter('warext_mc_is_premium', 'bool'),
                'allow_cracked' => (bool)$request->filter('warext_mc_allow_cracked', 'bool')
            ];

            $existingThread = \XF::finder('Warext\MinecraftVote:Server')
                ->where('discussion_thread_id', (int)$thread->thread_id)
                ->fetchOne();
            if ($existingThread)
            {
                return;
            }

            $creator = \XF::service('Warext\MinecraftVote:Server\Creator');
            $creator->setOwner($thread->User);
            $creator->setData($data);
            $creator->setCategoryIds([(int)$category->category_id]);
            $server = $creator->save(false);
            $server->discussion_thread_id = (int)$thread->thread_id;
            $server->source_type = 'thread';
            $server->save();
        }
        catch (\Throwable $e)
        {
            \XF::logException($e, false, 'Warext MinecraftVote thread integration: ');
        }
    }

    public static function threadEntityPostDelete(Entity $entity): void
    {
        if (!$entity instanceof \XF\Entity\Thread)
        {
            return;
        }

        $servers = \XF::finder('Warext\MinecraftVote:Server')
            ->where('discussion_thread_id', (int)$entity->thread_id)
            ->fetch();
        foreach ($servers as $server)
        {
            $server->discussion_thread_id = 0;
            $server->save();
        }
    }
}
