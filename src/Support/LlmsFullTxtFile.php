<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Support;

use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Support\Concerns\GeneratesOnDemand;
use Illuminate\Support\Facades\File;

/**
 * Reading and writing the per-locale `llms-full.txt` plus the full document: the site
 * preamble and the complete Markdown content of every page from the registered
 * {@see \Asignua\FilamentSeoFiles\Contracts\LlmsFullSource}s.
 *
 * A mirror of {@see LlmsTxtFile}; for where the files live see {@see LlmsPaths}.
 */
class LlmsFullTxtFile
{
    use GeneratesOnDemand;

    public function path(string $locale): string
    {
        return LlmsPaths::for('full', $locale);
    }

    public function read(string $locale): string
    {
        return File::exists($this->path($locale))
            ? (string) File::get($this->path($locale))
            : $this->template($locale);
    }

    public function write(string $locale, string $content): void
    {
        $path = $this->path($locale);

        AtomicFile::put($path, rtrim($content)."\n");
    }

    public function template(string $locale): string
    {
        $entries = [];

        foreach (SeoFiles::llmsFullSources() as $source) {
            foreach ($source->llmsDocuments($locale) as $document) {
                $entries[] = LlmsFull::entryFor($document);
            }
        }

        return LlmsFull::document(SeoFiles::siteName($locale), SeoFiles::description($locale), $entries);
    }
}
