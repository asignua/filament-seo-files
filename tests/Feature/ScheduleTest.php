<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Schedule;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;

class ScheduleTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function commands(LaravelSchedule $schedule): array
    {
        return array_map(
            fn (Event $event): string => $event->expression.' '.trim(str_replace(["'", '"', PHP_BINARY, 'artisan'], '', (string) $event->command)),
            $schedule->events(),
        );
    }

    public function test_it_is_off_by_default(): void
    {
        Schedule::register($schedule = new LaravelSchedule);

        $this->assertSame([], $schedule->events());
    }

    public function test_it_registers_both_commands_daily_without_overlapping(): void
    {
        config(['filament-seo-files.schedule.enabled' => true]);

        Schedule::register($schedule = new LaravelSchedule);

        $this->assertCount(2, $schedule->events());
        $this->assertStringContainsString('0 4 * * * ', $this->commands($schedule)[0]);
        $this->assertStringContainsString('seo-files:sitemap', $this->commands($schedule)[0]);
        $this->assertStringContainsString('20 4 * * * ', $this->commands($schedule)[1]);
        $this->assertStringContainsString('seo-files:llms', $this->commands($schedule)[1]);

        foreach ($schedule->events() as $event) {
            $this->assertTrue($event->withoutOverlapping);
        }
    }

    public function test_the_times_are_configurable(): void
    {
        config([
            'filament-seo-files.schedule.enabled' => true,
            'filament-seo-files.schedule.times' => ['sitemap' => '01:15', 'llms' => '23:45'],
        ]);

        Schedule::register($schedule = new LaravelSchedule);

        $this->assertStringContainsString('15 1 * * * ', $this->commands($schedule)[0]);
        $this->assertStringContainsString('45 23 * * * ', $this->commands($schedule)[1]);
    }
}
