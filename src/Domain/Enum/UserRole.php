<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum UserRole: string
{
    case ROLE_USER = 'ROLE_USER';
    case ROLE_PROJECT_MANAGER = 'ROLE_PROJECT_MANAGER';
    case ROLE_ADMIN = 'ROLE_ADMIN';

    public function label(): string
    {
        return match ($this) {
            self::ROLE_USER => 'Utilisateur',
            self::ROLE_PROJECT_MANAGER => 'Chef de projet',
            self::ROLE_ADMIN => 'Administrateur',
        };
    }
}
