<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\EntityColorService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FormExtension extends AbstractExtension
{
    public function __construct(
        private readonly EntityColorService $entityColorService,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('entity_color', $this->entityColorService->forId(...)),
            new TwigFunction('entity_initials', $this->entityColorService->initials(...)),
            new TwigFunction('form_choice_data', $this->getChoiceData(...)),
            new TwigFunction('form_choice_label', $this->getChoiceLabel(...)),
        ];
    }

    /**
     * @param iterable<object{value: mixed, data: mixed, label: string}> $choices
     */
    public function getChoiceData(iterable $choices, mixed $value): mixed
    {
        foreach ($choices as $choice) {
            if ($choice->value == $value) {
                return $choice->data;
            }
        }

        return null;
    }

    /**
     * @param iterable<object{value: mixed, label: string}> $choices
     */
    public function getChoiceLabel(iterable $choices, mixed $value, string $fallback = ''): string
    {
        foreach ($choices as $choice) {
            if ($choice->value == $value) {
                return $choice->label;
            }
        }

        return $fallback;
    }
}
