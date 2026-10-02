<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Contracts\LlmsFullSource;
use Asignua\FilamentSeoFiles\Contracts\LlmsIndexSource;
use Asignua\FilamentSeoFiles\Contracts\SitemapSource;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use InvalidArgumentException;

class SeoFilesRegistryTest extends TestCase
{
    public function test_defaults_suit_a_single_language_site(): void
    {
        $this->assertSame('https://site.test', SeoFiles::baseUrl());
        $this->assertSame('en', SeoFiles::defaultLocale());
        $this->assertSame(['en'], SeoFiles::allLocales());
        $this->assertSame('en', SeoFiles::unprefixedLocale());
        $this->assertSame([], SeoFiles::prefixedLocales());
        $this->assertSame('https://site.test/about', SeoFiles::localizedUrl('en', 'about'));
        $this->assertSame('https://site.test', SeoFiles::localizedUrl('en', ''));
        $this->assertFalse(SeoFiles::ownsPath('en', 'about'));
        $this->assertSame('Site', SeoFiles::siteName('en'));
        $this->assertNull(SeoFiles::description('en'));
    }

    public function test_the_base_url_is_trimmed(): void
    {
        config(['app.url' => 'https://site.test///']);

        $this->assertSame('https://site.test', SeoFiles::baseUrl());
    }

    public function test_locales_put_the_default_first_and_drop_duplicates(): void
    {
        SeoFiles::locales(default: 'uk', all: ['en', 'uk', 'en'], unprefixed: 'uk');

        $this->assertSame(['uk', 'en'], SeoFiles::allLocales());
        $this->assertSame(['en'], SeoFiles::prefixedLocales());
    }

    public function test_every_language_can_be_prefixed(): void
    {
        SeoFiles::locales(default: 'en', all: ['en', 'uk'], unprefixed: null);

        $this->assertNull(SeoFiles::unprefixedLocale());
        $this->assertSame(['en', 'uk'], SeoFiles::prefixedLocales());
        $this->assertSame('https://site.test/en/about', SeoFiles::localizedUrl('en', 'about'));
        $this->assertSame('https://site.test/en', SeoFiles::localizedUrl('en', ''));
    }

    public function test_resolvers_replace_the_defaults(): void
    {
        SeoFiles::baseUrlUsing(fn (): string => 'https://other.test');
        SeoFiles::ownedPathUsing(fn (string $locale, string $path): bool => $path === 'about');
        SeoFiles::siteNameUsing(fn (string $locale): string => 'Name '.$locale);
        SeoFiles::descriptionUsing(fn (string $locale): ?string => 'About '.$locale);

        $this->assertSame('https://other.test', SeoFiles::baseUrl());
        $this->assertTrue(SeoFiles::ownsPath('en', 'about'));
        $this->assertFalse(SeoFiles::ownsPath('en', 'contacts'));
        $this->assertSame('Name en', SeoFiles::siteName('en'));
        $this->assertSame('About en', SeoFiles::description('en'));
    }

    public function test_an_empty_site_name_falls_back_to_the_app_name(): void
    {
        SeoFiles::siteNameUsing(fn (string $locale): string => '');

        $this->assertSame('Site', SeoFiles::siteName('en'));
    }

    public function test_sources_are_sorted_by_the_contracts_they_implement(): void
    {
        $sitemapOnly = new class implements SitemapSource
        {
            public function sitemapEntries(): iterable
            {
                return [];
            }
        };
        $everything = new class implements LlmsFullSource, LlmsIndexSource, SitemapSource
        {
            public function sitemapEntries(): iterable
            {
                return [];
            }

            public function llmsSections(string $locale): iterable
            {
                return [];
            }

            public function llmsDocuments(string $locale): iterable
            {
                return [];
            }
        };

        SeoFiles::source($sitemapOnly, $everything);

        $this->assertSame([$sitemapOnly, $everything], SeoFiles::sitemapSources());
        $this->assertSame([$everything], SeoFiles::llmsIndexSources());
        $this->assertSame([$everything], SeoFiles::llmsFullSources());
    }

    public function test_an_object_that_is_no_source_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SeoFiles::source(new \stdClass);
    }

    public function test_flush_forgets_everything(): void
    {
        $this->twoLanguages();
        SeoFiles::baseUrlUsing(fn (): string => 'https://other.test');

        SeoFiles::flush();

        $this->assertSame('https://site.test', SeoFiles::baseUrl());
        $this->assertSame(['en'], SeoFiles::allLocales());
    }
}
