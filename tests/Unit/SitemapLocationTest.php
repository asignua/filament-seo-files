<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Unit;

use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Support\SitemapLocation;
use Asignua\FilamentSeoFiles\Tests\TestCase;

/**
 * The hybrid format of manual sitemap addresses: `https://…` as is, anything else a path
 * from the root WITHOUT the language prefix, to which the generator adds the host and the
 * prefix.
 */
class SitemapLocationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SeoFiles::baseUrlUsing(fn (): string => 'https://site.ua/');
        SeoFiles::locales(default: 'uk', all: ['uk', 'en'], unprefixed: 'uk');
    }

    public function test_a_path_gets_the_host_and_the_locale_prefix(): void
    {
        $this->assertSame('https://site.ua/katalog', SitemapLocation::forCustom('uk', 'katalog'));
        $this->assertSame('https://site.ua/en/katalog', SitemapLocation::forCustom('en', 'katalog'));
    }

    public function test_a_leading_slash_gives_the_same_result_on_both_kinds_of_locale(): void
    {
        // The stored form must be canonical, otherwise deduplication compares different strings.
        $this->assertSame('https://site.ua/katalog', SitemapLocation::forCustom('uk', '/katalog'));
        $this->assertSame('https://site.ua/en/katalog', SitemapLocation::forCustom('en', '/katalog/'));
    }

    public function test_an_absolute_address_is_used_verbatim(): void
    {
        // Neither the host nor the language prefix — even on a prefixed locale.
        $this->assertSame('https://x.org/faq', SitemapLocation::forCustom('en', 'https://x.org/faq'));
    }

    public function test_an_empty_value_gives_null(): void
    {
        $this->assertNull(SitemapLocation::forCustom('uk', ''));
        $this->assertNull(SitemapLocation::forCustom('uk', '   '));
        $this->assertNull(SitemapLocation::forCustom('uk', null));
    }

    public function test_normalize_canonicalises_paths_and_schemes(): void
    {
        $this->assertSame('katalog', SitemapLocation::normalize('  /katalog/  '));
        $this->assertSame('https://X.ORG/faq', SitemapLocation::normalize('HTTPS://X.ORG/faq'));
        // The trailing slash of a full address is NOT touched: on a foreign host it may be
        // a different page.
        $this->assertSame('https://x.org/faq/', SitemapLocation::normalize('https://x.org/faq/'));
    }

    public function test_is_absolute_ignores_scheme_case_and_rejects_non_http_schemes(): void
    {
        $this->assertTrue(SitemapLocation::isAbsolute('HtTpS://x.org'));
        $this->assertFalse(SitemapLocation::isAbsolute('//x.org'));
        $this->assertFalse(SitemapLocation::isAbsolute('ftp://x.org'));
        $this->assertFalse(SitemapLocation::isAbsolute('katalog'));
    }

    public function test_cluster_drops_empty_locales_and_puts_the_default_first(): void
    {
        $cluster = SitemapLocation::cluster(['en' => 'search', 'uk' => 'poshuk']);

        // The order is significant: <loc> is the first element.
        $this->assertSame(['uk', 'en'], array_keys($cluster));
        $this->assertSame('https://site.ua/poshuk', $cluster['uk']);
        $this->assertSame('https://site.ua/en/search', $cluster['en']);
    }

    public function test_cluster_falls_back_to_the_first_filled_locale_when_the_default_is_empty(): void
    {
        $cluster = SitemapLocation::cluster(['uk' => '', 'en' => 'grants']);

        $this->assertSame(['en'], array_keys($cluster));
        $this->assertSame('https://site.ua/en/grants', $cluster['en']);
    }

    public function test_cluster_of_an_all_empty_map_is_empty(): void
    {
        $this->assertSame([], SitemapLocation::cluster(['uk' => '', 'en' => null]));
        $this->assertSame([], SitemapLocation::cluster([]));
    }

    public function test_a_path_starting_with_any_site_language_is_flagged(): void
    {
        // `en/katalog` would give https://site.ua/en/en/katalog — on the uk tab too.
        $this->assertTrue(SitemapLocation::startsWithLanguagePrefix('en/katalog'));
        $this->assertTrue(SitemapLocation::startsWithLanguagePrefix('/uk/katalog'));
        $this->assertFalse(SitemapLocation::startsWithLanguagePrefix('enigma/katalog'));
        $this->assertFalse(SitemapLocation::startsWithLanguagePrefix('katalog/en'));
        $this->assertFalse(SitemapLocation::startsWithLanguagePrefix('https://x.org/en/faq'));
    }

    public function test_the_default_resolver_works_for_a_single_language_site(): void
    {
        SeoFiles::flush();

        $this->assertSame('https://site.test/search', SitemapLocation::forCustom('en', 'search'));
    }

    public function test_a_custom_resolver_replaces_the_default_url_building(): void
    {
        SeoFiles::localizedUrlUsing(fn (string $locale, string $path): string => "https://cdn.test/{$locale}/{$path}.html");

        $this->assertSame('https://cdn.test/en/search.html', SitemapLocation::forCustom('en', 'search'));
    }
}
