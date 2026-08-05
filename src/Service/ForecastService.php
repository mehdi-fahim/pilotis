<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Project;

final class ForecastService
{
    public function calculateForecastEndDate(Project $project): ?\DateTimeImmutable
    {
        $progress = $project->getProgressPercent();

        if ($progress <= 0.0) {
            return $project->getEndDate();
        }

        if ($progress >= 100.0) {
            return new \DateTimeImmutable('today');
        }

        $startDate = $project->getStartDate();
        $today = new \DateTimeImmutable('today');
        $elapsedDays = max(1, (int) $startDate->diff($today)->days);

        $estimatedTotalDays = (int) round($elapsedDays / ($progress / 100));
        $forecastEndDate = $startDate->modify(sprintf('+%d days', $estimatedTotalDays));

        if ($project->getEndDate() !== null && $forecastEndDate <= $project->getEndDate()) {
            return $project->getEndDate();
        }

        return $forecastEndDate;
    }
}
