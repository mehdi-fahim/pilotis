<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum TeamRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Propriétaire',
            self::ADMIN => 'Administrateur',
            self::MEMBER => 'Membre',
        };
    }
}
