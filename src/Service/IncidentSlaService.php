<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Enum\Priority;

final class IncidentSlaService
{
    public function computeDueDate(Priority $priority, \DateTimeImmutable $discoveredAt): \DateTimeImmutable
    {
        $days = match ($priority) {
            Priority::CRITICAL => 1,
            Priority::HIGH => 3,
            Priority::MEDIUM => 7,
            Priority::LOW => 14,
        };

        return $discoveredAt->modify(sprintf('+%d days', $days));
    }

    public function slaDays(Priority $priority): int
    {
        return match ($priority) {
            Priority::CRITICAL => 1,
            Priority::HIGH => 3,
            Priority::MEDIUM => 7,
            Priority::LOW => 14,
        };
    }
}
