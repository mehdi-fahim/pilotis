<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Project;
use App\Domain\Entity\Risk;
use App\Domain\Entity\Task;
use App\Domain\Enum\RiskStatus;
use App\Domain\Enum\TaskStatus;

final class AiSummaryService
{
    public function generateWeeklySummary(Project $project): string
    {
        $lines = [];
        $progress = $project->getProgressPercent();

        $lines[] = sprintf('Projet « %s » — synthèse hebdomadaire', $project->getName());
        $lines[] = sprintf('Avancement global : %.1f %% (%s).', $progress, $project->getHealthStatus()->label());

        $completedThisWeek = $project->getTasks()->filter(
            static fn (Task $task): bool => $task->getStatus() === TaskStatus::DONE
                && $task->getUpdatedAt() !== null
                && $task->getUpdatedAt() >= new \DateTimeImmutable('-7 days')
        )->count();

        $inProgress = $project->getTasks()->filter(
            static fn (Task $task): bool => $task->getStatus() === TaskStatus::IN_PROGRESS
        )->count();

        if ($completedThisWeek > 0) {
            $lines[] = sprintf('%d tâche(s) terminée(s) cette semaine.', $completedThisWeek);
        } else {
            $lines[] = 'Aucune tâche terminée cette semaine.';
        }

        if ($inProgress > 0) {
            $lines[] = sprintf('%d tâche(s) en cours de réalisation.', $inProgress);
        }

        $overdueTasks = $project->getTasks()->filter(static fn (Task $task): bool => $task->isOverdue());
        if ($overdueTasks->count() > 0) {
            $lines[] = sprintf('Point d\'attention : %d tâche(s) en retard.', $overdueTasks->count());
            foreach ($overdueTasks->slice(0, 3) as $task) {
                $lines[] = sprintf('  - %s (échéance : %s)', $task->getTitle(), $task->getDueDate()?->format('d/m/Y') ?? 'N/A');
            }
        }

        $activeRisks = $project->getRisks()->filter(
            static fn (Risk $risk): bool => !in_array($risk->getStatus(), [RiskStatus::RESOLVED, RiskStatus::ACCEPTED], true)
        );

        if ($activeRisks->count() > 0) {
            $lines[] = sprintf('Risques actifs : %d.', $activeRisks->count());
            $topRisk = $activeRisks->reduce(
                static fn (?Risk $highest, Risk $risk): Risk => $highest === null || $risk->getScore() > $highest->getScore() ? $risk : $highest
            );
            if ($topRisk !== null) {
                $lines[] = sprintf('  Risque principal : « %s » (score %d).', $topRisk->getTitle(), $topRisk->getScore());
            }
        } else {
            $lines[] = 'Aucun risque actif identifié.';
        }

        if ($project->getForecastEndDate() !== null && $project->getEndDate() !== null) {
            if ($project->getForecastEndDate() > $project->getEndDate()) {
                $lines[] = sprintf(
                    'Prévision de fin : %s (après la date cible du %s).',
                    $project->getForecastEndDate()->format('d/m/Y'),
                    $project->getEndDate()->format('d/m/Y')
                );
            } else {
                $lines[] = sprintf('Prévision de fin : %s, dans les délais.', $project->getForecastEndDate()->format('d/m/Y'));
            }
        }

        if ($progress >= 75) {
            $lines[] = 'Tendance positive : le projet approche de son terme.';
        } elseif ($progress < 25 && $project->getStatus()->value === 'active') {
            $lines[] = 'Le projet démarre ; un suivi rapproché est recommandé.';
        }

        return implode("\n", $lines);
    }
}
