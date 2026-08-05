<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case REVIEW = 'review';
    case DONE = 'done';

    public function label(): string
    {
        return match ($this) {
            self::TODO => 'À faire',
            self::IN_PROGRESS => 'En cours',
            self::REVIEW => 'Revue',
            self::DONE => 'Terminé',
        };
    }

    public function kanbanColumn(): string
    {
        return $this->value;
    }
}
