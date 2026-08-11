<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Incident;
use App\Domain\Entity\Project;
use App\Domain\Enum\HealthStatus;
use App\Domain\Enum\IncidentStatus;
use App\Domain\Enum\Priority;
use App\Domain\Enum\ProjectStatus;
use App\Repository\ActivityLogRepository;
use App\Repository\IncidentRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;

final class DashboardService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly TaskRepository $taskRepository,
        private readonly IncidentRepository $incidentRepository,
        private readonly ActivityLogRepository $activityLogRepository,
    ) {
    }

    public function getDashboardData(): array
    {
        return [
            'projects' => $this->getProjectStats(),
            'incidents' => $this->getIncidentStats(),
        ];
    }

    /** @return array<string, mixed> */
    private function getProjectStats(): array
    {
        $projects = $this->projectRepository->findAll();
        $averageProgress = 0.0;
        if ($projects !== []) {
            $averageProgress = round(
                array_sum(array_map(static fn (Project $p): float => $p->getProgressPercent(), $projects)) / count($projects),
                1
            );
        }

        $totalTasks = count($this->taskRepository->findAll());

        return [
            'projectCount' => $this->projectRepository->countAll(),
            'overdueProjects' => $this->projectRepository->findOverdue(),
            'openTasks' => $this->taskRepository->countOpen(),
            'completedTasks' => $totalTasks - $this->taskRepository->countOpen(),
            'averageProgress' => $averageProgress,
            'chartData' => [
                'projectsByStatus' => $this->buildProjectsByStatusChart(),
                'projectsByHealth' => $this->buildProjectsByHealthChart(),
            ],
            'overdueTasks' => $this->taskRepository->findOverdue(),
            'sparklines' => $this->buildProjectSparklines(),
            'statusBreakdown' => $this->buildBreakdown(
                $this->buildProjectsByStatusChart(),
                ProjectStatus::cases(),
                static fn (ProjectStatus $status): string => $status->label(),
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function getIncidentStats(): array
    {
        $weekStart = new \DateTimeImmutable('monday this week');

        $sparklines = $this->buildIncidentSparklines();

        return [
            'totalCount' => count($this->incidentRepository->findAll()),
            'openCount' => $this->incidentRepository->countOpen(),
            'criticalCount' => $this->incidentRepository->countCriticalOpen(),
            'overdueIncidents' => $this->incidentRepository->findOverdue(),
            'resolvedThisWeek' => $this->incidentRepository->countResolvedSince($weekStart),
            'recentIncidents' => $this->incidentRepository->findRecent(6),
            'openedLast7Days' => array_sum($sparklines['opened']),
            'resolvedLast7Days' => array_sum($sparklines['resolved']),
            'chartData' => [
                'byStatus' => $this->buildIncidentsByStatusChart(),
                'byPriority' => $this->buildIncidentsByPriorityChart(),
            ],
            'statusBreakdown' => $this->buildBreakdown(
                $this->buildIncidentsByStatusChart(),
                IncidentStatus::cases(),
                static fn (IncidentStatus $status): string => $status->label(),
            ),
            'priorityBreakdown' => $this->buildBreakdown(
                $this->buildIncidentsByPriorityChart(),
                Priority::cases(),
                static fn (Priority $priority): string => $priority->label(),
            ),
            'sparklines' => $sparklines,
        ];
    }

    /** @return array{projects: list<int>, tasks: list<int>, activity: list<int>} */
    private function buildProjectSparklines(): array
    {
        $projects = [];
        $tasks = [];
        $activity = [];

        for ($i = 6; $i >= 0; --$i) {
            $day = new \DateTimeImmutable("-{$i} days");
            $next = $day->modify('+1 day');

            $projectCount = (int) $this->activityLogRepository->countByEntityTypeBetween('Project', $day, $next);
            $taskCount = (int) $this->activityLogRepository->countByEntityTypeBetween('Task', $day, $next);

            $projects[] = $projectCount;
            $tasks[] = $taskCount;
            $activity[] = max(1, $projectCount + $taskCount);
        }

        return ['projects' => $projects, 'tasks' => $tasks, 'activity' => $activity];
    }

    /** @return array{opened: list<int>, resolved: list<int>} */
    private function buildIncidentSparklines(): array
    {
        $opened = [];
        $resolved = [];

        for ($i = 6; $i >= 0; --$i) {
            $day = new \DateTimeImmutable("-{$i} days");
            $next = $day->modify('+1 day');

            $opened[] = $this->incidentRepository->countOpenedBetween($day, $next);
            $resolved[] = $this->incidentRepository->countResolvedBetween($day, $next);
        }

        return ['opened' => $opened, 'resolved' => $resolved];
    }

    /** @return array<string, int> */
    private function buildProjectsByStatusChart(): array
    {
        $data = [];

        foreach (ProjectStatus::cases() as $status) {
            $data[$status->value] = 0;
        }

        foreach ($this->projectRepository->findAll() as $project) {
            ++$data[$project->getStatus()->value];
        }

        return $data;
    }

    /** @return array<string, int> */
    private function buildProjectsByHealthChart(): array
    {
        $data = [];

        foreach (HealthStatus::cases() as $healthStatus) {
            $data[$healthStatus->value] = 0;
        }

        foreach ($this->projectRepository->findAll() as $project) {
            ++$data[$project->getHealthStatus()->value];
        }

        return $data;
    }

    /** @return array<string, int> */
    private function buildIncidentsByStatusChart(): array
    {
        $data = [];

        foreach (IncidentStatus::cases() as $status) {
            $data[$status->value] = 0;
        }

        foreach ($this->incidentRepository->findAll() as $incident) {
            ++$data[$incident->getStatus()->value];
        }

        return $data;
    }

    /** @return array<string, int> */
    private function buildIncidentsByPriorityChart(): array
    {
        $data = [];

        foreach (Priority::cases() as $priority) {
            $data[$priority->value] = 0;
        }

        foreach ($this->incidentRepository->findAll() as $incident) {
            ++$data[$incident->getPriority()->value];
        }

        return $data;
    }

    /**
     * @param array<string, int> $chartData
     * @param list<ProjectStatus|IncidentStatus|Priority> $cases
     *
     * @return list<array{key: string, label: string, count: int, percent: int}>
     */
    private function buildBreakdown(array $chartData, array $cases, callable $labelFn): array
    {
        $total = max(1, array_sum($chartData));
        $items = [];

        foreach ($cases as $case) {
            $count = $chartData[$case->value] ?? 0;
            if ($count <= 0) {
                continue;
            }

            $items[] = [
                'key' => $case->value,
                'label' => $labelFn($case),
                'count' => $count,
                'percent' => (int) round($count / $total * 100),
            ];
        }

        return $items;
    }
}
