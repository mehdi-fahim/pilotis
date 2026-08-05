<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum HealthStatus: string
{
    case GREEN = 'green';
    case ORANGE = 'orange';
    case RED = 'red';

    public function label(): string
    {
        return match ($this) {
            self::GREEN => 'Sain',
            self::ORANGE => 'Attention',
            self::RED => 'Critique',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::GREEN => 'success',
            self::ORANGE => 'warning',
            self::RED => 'danger',
        };
    }
}
