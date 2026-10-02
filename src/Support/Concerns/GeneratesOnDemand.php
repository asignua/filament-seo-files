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
     * @throws LockTimeoutException when another request has been building it for too long
     */
    public function serve(string $locale): string
    {
        $path = $this->path($locale);

        if (File::exists($path)) {
            return (string) File::get($path);
        }

        return Cache::lock('filament-seo-files:'.md5($path), 120)->block(30, function () use ($locale, $path): string {
            // Another request may have written it while this one waited for the lock.
            if (File::exists($path)) {
                return (string) File::get($path);
            }

            $this->write($locale, $this->template($locale));

            return (string) File::get($path);
        });
    }
}
