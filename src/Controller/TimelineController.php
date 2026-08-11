<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Project;
use App\Domain\Entity\Task;
use App\Repository\DepartmentRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/timeline')]
final class TimelineController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly TaskRepository $taskRepository,
        private readonly DepartmentRepository $departmentRepository,
    ) {
    }

    #[Route('', name: 'app_timeline_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->addFlash('info', 'Ouvrez le Gantt depuis la fiche d\'un projet.');

        return $this->redirectToRoute('app_project_index');
    }

    #[Route('/{projectId}', name: 'app_timeline_project', methods: ['GET'], requirements: ['projectId' => '\d+'])]
    public function project(int $projectId): Response
    {
        $project = $this->projectRepository->find($projectId);

        if ($project === null) {
            throw $this->createNotFoundException();
        }

        $tasks = $this->taskRepository->findByProjectGroupedByStatus($project);
        [$timelineStart, $timelineEnd] = $this->resolveTimelineRange($project, $tasks);
        $totalDays = max(1, $timelineStart->diff($timelineEnd)->days + 1);

        $bars = array_map(function (Task $task) use ($timelineStart, $totalDays): array {
            $start = $task->getStartDate() ?? $task->getDueDate() ?? $timelineStart;
            $end = $task->getDueDate() ?? $start->modify('+1 day');
            if ($end < $start) {
                $end = $start->modify('+1 day');
            }

            $offsetDays = max(0, $timelineStart->diff($start)->days);
            if ($start < $timelineStart) {
                $offsetDays = 0;
            }
            $durationDays = max(1, $start->diff($end)->days + 1);
            $widthPercent = max(4.0, min(100.0, ($durationDays / $totalDays) * 100));

            return [
                'task' => $task,
                'offsetPercent' => ($offsetDays / $totalDays) * 100,
                'widthPercent' => $widthPercent,
                'start' => $start,
                'end' => $end,
            ];
        }, $tasks);

        $grouped = [];
        foreach ($bars as $bar) {
            $key = $bar['task']->getDepartment()?->getName() ?? 'Sans service';
            $grouped[$key][] = $bar;
        }
        ksort($grouped);

        return $this->render('timeline/gantt.html.twig', [
            'project' => $project,
            'groupedBars' => $grouped,
            'timelineStart' => $timelineStart,
            'timelineEnd' => $timelineEnd,
            'monthMarkers' => $this->buildMonthMarkers($timelineStart, $timelineEnd, $totalDays),
            'todayPercent' => $this->resolveTodayPercent($timelineStart, $timelineEnd, $totalDays),
        ]);
    }

    /** @param list<Task> $tasks
     * @return array{\DateTimeImmutable, \DateTimeImmutable}
     */
    private function resolveTimelineRange(Project $project, array $tasks): array
    {
        $start = $project->getStartDate();
        $end = $project->getEndDate() ?? $project->getForecastEndDate() ?? new \DateTimeImmutable('+30 days');

        foreach ($tasks as $task) {
            $taskStart = $task->getStartDate() ?? $task->getDueDate();
            $taskEnd = $task->getDueDate() ?? $taskStart;

            if ($taskStart !== null && $taskStart < $start) {
                $start = $taskStart;
            }

            if ($taskEnd !== null && $taskEnd > $end) {
                $end = $taskEnd;
            }
        }

        if ($end <= $start) {
            $end = $start->modify('+7 days');
        }

        return [$start, $end];
    }

    /** @return list<array{label: string, percent: float}> */
    private function buildMonthMarkers(\DateTimeImmutable $start, \DateTimeImmutable $end, int $totalDays): array
    {
        $markers = [];
        $cursor = $start->modify('first day of this month')->modify('+1 month');
        $last = $end->modify('first day of next month');

        while ($cursor <= $last) {
            if ($cursor > $start && $cursor < $end) {
                $offsetDays = $start->diff($cursor)->days;
                $markers[] = [
                    'label' => $cursor->format('M Y'),
                    'percent' => ($offsetDays / $totalDays) * 100,
                ];
            }
            $cursor = $cursor->modify('+1 month');
        }

        return $markers;
    }

    private function resolveTodayPercent(\DateTimeImmutable $start, \DateTimeImmutable $end, int $totalDays): ?float
    {
        $today = new \DateTimeImmutable('today');

        if ($today < $start || $today > $end) {
            return null;
        }

        return ($start->diff($today)->days / $totalDays) * 100;
    }
}
