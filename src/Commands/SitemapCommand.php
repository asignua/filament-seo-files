<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Commands;

use Asignua\FilamentSeoFiles\Commands\Concerns\DisplaysRelativePaths;
use Asignua\FilamentSeoFiles\Support\SitemapGenerator;
use Illuminate\Console\Command;

class SitemapCommand extends Command
{
    use DisplaysRelativePaths;

    protected $signature = 'seo-files:sitemap';

    protected $description = 'Generate sitemap.xml from the registered sources and the manual URLs (hreflang + lastmod)';

    public function handle(SitemapGenerator $generator): int
    {
        $result = $generator->generate();

        $total = $result['sources'] + $result['custom'];
        $this->info("Sitemap: {$total} URLs ({$result['sources']} from sources + {$result['custom']} manual) → {$this->relative($result['path'])}");

        if ($result['chunks'] > 0) {
            $this->info("sitemap.xml is an index of {$result['chunks']} part(s)");
        }

        if ($result['skipped'] > 0) {
            $this->warn("Manual URLs skipped as duplicates: {$result['skipped']}");
        }

        return self::SUCCESS;
    }
}
