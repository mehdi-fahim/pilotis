<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Project;
use App\Domain\Entity\Risk;
use App\Domain\Entity\Task;
use App\Domain\Enum\HealthStatus;
use App\Domain\Enum\RiskStatus;

final class ProjectHealthService
{
    public function calculate(Project $project): HealthStatus
    {
        $score = 0;

        $overdueTasks = $project->getTasks()->filter(
            static fn (Task $task): bool => $task->isOverdue()
        )->count();

        if ($overdueTasks > 0) {
            $score += min(3, $overdueTasks);
        }

        if ($project->isOverdue()) {
            $score += 2;
        }

        $riskScore = $project->getRisks()
            ->filter(static fn (Risk $risk): bool => !in_array($risk->getStatus(), [RiskStatus::RESOLVED, RiskStatus::ACCEPTED], true))
            ->map(static fn (Risk $risk): int => $risk->getScore())
            ->reduce(static fn (int $carry, int $item): int => max($carry, $item), 0);

        if ($riskScore >= 20) {
            $score += 3;
        } elseif ($riskScore >= 12) {
            $score += 2;
        } elseif ($riskScore >= 6) {
            $score += 1;
        }

        return match (true) {
            $score >= 5 => HealthStatus::RED,
            $score >= 2 => HealthStatus::ORANGE,
            default => HealthStatus::GREEN,
        };
    }
}
