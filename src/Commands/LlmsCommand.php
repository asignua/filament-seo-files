<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Commands;

use Asignua\FilamentSeoFiles\Commands\Concerns\DisplaysRelativePaths;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Support\LlmsFullTxtFile;
use Asignua\FilamentSeoFiles\Support\LlmsTxtFile;
use Illuminate\Console\Command;

/**
 * Rewrites llms.txt (https://llmstxt.org/) and llms-full.txt for every language: a short
 * curated index and the full site content for AI agents.
 *
 * Manual edits made in the panel's editor are lost: the files are rebuilt from the sources.
 */
class LlmsCommand extends Command
{
    use DisplaysRelativePaths;

    protected $signature = 'seo-files:llms {--locale=* : Limit generation to these languages}';

    protected $description = 'Generate llms.txt and llms-full.txt for every language (index + full site content for AI agents)';

    public function handle(LlmsTxtFile $file, LlmsFullTxtFile $full): int
    {
        /** @var list<string> $requested */
        $requested = (array) $this->option('locale');
        $locales = $requested !== [] ? $requested : SeoFiles::allLocales();

        foreach ($locales as $locale) {
            if (!in_array($locale, SeoFiles::allLocales(), true)) {
                $this->warn("Unknown language: {$locale} — skipped");

                continue;
            }

            // The short index.
            $content = $file->template($locale);
            $file->write($locale, $content);

            $links = substr_count($content, "\n- [");
            $this->info("llms.txt: {$locale} → {$this->relative($file->path($locale))} ({$links} links)");

            // The full content.
            $fullContent = $full->template($locale);
            $full->write($locale, $fullContent);

            $entries = substr_count($fullContent, "\n---\n");
            $this->info("llms-full.txt: {$locale} → {$this->relative($full->path($locale))} ({$entries} entries)");
        }

        return self::SUCCESS;
    }
}
