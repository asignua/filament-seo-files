<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles;

use Asignua\FilamentSeoFiles\Commands\LlmsCommand;
use Asignua\FilamentSeoFiles\Commands\SitemapCommand;
use Asignua\FilamentSeoFiles\Repositories\SitemapUrlRepository;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\Sitemap\SitemapServiceProvider;

class SeoFilesServiceProvider extends PackageServiceProvider
{
    public const string PACKAGE = 'asignua/filament-seo-files';

    public const string STYLESHEET = 'filament-seo-files';

    public static string $name = 'filament-seo-files';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigration('create_seo_sitemap_urls_table')
            ->hasCommands([SitemapCommand::class, LlmsCommand::class]);
    }

    public function packageRegistered(): void
    {
        // A new application starts with no sources (see SeoFiles::resetSources()).
        SeoFiles::resetSources();

        // The sitemap views are registered by spatie's provider; it is auto-discovered, but
        // a host that disabled package discovery would otherwise fail on the first render.
        $this->app->register(SitemapServiceProvider::class);

        $this->app->singleton(SitemapUrlRepository::class);
    }

    public function packageBooted(): void
    {
        // Published by `filament:assets`, but linked by the plugin itself after the panel's
        // theme (see the plugin's register()): a custom theme must not beat our `dark:` variants.
        FilamentAsset::register([
            Css::make(self::STYLESHEET, __DIR__.'/../resources/dist/filament-seo-files.css')->loadedOnRequest(),
        ], self::PACKAGE);

        // The scheduler is resolved lazily; the callback also runs when it is resolved
        // late, so the registration never depends on provider order.
        $this->callAfterResolving(LaravelSchedule::class, Schedule::register(...));

        // Routes wait for the whole application to boot: the languages are configured on the
        // `SeoFiles` registry in AppServiceProvider, which boots AFTER this provider.
        if (config('filament-seo-files.routes.register', true)) {
            $this->app->booted(function (): void {
                if (!$this->app->routesAreCached()) {
                    SeoFiles::routes();
                }
            });
        }
    }
}
