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
