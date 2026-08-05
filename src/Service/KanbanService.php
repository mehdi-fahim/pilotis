<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Project;
use App\Domain\Entity\Task;
use App\Domain\Enum\TaskStatus;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;

final class KanbanService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function moveTask(Task $task, TaskStatus $newStatus, int $newOrder): void
    {
        $project = $task->getProject();
        $previousStatus = $task->getStatus();
        $previousOrder = $task->getKanbanOrder();

        if ($previousStatus === $newStatus) {
            $this->reorderWithinColumn($project, $newStatus, $task, $previousOrder, $newOrder);
        } else {
            $this->closeGapInColumn($project, $previousStatus, $previousOrder);
            $this->makeRoomInColumn($project, $newStatus, $newOrder);
            $task->setStatus($newStatus)->setKanbanOrder($newOrder);
        }

        $this->entityManager->flush();
    }

    private function reorderWithinColumn(
        Project $project,
        TaskStatus $status,
        Task $movedTask,
        int $fromOrder,
        int $toOrder,
    ): void {
        if ($fromOrder === $toOrder) {
            return;
        }

        $tasks = $this->taskRepository->findByProjectGroupedByStatus($project);

        foreach ($tasks as $task) {
            if ($task->getStatus() !== $status || $task->getId() === $movedTask->getId()) {
                continue;
            }

            $order = $task->getKanbanOrder();

            if ($fromOrder < $toOrder && $order > $fromOrder && $order <= $toOrder) {
                $task->setKanbanOrder($order - 1);
            } elseif ($fromOrder > $toOrder && $order >= $toOrder && $order < $fromOrder) {
                $task->setKanbanOrder($order + 1);
            }
        }

        $movedTask->setKanbanOrder($toOrder);
    }

    private function closeGapInColumn(Project $project, TaskStatus $status, int $removedOrder): void
    {
        $tasks = $this->taskRepository->findByProjectGroupedByStatus($project);

        foreach ($tasks as $task) {
            if ($task->getStatus() === $status && $task->getKanbanOrder() > $removedOrder) {
                $task->setKanbanOrder($task->getKanbanOrder() - 1);
            }
        }
    }

    private function makeRoomInColumn(Project $project, TaskStatus $status, int $insertOrder): void
    {
        $tasks = $this->taskRepository->findByProjectGroupedByStatus($project);

        foreach ($tasks as $task) {
            if ($task->getStatus() === $status && $task->getKanbanOrder() >= $insertOrder) {
                $task->setKanbanOrder($task->getKanbanOrder() + 1);
            }
        }
    }
}
