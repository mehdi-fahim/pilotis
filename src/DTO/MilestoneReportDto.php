<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class MilestoneReportDto
{
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 200)]
    public ?string $title = null;

    #[Assert\NotBlank(message: 'Le contenu est obligatoire.')]
    public ?string $content = null;

    #[Assert\NotNull(message: 'La date du jalon est obligatoire.')]
    public ?\DateTimeImmutable $milestoneDate = null;
}
