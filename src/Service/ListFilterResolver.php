<?php

declare(strict_types=1);

namespace App\Service;

use BackedEnum;
use Symfony\Component\HttpFoundation\Request;

final class ListFilterResolver
{
    public function string(Request $request, string $key): ?string
    {
        $value = trim((string) $request->query->get($key, ''));

        return $value === '' ? null : $value;
    }

    public function bool(Request $request, string $key): bool
    {
        $value = $request->query->get($key);

        return $value === '1' || $value === 1 || $value === true || $value === 'true' || $value === 'on';
    }

    /**
     * @template T of BackedEnum
     * @param class-string<T> $enumClass
     * @return T|null
     */
    public function enum(Request $request, string $key, string $enumClass): ?BackedEnum
    {
        $value = $this->string($request, $key);
        if ($value === null) {
            return null;
        }

        return $enumClass::tryFrom($value);
    }

    public function intId(Request $request, string $key): ?int
    {
        $raw = $request->query->get($key);
        if ($raw === null || $raw === '' || $raw === false) {
            return null;
        }

        if (is_int($raw)) {
            return $raw > 0 ? $raw : null;
        }

        if (!is_numeric((string) $raw)) {
            return null;
        }

        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }
}