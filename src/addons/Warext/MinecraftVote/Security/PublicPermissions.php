<?php

namespace Warext\MinecraftVote\Security;

final class PublicPermissions
{
    protected const GROUP = 'warextMcVote';
    protected static array $configured = [];

    public static function allows(string $permission, bool $guestDefault, bool $memberDefault = true): bool
    {
        $visitor = \XF::visitor();
        if ($visitor->hasPermission(self::GROUP, $permission))
        {
            return true;
        }

        if (!array_key_exists($permission, self::$configured))
        {
            self::$configured[$permission] = (bool)\XF::db()->fetchOne(
                'SELECT 1 FROM xf_permission_entry WHERE permission_group_id = ? AND permission_id = ? LIMIT 1',
                [self::GROUP, $permission]
            );
        }

        if (self::$configured[$permission])
        {
            return false;
        }

        return $visitor->user_id ? $memberDefault : $guestDefault;
    }
}
