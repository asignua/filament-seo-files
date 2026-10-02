# Filament SEO Files

[![Stand With Ukraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/badges/StandWithUkraine.svg)](https://stand-with-ukraine.pp.ua)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/asignua/filament-seo-files.svg?style=flat-square)](https://packagist.org/packages/asignua/filament-seo-files)
[![Tests](https://img.shields.io/github/actions/workflow/status/asignua/filament-seo-files/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/asignua/filament-seo-files/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/asignua/filament-seo-files.svg?style=flat-square)](https://packagist.org/packages/asignua/filament-seo-files)
[![License](https://img.shields.io/packagist/l/asignua/filament-seo-files.svg?style=flat-square)](https://github.com/asignua/filament-seo-files/blob/main/LICENSE.md)
[![Plumb score](https://plumbphp.dev/badges/asignua/filament-seo-files/composite.svg)](https://plumbphp.dev/asignua/filament-seo-files)

<img class="filament-hidden" src="https://raw.githubusercontent.com/asignua/filament-seo-files/v1.0.0/art/cover.jpg" alt="Filament SEO Files">

The files crawlers and AI agents read — `sitemap.xml`, `robots.txt`, `llms.txt` and `llms-full.txt` — generated
from your own models and managed from a [Filament](https://filamentphp.com) panel.

Sitemap plugins stop at the sitemap, and nobody generates `llms.txt` at all. This one does all four, with
the details that are easy to get wrong: `hreflang` clusters that are reciprocal, `x-default`, manual URLs that can
never shadow a real page, a sitemap index once a site outgrows 50 000 URLs, and per-language `llms.txt` served
without breaking your home page.

- [Screenshots](#screenshots)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start) — a model in three lines
- [Multilingual sites](#multilingual-sites)
- [Custom sources](#custom-sources)
- [Manual sitemap URLs](#manual-sitemap-urls)
- [Large sites: the sitemap index](#large-sites-the-sitemap-index)
- [robots.txt](#robotstxt)
- [llms.txt and llms-full.txt](#llmstxt-and-llms-fulltxt)
- [Scheduling](#scheduling)
- [Authorization](#authorization)
- [Commands](#commands)
- [Using the actions on your own page](#using-the-actions-on-your-own-page)
- [Configuration](#configuration)
- [Translations](#translations)
- [AI agents](#ai-agents)
- [Testing](#testing)

## Screenshots

The "SEO files" page: generate the sitemap and llms files, edit robots.txt.

![The SEO files page](https://raw.githubusercontent.com/asignua/filament-seo-files/v1.0.0/art/seo-files-page.jpg)

![The SEO files page, dark mode](https://raw.githubusercontent.com/asignua/filament-seo-files/v1.0.0/art/seo-files-page-dark.jpg)

Manual "Sitemap URLs": pages no source knows about.

![Sitemap URLs table](https://raw.githubusercontent.com/asignua/filament-seo-files/v1.0.0/art/sitemap-urls.jpg)

One record is one multilingual URL, with a live preview of the final address.

![Sitemap URL form](https://raw.githubusercontent.com/asignua/filament-seo-files/v1.0.0/art/sitemap-url-form.jpg)

The generated `llms.txt`, editable per language.

![llms.txt editor](https://raw.githubusercontent.com/asignua/filament-seo-files/v1.0.0/art/llms-editor.jpg)

The generated `sitemap.xml` with reciprocal `hreflang` alternates and `x-default`.

![Generated sitemap.xml](https://raw.githubusercontent.com/asignua/filament-seo-files/v1.0.0/art/sitemap-xml.jpg)


## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Filament 5 (only for the panel UI — the generation itself runs anywhere)

## Installation

```bash
composer require asignua/filament-seo-files
```

Publish and run the migration for the manual "Sitemap URLs" table (skip it if you do not use that resource):

```bash
php artisan vendor:publish --tag="filament-seo-files-migrations"
php artisan migrate
```

Optionally publish the config (every key is commented) and the translations:

```bash
php artisan vendor:publish --tag="filament-seo-files-config"
php artisan vendor:publish --tag="filament-seo-files-translations"
```

Register the plugin in your panel provider to get the page and the resource:

```php
use Asignua\FilamentSeoFiles\SeoFilesPlugin;

$panel->plugin(SeoFilesPlugin::make());
```

The package has two layers. The **registry** `SeoFiles` holds your sources and resolvers and works everywhere —
console, scheduler, queue, routes — with or without a panel. The **plugin** `SeoFilesPlugin` only draws the panel UI.

## Styling

The views use a few Tailwind utilities that Filament's own stylesheet does not contain. The plugin ships them as a small compiled file (`resources/dist/filament-seo-files.css`, no preflight) and links it after the panel's styles, so **no custom theme or `@source` line is needed**. Publish the file after installing or upgrading:

```bash
php artisan filament:assets
```

The stylesheet is linked by the plugin registered in the panel. Editing the views? Rebuild with `npm install && npm run build`.

## Quick start

In `AppServiceProvider::boot()` tell the registry where your pages are:

```php
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Sources\ModelSource;

SeoFiles::source(
    ModelSource::make(Post::class)
        ->query(fn ($query) => $query->where('published', true))
        ->url(fn (Post $post, string $locale): ?string => route('posts.show', $post))
        ->title(fn (Post $post, string $locale): string => $post->title)
        ->description(fn (Post $post, string $locale): ?string => $post->excerpt)
        ->body(fn (Post $post, string $locale): string => $post->content) // HTML or Markdown
        ->section('Blog'),
);
```

Then:

```bash
php artisan seo-files:sitemap   # public/sitemap.xml
php artisan seo-files:llms      # public/llms.txt and public/llms-full.txt
```

That is the whole setup for a single-language site. The base URL defaults to `app.url`.

What `ModelSource` does for you:

- reads the table in chunks by primary key, so a large table is never loaded whole;
- skips a record in a language where `url()` returns `null`;
- `<lastmod>` is `updated_at` unless you pass `->lastModified(fn (Post $post) => …)`;
- the `llms.txt` description is stripped of tags and cut to 200 characters (`llms.description_limit`);
- an HTML body is converted to Markdown with [league/html-to-markdown](https://github.com/thephpleague/html-to-markdown);
  call `->markdown()` when the body closure already returns Markdown;
- a `url()` result that starts with `/` gets the base URL prepended, an absolute one is used as is;
- `->locales(['en'])` restricts a source to some languages, `->chunk(500)` changes the chunk size, `->limit(200)` caps the
  records in `llms-full.txt`, which carries whole page bodies.

## Multilingual sites

```php
SeoFiles::locales(default: 'en', all: ['en', 'uk', 'de'], unprefixed: 'en');
```

- `default` is the language of `<loc>` and `x-default`.
- `unprefixed` is the language served without a `/{locale}/` URL prefix (`null` if every language is prefixed).
- When the languages live in a config that can change after boot (a CMS), use
  `SeoFiles::localesUsing(fn () => ['default' => …, 'all' => […], 'unprefixed' => …])`: it is resolved at the moment of use.

For each page `ModelSource` asks your `url()` closure once per language, puts the default language into `<loc>` (or the
first language that has a URL) and lists every language version as a reciprocal `hreflang` alternate, plus `x-default`.

Four more resolvers adapt the plugin to how your site builds URLs and names itself:

```php
SeoFiles::baseUrlUsing(fn (): string => config('app.url'));            // default: app.url
SeoFiles::localizedUrlUsing(fn (string $locale, string $path): string => …); // default: {base}/[{locale}/]{path}
SeoFiles::siteNameUsing(fn (string $locale): string => …);             // default: app.name
SeoFiles::descriptionUsing(fn (string $locale): ?string => …);         // default: none
```

`baseUrlUsing` feeds both `sitemap.xml` and `robots.txt`, so they can never point at different hosts. A scheduler has no
request, so the base URL always comes from here, never from `url()`.

## Custom sources

A source implements any subset of three contracts and is registered with `SeoFiles::source(...)`:

```php
use Asignua\FilamentSeoFiles\Contracts\LlmsFullSource;
use Asignua\FilamentSeoFiles\Contracts\LlmsIndexSource;
use Asignua\FilamentSeoFiles\Contracts\SitemapSource;
use Asignua\FilamentSeoFiles\Data\LlmsDocument;
use Asignua\FilamentSeoFiles\Data\LlmsLink;
use Asignua\FilamentSeoFiles\Data\LlmsSection;
use Asignua\FilamentSeoFiles\Data\SitemapEntry;

class CatalogSource implements LlmsFullSource, LlmsIndexSource, SitemapSource
{
    public function sitemapEntries(): iterable
    {
        foreach (Product::query()->lazyById() as $product) {
            yield new SitemapEntry(
                url: "https://shop.test/p/{$product->slug}",                 // the default language
                alternates: ['en' => "https://shop.test/p/{$product->slug}",
                             'uk' => "https://shop.test/uk/p/{$product->slug}"],
                lastModified: $product->updated_at,                          // optional
            );
        }
    }

    public function llmsSections(string $locale): iterable
    {
        yield new LlmsSection('Catalogue', [
            new LlmsLink('All products', 'https://shop.test/p', 'The full catalogue'),
        ]);
    }

    public function llmsDocuments(string $locale): iterable
    {
        yield new LlmsDocument('Delivery', 'https://shop.test/delivery', "# Delivery\n\nWe ship worldwide.");
    }
}
```

Return a generator for large tables — the sitemap is generated as a stream. A hreflang cluster is an entry, so it is never
cut between two sitemap files.

## Manual sitemap URLs

Pages no source knows about — a search page, RSS feeds, an external landing page — live in the **Sitemap URLs**
resource (the migration above creates its table). One record is one multilingual `<url>`:

- the value is a **path from the site root without the language prefix** (`search`) — the plugin adds the host and the
  `/{locale}/` prefix — or a full `https://…` address, used as is;
- the form rejects spaces, invalid addresses, `ftp://` and `//host`, the root path, paths that start with a language
  prefix, and paths your site already owns;
- tell the plugin what the site owns, so a manual URL can never duplicate a real page:

```php
SeoFiles::ownedPathUsing(fn (string $locale, string $path): bool => Page::query()->where('slug', $path)->exists());
```

Sources are written **first** and manual records second, and an address that was already emitted — as a `<loc>` or as an
alternate — is never emitted again. A manual record cannot override a real page.

Writes go through `Asignua\FilamentSeoFiles\Repositories\SitemapUrlRepository` (the model has `$guarded = ['*']`). To
extend the model — to add an audit trail, say — subclass it and point `filament-seo-files.models.sitemap_url` at your class.

## Large sites: the sitemap index

The protocol allows 50 000 URLs or 50 MB (uncompressed) per file. Generation is streaming: tags collect in a buffer of
`sitemap.max_urls`, and on overflow the buffer is written as a part (`sitemap-1.xml`, `sitemap-2.xml`, …) next to
`sitemap.xml`, which then becomes a `<sitemapindex>` with each part's `<lastmod>`.

| Option | Default | |
| --- | --- | --- |
| `sitemap.max_urls` | `50000` | URLs per part |
| `sitemap.split` | `auto` | `auto`: one file while the URLs fit; `always`: always an index; `never`: always one file |
| `sitemap.chunk_name` | `sitemap-{n}.xml` | name of a part |
| `sitemap.max_bytes` | `45000000` | a part is closed early at this estimated size |

The deduplication map is shared by all parts, a hreflang cluster is never split, parts of an earlier run that this run
did not write are deleted (only files that match `chunk_name`, only next to `sitemap.xml`), and every file is written to
a temporary file and renamed, so a crawler never sees a half-written index. `robots.txt` and `llms.txt` keep pointing
at `sitemap.xml`.

## robots.txt

`RobotsFile` reads and writes `public/robots.txt`. Until the file exists the recommended template is served by the editor:

```
User-agent: *
Content-Signal: search=yes, ai-input=yes, ai-train=no
Allow: /

Sitemap: https://example.com/sitemap.xml
```

[`Content-Signal`](https://contentsignals.org) says search and agent answers are welcome and model training is not; it
sits inside the `User-agent` group on purpose. Replace the template:

```php
SeoFiles::robotsTemplateUsing(fn (string $baseUrl, string $default): string => $default."\nDisallow: /admin");
```

Edit the live file from the panel ("SEO files" page → robots.txt → Edit, with a "Reset to template" button).

## llms.txt and llms-full.txt

[llms.txt](https://llmstxt.org) is a short curated index of the site for AI agents; `llms-full.txt` carries the full text
of every page, separated by `---`. One pair per language:

| Language | `llms.txt` | `llms-full.txt` |
| --- | --- | --- |
| the unprefixed one | `public/llms.txt` | `public/llms-full.txt` |
| a prefixed one | `public/.llms/{locale}.txt` | `public/.llms/{locale}-full.txt` |

Prefixed languages are served at `/{locale}/llms.txt` and `/{locale}/llms-full.txt` by **routes**, which the package
registers for you. They are not static files because a real `public/{locale}/` directory would shadow your
`/{locale}/` home page (`php artisan serve` and nginx's `try_files $uri $uri/` would serve the directory). The leading
dot of `.llms` also keeps nginx from serving the stored files directly. A language that has no stored file yet is served
a freshly built template.

**Sites with a catch-all route** must register the routes *before* it, otherwise the catch-all swallows both URLs:

```php
// config/filament-seo-files.php
'routes' => ['register' => false],

// routes/web.php
use Asignua\FilamentSeoFiles\SeoFiles;

SeoFiles::routes();            // before the catch-all
Route::get('/{path?}', …)->where('path', '.*');
```

The index is built from the sections of every `LlmsIndexSource`, plus an `## Optional` section that points at
`sitemap.xml` (the spec lets an agent skip it when it needs a shorter context). The blockquote under the title comes
from `SeoFiles::descriptionUsing(...)`; the language is checked against `^[a-z]{2,3}([-_][A-Za-z]{2,4})?$` before any
path is built, for both files.

## Scheduling

Off by default. Turn it on and make sure the Laravel scheduler runs (`php artisan schedule:work`, or `* * * * * php artisan schedule:run`):

```php
// config/filament-seo-files.php
'schedule' => [
    'enabled' => true,
    'times' => ['sitemap' => '04:00', 'llms' => '04:20'],
],
```

Both commands run daily and `withoutOverlapping()`: they write into `public/`, and two parallel writes into one file give a
truncated file nobody notices. Without a scheduler process nothing runs, and the failure is silent — check it once.
Add your own tasks with a plain `withSchedule()`; the registrations add up.

## Authorization

By default everyone who can enter the panel can open the page and the resource — unless a `seo-files.manage` gate is
defined, in which case the gate decides. Or pass a closure:

```php
$panel->plugin(SeoFilesPlugin::make()
    ->authorize(fn (): bool => auth()->user()?->isAdmin())
    ->navigationGroup('SEO')
    ->navigationSort(20));
```

`->page(false)` hides the "SEO files" page (when you embed the actions in your own page, see below) and
`->resource(false)` hides "Sitemap URLs".

## Commands

| Command | |
| --- | --- |
| `php artisan seo-files:sitemap` | writes `sitemap.xml` (and the parts of an index) |
| `php artisan seo-files:llms` | writes `llms.txt` and `llms-full.txt` for every language |
| `php artisan seo-files:llms --locale=uk --locale=en` | only these languages |

`seo-files:llms` rebuilds the files from the sources, so manual edits made in the panel's editor are replaced.

## Using the actions on your own page

The four buttons of the "SEO files" page are public Filament actions. Put them on any page, for instance an existing
"Tools" page, and hide the stock page with `->page(false)`:

```php
use Asignua\FilamentSeoFiles\Actions\EditLlmsAction;
use Asignua\FilamentSeoFiles\Actions\EditRobotsAction;
use Asignua\FilamentSeoFiles\Actions\GenerateLlmsAction;
use Asignua\FilamentSeoFiles\Actions\GenerateSitemapAction;

public function generateSitemapAction(): Action { return GenerateSitemapAction::make(); }
public function editRobotsAction(): Action { return EditRobotsAction::make(); }
public function generateLlmsAction(): Action { return GenerateLlmsAction::make(); }
public function editLlmsAction(): Action { return EditLlmsAction::make(); }
```

Render each with `{{ $this->generateSitemapAction }}` and keep `<x-filament-actions::modals />` on the page. The actions do
not check authorization themselves — the page that hosts them does.

## Configuration

`config/filament-seo-files.php` — tables, models, the sitemap/robots/llms paths (all default to `public_path()`
and are resolved at run time), the sitemap index limits, `routes.register`, the schedule and the `llms` description
limit. Closures (sources, base URL, languages) cannot live in a cacheable config file and are set on the `SeoFiles`
registry instead.

`SeoFiles::flush()` forgets everything configured on the registry — handy in tests.

## Translations

The interface ships in English, Ukrainian, German, Spanish, French, Italian, Dutch, Polish, Brazilian Portuguese and
Turkish under the `filament-seo-files::seo-files` namespace. A test keeps every language in step with the English keys
and placeholders. Override a string by publishing the translations and editing the copy in `lang/vendor/filament-seo-files`.

## AI agents

The package ships [Laravel Boost](https://laravel.com/docs/boost) guidelines (`resources/boost/guidelines/core.blade.php`)
that describe the registry, `ModelSource`, the routes and the commands, so a coding agent wires it up correctly.

## Testing

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/pint --test
```

The suite runs on [Orchestra Testbench](https://packages.tools/testbench) with a `workbench/` panel and a `Post` model.
Tests write into a throw-away `public/`, never the real one.

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
