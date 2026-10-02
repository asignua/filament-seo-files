# Changelog

All notable changes to `asignua/filament-seo-files` are documented here.

## v1.0.0 - unreleased

- `sitemap.xml` with hreflang alternates, `x-default` and `<lastmod>`; never `priority` or `changefreq`.
- Large sites: above `sitemap.max_urls` (50 000) `sitemap.xml` becomes a `<sitemapindex>` and the pages are written as `sitemap-1.xml` … `sitemap-N.xml`; streaming generation, one deduplication map for all parts, parts closed early near 50 MB, stale parts removed, every file written atomically. `sitemap.split`: `auto` / `always` / `never`.
- Pluggable page sources (`SitemapSource`, `LlmsIndexSource`, `LlmsFullSource`) and a fluent `ModelSource` for Eloquent models.
- Static registry `SeoFiles` — works in console, scheduler, queue and routes, with or without a panel.
- Manual sitemap URLs: a Filament resource over a JSON per-language map, validated against typos, language prefixes and pages the site already owns.
- `robots.txt` with a recommended template (including `Content-Signal`) and an editor.
- `llms.txt` and `llms-full.txt` per language; `/{locale}/llms.txt` routes for prefixed languages.
- Commands `seo-files:sitemap` and `seo-files:llms`, optional daily schedule.
- Panel: the "SEO files" page, the "Sitemap URLs" resource and four public action factories (`GenerateSitemapAction`, `EditRobotsAction`, `GenerateLlmsAction`, `EditLlmsAction`) for embedding in your own pages.
- Authorization through `SeoFilesPlugin::authorize()` or the `seo-files.manage` gate.
- Translations: English, Ukrainian, German, Spanish, French, Italian, Dutch, Polish, Brazilian Portuguese and Turkish.
- Laravel Boost guidelines.
