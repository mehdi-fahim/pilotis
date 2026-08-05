<?php

declare(strict_types=1);

namespace App\DTO;

use App\Domain\Enum\RiskStatus;
use Symfony\Component\Validator\Constraints as Assert;

class RiskDto
{
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 200)]
    public ?string $title = null;

    public ?string $description = null;

    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'La probabilité doit être comprise entre {{ min }} et {{ max }}.')]
    public int $probability = 3;

    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'L\'impact doit être compris entre {{ min }} et {{ max }}.')]
    public int $impact = 3;

    public ?string $mitigationPlan = null;

    public RiskStatus $status = RiskStatus::IDENTIFIED;
}
