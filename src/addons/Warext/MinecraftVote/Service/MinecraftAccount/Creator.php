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
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_minecraft_username_format'));
        }

        $uuid = $this->normalizeUuid($uuid);

        if ($this->repository('Warext\MinecraftVote:MinecraftAccount')
            ->hasUsernameForUser($this->user->user_id, $username))
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_account_already_linked'));
        }

        $this->username = $username;
        $this->uuid = $uuid;
    }

    public function save(): MinecraftAccount
    {
        if (!$this->user->user_id)
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_account_login_required'));
        }

        if ($this->username === '')
        {
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_minecraft_username_required'));
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
            throw new PrintableException((string)\XF::phrase('warext_mc_dyn_invalid_minecraft_uuid'));
        }

        return substr($hex, 0, 8) . '-'
            . substr($hex, 8, 4) . '-'
            . substr($hex, 12, 4) . '-'
            . substr($hex, 16, 4) . '-'
            . substr($hex, 20, 12);
    }
}
