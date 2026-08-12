<?php

declare(strict_types=1);

namespace App\DTO;

use App\Domain\Entity\Actor;
use App\Domain\Entity\Department;
use App\Domain\Enum\IncidentStatus;
use App\Domain\Enum\Priority;

final class IncidentDto
{
    public ?string $title = null;
    public ?string $description = null;
    public IncidentStatus $status = IncidentStatus::OPEN;
    public Priority $priority = Priority::MEDIUM;
    public ?Department $department = null;

    /** @var list<Actor> */
    public array $assignedActors = [];

    public ?\DateTimeImmutable $discoveredAt = null;
    public ?string $solution = null;
    public ?string $reproductionSteps = null;
    public ?string $impact = null;
    public ?string $environment = null;
    public ?string $rootCause = null;
    public ?\DateTimeImmutable $dueDate = null;
}
