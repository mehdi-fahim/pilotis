<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum IncidentStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case WAITING = 'waiting';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Ouvert',
            self::IN_PROGRESS => 'En cours',
            self::WAITING => 'En attente',
            self::RESOLVED => 'Résolu',
            self::CLOSED => 'Clôturé',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::OPEN => 'danger',
            self::IN_PROGRESS => 'primary',
            self::WAITING => 'warning',
            self::RESOLVED => 'success',
            self::CLOSED => 'secondary',
        };
    }

    public function isOpen(): bool
    {
        return match ($this) {
            self::RESOLVED, self::CLOSED => false,
            default => true,
        };
    }
}
