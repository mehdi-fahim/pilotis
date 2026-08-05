<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Project;
use App\Domain\Entity\Task;
use App\Domain\Enum\TaskStatus;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Service\KanbanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kanban')]
final class KanbanController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly TaskRepository $taskRepository,
        private readonly KanbanService $kanbanService,
    ) {
    }

    #[Route('', name: 'app_kanban_index', methods: ['GET'])]
    public function index(): Response
    {
        $projects = $this->projectRepository->findBy([], ['name' => 'ASC']);

        if (\count($projects) === 1) {
            return $this->redirectToRoute('app_kanban_board', [
                'projectId' => $projects[0]->getId(),
            ]);
        }

        return $this->render('kanban/index.html.twig', [
            'projects' => $projects,
        ]);
    }

    #[Route('/{projectId}', name: 'app_kanban_board', methods: ['GET'], requirements: ['projectId' => '\d+'])]
    public function board(int $projectId): Response
    {
        $project = $this->projectRepository->find($projectId);

        if ($project === null) {
            throw $this->createNotFoundException();
        }

        $tasks = $this->taskRepository->findByProjectGroupedByStatus($project);
        $columns = [];

        foreach (TaskStatus::cases() as $status) {
            $columns[$status->value] = [
                'status' => $status,
                'tasks' => array_values(array_filter(
                    $tasks,
                    static fn (Task $task): bool => $task->getStatus() === $status
                )),
            ];
        }

        return $this->render('kanban/board.html.twig', [
            'project' => $project,
            'columns' => $columns,
        ]);
    }

    #[Route('/{projectId}/move', name: 'app_kanban_move', methods: ['POST'], requirements: ['projectId' => '\d+'])]
    public function move(int $projectId, Request $request): JsonResponse
    {
        $project = $this->projectRepository->find($projectId);

        if ($project === null) {
            return $this->json(['error' => 'Project not found'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->json(['error' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        }

        $taskId = $payload['taskId'] ?? null;
        $statusValue = $payload['status'] ?? null;
        $order = $payload['order'] ?? null;

        if (!is_int($taskId) && !is_numeric($taskId)) {
            return $this->json(['error' => 'Invalid taskId'], Response::HTTP_BAD_REQUEST);
        }

        $task = $this->taskRepository->find((int) $taskId);

        if (!$task instanceof Task || $task->getProject()->getId() !== $project->getId()) {
            return $this->json(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $status = TaskStatus::from((string) $statusValue);
        } catch (\ValueError) {
            return $this->json(['error' => 'Invalid status'], Response::HTTP_BAD_REQUEST);
        }

        $this->kanbanService->moveTask($task, $status, (int) $order);

        return $this->json(['success' => true]);
    }
}
