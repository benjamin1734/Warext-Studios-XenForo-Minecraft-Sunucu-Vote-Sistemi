<?php

namespace Warext\MinecraftVote\Repository;

use Warext\MinecraftVote\Entity\MinecraftAccount;
use XF\Mvc\Entity\Repository;

class MinecraftAccount extends Repository
{
    public function findForUser(int $userId)
    {
        return $this->finder('Warext\MinecraftVote:MinecraftAccount')
            ->where('user_id', $userId)
            ->order('is_primary', 'DESC')
            ->order('created_date', 'ASC');
    }

    public function getForUser(int $accountId, int $userId): ?MinecraftAccount
    {
        return $this->finder('Warext\MinecraftVote:MinecraftAccount')
            ->where('account_id', $accountId)
            ->where('user_id', $userId)
            ->fetchOne();
    }

    public function hasUsernameForUser(int $userId, string $username): bool
    {
        return (bool)$this->finder('Warext\MinecraftVote:MinecraftAccount')
            ->where('user_id', $userId)
            ->where('minecraft_username', $username)
            ->fetchOne();
    }

    public function makePrimary(MinecraftAccount $account): void
    {
        $db = $this->db();
        $db->beginTransaction();

        try
        {
            $db->update('xf_warext_mc_account', ['is_primary' => 0], 'user_id = ?', $account->user_id);
            $account->is_primary = true;
            $account->save();
            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }
    }

    public function promotePrimaryIfNeeded(int $userId): void
    {
        if ($this->finder('Warext\MinecraftVote:MinecraftAccount')
            ->where('user_id', $userId)
            ->where('is_primary', 1)
            ->fetchOne())
        {
            return;
        }

        $account = $this->finder('Warext\MinecraftVote:MinecraftAccount')
            ->where('user_id', $userId)
            ->order('created_date', 'ASC')
            ->fetchOne();

        if ($account)
        {
            $account->is_primary = true;
            $account->save();
        }
    }
}
