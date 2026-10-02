<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles;

use Asignua\FilamentSeoFiles\Pages\SeoFilesPage;
use Asignua\FilamentSeoFiles\Resources\SitemapUrls\SitemapUrlResource;
use BackedEnum;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentAsset;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * The panel side of the package: the "SEO files" page and the "Sitemap URLs" resource.
 *
 *     ->plugin(SeoFilesPlugin::make()
 *         ->authorize(fn (): bool => auth()->user()?->isAdmin())
 *         ->navigationGroup('SEO'))
 *
 * Commands, the schedule and the routes do NOT depend on it: they live in the
 * {@see SeoFiles} registry and work without any panel.
 */
class SeoFilesPlugin implements Plugin
{
    public const string ID = 'filament-seo-files';

    /** The gate that is consulted when no `authorize()` closure was given and the gate exists. */
    public const string GATE = 'seo-files.manage';

    protected bool|Closure|null $authorize = null;

    protected bool|Closure $page = true;

    protected bool|Closure $resource = true;

    protected string|UnitEnum|Closure|null $navigationGroup = null;

    protected ?int $navigationSort = null;

    protected string|BackedEnum|Closure|null $navigationIcon = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static */
        return filament(static::ID);
    }

    public function getId(): string
    {
        return static::ID;
    }

    /**
     * Who may open the page and the resource. Default: the `seo-files.manage` gate when it
     * is defined, otherwise everyone who can enter the panel.
     */
    public function authorize(bool|Closure $callback): static
    {
        $this->authorize = $callback;

        return $this;
    }

    /**
     * Show the "SEO files" page. Turn it off when the actions are embedded in your own
     * page ({@see Actions\GenerateSitemapAction} and friends).
     */
    public function page(bool|Closure $condition = true): static
    {
        $this->page = $condition;

        return $this;
    }

    /**
     * Show the "Sitemap URLs" resource.
     */
    public function resource(bool|Closure $condition = true): static
    {
        $this->resource = $condition;

        return $this;
    }

    public function navigationGroup(string|UnitEnum|Closure|null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function navigationIcon(string|BackedEnum|Closure|null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function getNavigationGroup(): string|UnitEnum|null
    {
        return value($this->navigationGroup);
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    public function getNavigationIcon(): string|BackedEnum|null
    {
        return value($this->navigationIcon);
    }

    /**
     * May the current user manage the SEO files? Works whether or not the plugin is
     * registered in the current panel.
     */
    public static function allows(): bool
    {
        $plugin = filament()->hasPlugin(static::ID) ? static::get() : null;

        return ($plugin ?? static::make())->isAllowed();
    }

    public function isAllowed(): bool
    {
        if ($this->authorize !== null) {
            return (bool) value($this->authorize);
        }

        return !Gate::has(self::GATE) || Gate::allows(self::GATE);
    }

    public function register(Panel $panel): void
    {
        if ((bool) value($this->page)) {
            $panel->pages([SeoFilesPage::class]);
        }

        if ((bool) value($this->resource)) {
            $panel->resources([SitemapUrlResource::class]);
        }

        // After the panel's theme, not before it as auto-loaded plugin assets are: a custom
        // theme compiles the same utilities, and with equal specificity the later file wins.
        $panel->renderHook(PanelsRenderHook::STYLES_AFTER, fn (): string => '<link rel="stylesheet" href="'
            .e(FilamentAsset::getStyleHref(SeoFilesServiceProvider::STYLESHEET, SeoFilesServiceProvider::PACKAGE)).'" />');
    }

    public function boot(Panel $panel): void {}
}
