# Changelog

All notable changes to `asignua/filament-seo-files` are documented here.

## Unreleased

- Fix: the plugin's stylesheet no longer declares generic utilities (`.flex`, `.text-sm`, `.text-gray-400`…). Linked after the panel's theme, they beat the theme's responsive and `dark:` variants on every panel page. The page and the form preview use `fi-seo-files-*` classes now, and the stylesheet is linked only on the plugin's own pages. Run `php artisan filament:assets` after upgrading.
- Fix: the panel's "Generate" buttons and the llms editor (reset, first read) build the public files outside the admin context: no Filament panel, tenant or logged-in user, as the scheduler does (every configured guard, the default one and each panel's, is swapped for one with nobody in it for the duration, since a session guard would re-read the admin from the session; `Filament::auth()` is covered too). Before, a tenant scope or a user-dependent global scope leaked into `sitemap.xml` and `llms-full.txt`.
- Fix: `seo-files:sitemap` and `seo-files:llms` take a non-blocking cache lock, so a panel click cannot interleave with the nightly run (one run deleting the parts the other lists). A refused run exits with an error; the panel shows it as a failure, and so it does for any non-zero exit code.
- Fix: the llms files are built in the language of the file (`app()->getLocale()` is that language inside the source closures), and `ModelSource` takes the default title from the translatable `title` of that language. A non-string title no longer throws.
- Fix: HTML entities and no-break spaces in `ModelSource` descriptions are decoded to plain text before the length limit is applied.
- Fix: a `ModelSource` restricted with `->locales()` no longer scans its table for the languages it does not serve.
- Fix: the source registry is reset when the service provider registers, so a host test suite that boots a fresh application per test no longer piles up copies of a source (and Octane's cloned per-request applications keep the sources registered at boot).
- Fix: the generation time on the "SEO files" page is shown in the app timezone (it was UTC).
- Fix: the "Sitemap URLs" search is case-insensitive on MySQL/MariaDB.
- Fix: BCP 47 locales such as `es-419` and `zh-Hant-TW` no longer break the "SEO files" page or abort `seo-files:llms`.

## v1.0.1 - 2026-10-03

- Fix: the "SEO files" page and the "Sitemap URLs" form used Tailwind utilities that Filament's stylesheet does not contain, so in a panel without a custom theme scanning the plugin they were unstyled. The plugin now ships a small compiled stylesheet (`resources/dist/filament-seo-files.css`) and links it after the panel's styles. Run `php artisan filament:assets` after upgrading.
- **Security (breaking):** authorization now fails closed. Without an `authorize()` closure and without a `seo-files.manage` gate nobody can open the page, the resource or the actions (before, every panel user could rewrite `robots.txt` and the llms files). Restore the old behaviour with `SeoFilesPlugin::make()->authorize(true)`; `authorize(null)` returns to the default.
- **Security:** the four public actions (`GenerateSitemapAction`, `EditRobotsAction`, `GenerateLlmsAction`, `EditLlmsAction`) now check `SeoFilesPlugin::allows()` themselves, so embedding them in a page with weaker access no longer bypasses the policy.
- **Security:** `/{locale}/llms.txt` and `/{locale}/llms-full.txt` no longer rebuild the document from the database on every request while no file is stored: the first request builds and stores it once, behind a cache lock (`503` + `Retry-After` at once while another request is building — a waiting request no longer holds a PHP worker). The routes no longer use the `web` middleware group (no session, no cookies, cacheable by a CDN); `routes.middleware` sets it.
- Fix: every language version of a page is now a `<url>` of its own listing the full hreflang cluster, as Google's sitemap method expects (before, only the default language was a `<loc>`). Sitemaps hold twice as many `<url>` entries on a two-language site, and `sitemap.max_urls` counts them.
- Fix: hreflang values are BCP 47 — `pt_BR` is written as `pt-BR`.
- Fix: `ModelSource` lists at most `llms.index_limit` (100) records in `llms.txt`, newest first; `->indexLimit()` per source, `null` for all. The llms editor accepts up to 1 000 000 characters, so a generated file can be saved unchanged.
- Fix: the llms "Generate" confirmation says that editor changes are replaced; with the schedule on, both llms actions name the time of the nightly rewrite.
- Fix: the root path `/` can be added as a manual sitemap URL (rejected only when the site owns `''`); a second manual record with the same address in a language is rejected.
- Fix: `robots.txt`, `llms.txt` and `llms-full.txt` are written through a temporary file and a rename, like the sitemap. When that cannot work without side effects — the directory is not writable, or the file belongs to another user (a hardened deploy where only these files are writable for php-fpm) — the file is written in place as before; an existing file keeps its mode.
- Fix: `seo-files:sitemap` reports the number of `<url>` entries (one per language version), matching the file and the "SEO files" page: `Sitemap: N URLs (M pages from sources + K manual)`. `SitemapGenerator::generate()` returns it as `urls`.
- Fix: two sources listing the same language version no longer produce a one-way hreflang: an alternate already emitted by an earlier cluster is left out.
- The "SEO files" page caches the sitemap URL count per generation instead of rescanning the file on every request.
- Fix: the "SEO files" page reads only the head of `sitemap.xml` to tell an index from a single file.
- Fix: parentheses and spaces in `llms.txt` link URLs are percent-encoded; a line break in an `llms-full.txt` title, site name or description no longer breaks the entry header or the preamble.
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
