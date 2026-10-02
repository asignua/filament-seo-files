<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Actions;

use Asignua\FilamentSeoFiles\SeoFilesPlugin;
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
            // The plugin's policy travels with the action: a host page with weaker access (a
            // shared Tools page, a dashboard) must not open a back door to the web root.
            ->authorize(static fn (): bool => SeoFilesPlugin::allows())
            ->label(__('filament-seo-files::seo-files.actions.generate'))
            ->icon('heroicon-o-map')
            ->requiresConfirmation()
            ->action(fn () => $this->runCommand('seo-files:sitemap'));
    }
}
