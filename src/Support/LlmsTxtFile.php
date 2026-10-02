<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Support;

use Asignua\FilamentSeoFiles\Data\LlmsLink;
use Asignua\FilamentSeoFiles\Data\LlmsSection;
use Asignua\FilamentSeoFiles\SeoFiles;
use Illuminate\Support\Facades\File;

/**
 * Reading and writing the per-locale `llms.txt` (https://llmstxt.org/) and assembling its
 * template from the registered {@see \Asignua\FilamentSeoFiles\Contracts\LlmsIndexSource}s.
 *
 * A mirror of {@see RobotsFile} with two differences: the template is GENERATED from the
 * sources rather than an inline heredoc, and there are several files — one per language.
 * For where the files live see {@see LlmsPaths}.
 *
 * The file deliberately stays a SHORT curated index: single records (hundreds of grants,
 * say) are not listed — that is what `sitemap.xml` is for, and the `## Optional` section
 * points at it.
 */
class LlmsTxtFile
{
    public function path(string $locale): string
    {
        return LlmsPaths::for('index', $locale);
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

        File::ensureDirectoryExists(dirname($path));
        File::put($path, rtrim($content)."\n");
    }

    public function template(string $locale): string
    {
        $sections = [];

        foreach (SeoFiles::llmsIndexSources() as $source) {
            foreach ($source->llmsSections($locale) as $section) {
                $sections[] = $section;
            }
        }

        $sections[] = $this->optionalSection($locale);

        return LlmsTxt::render(SeoFiles::siteName($locale), SeoFiles::description($locale), $sections);
    }

    private function optionalSection(string $locale): LlmsSection
    {
        $file = new SitemapFile;

        // "Optional" has a special meaning in the spec: an agent may skip these links when
        // it needs a shorter context.
        return new LlmsSection('Optional', [
            new LlmsLink(
                basename($file->path()),
                $file->url(),
                trans('filament-seo-files::seo-files.llms.sitemap_description', [], $locale),
            ),
        ]);
    }
}
