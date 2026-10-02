<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * A per-language map (`{"en": "…", "uk": "…"}`) in a JSON column.
 *
 * The stock `array` cast calls `json_encode()` without flags and escapes Cyrillic to
 * `До…`; MariaDB keeps the literal string PHP sent (its `json` type is a
 * `longtext` with a `json_valid()` check), so the table, dumps and backup diffs become
 * unreadable. This cast writes UNESCAPED unicode and slashes and reads BOTH formats, so
 * rows written by another cast keep working and normalise themselves on the next save.
 *
 * @implements CastsAttributes<array<string, string>, mixed>
 */
class LocaleMap implements CastsAttributes
{
    public const int FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, string>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (!is_array($decoded)) {
            return [];
        }

        $map = [];

        foreach ($decoded as $locale => $text) {
            $map[(string) $locale] = is_scalar($text) ? (string) $text : '';
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode(is_array($value) ? $value : [], self::FLAGS) ?: '[]';
    }
}
