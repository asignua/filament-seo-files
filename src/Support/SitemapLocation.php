<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Support;

use Asignua\FilamentSeoFiles\SeoFiles;
use Illuminate\Support\Str;

/**
 * Building absolute addresses for the MANUAL sitemap entries.
 *
 * A manual URL is stored in a HYBRID format: a value with an `http(s)://` scheme is taken
 * as is (an external landing page, another subdomain); anything else is a PATH FROM THE
 * ROOT WITHOUT the language prefix — the host and the prefix are added by the generator
 * ({@see SeoFiles::localizedUrl()}), exactly like the path of a regular page.
 *
 * Everything here is pure (the only dependency is the {@see SeoFiles} registry).
 */
final class SitemapLocation
{
    /**
     * Is the value a full address with a scheme (and not a path)? The scheme's case does
     * not matter; a protocol-relative `//example.com` does NOT count — the sitemap
     * library's `url()` would turn it into an address on the current host.
     */
    public static function isAbsolute(string $value): bool
    {
        return Str::startsWith(Str::lower(trim($value)), ['http://', 'https://']);
    }

    /**
     * The canonical form of a stored value: a full address keeps its path but gets a
     * lower-case scheme; a path loses its surrounding slashes (the root path stays `/`). Deduplication compares
     * strings, so one form must be stored, not `search`, `/search` and `/search/` side
     * by side.
     */
    public static function normalize(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (self::isAbsolute($value)) {
            $position = (int) strpos($value, '://');

            return Str::lower(substr($value, 0, $position)).substr($value, $position);
        }

        // The root path is kept as `/`: an empty string means "no address in this language".
        $path = trim($value, '/');

        return $path === '' ? '/' : $path;
    }

    /**
     * The absolute address of one manual value; `null` when the language is empty.
     */
    public static function forCustom(string $locale, ?string $value): ?string
    {
        $value = self::normalize((string) $value);

        if ($value === '') {
            return null;
        }

        return self::isAbsolute($value) ? $value : SeoFiles::localizedUrl($locale, $value);
    }

    /**
     * The language cluster of one manual record: `locale => absolute address`, empty
     * languages dropped, THE DEFAULT LANGUAGE FIRST — its address becomes `<loc>`, so the
     * order is significant, and `array_key_first()` of the result is `<loc>`, falling
     * back to the first filled language when the default one is empty.
     *
     * @param array<string, string|null> $map the `url` values per language
     *
     * @return array<string, string>
     */
    public static function cluster(array $map): array
    {
        $cluster = [];

        foreach (SeoFiles::allLocales() as $locale) {
            $href = self::forCustom($locale, $map[$locale] ?? null);

            if ($href !== null) {
                $cluster[$locale] = $href;
            }
        }

        return $cluster;
    }

    /**
     * The hreflang value of a site language: BCP 47 uses a hyphen, so Laravel's `pt_BR`
     * becomes `pt-BR` (Google ignores an invalid annotation, silently breaking the cluster).
     */
    public static function hreflang(string $locale): string
    {
        return str_replace('_', '-', $locale);
    }

    /**
     * Is the first path segment a code of one of the site's languages? Such a path is
     * almost always an editor's mistake (`en/catalog` would give `/en/en/catalog`), so the
     * form rejects it. ALL languages are checked, not just the field's one: `en/…` on the
     * `uk` tab is just as redundant.
     */
    public static function startsWithLanguagePrefix(string $path): bool
    {
        $path = self::normalize($path);

        if ($path === '' || self::isAbsolute($path)) {
            return false;
        }

        return in_array(explode('/', $path)[0], SeoFiles::allLocales(), true);
    }
}
