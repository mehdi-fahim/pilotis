<?php

declare(strict_types=1);

namespace App\Service;

final class EntityColorService
{
    /** @var list<array{bg: string, text: string, border: string, dot: string}> */
    private const PALETTE = [
        ['bg' => '#dbeafe', 'text' => '#1d4ed8', 'border' => '#93c5fd', 'dot' => '#3b82f6'],
        ['bg' => '#ede9fe', 'text' => '#6d28d9', 'border' => '#c4b5fd', 'dot' => '#8b5cf6'],
        ['bg' => '#d1fae5', 'text' => '#047857', 'border' => '#6ee7b7', 'dot' => '#10b981'],
        ['bg' => '#ffedd5', 'text' => '#c2410c', 'border' => '#fdba74', 'dot' => '#f97316'],
        ['bg' => '#fce7f3', 'text' => '#be185d', 'border' => '#f9a8d4', 'dot' => '#ec4899'],
        ['bg' => '#fef3c7', 'text' => '#b45309', 'border' => '#fcd34d', 'dot' => '#f59e0b'],
        ['bg' => '#e0e7ff', 'text' => '#4338ca', 'border' => '#a5b4fc', 'dot' => '#6366f1'],
        ['bg' => '#ccfbf1', 'text' => '#0f766e', 'border' => '#5eead4', 'dot' => '#14b8a6'],
    ];

    /** @return array{bg: string, text: string, border: string, dot: string, index: int} */
    public function forId(?int $id, string $fallback = 'default'): array
    {
        $index = $id !== null
            ? abs($id) % count(self::PALETTE)
            : abs(crc32($fallback)) % count(self::PALETTE);

        return [...self::PALETTE[$index], 'index' => $index];
    }

    public function initials(string $label): string
    {
        $parts = preg_split('/\s+/', trim($label)) ?: [];

        if ($parts === []) {
            return '?';
        }

        if (count($parts) === 1) {
            return strtoupper(substr($parts[0], 0, 2));
        }

        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }
}
