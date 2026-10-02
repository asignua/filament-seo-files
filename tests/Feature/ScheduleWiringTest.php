<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;

/**
 * The service provider hooks the registration onto the scheduler by itself.
 */
class ScheduleWiringTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('filament-seo-files.schedule.enabled', true);
    }

    public function test_the_scheduler_gets_the_entries_without_any_setup(): void
    {
        $commands = collect(app(Schedule::class)->events())
            ->map(fn ($event): string => (string) $event->command)
            ->implode("\n");

        $this->assertStringContainsString('seo-files:sitemap', $commands);
        $this->assertStringContainsString('seo-files:llms', $commands);
    }
}
