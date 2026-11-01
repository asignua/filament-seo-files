<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * A list of URLs in a JSON column, written with unescaped slashes and unicode (the stock
 * `array` cast would store `https:\/\/…`). Reads both formats.
 *
 * @implements CastsAttributes<list<string>, mixed>
 */
class UrlList implements CastsAttributes
{
    /**
     * @param array<string, mixed> $attributes
     *
     * @return list<string>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        $decoded = is_string($value) && $value !== '' ? json_decode($value, true) : null;

        return is_array($decoded)
            ? array_values(array_filter(array_map(static fn (mixed $item): string => is_scalar($item) ? (string) $item : '', $decoded)))
            : [];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode(array_values(is_array($value) ? $value : []), LocaleMap::FLAGS) ?: '[]';
    }
}
