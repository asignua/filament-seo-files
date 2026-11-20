<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Actions;

use Asignua\FilamentSeoFiles\SeoFilesPlugin;
use Asignua\FilamentSeoFiles\Support\IndexNow;
use Filament\Actions\Action;

/**
 * Tells IndexNow engines (Bing, Yandex…) about every URL of the generated sitemap. Hidden
 * until IndexNow is switched on and has a valid key.
 */
class PingIndexNowAction extends Action
{
    use RunsArtisan;

    public static function getDefaultName(): ?string
    {
        return 'pingIndexNow';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize(static fn (): bool => SeoFilesPlugin::allows())
            ->visible(static fn (): bool => IndexNow::enabled() && IndexNow::validKey())
            ->label(__('filament-seo-files::seo-files.actions.indexnow'))
            ->icon('heroicon-o-paper-airplane')
            ->requiresConfirmation()
            ->modalDescription(__('filament-seo-files::seo-files.actions.indexnow_warning'))
            ->action(fn () => $this->runCommand('seo-files:indexnow', ['--all' => true, '--now' => true]));
    }
}
