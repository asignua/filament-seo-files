<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Repositories;

use Asignua\FilamentSeoFiles\Models\SitemapUrl;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Support\SitemapLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Writes the manual sitemap URLs — the ONLY door for writes (the model has
 * `$guarded = ['*']`, so a field not assigned here would silently not be saved).
 */
class SitemapUrlRepository
{
    /**
     * @return class-string<SitemapUrl>
     */
    public function modelClass(): string
    {
        /** @var class-string<SitemapUrl> */
        return (string) config('filament-seo-files.models.sitemap_url', SitemapUrl::class);
    }

    /**
     * @return Builder<SitemapUrl>
     */
    public function query(): Builder
    {
        return $this->modelClass()::query();
    }

    /**
     * @param array<string, mixed> $data `url` and `title` as `locale => value` maps, `active`
     */
    public function create(array $data): SitemapUrl
    {
        $class = $this->modelClass();

        return $this->fill(new $class, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(SitemapUrl $sitemapUrl, array $data): SitemapUrl
    {
        return $this->fill($sitemapUrl, $data);
    }

    /**
     * Does another record already hold this (normalised) address in this language?
     */
    public function existsInLocale(string $locale, string $url, mixed $ignoreId = null): bool
    {
        return $this->query()
            ->where('url->'.$locale, $url)
            ->when($ignoreId !== null, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * Walks the active manual URLs in chunks by id.
     *
     * @param callable(Collection<int, SitemapUrl>): void $callback
     */
    public function chunkActive(callable $callback, int $count = 200): void
    {
        $this->query()
            ->where('active', true)
            ->orderBy('id')
            ->chunkById($count, $callback);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function fill(SitemapUrl $sitemapUrl, array $data): SitemapUrl
    {
        if (array_key_exists('url', $data)) {
            $sitemapUrl->url = $this->map((array) $data['url'], normalize: true);
        }

        if (array_key_exists('title', $data)) {
            $sitemapUrl->title = $this->map((array) $data['title']);
        }

        if (array_key_exists('active', $data)) {
            $sitemapUrl->active = (bool) $data['active'];
        }

        $sitemapUrl->save();

        return $sitemapUrl;
    }

    /**
     * A value for EVERY language of the site. An emptied language stays an empty string
     * rather than being dropped, so the stored map says explicitly "no such language"
     * (the generator treats an empty string that way) and an old address cannot linger in
     * a language the editor cleared.
     *
     * @param array<string, mixed> $values
     *
     * @return array<string, string>
     */
    private function map(array $values, bool $normalize = false): array
    {
        $map = [];

        foreach (SeoFiles::allLocales() as $locale) {
            $value = trim((string) ($values[$locale] ?? ''));
            $map[$locale] = $normalize ? SitemapLocation::normalize($value) : $value;
        }

        return $map;
    }
}
