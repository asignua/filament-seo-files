<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Models;

use Asignua\FilamentSeoFiles\Casts\LocaleMap;
use Asignua\FilamentSeoFiles\SeoFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A manual URL of `sitemap.xml` — an address the generator would never learn about from
 * the registered sources: a search page, RSS feeds, an external landing page.
 *
 * ONE record = one multilingual `<url>`: `url` is a map `{locale: value}` that
 * {@see \Asignua\FilamentSeoFiles\Support\SitemapLocation::cluster()} turns into `<loc>`
 * plus hreflang alternates. The value is hybrid: `https://…` is used as is, anything else
 * is a path from the root WITHOUT the language prefix.
 *
 * `title` is a label for the admin ONLY; it never reaches the XML.
 *
 * No mass assignment: all writes go through
 * {@see \Asignua\FilamentSeoFiles\Repositories\SitemapUrlRepository}.
 *
 * @property int                   $id
 * @property array<string, string> $url
 * @property array<string, string> $title
 * @property bool                  $active
 * @property Carbon|null           $created_at
 * @property Carbon|null           $updated_at
 */
class SitemapUrl extends Model
{
    /** @var list<string> */
    protected $guarded = ['*'];

    public function getTable(): string
    {
        return (string) config('filament-seo-files.tables.sitemap_urls', 'seo_sitemap_urls');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'url' => LocaleMap::class,
            'title' => LocaleMap::class,
            'active' => 'boolean',
        ];
    }

    /**
     * A label for lists and breadcrumbs: the title in the current language, then the
     * default one, then any other — a borrowed value is marked with its language
     * (`[en] Search`). Falls back to the first filled address.
     */
    public function label(): string
    {
        return self::pick($this->title)
            ?? self::pick($this->url)
            ?? '#'.$this->getKey();
    }

    /**
     * @param array<string, string> $map
     */
    public static function pick(array $map): ?string
    {
        $current = app()->getLocale();

        if (($map[$current] ?? '') !== '') {
            return $map[$current];
        }

        foreach ([SeoFiles::defaultLocale(), ...SeoFiles::allLocales(), ...array_keys($map)] as $locale) {
            if (($map[$locale] ?? '') !== '') {
                return '['.$locale.'] '.$map[$locale];
            }
        }

        return null;
    }
}
