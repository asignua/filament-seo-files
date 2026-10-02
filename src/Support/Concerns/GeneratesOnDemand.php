<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Support\Concerns;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * The public side of a per-language llms file: serve the stored file, and when there is
 * none yet, build it ONCE and store it (write-through, behind a cache lock).
 *
 * Building walks every source — for a `ModelSource` that is the whole table, with an
 * HTML-to-Markdown conversion per record for llms-full.txt. Doing that on every anonymous
 * request until somebody runs `seo-files:llms` would hand anyone a cheap way to load the
 * server, so the first request pays once and every later one reads a file.
 */
trait GeneratesOnDemand
{
    abstract public function path(string $locale): string;

    abstract public function template(string $locale): string;

    abstract public function write(string $locale, string $content): void;

    /**
     * The stored file; built and stored first when it does not exist.
     *
     * @throws LockTimeoutException at once when another request is building it
     */
    public function serve(string $locale): string
    {
        $path = $this->path($locale);

        if (File::exists($path)) {
            return (string) File::get($path);
        }

        // Never wait for the lock: while one request builds the file, every other one would
        // hold a PHP worker for as long as it waits — a burst could tie up the whole pool.
        // The caller answers 503 + Retry-After instead.
        $lock = Cache::lock('filament-seo-files:'.md5($path), 600);

        if (!$lock->get()) {
            throw new LockTimeoutException('The file is being built by another request.');
        }

        try {
            // Another request may have written it between the check above and the lock.
            clearstatcache(true, $path);

            if (is_file($path)) {
                return (string) File::get($path);
            }

            $this->write($locale, $this->template($locale));

            return (string) File::get($path);
        } finally {
            $lock->release();
        }
    }
}
