<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles;

use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;

/**
 * Registers the scheduled generation of the public files. Off by default
 * (`filament-seo-files.schedule.enabled`).
 *
 * A separate class, not the body of a service-provider callback, so that a test can pass a
 * fresh `Schedule` with the config it needs.
 *
 * `withoutOverlapping()` is not about load but about integrity: both commands write into
 * public/, and two parallel writes into one file give a truncated sitemap.xml nobody notices.
 */
final class Schedule
{
    public static function register(LaravelSchedule $schedule): void
    {
        if (!(bool) config('filament-seo-files.schedule.enabled', false)) {
            return;
        }

        $schedule->command('seo-files:sitemap')
            ->dailyAt((string) config('filament-seo-files.schedule.times.sitemap', '04:00'))
            ->withoutOverlapping();

        $schedule->command('seo-files:llms')
            ->dailyAt((string) config('filament-seo-files.schedule.times.llms', '04:20'))
            ->withoutOverlapping();
    }
}
