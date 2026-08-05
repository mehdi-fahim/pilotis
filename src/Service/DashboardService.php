<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Project;
use App\Domain\Enum\HealthStatus;
use App\Domain\Enum\ProjectStatus;
use App\Domain\Enum\TaskStatus;
use App\Repository\ActivityLogRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;

final class DashboardService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly TaskRepository $taskRepository,
        private readonly ActivityLogRepository $activityLogRepository,
    ) {
    }

    public function getDashboardData(): array
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
        $completedTasks = $totalTasks - $this->taskRepository->countOpen();

        return [
            'projectCount' => $this->projectRepository->countAll(),
            'overdueProjects' => $this->projectRepository->findOverdue(),
            'openTasks' => $this->taskRepository->countOpen(),
            'completedTasks' => $completedTasks,
            'averageProgress' => $averageProgress,
            'timeSpent' => $this->taskRepository->getTotalTimeSpentMinutes(),
            'chartData' => [
                'projectsByStatus' => $this->buildProjectsByStatusChart(),
                'projectsByHealth' => $this->buildProjectsByHealthChart(),
            ],
            'recentActivities' => $this->activityLogRepository->findRecent(8),
            'overdueTasks' => $this->taskRepository->findOverdue(),
            'sparklines' => $this->buildSparklines(),
        ];
    }

    /** @return array{projects: list<int>, tasks: list<int>, activity: list<int>} */
    private function buildSparklines(): array
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
}
