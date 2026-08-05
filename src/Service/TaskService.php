<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Actor;
use App\Domain\Entity\Department;
use App\Domain\Entity\Project;
use App\Domain\Entity\Task;
use App\Domain\Entity\User;
use App\Domain\Enum\Priority;
use App\Domain\Enum\TaskStatus;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;

final class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectService $projectService,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function create(
        Project $project,
        string $title,
        ?string $description = null,
        ?Actor $assignedActor = null,
        ?Department $department = null,
        TaskStatus $status = TaskStatus::TODO,
        Priority $priority = Priority::MEDIUM,
        int $estimateMinutes = 0,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $dueDate = null,
        ?User $actor = null,
    ): Task {
        $kanbanOrder = $this->taskRepository->getMaxKanbanOrder($project, $status) + 1;

        $task = (new Task())
            ->setProject($project)
            ->setTitle($title)
            ->setDescription($description)
            ->setAssignedActor($assignedActor)
            ->setDepartment($department)
            ->setStatus($status)
            ->setPriority($priority)
            ->setEstimateMinutes($estimateMinutes)
            ->setStartDate($startDate)
            ->setDueDate($dueDate)
            ->setKanbanOrder($kanbanOrder);

        $project->addTask($task);

        $this->entityManager->persist($task);
        $this->projectService->recalculateMetrics($project);
        $this->entityManager->flush();

        $this->activityLogger->log('task.created', $task, $actor);

        return $task;
    }

    /**
     * @param array{
     *     title?: string,
     *     description?: string|null,
     *     assignedActor?: Actor|null,
     *     department?: Department|null,
     *     status?: TaskStatus,
     *     priority?: Priority,
     *     estimateMinutes?: int,
     *     startDate?: \DateTimeImmutable|null,
     *     dueDate?: \DateTimeImmutable|null,
     *     kanbanOrder?: int
     * } $data
     */
    public function update(Task $task, array $data, ?User $actor = null): Task
    {
        if (isset($data['title'])) {
            $task->setTitle($data['title']);
        }

        if (array_key_exists('description', $data)) {
            $task->setDescription($data['description']);
        }

        if (array_key_exists('assignedActor', $data)) {
            $task->setAssignedActor($data['assignedActor']);
        }

        if (array_key_exists('department', $data)) {
            $task->setDepartment($data['department']);
        }

        if (isset($data['status'])) {
            $task->setStatus($data['status']);
        }

        if (isset($data['priority'])) {
            $task->setPriority($data['priority']);
        }

        if (isset($data['estimateMinutes'])) {
            $task->setEstimateMinutes($data['estimateMinutes']);
        }

        if (array_key_exists('startDate', $data)) {
            $task->setStartDate($data['startDate']);
        }

        if (array_key_exists('dueDate', $data)) {
            $task->setDueDate($data['dueDate']);
        }

        if (isset($data['kanbanOrder'])) {
            $task->setKanbanOrder($data['kanbanOrder']);
        }

        $this->projectService->recalculateMetrics($task->getProject());
        $this->entityManager->flush();

        $this->activityLogger->log('task.updated', $task, $actor, $data);

        return $task;
    }

    public function updateTimeSpent(Task $task, int $minutes, ?User $actor = null): Task
    {
        if ($minutes < 0) {
            throw new \InvalidArgumentException('Time spent cannot be negative.');
        }

        $task->setTimeSpentMinutes($minutes);

        $this->projectService->recalculateMetrics($task->getProject());
        $this->entityManager->flush();

        $this->activityLogger->log('task.time_updated', $task, $actor, [
            'timeSpentMinutes' => $minutes,
        ]);

        return $task;
    }
}
