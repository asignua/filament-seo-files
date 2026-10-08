<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Commands;

use Asignua\FilamentSeoFiles\Commands\Concerns\DisplaysRelativePaths;
use Asignua\FilamentSeoFiles\Support\GenerationInProgress;
use Asignua\FilamentSeoFiles\Support\SitemapGenerator;
use Illuminate\Console\Command;

class SitemapCommand extends Command
{
    use DisplaysRelativePaths;

    protected $signature = 'seo-files:sitemap';

    protected $description = 'Generate sitemap.xml from the registered sources and the manual URLs (hreflang + lastmod)';

    public function handle(SitemapGenerator $generator): int
    {
        try {
            $result = $generator->generate();
        } catch (GenerationInProgress $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        // One <url> per language version: on a multilingual site there are more URLs than
        // pages, and the count must match the file (and the "SEO files" page).
        $this->info("Sitemap: {$result['urls']} URLs ({$result['sources']} pages from sources + {$result['custom']} manual) → {$this->relative($result['path'])}");

        if ($result['chunks'] > 0) {
            $this->info("sitemap.xml is an index of {$result['chunks']} part(s)");
        }

        if ($result['skipped'] > 0) {
            $this->warn("Manual URLs skipped as duplicates: {$result['skipped']}");
        }

        return self::SUCCESS;
    }
}
