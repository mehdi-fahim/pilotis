<?php

declare(strict_types=1);

namespace App\DTO;

use App\Domain\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class TeamDto
{
    #[Assert\NotBlank(message: 'Le nom de l\'équipe est obligatoire.')]
    #[Assert\Length(max: 150)]
    public ?string $name = null;

    public ?string $description = null;

    #[Assert\NotNull(message: 'Le propriétaire est obligatoire.')]
    public ?User $owner = null;
}
