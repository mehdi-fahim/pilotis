<?php

declare(strict_types=1);

namespace App\DTO;

use App\Domain\Entity\Department;
use Symfony\Component\Validator\Constraints as Assert;

class ActorDto
{
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(max: 100)]
    public ?string $firstName = null;

    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 100)]
    public ?string $lastName = null;

    #[Assert\Email]
    public ?string $email = null;

    #[Assert\Length(max: 30)]
    public ?string $phone = null;

    #[Assert\Length(max: 100)]
    public ?string $role = null;

    public ?Department $department = null;

    public ?string $notes = null;
}
