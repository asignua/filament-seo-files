<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Actions;

use Filament\Actions\Action;

/**
 * Regenerates sitemap.xml. Use it on any Filament page:
 *
 *     public function generateSitemapAction(): Action
 *     {
 *         return GenerateSitemapAction::make();
 *     }
 */
class GenerateSitemapAction extends Action
{
    use RunsArtisan;

    public static function getDefaultName(): ?string
    {
        return 'generateSitemap';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('filament-seo-files::seo-files.actions.run'))
            ->icon('heroicon-o-map')
            ->requiresConfirmation()
            ->action(fn () => $this->runCommand('seo-files:sitemap'));
    }
}
