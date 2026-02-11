<?php

namespace App\Enums;

enum TenantRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Readonly = 'readonly';

    /**
     * Get roles that can manage members (invite, update roles, etc.).
     *
     * @return list<self>
     */
    public static function managementRoles(): array
    {
        return [self::Owner, self::Admin];
    }

    /**
     * Get roles that can create content.
     *
     * @return list<self>
     */
    public static function contentCreatorRoles(): array
    {
        return [self::Owner, self::Admin, self::Member];
    }
}
