<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Actions;

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
            ->label(__('filament-seo-files::seo-files.actions.run'))
            ->icon('heroicon-o-sparkles')
            ->requiresConfirmation()
            ->action(fn () => $this->runCommand('seo-files:llms'));
    }
}
