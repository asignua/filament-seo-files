<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Support;

use Asignua\FilamentSeoFiles\SeoFiles;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\File;

/**
 * Where `sitemap.xml` and its parts live, and when it was last generated.
 */
class SitemapFile
{
    public const string DEFAULT_CHUNK_NAME = 'sitemap-{n}.xml';

    public function path(): string
    {
        return (string) (config('filament-seo-files.sitemap.path') ?? public_path('sitemap.xml'));
    }

    /**
     * The public address of a file written under `public/` — the sitemap itself or one of
     * its parts. Relative to the web root, not just the file name: with `sitemap.path` in a
     * subdirectory (`public/sitemaps/sitemap.xml`) the index, robots.txt and llms.txt must
     * point at `/sitemaps/…`. A path outside `public/` falls back to the file name at the root.
     */
    public function urlFor(string $path): string
    {
        $public = rtrim(str_replace('\\', '/', public_path()), '/').'/';
        $path = str_replace('\\', '/', $path);

        $relative = str_starts_with($path, $public)
            ? substr($path, strlen($public))
            : basename($path);

        return SeoFiles::baseUrl().'/'.ltrim($relative, '/');
    }

    public function url(): string
    {
        return $this->urlFor($this->path());
    }

    public function exists(): bool
    {
        return File::exists($this->path());
    }

    public function generatedAt(): ?Carbon
    {
        return $this->exists()
            ? Date::createFromTimestamp((int) File::lastModified($this->path()), (string) config('app.timezone'))
            : null;
    }

    /**
     * The name template of a part file; it must contain `{n}`.
     */
    public function chunkName(): string
    {
        $name = (string) config('filament-seo-files.sitemap.chunk_name', self::DEFAULT_CHUNK_NAME);

        return str_contains($name, '{n}') && basename($name) === $name ? $name : self::DEFAULT_CHUNK_NAME;
    }

    public function chunkPath(int $number): string
    {
        return dirname($this->path()).'/'.str_replace('{n}', (string) $number, $this->chunkName());
    }

    /**
     * Part files on disk that match the name template, and ONLY those: nothing else in the
     * directory is ever touched.
     *
     * @return list<string>
     */
    public function chunkFiles(): array
    {
        $pattern = '~^'.str_replace(preg_quote('{n}', '~'), '[0-9]+', preg_quote($this->chunkName(), '~')).'$~';
        $found = [];

        foreach (File::glob(dirname($this->path()).'/*') ?: [] as $file) {
            if (is_file($file) && preg_match($pattern, basename($file)) === 1) {
                $found[] = $file;
            }
        }

        sort($found);

        return $found;
    }

    /**
     * The number of `<url>` entries in the sitemap (across all parts when it is an index),
     * counted without parsing the files; null when nothing has been generated yet.
     *
     * Counting streams every file (up to ~50 MB each), and the "SEO files" page asks on
     * every Livewire round trip, so the result is cached against the sitemap's mtime and
     * size: a generation run rewrites `sitemap.xml` last (after its parts), which changes
     * the stamp and invalidates the count.
     */
    public function urlCount(): ?int
    {
        if (!$this->exists()) {
            return null;
        }

        $path = $this->path();
        clearstatcache(true, $path);
        $stamp = @filemtime($path).':'.@filesize($path);
        $key = 'filament-seo-files:url-count:'.md5($path);

        $cached = Cache::get($key);

        if (is_array($cached) && ($cached['stamp'] ?? null) === $stamp && is_int($cached['count'] ?? null)) {
            return $cached['count'];
        }

        $count = $this->isIndex()
            ? array_sum(array_map($this->countIn(...), $this->chunkFiles()))
            : $this->countIn($path);

        Cache::put($key, ['stamp' => $stamp, 'count' => $count], now()->addDay());

        return $count;
    }

    /**
     * The root element sits in the first few hundred bytes: read the head of the file, not
     * the whole of it (a single sitemap can be 50 MB).
     */
    public function isIndex(): bool
    {
        if (!$this->exists()) {
            return false;
        }

        $handle = fopen($this->path(), 'rb');

        if ($handle === false) {
            return false;
        }

        $head = (string) fread($handle, 4096);
        fclose($handle);

        return str_contains($head, '<sitemapindex');
    }

    /**
     * Occurrences of a marker in a file, read in 1 MB blocks (a sitemap can be 50 MB).
     */
    private function countIn(string $path, string $marker = '<url>'): int
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return 0;
        }

        $count = 0;
        $tail = '';

        while (!feof($handle)) {
            $block = $tail.(string) fread($handle, 1048576);
            $count += substr_count($block, $marker);
            // Keep the end of the block: a marker may be cut by the block boundary. The kept
            // part is shorter than the marker, so it can never be counted twice.
            $tail = substr($block, -(strlen($marker) - 1));
        }

        fclose($handle);

        return $count;
    }
}
