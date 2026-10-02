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
    ],

    // Public routes.
    'routes' => [
        // Register /{locale}/llms.txt and /{locale}/llms-full.txt for the prefixed languages
        // automatically. Turn this off on a site with a catch-all route and call
        // SeoFiles::routes() before that route instead.
        'register' => true,
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
