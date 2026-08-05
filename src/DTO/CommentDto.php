<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CommentDto
{
    #[Assert\NotBlank(message: 'Le commentaire est obligatoire.')]
    public ?string $content = null;
}
