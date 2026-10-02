<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles;

use Asignua\FilamentSeoFiles\Commands\LlmsCommand;
use Asignua\FilamentSeoFiles\Commands\SitemapCommand;
use Asignua\FilamentSeoFiles\Repositories\SitemapUrlRepository;
use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\Sitemap\SitemapServiceProvider;

class SeoFilesServiceProvider extends PackageServiceProvider
{
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
        // The sitemap views are registered by spatie's provider; it is auto-discovered, but
        // a host that disabled package discovery would otherwise fail on the first render.
        $this->app->register(SitemapServiceProvider::class);

        $this->app->singleton(SitemapUrlRepository::class);
    }

    public function packageBooted(): void
    {
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
