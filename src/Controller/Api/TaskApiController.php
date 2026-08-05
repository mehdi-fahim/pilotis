<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Domain\Entity\Task;
use App\DTO\TaskDto;
use App\Form\TaskFormType;
use App\Repository\TaskRepository;
use App\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tasks')]
final class TaskApiController extends AbstractController
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TaskService $taskService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'api_task_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $projectId = $request->query->getInt('projectId');

        if ($projectId > 0) {
            $project = $this->entityManager->getRepository(\App\Domain\Entity\Project::class)->find($projectId);
            $tasks = $project ? $this->taskRepository->findByProjectGroupedByStatus($project) : [];
        } else {
            $tasks = $this->taskRepository->findBy([], ['dueDate' => 'ASC']);
        }

        return $this->json(array_map($this->serializeTask(...), $tasks));
    }

    #[Route('/{id}', name: 'api_task_show', methods: ['GET'])]
    public function show(Task $task): JsonResponse
    {
        return $this->json($this->serializeTask($task));
    }

    #[Route('', name: 'api_task_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = new TaskDto();
        $form = $this->createForm(TaskFormType::class, $dto);
        $form->submit(json_decode($request->getContent(), true) ?? []);

        if (!$form->isValid()) {
            return $this->json(['errors' => (string) $form->getErrors(true, false)], Response::HTTP_BAD_REQUEST);
        }

        $task = $this->taskService->create(
            $dto->project ?? throw $this->createNotFoundException(),
            (string) $dto->title,
            $dto->description,
            $dto->assignedActor,
            $dto->department,
            $dto->status,
            $dto->priority,
            $dto->estimateMinutes,
            $dto->startDate,
            $dto->dueDate,
            $this->getUser(),
        );

        if ($dto->timeSpentMinutes > 0) {
            $this->taskService->updateTimeSpent($task, $dto->timeSpentMinutes, $this->getUser());
        }

        return $this->json($this->serializeTask($task), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_task_update', methods: ['PUT', 'PATCH'])]
    public function update(Task $task, Request $request): JsonResponse
    {
        $dto = $this->mapEntityToDto($task);
        $form = $this->createForm(TaskFormType::class, $dto);
        $form->submit(json_decode($request->getContent(), true) ?? [], !$request->isMethod('PATCH'));

        if (!$form->isValid()) {
            return $this->json(['errors' => (string) $form->getErrors(true, false)], Response::HTTP_BAD_REQUEST);
        }

        $this->taskService->update($task, [
            'title' => $dto->title,
            'description' => $dto->description,
            'assignedActor' => $dto->assignedActor,
            'department' => $dto->department,
            'status' => $dto->status,
            'priority' => $dto->priority,
            'estimateMinutes' => $dto->estimateMinutes,
            'startDate' => $dto->startDate,
            'dueDate' => $dto->dueDate,
        ], $this->getUser());

        $this->taskService->updateTimeSpent($task, $dto->timeSpentMinutes, $this->getUser());

        return $this->json($this->serializeTask($task));
    }

    #[Route('/{id}', name: 'api_task_delete', methods: ['DELETE'])]
    public function delete(Task $task): JsonResponse
    {
        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /** @return array<string, mixed> */
    private function serializeTask(Task $task): array
    {
        return [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'projectId' => $task->getProject()->getId(),
            'assignedActorId' => $task->getAssignedActor()?->getId(),
            'departmentId' => $task->getDepartment()?->getId(),
            'status' => $task->getStatus()->value,
            'priority' => $task->getPriority()->value,
            'estimateMinutes' => $task->getEstimateMinutes(),
            'timeSpentMinutes' => $task->getTimeSpentMinutes(),
            'startDate' => $task->getStartDate()?->format('Y-m-d'),
            'dueDate' => $task->getDueDate()?->format('Y-m-d'),
            'kanbanOrder' => $task->getKanbanOrder(),
        ];
    }

    private function mapEntityToDto(Task $task): TaskDto
    {
        $dto = new TaskDto();
        $dto->title = $task->getTitle();
        $dto->description = $task->getDescription();
        $dto->project = $task->getProject();
        $dto->assignedActor = $task->getAssignedActor();
        $dto->department = $task->getDepartment();
        $dto->status = $task->getStatus();
        $dto->priority = $task->getPriority();
        $dto->estimateMinutes = $task->getEstimateMinutes();
        $dto->timeSpentMinutes = $task->getTimeSpentMinutes();
        $dto->startDate = $task->getStartDate();
        $dto->dueDate = $task->getDueDate();

        return $dto;
    }
}
