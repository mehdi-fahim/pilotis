<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Entity\Project;
use App\DTO\ProjectDto;
use App\Form\ProjectFormType;
use App\Repository\ProjectRepository;
use App\Service\ActivityLogger;
use App\Service\AiSummaryService;
use App\Service\ProjectService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/projects')]
final class ProjectController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly ProjectService $projectService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
        private readonly AiSummaryService $aiSummaryService,
    ) {
    }

    #[Route('', name: 'app_project_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('project/index.html.twig', [
            'projects' => $this->projectRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $dto = new ProjectDto();
        $dto->startDate = new \DateTimeImmutable();
        $form = $this->createForm(ProjectFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project = $this->projectService->create(
                (string) $dto->name,
                $dto->description,
                $dto->startDate,
                $dto->endDate,
                $dto->priority,
                $dto->status,
                $this->getUser(),
            );

            $this->addFlash('success', 'Projet créé.');

            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        return $this->render('project/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_show', methods: ['GET'])]
    public function show(Project $project, Request $request): Response
    {
        return $this->render('project/show.html.twig', [
            'project' => $project,
            'activeTab' => $request->query->get('tab', 'overview'),
            'aiSummary' => $this->aiSummaryService->generateWeeklySummary($project),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_project_edit', methods: ['GET', 'POST'])]
    public function edit(Project $project, Request $request): Response
    {
        $dto = $this->mapEntityToDto($project);
        $form = $this->createForm(ProjectFormType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->projectService->update($project, [
                'name' => $dto->name,
                'description' => $dto->description,
                'startDate' => $dto->startDate,
                'endDate' => $dto->endDate,
                'priority' => $dto->priority,
                'status' => $dto->status,
            ], $this->getUser());

            $this->addFlash('success', 'Projet mis à jour.');

            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        return $this->render('project/edit.html.twig', [
            'form' => $form,
            'project' => $project,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_project_delete', methods: ['POST'])]
    public function delete(Project $project, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete-project-' . $project->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($project);
        $this->entityManager->flush();
        $this->activityLogger->log('project.deleted', $project, $this->getUser());

        $this->addFlash('success', 'Projet supprimé.');

        return $this->redirectToRoute('app_project_index');
    }

    private function mapEntityToDto(Project $project): ProjectDto
    {
        $dto = new ProjectDto();
        $dto->name = $project->getName();
        $dto->description = $project->getDescription();
        $dto->startDate = $project->getStartDate();
        $dto->endDate = $project->getEndDate();
        $dto->forecastEndDate = $project->getForecastEndDate();
        $dto->status = $project->getStatus();
        $dto->priority = $project->getPriority();
        $dto->healthStatus = $project->getHealthStatus();

        return $dto;
    }
}
