<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Commands;

use Asignua\FilamentSeoFiles\Support\IndexNow;
use Illuminate\Console\Command;

class IndexNowCommand extends Command
{
    protected $signature = 'seo-files:indexnow {urls?* : Absolute URLs to ping} {--now : Send immediately instead of queueing}';

    protected $description = 'Ping IndexNow about changed URLs (give the URLs, or run without arguments to check the setup)';

    public function handle(): int
    {
        if (!IndexNow::enabled()) {
            $this->error('IndexNow is off: set filament-seo-files.indexnow.enabled and a key.');

            return self::FAILURE;
        }

        if (!IndexNow::validKey()) {
            $this->error('filament-seo-files.indexnow.key must be 8–128 characters of a-z, A-Z, 0-9 and "-".');

            return self::FAILURE;
        }

        /** @var list<string> $urls */
        $urls = (array) $this->argument('urls');

        if ($urls === []) {
            $this->info('IndexNow is on. Key file: '.IndexNow::keyLocation());

            return self::SUCCESS;
        }

        $count = IndexNow::submit($urls, (bool) $this->option('now'));

        if ($count === 0) {
            $this->warn('No URL of '.IndexNow::host().' given.');

            return self::FAILURE;
        }

        $this->info("IndexNow: {$count} URL(s) ".($this->option('now') ? 'sent' : 'queued'));

        return self::SUCCESS;
    }
}
