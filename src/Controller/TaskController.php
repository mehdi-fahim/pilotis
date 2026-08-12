<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Task;
use App\DTO\CommentDto;
use App\DTO\TaskDto;
use App\Form\CommentFormType;
use App\Form\TaskFormType;
use App\Repository\ActorRepository;
use App\Repository\DepartmentRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Service\ActivityLogger;
use App\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tasks')]
final class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly DepartmentRepository $departmentRepository,
        private readonly ActorRepository $actorRepository,
        private readonly TaskService $taskService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    #[Route('', name: 'app_task_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->addFlash('info', 'Les tâches se consultent depuis un projet.');

        return $this->redirectToRoute('app_project_index');
    }

    #[Route('/new', name: 'app_task_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $dto = new TaskDto();

        $projectId = $request->query->get('projectId');
        $projectId = is_numeric($projectId) ? (int) $projectId : 0;
        if ($projectId <= 0) {
            $this->addFlash('info', 'Créez une tâche depuis la fiche d\'un projet.');

            return $this->redirectToRoute('app_project_index');
        }

        $project = $this->projectRepository->find($projectId);
        if ($project === null) {
            throw $this->createNotFoundException();
        }
        $dto->project = $project;

        $departmentId = $request->query->get('department');
        if (is_numeric($departmentId) && (int) $departmentId > 0) {
            $department = $this->departmentRepository->find((int) $departmentId);
            if ($department !== null) {
                $dto->department = $department;
            }
        }

        $actorId = $request->query->get('actor');
        if (is_numeric($actorId) && (int) $actorId > 0) {
            $actor = $this->actorRepository->find((int) $actorId);
            if ($actor !== null) {
                $dto->assignedActor = $actor;
            }
        }

        $form = $this->createForm(TaskFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task = $this->taskService->create(
                $dto->project ?? throw $this->createNotFoundException('Projet requis.'),
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

            $this->addFlash('success', 'Tâche créée.');

            return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
        }

        return $this->render('task/new.html.twig', [
            'form' => $form,
            'project' => $project,
        ]);
    }

    #[Route('/{id}', name: 'app_task_show', methods: ['GET'])]
    public function show(Task $task): Response
    {
        $commentForm = $this->createForm(CommentFormType::class, new CommentDto(), [
            'action' => $this->generateUrl('app_comment_add', ['id' => $task->getId()]),
        ]);

        return $this->render('task/show.html.twig', [
            'task' => $task,
            'commentForm' => $commentForm,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function edit(Task $task, Request $request): Response
    {
        $dto = $this->mapEntityToDto($task);
        $form = $this->createForm(TaskFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

            $this->addFlash('success', 'Tâche mise à jour.');

            return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form,
            'task' => $task,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_task_delete', methods: ['POST'])]
    public function delete(Task $task, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete-task-' . $task->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $projectId = $task->getProject()->getId();
        $this->entityManager->remove($task);
        $this->entityManager->flush();
        $this->activityLogger->log('task.deleted', $task, $this->getUser());

        $this->addFlash('success', 'Tâche supprimée.');

        return $this->redirectToRoute('app_project_show', ['id' => $projectId, 'tab' => 'tasks']);
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
