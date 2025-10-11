<?php

namespace App\Enums;

enum RolesEnum: string
{
    // case NAMEINAPP = 'name-in-database';
    case SUPERADMIN = 'super-admin';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            static::SUPERADMIN => 'Super Admin',
            static::ADMIN => 'Admin',
        };
    }
}
