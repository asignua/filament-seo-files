<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Pages;

use Asignua\FilamentSeoFiles\Actions\EditLlmsAction;
use Asignua\FilamentSeoFiles\Actions\EditRobotsAction;
use Asignua\FilamentSeoFiles\Actions\GenerateLlmsAction;
use Asignua\FilamentSeoFiles\Actions\GenerateSitemapAction;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\SeoFilesPlugin;
use Asignua\FilamentSeoFiles\Support\RobotsFile;
use Asignua\FilamentSeoFiles\Support\SitemapFile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * "SEO files": regenerate sitemap.xml and the llms files, edit robots.txt and llms.txt.
 * The four actions are public factories (see `Actions\`), so any other Filament page can
 * embed them instead of this one.
 */
class SeoFilesPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlassCircle;

    protected string $view = 'filament-seo-files::pages.seo-files';

    public static function getSlug(?Panel $panel = null): string
    {
        return 'seo-files';
    }

    public static function canAccess(): bool
    {
        return SeoFilesPlugin::allows();
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-seo-files::seo-files.page.navigation');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return self::plugin()?->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return self::plugin()?->getNavigationSort() ?? 4;
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return self::plugin()?->getNavigationIcon() ?? static::$navigationIcon;
    }

    public function getTitle(): string
    {
        return __('filament-seo-files::seo-files.page.title');
    }

    public function generateSitemapAction(): Action
    {
        return GenerateSitemapAction::make();
    }

    public function editRobotsAction(): Action
    {
        return EditRobotsAction::make();
    }

    public function generateLlmsAction(): Action
    {
        return GenerateLlmsAction::make();
    }

    public function editLlmsAction(): Action
    {
        return EditLlmsAction::make();
    }

    /**
     * The sections of the page: title, help, a status line and the actions.
     *
     * @return list<array{title: string, help: string, status: string, actions: list<string>}>
     */
    public function getSections(): array
    {
        $sitemap = new SitemapFile;
        $generatedAt = $sitemap->generatedAt();
        $urls = $sitemap->urlCount();
        $parts = $sitemap->isIndex() ? count($sitemap->chunkFiles()) : 0;

        return [
            [
                'title' => 'sitemap.xml',
                'help' => __('filament-seo-files::seo-files.page.sitemap_help'),
                'status' => $generatedAt !== null
                    ? __('filament-seo-files::seo-files.page.generated_at', ['time' => $generatedAt->isoFormat('LLL')])
                        .' · '.__('filament-seo-files::seo-files.page.url_count', ['count' => $urls ?? 0])
                        .($parts > 0 ? ' · '.__('filament-seo-files::seo-files.page.part_count', ['count' => $parts]) : '')
                    : __('filament-seo-files::seo-files.page.not_generated'),
                'actions' => ['generateSitemapAction'],
            ],
            [
                'title' => 'robots.txt',
                'help' => __('filament-seo-files::seo-files.page.robots_help'),
                'status' => app(RobotsFile::class)->path() !== '' && file_exists(app(RobotsFile::class)->path())
                    ? __('filament-seo-files::seo-files.page.file_exists')
                    : __('filament-seo-files::seo-files.page.template_served'),
                'actions' => ['editRobotsAction'],
            ],
            [
                'title' => 'llms.txt / llms-full.txt',
                'help' => __('filament-seo-files::seo-files.page.llms_help', ['locales' => implode(', ', SeoFiles::allLocales())]),
                'status' => '',
                'actions' => ['generateLlmsAction', 'editLlmsAction'],
            ],
        ];
    }

    private static function plugin(): ?SeoFilesPlugin
    {
        return filament()->hasPlugin(SeoFilesPlugin::ID) ? SeoFilesPlugin::get() : null;
    }
}
