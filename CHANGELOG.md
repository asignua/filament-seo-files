# Changelog

All notable changes to `asignua/filament-seo-files` are documented here.

## v1.0.1 - 2026-10-02

- Fix: the "SEO files" page and the "Sitemap URLs" form used Tailwind utilities that Filament's stylesheet does not contain, so in a panel without a custom theme scanning the plugin they were unstyled. The plugin now ships a small compiled stylesheet (`resources/dist/filament-seo-files.css`) and links it after the panel's styles. Run `php artisan filament:assets` after upgrading.
- **Security (breaking):** authorization now fails closed. Without an `authorize()` closure and without a `seo-files.manage` gate nobody can open the page, the resource or the actions (before, every panel user could rewrite `robots.txt` and the llms files). Restore the old behaviour with `SeoFilesPlugin::make()->authorize(true)`; `authorize(null)` returns to the default.
- **Security:** the four public actions (`GenerateSitemapAction`, `EditRobotsAction`, `GenerateLlmsAction`, `EditLlmsAction`) now check `SeoFilesPlugin::allows()` themselves, so embedding them in a page with weaker access no longer bypasses the policy.
- **Security:** `/{locale}/llms.txt` and `/{locale}/llms-full.txt` no longer rebuild the document from the database on every request while no file is stored: the first request builds and stores it once, behind a cache lock (`503` + `Retry-After` while another request is building). The routes no longer use the `web` middleware group (no session, no cookies, cacheable by a CDN); `routes.middleware` sets it.
- Fix: every language version of a page is now a `<url>` of its own listing the full hreflang cluster, as Google's sitemap method expects (before, only the default language was a `<loc>`). Sitemaps hold twice as many `<url>` entries on a two-language site, and `sitemap.max_urls` counts them.
- Fix: hreflang values are BCP 47 — `pt_BR` is written as `pt-BR`.
- Fix: `ModelSource` lists at most `llms.index_limit` (100) records in `llms.txt`, newest first; `->indexLimit()` per source, `null` for all. The llms editor accepts up to 1 000 000 characters, so a generated file can be saved unchanged.
- Fix: the llms "Generate" confirmation says that editor changes are replaced; with the schedule on, both llms actions name the time of the nightly rewrite.
- Fix: the root path `/` can be added as a manual sitemap URL (rejected only when the site owns `''`); a second manual record with the same address in a language is rejected.
- Fix: `robots.txt`, `llms.txt` and `llms-full.txt` are written through a temporary file and a rename, like the sitemap.
- Fix: the "SEO files" page reads only the head of `sitemap.xml` to tell an index from a single file.
- Fix: parentheses and spaces in `llms.txt` link URLs are percent-encoded; a line break in an `llms-full.txt` title no longer breaks the entry header.
- The generate actions lift PHP's time limit for the command they run.
- Removed the unused `actions.run` and `validation.home` translation keys.

## v1.0.0 - 2026-10-02

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
