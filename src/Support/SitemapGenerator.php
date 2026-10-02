<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Support;

use Asignua\FilamentSeoFiles\Models\SitemapUrl;
use Asignua\FilamentSeoFiles\Repositories\SitemapUrlRepository;
use Asignua\FilamentSeoFiles\SeoFiles;
use DateTimeInterface;
use Illuminate\Support\Facades\File;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Sitemap as SitemapTag;
use Spatie\Sitemap\Tags\Url;

/**
 * Generates `sitemap.xml` (hreflang + lastmod) from TWO places:
 *  1) the registered {@see \Asignua\FilamentSeoFiles\Contracts\SitemapSource}s — the site's
 *     own pages;
 *  2) the manual addresses ({@see SitemapUrl}) — what no source knows about: a search
 *     page, RSS feeds, an external landing page.
 *
 * The sources go FIRST: `Sitemap::render()` deduplicates tags with `unique('url')`, i.e.
 * the first tag with a given <loc> wins, so a manual record can never override a real page.
 *
 * LARGE SITES. The protocol allows 50 000 URLs and 50 MB (uncompressed) per file. The
 * generation is streaming: tags collect in a buffer of at most `sitemap.max_urls`, and when
 * it overflows (by count, or by the estimated size of the XML) the buffer is written out as
 * a part (`sitemap-1.xml`, …) and a new one starts. If anything was split off, `sitemap.xml`
 * itself becomes a `<sitemapindex>` listing the parts; otherwise it is the single file as
 * ever. A hreflang cluster is atomic — it is never cut between two parts — and the
 * deduplication map is shared by all parts, so an address appears once in the whole set.
 */
class SitemapGenerator
{
    /**
     * Every address already emitted — both <loc> and alternates, across ALL parts.
     *
     * `unique('url')` of the sitemap library looks ONLY at <loc> and does not see
     * alternates, so without this map a manual cluster whose alternate equals the <loc> of a
     * real page would yield two clusters for one address with different sets of alternates
     * — a non-reciprocal hreflang that Google discards wholesale.
     *
     * @var array<string, true>
     */
    private array $seen = [];

    private Sitemap $buffer;

    private int $bufferUrls = 0;

    private int $bufferBytes = 0;

    private ?DateTimeInterface $bufferLastModified = null;

    /** @var list<array{path: string, lastModified: DateTimeInterface}> */
    private array $parts = [];

    private int $totalUrls = 0;

    private string $mode = 'auto';

    private SitemapFile $files;

    /**
     * @return array{sources: int, custom: int, skipped: int, path: string, chunks: int}
     */
    public function generate(): array
    {
        $this->files = new SitemapFile;
        $this->seen = [];
        $this->parts = [];
        $this->totalUrls = 0;
        $this->mode = $this->splitMode();
        $this->resetBuffer();

        $fromSources = $this->addSourceUrls();
        [$custom, $skipped] = $this->addCustomUrls();

        $path = $this->files->path();
        File::ensureDirectoryExists(dirname($path));

        if ($this->parts === [] && $this->mode !== 'always') {
            // Everything fits: one plain sitemap.xml, and no parts of an earlier index run.
            $this->writeAtomically($path, $this->buffer->render());
            $this->deleteStaleParts([]);

            return ['sources' => $fromSources, 'custom' => $custom, 'skipped' => $skipped, 'path' => $path, 'chunks' => 0];
        }

        // The last, partly filled buffer is a part as well (an empty one only when nothing
        // was emitted at all, so that the index never lists nothing).
        if ($this->bufferUrls > 0 || $this->parts === []) {
            $this->flushPart();
        }

        $index = SitemapIndex::create();
        $written = [];

        foreach ($this->parts as $part) {
            $index->add(
                SitemapTag::create(SeoFiles::baseUrl().'/'.basename($part['path']))
                    ->setLastModificationDate($part['lastModified']),
            );
            $written[] = $part['path'];
        }

        // The index goes last: a crawler that fetches sitemap.xml in the middle of a run
        // still sees a complete previous set, never an index that points at missing parts.
        $this->writeAtomically($path, $index->render());
        $this->deleteStaleParts($written);

        return ['sources' => $fromSources, 'custom' => $custom, 'skipped' => $skipped, 'path' => $path, 'chunks' => count($this->parts)];
    }

    /**
     * Pass 1: the registered sources.
     */
    private function addSourceUrls(): int
    {
        $count = 0;

        foreach (SeoFiles::sitemapSources() as $source) {
            foreach ($source->sitemapEntries() as $entry) {
                if (isset($this->seen[$entry->url])) {
                    continue;
                }

                $lastModified = $entry->lastModified ?? now();

                $url = Url::create($entry->url)
                    ->setLastModificationDate($lastModified);
                // NO setPriority / setChangeFrequency (Google ignores them).
                $this->seen[$entry->url] = true;

                // hreflang alternates: every language version the source lists.
                foreach ($entry->alternates as $locale => $href) {
                    $url->addAlternate($href, (string) $locale);
                    $this->seen[$href] = true;
                }

                // x-default → the default-language URL.
                $url->addAlternate($entry->url, 'x-default');

                $this->push($url, $entry->url, $entry->alternates, $lastModified);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Pass 2: the active manual addresses. One record = one <url> with alternates for
     * every filled language.
     *
     * @return array{0: int, 1: int} added, skipped as duplicates
     */
    private function addCustomUrls(): array
    {
        $count = 0;
        $skipped = 0;

        app(SitemapUrlRepository::class)->chunkActive(function ($rows) use (&$count, &$skipped): void {
            foreach ($rows as $row) {
                /** @var array<string, string> $cluster locale => absolute address, the default language first */
                $cluster = SitemapLocation::cluster($row->url);

                // A record empty in EVERY language yields no address. The form does not
                // let it through, this guards against a record created around the form.
                if ($cluster === []) {
                    continue;
                }

                // <loc> is the default language, or the first filled one when it is empty:
                // an English-only landing page or an /en/-only feed is a legitimate record,
                // and silently dropping it would be exactly the invisible failure nothing
                // here could catch.
                $loc = $cluster[array_key_first($cluster)];

                if (isset($this->seen[$loc])) {
                    $skipped++;

                    continue;
                }

                $lastModified = $row->updated_at ?? now();

                $url = Url::create($loc)
                    ->setLastModificationDate($lastModified);
                // NO setPriority / setChangeFrequency — the same rule as in pass 1.

                foreach ($cluster as $locale => $href) {
                    // A language version already emitted by another cluster cannot be an
                    // alternate here — see the note on $seen.
                    if ($href !== $loc && isset($this->seen[$href])) {
                        continue;
                    }

                    $url->addAlternate($href, $locale);
                }

                $url->addAlternate($loc, 'x-default');

                foreach ($cluster as $href) {
                    $this->seen[$href] = true;
                }

                $this->push($url, $loc, $cluster, $lastModified);
                $count++;
            }
        });

        return [$count, $skipped];
    }

    /**
     * Adds a ready tag to the buffer, first closing the buffer as a part when the tag would
     * overflow it. The whole cluster goes into one part.
     *
     * @param array<string, string> $alternates
     */
    private function push(Url $url, string $loc, array $alternates, DateTimeInterface $lastModified): void
    {
        $bytes = $this->estimateBytes($loc, $alternates);

        if ($this->mode !== 'never' && $this->bufferUrls > 0 && (
            $this->bufferUrls >= $this->maxUrls()
            || $this->bufferBytes + $bytes > $this->maxBytes()
        )) {
            $this->flushPart();
        }

        $this->buffer->add($url);
        $this->bufferUrls++;
        $this->bufferBytes += $bytes;
        $this->totalUrls++;

        if ($this->bufferLastModified === null || $lastModified > $this->bufferLastModified) {
            $this->bufferLastModified = $lastModified;
        }
    }

    /**
     * Writes the buffer out as the next part and starts a new buffer.
     */
    private function flushPart(): void
    {
        $path = $this->files->chunkPath(count($this->parts) + 1);

        File::ensureDirectoryExists(dirname($path));
        $this->writeAtomically($path, $this->buffer->render());

        $this->parts[] = ['path' => $path, 'lastModified' => $this->bufferLastModified ?? now()];
        $this->resetBuffer();
    }

    private function resetBuffer(): void
    {
        $this->buffer = Sitemap::create();
        $this->bufferUrls = 0;
        $this->bufferBytes = 0;
        $this->bufferLastModified = null;
    }

    /**
     * A rough size of the tag's XML: rendering every tag just to measure it would double the
     * work. The constants cover the markup around the values; the estimate errs on the large
     * side and the threshold (`sitemap.max_bytes`) sits below the protocol's 50 MB.
     *
     * @param array<string, string> $alternates
     */
    private function estimateBytes(string $loc, array $alternates): int
    {
        $bytes = strlen($loc) + 90;

        foreach ($alternates as $locale => $href) {
            $bytes += strlen($href) + strlen((string) $locale) + 70;
        }

        // The x-default alternate.
        return $bytes + strlen($loc) + 80;
    }

    /**
     * Part files of an earlier run that this run did not write. Only files that match the
     * part-name template, and only in the directory of the sitemap, are ever removed.
     *
     * @param list<string> $keep
     */
    private function deleteStaleParts(array $keep): void
    {
        foreach ($this->files->chunkFiles() as $file) {
            if (!in_array($file, $keep, true)) {
                File::delete($file);
            }
        }
    }

    /**
     * Temp file + rename: a crawler never sees a half-written file.
     */
    private function writeAtomically(string $path, string $contents): void
    {
        $temporary = $path.'.'.uniqid('tmp', true);

        File::put($temporary, $contents);
        rename($temporary, $path);
    }

    private function splitMode(): string
    {
        $mode = (string) config('filament-seo-files.sitemap.split', 'auto');

        return in_array($mode, ['auto', 'always', 'never'], true) ? $mode : 'auto';
    }

    private function maxUrls(): int
    {
        return max(1, (int) config('filament-seo-files.sitemap.max_urls', 50000));
    }

    private function maxBytes(): int
    {
        return max(1, (int) config('filament-seo-files.sitemap.max_bytes', 45000000));
    }
}
