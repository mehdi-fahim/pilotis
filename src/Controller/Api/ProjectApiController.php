<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Domain\Entity\Project;
use App\DTO\ProjectDto;
use App\Form\ProjectFormType;
use App\Repository\ProjectRepository;
use App\Service\ProjectService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/projects')]
final class ProjectApiController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly ProjectService $projectService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'api_project_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $projects = $this->projectRepository->findBy([], ['name' => 'ASC']);

        return $this->json(array_map($this->serializeProject(...), $projects));
    }

    #[Route('/{id}', name: 'api_project_show', methods: ['GET'])]
    public function show(Project $project): JsonResponse
    {
        return $this->json($this->serializeProject($project));
    }

    #[Route('', name: 'api_project_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = new ProjectDto();
        $form = $this->createForm(ProjectFormType::class, $dto);
        $form->submit(json_decode($request->getContent(), true) ?? []);

        if (!$form->isValid()) {
            return $this->json(['errors' => (string) $form->getErrors(true, false)], Response::HTTP_BAD_REQUEST);
        }

        $project = $this->projectService->create(
            (string) $dto->name,
            $dto->description,
            $dto->startDate,
            $dto->endDate,
            $dto->priority,
            $dto->status,
            $this->getUser(),
        );

        return $this->json($this->serializeProject($project), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_project_update', methods: ['PUT', 'PATCH'])]
    public function update(Project $project, Request $request): JsonResponse
    {
        $dto = $this->mapEntityToDto($project);
        $form = $this->createForm(ProjectFormType::class, $dto);
        $form->submit(json_decode($request->getContent(), true) ?? [], !$request->isMethod('PATCH'));

        if (!$form->isValid()) {
            return $this->json(['errors' => (string) $form->getErrors(true, false)], Response::HTTP_BAD_REQUEST);
        }

        $this->projectService->update($project, [
            'name' => $dto->name,
            'description' => $dto->description,
            'startDate' => $dto->startDate,
            'endDate' => $dto->endDate,
            'priority' => $dto->priority,
            'status' => $dto->status,
        ], $this->getUser());

        return $this->json($this->serializeProject($project));
    }

    #[Route('/{id}', name: 'api_project_delete', methods: ['DELETE'])]
    public function delete(Project $project): JsonResponse
    {
        $this->entityManager->remove($project);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /** @return array<string, mixed> */
    private function serializeProject(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'startDate' => $project->getStartDate()->format('Y-m-d'),
            'endDate' => $project->getEndDate()?->format('Y-m-d'),
            'forecastEndDate' => $project->getForecastEndDate()?->format('Y-m-d'),
            'status' => $project->getStatus()->value,
            'priority' => $project->getPriority()->value,
            'healthStatus' => $project->getHealthStatus()->value,
            'progressPercent' => $project->getProgressPercent(),
        ];
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
