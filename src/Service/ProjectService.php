<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Entity\Project;
use App\Domain\Entity\User;
use App\Domain\Enum\Priority;
use App\Domain\Enum\ProjectStatus;
use Doctrine\ORM\EntityManagerInterface;

final class ProjectService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectHealthService $projectHealthService,
        private readonly ForecastService $forecastService,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function create(
        string $name,
        ?string $description = null,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null,
        Priority $priority = Priority::MEDIUM,
        ProjectStatus $status = ProjectStatus::DRAFT,
        ?User $actor = null,
    ): Project {
        $project = (new Project())
            ->setName($name)
            ->setDescription($description)
            ->setStartDate($startDate ?? new \DateTimeImmutable())
            ->setEndDate($endDate)
            ->setPriority($priority)
            ->setStatus($status);

        $this->recalculateMetrics($project);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        $this->activityLogger->log('project.created', $project, $actor);

        return $project;
    }

    /**
     * @param array{
     *     name?: string,
     *     description?: string|null,
     *     startDate?: \DateTimeImmutable,
     *     endDate?: \DateTimeImmutable|null,
     *     priority?: Priority,
     *     status?: ProjectStatus
     * } $data
     */
    public function update(Project $project, array $data, ?User $actor = null): Project
    {
        if (isset($data['name'])) {
            $project->setName($data['name']);
        }

        if (array_key_exists('description', $data)) {
            $project->setDescription($data['description']);
        }

        if (isset($data['startDate'])) {
            $project->setStartDate($data['startDate']);
        }

        if (array_key_exists('endDate', $data)) {
            $project->setEndDate($data['endDate']);
        }

        if (isset($data['priority'])) {
            $project->setPriority($data['priority']);
        }

        if (isset($data['status'])) {
            $project->setStatus($data['status']);
        }

        $this->recalculateMetrics($project);
        $this->entityManager->flush();

        $this->activityLogger->log('project.updated', $project, $actor, $data);

        return $project;
    }

    public function recalculateMetrics(Project $project): void
    {
        $project
            ->setHealthStatus($this->projectHealthService->calculate($project))
            ->setForecastEndDate($this->forecastService->calculateForecastEndDate($project));
    }
}
