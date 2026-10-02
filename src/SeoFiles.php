<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles;

use Asignua\FilamentSeoFiles\Contracts\LlmsFullSource;
use Asignua\FilamentSeoFiles\Contracts\LlmsIndexSource;
use Asignua\FilamentSeoFiles\Contracts\SitemapSource;
use Asignua\FilamentSeoFiles\Http\Controllers\LlmsController;
use Asignua\FilamentSeoFiles\Http\Controllers\LlmsFullController;
use Closure;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

/**
 * The static registry: sources and resolvers that work everywhere (console, scheduler,
 * queue, routes), with or without a Filament panel. Configure it in
 * `AppServiceProvider::register()` or `boot()`; the panel plugin only draws the UI.
 *
 *     SeoFiles::locales(default: 'en', all: ['en', 'uk'], unprefixed: 'en');
 *     SeoFiles::source(ModelSource::make(Post::class)->url(fn (Post $post, string $locale) => route('posts.show', $post)));
 *
 * Every resolver has a default that suits a single-language site.
 */
final class SeoFiles
{
    private static ?Closure $baseUrl = null;

    private static ?string $defaultLocale = null;

    /** @var list<string>|null */
    private static ?array $locales = null;

    private static ?string $unprefixed = null;

    private static bool $unprefixedSet = false;

    private static ?Closure $localizedUrl = null;

    private static ?Closure $ownedPath = null;

    private static ?Closure $siteName = null;

    private static ?Closure $description = null;

    private static ?Closure $robotsTemplate = null;

    /** @var list<object> */
    private static array $sources = [];

    private static bool $routesRegistered = false;

    /**
     * The site's base URL (scheme + host, no trailing slash). A scheduler has no request,
     * so `url()` is not an option: the base must come from here. Default: `app.url`.
     *
     * @param Closure(): string $resolver
     */
    public static function baseUrlUsing(Closure $resolver): void
    {
        self::$baseUrl = $resolver;
    }

    /**
     * Languages of the site. `unprefixed` is the language served without a `/{locale}/` URL
     * prefix (`null` = every language is prefixed). Default: the single `app.locale`, unprefixed.
     *
     * @param list<string> $all
     */
    public static function locales(string $default, array $all, ?string $unprefixed = null): void
    {
        $all = array_values(array_unique([$default, ...$all]));

        self::$defaultLocale = $default;
        self::$locales = $all;
        self::$unprefixed = $unprefixed;
        self::$unprefixedSet = true;
    }

    /**
     * Builds the absolute URL of a language version from a path WITHOUT the language prefix
     * (the manual sitemap URLs call it). Default: `{base}/[{locale}/]{path}`.
     *
     * @param Closure(string $locale, string $path): string $resolver
     */
    public static function localizedUrlUsing(Closure $resolver): void
    {
        self::$localizedUrl = $resolver;
    }

    /**
     * Does a path already belong to a page of the site (so a manual sitemap URL would
     * duplicate it)? Default: nothing is owned.
     *
     * @param Closure(string $locale, string $path): bool $resolver
     */
    public static function ownedPathUsing(Closure $resolver): void
    {
        self::$ownedPath = $resolver;
    }

    /**
     * @param Closure(string $locale): string $resolver
     */
    public static function siteNameUsing(Closure $resolver): void
    {
        self::$siteName = $resolver;
    }

    /**
     * The blockquote of `llms.txt` / `llms-full.txt`.
     *
     * @param Closure(string $locale): ?string $resolver
     */
    public static function descriptionUsing(Closure $resolver): void
    {
        self::$description = $resolver;
    }

    /**
     * Replaces the default robots.txt template. The closure gets the base URL and the
     * default template, and returns the new template.
     *
     * @param Closure(string $baseUrl, string $default): string $resolver
     */
    public static function robotsTemplateUsing(Closure $resolver): void
    {
        self::$robotsTemplate = $resolver;
    }

    /**
     * Registers a page source. It may implement any subset of {@see SitemapSource},
     * {@see LlmsIndexSource} and {@see LlmsFullSource}.
     */
    public static function source(object ...$sources): void
    {
        foreach ($sources as $source) {
            if (!$source instanceof SitemapSource && !$source instanceof LlmsIndexSource && !$source instanceof LlmsFullSource) {
                throw new InvalidArgumentException(sprintf(
                    '%s implements none of the source contracts (%s, %s, %s).',
                    $source::class,
                    SitemapSource::class,
                    LlmsIndexSource::class,
                    LlmsFullSource::class,
                ));
            }

            self::$sources[] = $source;
        }
    }

    /**
     * The `/{locale}/llms.txt` and `/{locale}/llms-full.txt` routes of the prefixed
     * languages. The service provider registers them itself unless
     * `filament-seo-files.routes.register` is false; a site with a catch-all route turns
     * that off and calls this BEFORE the catch-all, which would swallow both URLs.
     */
    public static function routes(): void
    {
        $prefixed = self::prefixedLocales();

        if ($prefixed === [] || self::$routesRegistered) {
            return;
        }

        self::$routesRegistered = true;

        $pattern = implode('|', array_map(preg_quote(...), $prefixed));

        // A route, not a static file: a real `public/{locale}/` directory would shadow the
        // site's `/{locale}/` home page (see LlmsTxtFile::path()).
        Route::middleware(['web'])
            ->get('/{locale}/llms.txt', LlmsController::class)
            ->where('locale', $pattern)
            ->name('seo-files.llms.locale');

        Route::middleware(['web'])
            ->get('/{locale}/llms-full.txt', LlmsFullController::class)
            ->where('locale', $pattern)
            ->name('seo-files.llms-full.locale');
    }

    /**
     * Forgets everything configured (for tests).
     */
    public static function flush(): void
    {
        self::$baseUrl = null;
        self::$defaultLocale = null;
        self::$locales = null;
        self::$unprefixed = null;
        self::$unprefixedSet = false;
        self::$localizedUrl = null;
        self::$ownedPath = null;
        self::$siteName = null;
        self::$description = null;
        self::$robotsTemplate = null;
        self::$sources = [];
        self::$routesRegistered = false;
    }

    public static function baseUrl(): string
    {
        $base = self::$baseUrl !== null
            ? (self::$baseUrl)()
            : (string) config('app.url');

        return rtrim($base, '/');
    }

    public static function defaultLocale(): string
    {
        return self::$defaultLocale ?? (string) config('app.locale');
    }

    /**
     * Every language of the site, the default one first.
     *
     * @return list<string>
     */
    public static function allLocales(): array
    {
        return self::$locales ?? [self::defaultLocale()];
    }

    /**
     * The language served without a URL prefix, or null when every language is prefixed.
     */
    public static function unprefixedLocale(): ?string
    {
        return self::$unprefixedSet ? self::$unprefixed : self::defaultLocale();
    }

    /**
     * @return list<string>
     */
    public static function prefixedLocales(): array
    {
        return array_values(array_diff(self::allLocales(), [self::unprefixedLocale()]));
    }

    /**
     * Absolute URL of a language version of a path given WITHOUT the language prefix.
     */
    public static function localizedUrl(string $locale, string $path): string
    {
        if (self::$localizedUrl !== null) {
            return (self::$localizedUrl)($locale, $path);
        }

        $prefix = $locale === self::unprefixedLocale() ? '' : $locale;
        $tail = trim($prefix.'/'.trim($path, '/'), '/');

        return self::baseUrl().($tail === '' ? '' : '/'.$tail);
    }

    public static function ownsPath(string $locale, string $path): bool
    {
        return self::$ownedPath !== null && (bool) (self::$ownedPath)($locale, $path);
    }

    public static function siteName(string $locale): string
    {
        $name = self::$siteName !== null ? (self::$siteName)($locale) : '';

        return $name !== '' ? $name : (string) config('app.name');
    }

    public static function description(string $locale): ?string
    {
        return self::$description !== null ? (self::$description)($locale) : null;
    }

    public static function robotsTemplate(string $default): string
    {
        return self::$robotsTemplate !== null
            ? (self::$robotsTemplate)(self::baseUrl(), $default)
            : $default;
    }

    /**
     * @return list<SitemapSource>
     */
    public static function sitemapSources(): array
    {
        return array_values(array_filter(self::$sources, static fn (object $source): bool => $source instanceof SitemapSource));
    }

    /**
     * @return list<LlmsIndexSource>
     */
    public static function llmsIndexSources(): array
    {
        return array_values(array_filter(self::$sources, static fn (object $source): bool => $source instanceof LlmsIndexSource));
    }

    /**
     * @return list<LlmsFullSource>
     */
    public static function llmsFullSources(): array
    {
        return array_values(array_filter(self::$sources, static fn (object $source): bool => $source instanceof LlmsFullSource));
    }
}
