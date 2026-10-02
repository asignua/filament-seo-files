<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Actions;

use Asignua\FilamentSeoFiles\Schedule;
use Asignua\FilamentSeoFiles\SeoFilesPlugin;
use Filament\Actions\Action;

/**
 * Regenerates llms.txt and llms-full.txt for every language.
 */
class GenerateLlmsAction extends Action
{
    use RunsArtisan;

    public static function getDefaultName(): ?string
    {
        return 'generateLlms';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            // The plugin's policy travels with the action: a host page with weaker access (a
            // shared Tools page, a dashboard) must not open a back door to the web root.
            ->authorize(static fn (): bool => SeoFilesPlugin::allows())
            ->label(__('filament-seo-files::seo-files.actions.generate'))
            ->icon('heroicon-o-sparkles')
            ->requiresConfirmation()
            // The command replaces the editor's work in every language: say so in the
            // confirmation instead of Filament's generic "Are you sure?".
            ->modalDescription(static fn (): string => trim(
                __('filament-seo-files::seo-files.actions.generate_llms_warning')
                .(Schedule::llmsTime() !== null
                    ? ' '.__('filament-seo-files::seo-files.actions.scheduled_overwrite', ['time' => Schedule::llmsTime()])
                    : ''),
            ))
            ->action(fn () => $this->runCommand('seo-files:llms'));
    }
}
