<?php

declare(strict_types=1);

use Asignua\FilamentSeoFiles\Models\SitemapUrl;

/*
 * Filament SEO Files.
 *
 * Closures (sources, base URL, languages, resolvers) cannot live in a config file that has
 * to stay cacheable: set them on the `SeoFiles` registry in AppServiceProvider.
 * Panel options (authorization, navigation, which UI to show) live on `SeoFilesPlugin`.
 */
return [

    /*
     * Database tables. Rename before running the published migration.
     */
    'tables' => [
        // Manual sitemap URLs (the "Sitemap URLs" resource).
        'sitemap_urls' => 'seo_sitemap_urls',
    ],

    /*
     * Eloquent models. Point a key at your own subclass to add traits (an audit log, say).
     * Writes always go through SitemapUrlRepository, which resolves the class from here.
     */
    'models' => [
        'sitemap_url' => SitemapUrl::class,
    ],

    // sitemap.xml.
    'sitemap' => [
        // Absolute path of the generated file. null = public_path('sitemap.xml'),
        // resolved at run time.
        'path' => null,

        // The protocol allows 50 000 URLs and 50 MB (uncompressed) per file. Above
        // max_urls the generator writes several part files and turns sitemap.xml into a
        // <sitemapindex> that lists them.
        'max_urls' => 50000,

        // auto   = one sitemap.xml while the URLs fit, an index with parts once they do not;
        // always = always an index with at least one part;
        // never  = always ONE file, even above the limits (search engines may reject it).
        'split' => 'auto',

        // The name of a part file; {n} is its number. Parts are written next to sitemap.xml
        // and the stale ones of a previous run (matching this pattern) are deleted.
        'chunk_name' => 'sitemap-{n}.xml',

        // A part is closed early when its estimated XML size reaches this many bytes
        // (the protocol limit is 52 428 800).
        'max_bytes' => 45000000,

        // Image sitemap: <image:image><image:loc> inside each <url> (Google's current spec;
        // caption, title, geo_location and license are deprecated and never written).
        // Images come from the source entries (ModelSource::images(), the HasSitemapImages
        // interface, SitemapEntry::$images), from SeoFiles::imagesUsing(), and for manual
        // URLs from their `images` column (run the add_images migration to get it).
        'images' => [
            // Off by default.
            'enabled' => false,

            // Google reads up to 1 000 images per <url>.
            'max_per_url' => 1000,
        ],

        // News sitemap: a separate file with <news:news> for the articles published in the
        // last `max_age_days` days, listed in the sitemap index (turning this on makes
        // sitemap.xml an index). Items come from SitemapEntry::$news / ModelSource::news().
        'news' => [
            // Off by default.
            'enabled' => false,

            // Written next to sitemap.xml.
            'file' => 'sitemap-news.xml',

            // Google asks for articles of the last two days only.
            'max_age_days' => 2,

            // At most 1 000 <news:news> per file (the newest are kept).
            'max_urls' => 1000,
        ],
    ],

    // robots.txt.
    'robots' => [
        // Absolute path of the file the editor reads and writes.
        // null = public_path('robots.txt').
        'path' => null,
    ],

    // llms.txt and llms-full.txt.
    'llms' => [
        // llms.txt of the language served WITHOUT a URL prefix. null = public_path('llms.txt').
        'path' => null,

        // llms-full.txt of that language. null = public_path('llms-full.txt').
        'full_path' => null,

        // Directory with the files of the PREFIXED languages ({locale}.txt and
        // {locale}-full.txt), served by the /{locale}/llms.txt routes. It must NOT be
        // public/{locale}/: that directory would shadow the language's home page.
        // null = public_path('.llms').
        'directory' => null,

        // The longest description of a link in llms.txt, in characters (ModelSource).
        'description_limit' => 200,

        // The most records one ModelSource lists in llms.txt, newest first — the file is a
        // short index, sitemap.xml has the complete list. null = every record.
        // Override per source with ->indexLimit().
        'index_limit' => 100,
    ],

    // Public routes.
    'routes' => [
        // Register /{locale}/llms.txt and /{locale}/llms-full.txt for the prefixed languages
        // automatically. Turn this off on a site with a catch-all route and call
        // SeoFiles::routes() before that route instead.
        'register' => true,

        // Middleware of those routes. Empty by default: the `web` group would start a
        // session and set cookies on a plain text file, so a CDN could not cache it.
        'middleware' => [],
    ],

    // IndexNow: tells Bing, Yandex & co which URLs changed. Off by default.
    'indexnow' => [
        // Off by default.
        'enabled' => false,

        // 8–128 characters of a-z, A-Z, 0-9 and "-". The plugin serves it at /{key}.txt.
        'key' => null,

        // Where the key file is reachable; null = {base URL}/{key}.txt.
        'key_location' => null,

        // api.indexnow.org forwards to every participating engine; use
        // https://www.bing.com/indexnow or https://yandex.com/indexnow to talk to one.
        'endpoint' => 'https://api.indexnow.org/indexnow',

        // Ping the changed URLs after every seo-files:sitemap run.
        'on_generate' => true,

        // The first run only records a baseline; true pings the whole site once.
        'ping_initial' => false,

        // At most one automatic ping per this many seconds (changes wait for the next run).
        'throttle_seconds' => 3600,

        // Queue connection / name of the ping job; null = the defaults.
        'connection' => null,
        'queue' => null,
    ],

    // Scheduled generation.
    'schedule' => [
        // Regenerate sitemap.xml and the llms files daily. Needs the Laravel scheduler running.
        'enabled' => false,

        // When it runs (server time, HH:MM).
        'times' => [
            'sitemap' => '04:00',
            'llms' => '04:20',
        ],
    ],

];
