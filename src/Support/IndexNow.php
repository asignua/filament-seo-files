<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Support;

use Asignua\FilamentSeoFiles\Jobs\PingIndexNow;
use Asignua\FilamentSeoFiles\SeoFiles;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * IndexNow (Bing, Yandex, Seznam, Naver…): tells search engines which URLs changed instead
 * of waiting for them to re-read the sitemap. OFF by default (`indexnow.enabled`).
 *
 * The key file is served by the plugin's route (`/{key}.txt`, see SeoFiles::routes()). After
 * `seo-files:sitemap` the URLs whose `<lastmod>` changed (or that are new) since the last
 * ping go to a queued job; a URL whose `<lastmod>` is the generation time (a source that
 * gave none) is pinged once, when it first appears. {@see submit()} pings on demand.
 */
final class IndexNow
{
    /** The protocol caps one request at 10 000 URLs. */
    public const int MAX_PER_REQUEST = 10000;

    public static function enabled(): bool
    {
        return (bool) config('filament-seo-files.indexnow.enabled', false);
    }

    public static function key(): string
    {
        return (string) config('filament-seo-files.indexnow.key', '');
    }

    /**
     * 8–128 characters of `a-z A-Z 0-9 -` (the protocol's rule).
     */
    public static function validKey(): bool
    {
        return preg_match('/^[A-Za-z0-9-]{8,128}$/', self::key()) === 1;
    }

    public static function keyLocation(): string
    {
        $configured = config('filament-seo-files.indexnow.key_location');

        return is_string($configured) && $configured !== ''
            ? $configured
            : SeoFiles::baseUrl().'/'.self::key().'.txt';
    }

    public static function host(): string
    {
        return (string) parse_url(SeoFiles::baseUrl(), PHP_URL_HOST);
    }

    /**
     * Called by the sitemap generator with every `<loc>` it wrote and a change signature.
     * Never throws: a failed ping must not fail the sitemap.
     *
     * @param array<string, string> $current url => signature
     *
     * @return int URLs queued
     */
    public static function afterGeneration(array $current): int
    {
        if (!self::enabled() || !self::validKey() || !config('filament-seo-files.indexnow.on_generate', true)) {
            return 0;
        }

        // Foreign hosts (an external landing page among the manual URLs) can never be pinged:
        // leave them out of change detection, or they would look changed on every run.
        $host = self::host();
        $current = array_filter(
            $current,
            static fn (string $url): bool => parse_url($url, PHP_URL_HOST) === $host,
            ARRAY_FILTER_USE_KEY,
        );

        // Nothing emitted (an emptied source, a failed query): not a baseline. Recording it
        // would make the next normal run look like a first full change.
        if ($current === []) {
            return 0;
        }

        $state = self::readState();

        if ($state === null) {
            // First run: the baseline. Telling the engines about the whole site at once is
            // opt-in.
            self::writeState($current);

            if (!config('filament-seo-files.indexnow.ping_initial', false)) {
                return 0;
            }

            $state = [];
        }

        $changed = [];

        foreach ($current as $url => $signature) {
            if (($state[$url] ?? null) !== $signature) {
                $changed[$url] = $signature;
            }
        }

        // Unchanged URLs keep their signature; the changed ones keep the OLD one until the
        // job succeeds, so a failed or throttled ping is retried by the next run.
        $next = [];

        foreach ($current as $url => $signature) {
            if (!isset($changed[$url])) {
                $next[$url] = $signature;
            } elseif (isset($state[$url])) {
                $next[$url] = $state[$url];
            }
        }

        if ($changed === []) {
            self::writeState($next);

            return 0;
        }

        if (self::throttled()) {
            // Leave the state as it was: nothing is lost, the next run sees the same changes.
            return 0;
        }

        self::writeState($next);

        return self::dispatch($changed);
    }

    /**
     * Pings URLs on demand (not throttled). Only URLs of this site's host are sent.
     *
     * @param list<string> $urls
     *
     * @return int URLs queued
     */
    public static function submit(array $urls, bool $now = false): int
    {
        if (!self::enabled() || !self::validKey()) {
            return 0;
        }

        $signed = [];

        foreach ($urls as $url) {
            $signed[$url] = '';
        }

        return self::dispatch($signed, $now, record: false);
    }

    /**
     * @param array<string, string> $urls url => signature
     */
    private static function dispatch(array $urls, bool $now = false, bool $record = true): int
    {
        $host = self::host();
        $urls = array_filter(
            $urls,
            static fn (string $url): bool => parse_url($url, PHP_URL_HOST) === $host,
            ARRAY_FILTER_USE_KEY,
        );

        if ($urls === []) {
            return 0;
        }

        // Only the automatic path is throttled, so only it stamps; a manual submit must not
        // delay the next scheduled ping. The TTL follows the throttle window.
        if ($record) {
            Cache::put('filament-seo-files:indexnow:last', time(), now()->addSeconds(max(60, (int) config('filament-seo-files.indexnow.throttle_seconds', 3600)) + 60));
        }

        foreach (array_chunk($urls, self::MAX_PER_REQUEST, true) as $chunk) {
            $job = new PingIndexNow(array_keys($chunk), $record ? $chunk : []);

            $now ? dispatch_sync($job) : dispatch($job);
        }

        return count($urls);
    }

    private static function throttled(): bool
    {
        $seconds = (int) config('filament-seo-files.indexnow.throttle_seconds', 3600);
        $last = Cache::get('filament-seo-files:indexnow:last');

        return $seconds > 0 && is_int($last) && time() - $last < $seconds;
    }

    public static function statePath(): string
    {
        return storage_path('app/seo-files/indexnow.json');
    }

    /**
     * @return array<string, string>|null null = no baseline yet
     */
    public static function readState(): ?array
    {
        $path = self::statePath();

        if (!is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? array_map(strval(...), $decoded) : null;
    }

    /**
     * @param array<string, string> $state
     */
    public static function writeState(array $state): void
    {
        File::ensureDirectoryExists(dirname(self::statePath()));
        AtomicFile::put(self::statePath(), (string) json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Records the signatures a successful ping covered.
     *
     * @param array<string, string> $signatures
     */
    public static function remember(array $signatures): void
    {
        if ($signatures === []) {
            return;
        }

        self::writeState([...(self::readState() ?? []), ...$signatures]);
    }
}
