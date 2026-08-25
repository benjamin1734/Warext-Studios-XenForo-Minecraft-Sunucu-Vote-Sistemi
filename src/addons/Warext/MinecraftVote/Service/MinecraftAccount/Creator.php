<?php

namespace Warext\MinecraftVote\Service\MinecraftAccount;

use Warext\MinecraftVote\Entity\MinecraftAccount;
use XF\App;
use XF\Entity\User;
use XF\PrintableException;
use XF\Service\AbstractService;

class Creator extends AbstractService
{
    protected User $user;
    protected string $username = '';
    protected string $uuid = '';

    public function __construct(App $app, User $user)
    {
        parent::__construct($app);
        $this->user = $user;
    }

    public function setData(string $username, string $uuid = ''): void
    {
        $username = trim($username);
        if (!preg_match('/^[A-Za-z0-9_]{3,16}$/', $username))
        {
            throw new PrintableException('Minecraft kullanıcı adı 3-16 karakter olmalı ve yalnızca harf, rakam veya alt çizgi içermelidir.');
        }

        $uuid = $this->normalizeUuid($uuid);

        if ($this->repository('Warext\MinecraftVote:MinecraftAccount')
            ->hasUsernameForUser($this->user->user_id, $username))
        {
            throw new PrintableException('Bu Minecraft kullanıcı adı hesabınıza zaten bağlı.');
        }

        $this->username = $username;
        $this->uuid = $uuid;
    }

    public function save(): MinecraftAccount
    {
        if (!$this->user->user_id)
        {
            throw new PrintableException('Minecraft hesabı eklemek için giriş yapmanız gerekiyor.');
        }

        if ($this->username === '')
        {
            throw new PrintableException('Minecraft kullanıcı adı gereklidir.');
        }

        $repo = $this->repository('Warext\MinecraftVote:MinecraftAccount');
        $hasAny = (bool)$repo->findForUser($this->user->user_id)->fetchOne();

        $account = $this->em()->create('Warext\MinecraftVote:MinecraftAccount');
        $account->user_id = $this->user->user_id;
        $account->minecraft_username = $this->username;
        $account->minecraft_uuid = $this->uuid;
        $account->verification_state = 'unverified';
        $account->is_primary = !$hasAny;
        $account->save();

        return $account;
    }

    protected function normalizeUuid(string $uuid): string
    {
        $uuid = strtolower(trim($uuid));
        if ($uuid === '')
        {
            return '';
        }

        $hex = str_replace('-', '', $uuid);
        if (!preg_match('/^[a-f0-9]{32}$/', $hex))
        {
            throw new PrintableException('Minecraft UUID biçimi geçersiz.');
        }

        return substr($hex, 0, 8) . '-'
            . substr($hex, 8, 4) . '-'
            . substr($hex, 12, 4) . '-'
            . substr($hex, 16, 4) . '-'
            . substr($hex, 20, 12);
    }
}
