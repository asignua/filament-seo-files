<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Actions;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Runs an Artisan command from an action and reports the result as a notification.
 */
trait RunsArtisan
{
    /**
     * @param array<string, mixed> $parameters
     */
    protected function runCommand(string $command, array $parameters = []): void
    {
        // The command runs inside the Livewire request. On a large site (a sitemap index, an
        // llms-full.txt with a Markdown conversion per record) it can outlive PHP's
        // max_execution_time; lift it for this request. A proxy timeout still applies —
        // schedule the commands for sites that big.
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        try {
            Artisan::call($command, $parameters);
            $output = trim(Artisan::output());
        } catch (Throwable $exception) {
            Notification::make()
                ->title(__('filament-seo-files::seo-files.actions.failed'))
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('filament-seo-files::seo-files.actions.finished'))
            ->body($output !== '' ? $output : null)
            ->success()
            ->send();
    }
}
