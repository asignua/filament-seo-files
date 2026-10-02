<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Pages;

use Asignua\FilamentSeoFiles\Actions\EditLlmsAction;
use Asignua\FilamentSeoFiles\Actions\EditRobotsAction;
use Asignua\FilamentSeoFiles\Actions\GenerateLlmsAction;
use Asignua\FilamentSeoFiles\Actions\GenerateSitemapAction;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\SeoFilesPlugin;
use Asignua\FilamentSeoFiles\Support\LlmsTxtFile;
use Asignua\FilamentSeoFiles\Support\RobotsFile;
use Asignua\FilamentSeoFiles\Support\SitemapFile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Date;
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
        // Counted only when there is a file: urlCount() reads it whole.
        $urls = $generatedAt !== null ? $sitemap->urlCount() : null;
        $parts = $generatedAt !== null && $sitemap->isIndex() ? count($sitemap->chunkFiles()) : 0;

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
                    : __('filament-seo-files::seo-files.page.robots_missing'),
                'actions' => ['editRobotsAction'],
            ],
            [
                'title' => 'llms.txt / llms-full.txt',
                'help' => __('filament-seo-files::seo-files.page.llms_help', ['locales' => implode(', ', SeoFiles::allLocales())]),
                'status' => $this->llmsStatus(),
                'actions' => ['generateLlmsAction', 'editLlmsAction'],
            ],
        ];
    }

    /**
     * When the llms.txt files were last written and for which languages they exist.
     */
    private function llmsStatus(): string
    {
        $files = app(LlmsTxtFile::class);
        $times = [];

        foreach (SeoFiles::allLocales() as $locale) {
            $path = $files->path($locale);

            if (is_file($path)) {
                $times[$locale] = (int) filemtime($path);
            }
        }

        if ($times === []) {
            return __('filament-seo-files::seo-files.page.not_generated');
        }

        return __('filament-seo-files::seo-files.page.generated_at', ['time' => Date::createFromTimestamp(max($times))->isoFormat('LLL')])
            .' · '.__('filament-seo-files::seo-files.page.llms_languages', ['locales' => implode(', ', array_keys($times))]);
    }

    private static function plugin(): ?SeoFilesPlugin
    {
        return filament()->hasPlugin(SeoFilesPlugin::ID) ? SeoFilesPlugin::get() : null;
    }
}
