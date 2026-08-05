<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum RiskStatus: string
{
    case IDENTIFIED = 'identified';
    case MITIGATING = 'mitigating';
    case RESOLVED = 'resolved';
    case ACCEPTED = 'accepted';

    public function label(): string
    {
        return match ($this) {
            self::IDENTIFIED => 'Identifié',
            self::MITIGATING => 'En mitigation',
            self::RESOLVED => 'Résolu',
            self::ACCEPTED => 'Accepté',
        };
    }
}
