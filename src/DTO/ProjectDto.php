<?php

declare(strict_types=1);

namespace App\DTO;

use App\Domain\Enum\HealthStatus;
use App\Domain\Enum\Priority;
use App\Domain\Enum\ProjectStatus;
use Symfony\Component\Validator\Constraints as Assert;

class ProjectDto
{
    #[Assert\NotBlank(message: 'Le nom du projet est obligatoire.')]
    #[Assert\Length(max: 200)]
    public ?string $name = null;

    public ?string $description = null;

    #[Assert\NotNull(message: 'La date de début est obligatoire.')]
    public ?\DateTimeImmutable $startDate = null;

    public ?\DateTimeImmutable $endDate = null;

    public ?\DateTimeImmutable $forecastEndDate = null;

    public ProjectStatus $status = ProjectStatus::DRAFT;

    public Priority $priority = Priority::MEDIUM;

    public HealthStatus $healthStatus = HealthStatus::GREEN;
}
