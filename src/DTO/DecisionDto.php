<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class DecisionDto
{
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 200)]
    public ?string $title = null;

    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    public ?string $description = null;

    #[Assert\NotNull(message: 'La date de réunion est obligatoire.')]
    public ?\DateTimeImmutable $meetingDate = null;
}
