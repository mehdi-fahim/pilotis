<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class DepartmentDto
{
    #[Assert\NotBlank(message: 'Le nom du service est obligatoire.')]
    #[Assert\Length(max: 100)]
    public ?string $name = null;

    #[Assert\Length(max: 20)]
    public ?string $code = null;

    public ?string $description = null;
}
