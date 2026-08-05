<?php

declare(strict_types=1);

namespace App\DTO;

use App\Domain\Entity\Actor;
use App\Domain\Entity\Department;
use App\Domain\Entity\Project;
use App\Domain\Enum\Priority;
use App\Domain\Enum\TaskStatus;
use Symfony\Component\Validator\Constraints as Assert;

class TaskDto
{
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    public ?string $description = null;

    #[Assert\NotNull(message: 'Le projet est obligatoire.')]
    public ?Project $project = null;

    public ?Actor $assignedActor = null;

    public ?Department $department = null;

    public TaskStatus $status = TaskStatus::TODO;

    public Priority $priority = Priority::MEDIUM;

    #[Assert\PositiveOrZero(message: 'L\'estimation doit être positive ou nulle.')]
    public int $estimateMinutes = 0;

    #[Assert\PositiveOrZero(message: 'Le temps passé doit être positif ou nul.')]
    public int $timeSpentMinutes = 0;

    public ?\DateTimeImmutable $startDate = null;

    public ?\DateTimeImmutable $dueDate = null;
}
