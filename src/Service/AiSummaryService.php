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
    /**
     * @return array{
     *     progress: float,
     *     healthLabel: string,
     *     healthValue: string,
     *     completedThisWeek: int,
     *     inProgress: int,
     *     overdueCount: int,
     *     overdueTasks: list<array{title: string, dueDate: string}>,
     *     activeRisks: int,
     *     topRisk: ?array{title: string, score: int},
     *     forecast: ?array{tone: string, text: string},
     *     insight: ?array{tone: string, text: string}
     * }
     */
    public function weeklyFacts(Project $project): array
    {
        $progress = $project->getProgressPercent();
        $health = $project->getHealthStatus();

        $completedThisWeek = $project->getTasks()->filter(
            static fn (Task $task): bool => $task->getStatus() === TaskStatus::DONE
                && $task->getUpdatedAt() !== null
                && $task->getUpdatedAt() >= new \DateTimeImmutable('-7 days')
        )->count();

        $inProgress = $project->getTasks()->filter(
            static fn (Task $task): bool => $task->getStatus() === TaskStatus::IN_PROGRESS
        )->count();

        $overdueTasks = $project->getTasks()->filter(static fn (Task $task): bool => $task->isOverdue());
        $overdueItems = [];
        foreach ($overdueTasks->slice(0, 3) as $task) {
            $overdueItems[] = [
                'title' => $task->getTitle(),
                'dueDate' => $task->getDueDate()?->format('d/m/Y') ?? 'N/A',
            ];
        }

        $activeRisks = $project->getRisks()->filter(
            static fn (Risk $risk): bool => !in_array($risk->getStatus(), [RiskStatus::RESOLVED, RiskStatus::ACCEPTED], true)
        );

        $topRisk = null;
        if ($activeRisks->count() > 0) {
            $highest = $activeRisks->reduce(
                static fn (?Risk $current, Risk $risk): Risk => $current === null || $risk->getScore() > $current->getScore() ? $risk : $current
            );
            if ($highest !== null) {
                $topRisk = [
                    'title' => $highest->getTitle(),
                    'score' => $highest->getScore(),
                ];
            }
        }

        $forecast = null;
        if ($project->getForecastEndDate() !== null && $project->getEndDate() !== null) {
            if ($project->getForecastEndDate() > $project->getEndDate()) {
                $forecast = [
                    'tone' => 'warning',
                    'text' => sprintf(
                        'Prévision de fin : %s (après la date cible du %s).',
                        $project->getForecastEndDate()->format('d/m/Y'),
                        $project->getEndDate()->format('d/m/Y')
                    ),
                ];
            } else {
                $forecast = [
                    'tone' => 'success',
                    'text' => sprintf('Prévision de fin : %s, dans les délais.', $project->getForecastEndDate()->format('d/m/Y')),
                ];
            }
        }

        $insight = null;
        if ($progress >= 75) {
            $insight = [
                'tone' => 'success',
                'text' => 'Tendance positive : le projet approche de son terme.',
            ];
        } elseif ($progress < 25 && $project->getStatus()->value === 'active') {
            $insight = [
                'tone' => 'info',
                'text' => 'Le projet démarre ; un suivi rapproché est recommandé.',
            ];
        }

        return [
            'progress' => $progress,
            'healthLabel' => $health->label(),
            'healthValue' => $health->value,
            'completedThisWeek' => $completedThisWeek,
            'inProgress' => $inProgress,
            'overdueCount' => $overdueTasks->count(),
            'overdueTasks' => $overdueItems,
            'activeRisks' => $activeRisks->count(),
            'topRisk' => $topRisk,
            'forecast' => $forecast,
            'insight' => $insight,
        ];
    }

    public function generateWeeklySummary(Project $project): string
    {
        $facts = $this->weeklyFacts($project);
        $lines = [];

        $lines[] = sprintf('Projet « %s » — synthèse hebdomadaire', $project->getName());
        $lines[] = sprintf('Avancement global : %.1f %% (%s).', $facts['progress'], $facts['healthLabel']);

        if ($facts['completedThisWeek'] > 0) {
            $lines[] = sprintf('%d tâche(s) terminée(s) cette semaine.', $facts['completedThisWeek']);
        } else {
            $lines[] = 'Aucune tâche terminée cette semaine.';
        }

        if ($facts['inProgress'] > 0) {
            $lines[] = sprintf('%d tâche(s) en cours de réalisation.', $facts['inProgress']);
        }

        if ($facts['overdueCount'] > 0) {
            $lines[] = sprintf('Point d\'attention : %d tâche(s) en retard.', $facts['overdueCount']);
            foreach ($facts['overdueTasks'] as $task) {
                $lines[] = sprintf('  - %s (échéance : %s)', $task['title'], $task['dueDate']);
            }
        }

        if ($facts['activeRisks'] > 0) {
            $lines[] = sprintf('Risques actifs : %d.', $facts['activeRisks']);
            if ($facts['topRisk'] !== null) {
                $lines[] = sprintf('  Risque principal : « %s » (score %d).', $facts['topRisk']['title'], $facts['topRisk']['score']);
            }
        } else {
            $lines[] = 'Aucun risque actif identifié.';
        }

        if ($facts['forecast'] !== null) {
            $lines[] = $facts['forecast']['text'];
        }

        if ($facts['insight'] !== null) {
            $lines[] = $facts['insight']['text'];
        }

        return implode("\n", $lines);
    }
}
