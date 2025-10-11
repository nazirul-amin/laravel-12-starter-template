<?php

namespace App\Enums;

enum PermissionsEnum: string
{
    // case NAMEINAPP = 'name-in-database';
    case CREATE_USER = 'create-user';
    case READ_USER = 'read-user';
    case UPDATE_USER = 'update-user';
    case DELETE_USER = 'delete-user';

    public function label(): string
    {
        return match ($this) {
            static::CREATE_USER => 'Create User',
            static::READ_USER => 'Read User',
            static::UPDATE_USER => 'Update User',
            static::DELETE_USER => 'Delete User',
        };
    }
}
