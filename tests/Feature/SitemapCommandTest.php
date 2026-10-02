<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Contracts\SitemapSource;
use Asignua\FilamentSeoFiles\Data\SitemapEntry;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Workbench\App\Models\Post;

class SitemapCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->twoLanguages();
        $this->registerPosts();
    }

    public function test_it_generates_xml_with_hreflang_x_default_and_lastmod(): void
    {
        $this->makePost('hello', 'Hello', 'Привіт');

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $xml = $this->sitemapXml();

        $this->assertStringContainsString('<loc>https://site.test/posts/hello</loc>', $xml);
        $this->assertStringContainsString('hreflang="uk" href="https://site.test/uk/posts/hello"', $xml);
        $this->assertStringContainsString('hreflang="en" href="https://site.test/posts/hello"', $xml);
        $this->assertStringContainsString('hreflang="x-default" href="https://site.test/posts/hello"', $xml);
        $this->assertStringContainsString('<lastmod>', $xml);
    }

    public function test_every_language_version_is_a_url_of_its_own_with_the_full_cluster(): void
    {
        // Google's sitemap hreflang method: each version is declared as a <loc>, and each
        // <url> lists every alternate including itself, so the cluster is reciprocal.
        $this->makePost('hello', 'Hello', 'Привіт');
        $this->manualUrl(['en' => 'search', 'uk' => 'poshuk']);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        preg_match_all('~<url>.*?</url>~s', $this->sitemapXml(), $blocks);
        $this->assertCount(4, $blocks[0]);

        $byLoc = [];

        foreach ($blocks[0] as $block) {
            preg_match('~<loc>([^<]+)</loc>~', $block, $loc);
            $byLoc[$loc[1]] = $block;
        }

        foreach (['https://site.test/posts/hello', 'https://site.test/uk/posts/hello'] as $loc) {
            $this->assertArrayHasKey($loc, $byLoc);
            $this->assertStringContainsString('hreflang="en" href="https://site.test/posts/hello"', $byLoc[$loc]);
            $this->assertStringContainsString('hreflang="uk" href="https://site.test/uk/posts/hello"', $byLoc[$loc]);
            $this->assertStringContainsString('hreflang="x-default" href="https://site.test/posts/hello"', $byLoc[$loc]);
        }

        foreach (['https://site.test/search', 'https://site.test/uk/poshuk'] as $loc) {
            $this->assertArrayHasKey($loc, $byLoc);
            $this->assertStringContainsString('hreflang="uk" href="https://site.test/uk/poshuk"', $byLoc[$loc]);
            $this->assertStringContainsString('hreflang="x-default" href="https://site.test/search"', $byLoc[$loc]);
        }
    }

    public function test_hreflang_uses_bcp_47_codes(): void
    {
        // Laravel's `pt_BR` is not a valid hreflang; Google ignores the annotation.
        SeoFiles::flush();
        SeoFiles::locales(default: 'en', all: ['en', 'pt_BR'], unprefixed: 'en');
        $this->manualUrl(['en' => 'search', 'pt_BR' => 'busca']);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $xml = $this->sitemapXml();

        $this->assertStringContainsString('hreflang="pt-BR" href="https://site.test/pt_BR/busca"', $xml);
        $this->assertStringNotContainsString('hreflang="pt_BR"', $xml);
    }

    public function test_it_never_emits_priority_or_changefreq(): void
    {
        $this->makePost('hello', 'Hello', 'Привіт');
        $this->manualUrl(['en' => 'search']);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertStringNotContainsString('<priority>', $this->sitemapXml());
        $this->assertStringNotContainsString('<changefreq>', $this->sitemapXml());
    }

    public function test_records_filtered_out_by_the_query_are_not_listed(): void
    {
        $this->makePost('visible', 'Visible');
        $this->makePost('draft', 'Draft', null, ['published' => false]);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertStringContainsString('/posts/visible', $this->sitemapXml());
        $this->assertStringNotContainsString('/posts/draft', $this->sitemapXml());
    }

    public function test_a_language_without_a_url_is_not_an_alternate(): void
    {
        $this->makePost('english-only', 'English only');

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertStringNotContainsString('hreflang="uk"', $this->sitemapXml());
        $this->assertStringContainsString('hreflang="x-default"', $this->sitemapXml());
    }

    public function test_the_loc_falls_back_to_the_first_language_that_has_a_url(): void
    {
        SeoFiles::flush();
        SeoFiles::locales(default: 'uk', all: ['uk', 'en'], unprefixed: 'uk');
        $this->registerPosts();
        $this->makePost('english-only', 'English only');

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertStringContainsString('<loc>https://site.test/en/posts/english-only</loc>', $this->sitemapXml());
    }

    public function test_the_base_url_comes_from_the_registry(): void
    {
        SeoFiles::baseUrlUsing(fn (): string => 'https://grants.example.org/');
        $this->makePost('hello', 'Hello');

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertStringContainsString('<loc>https://grants.example.org/posts/hello</loc>', $this->sitemapXml());
    }

    public function test_the_target_file_is_configurable(): void
    {
        config(['filament-seo-files.sitemap.path' => $this->publicPath.'/maps/custom.xml']);
        $this->makePost('hello', 'Hello');

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertFileExists($this->publicPath.'/maps/custom.xml');
        $this->assertFileDoesNotExist($this->publicPath.'/sitemap.xml');
    }

    public function test_a_source_url_that_is_a_path_gets_the_base_prepended(): void
    {
        SeoFiles::flush();
        SeoFiles::source(\Asignua\FilamentSeoFiles\Sources\ModelSource::make(Post::class)
            ->url(fn (Post $post, string $locale): string => '/p/'.$post->slug));
        $this->makePost('hello', 'Hello');

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertStringContainsString('<loc>https://site.test/p/hello</loc>', $this->sitemapXml());
    }

    public function test_an_active_manual_url_is_emitted_with_hreflang_and_lastmod(): void
    {
        $this->manualUrl(['en' => 'search', 'uk' => 'poshuk']);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $xml = $this->sitemapXml();

        $this->assertStringContainsString('<loc>https://site.test/search</loc>', $xml);
        $this->assertStringContainsString('hreflang="uk" href="https://site.test/uk/poshuk"', $xml);
        $this->assertStringContainsString('hreflang="x-default" href="https://site.test/search"', $xml);
        $this->assertStringContainsString('<lastmod>', $xml);
    }

    public function test_an_absolute_manual_url_is_used_verbatim(): void
    {
        $this->manualUrl(['en' => 'https://partner.example.org/landing']);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertStringContainsString('<loc>https://partner.example.org/landing</loc>', $this->sitemapXml());
        $this->assertStringNotContainsString('site.test/https:', $this->sitemapXml());
    }

    public function test_an_inactive_manual_url_is_skipped(): void
    {
        $this->manualUrl(['en' => 'switched-off'], active: false);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertStringNotContainsString('switched-off', $this->sitemapXml());
    }

    public function test_a_manual_url_that_duplicates_a_source_page_is_emitted_once(): void
    {
        // Two equal addresses with different sets of alternates are a non-reciprocal
        // hreflang that Google discards, so the source page must win.
        $this->makePost('hello', 'Hello', 'Привіт');
        $this->manualUrl(['en' => 'posts/hello']);

        $this->artisan('seo-files:sitemap')
            ->expectsOutputToContain('Manual URLs skipped as duplicates: 1')
            ->assertExitCode(0);

        $this->assertSame(1, substr_count($this->sitemapXml(), '<loc>https://site.test/posts/hello</loc>'));
    }

    public function test_a_manual_alternate_already_emitted_by_a_source_is_not_repeated(): void
    {
        // The manual record's uk version equals the source page's <loc> in its own cluster:
        // `unique('url')` only sees <loc>, so the $seen map must keep it out of the alternates.
        SeoFiles::flush();
        SeoFiles::locales(default: 'en', all: ['en', 'uk'], unprefixed: 'en');
        $this->registerPosts();
        $this->makePost('hello', 'Hello', 'Привіт');
        $this->manualUrl(['en' => 'landing', 'uk' => 'posts/hello']);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        // The page's uk URL is https://site.test/uk/posts/hello; the manual uk version is
        // https://site.test/uk/posts/hello too (prefix + path) — and appears only under the page.
        preg_match_all('~<url>.*?</url>~s', $this->sitemapXml(), $blocks);

        $landing = array_values(array_filter($blocks[0], fn (string $block): bool => str_contains($block, '<loc>https://site.test/landing</loc>')));

        $this->assertCount(1, $landing);
        $this->assertStringNotContainsString('https://site.test/uk/posts/hello', $landing[0]);
        $this->assertSame(1, substr_count($this->sitemapXml(), '<loc>https://site.test/uk/posts/hello</loc>'));
    }

    public function test_a_manual_url_with_an_empty_default_language_falls_back_to_the_first_filled_one(): void
    {
        $this->manualUrl(['en' => '', 'uk' => 'tilky-uk']);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $xml = $this->sitemapXml();

        $this->assertStringContainsString('<loc>https://site.test/uk/tilky-uk</loc>', $xml);
        $this->assertStringContainsString('hreflang="x-default" href="https://site.test/uk/tilky-uk"', $xml);
    }

    public function test_a_manual_url_empty_in_every_language_is_not_emitted(): void
    {
        $this->manualUrl(['en' => '', 'uk' => '']);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertSame(0, substr_count($this->sitemapXml(), '<loc>'));
    }

    public function test_the_reported_count_is_the_number_of_url_entries(): void
    {
        // Two pages in two languages and one English-only page: 3 pages, 5 <url> entries.
        $this->makePost('a', 'A', 'А');
        $this->makePost('b', 'B', 'Б');
        $this->makePost('c', 'C');

        $this->artisan('seo-files:sitemap')
            ->expectsOutputToContain('Sitemap: 5 URLs (3 pages from sources + 0 manual)')
            ->assertExitCode(0);

        $this->assertSame(5, substr_count($this->sitemapXml(), '<url>'));
    }

    public function test_overlapping_sources_keep_hreflang_reciprocal(): void
    {
        // The second source lists, as its uk version, an address the first one already
        // emitted in its own cluster. Listing it again would be a one-way hreflang.
        SeoFiles::flush();
        $this->twoLanguages();
        SeoFiles::source(new class implements SitemapSource
        {
            public function sitemapEntries(): iterable
            {
                yield new SitemapEntry('https://site.test/one', ['en' => 'https://site.test/one', 'uk' => 'https://site.test/uk/shared']);
            }
        });
        SeoFiles::source(new class implements SitemapSource
        {
            public function sitemapEntries(): iterable
            {
                yield new SitemapEntry('https://site.test/two', ['en' => 'https://site.test/two', 'uk' => 'https://site.test/uk/shared']);
            }
        });

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        preg_match_all('~<url>.*?</url>~s', $this->sitemapXml(), $blocks);
        $two = array_values(array_filter($blocks[0], fn (string $block): bool => str_contains($block, '<loc>https://site.test/two</loc>')));

        $this->assertCount(1, $two);
        $this->assertStringNotContainsString('https://site.test/uk/shared', $two[0]);
        $this->assertSame(1, substr_count($this->sitemapXml(), '<loc>https://site.test/uk/shared</loc>'));
    }

    public function test_it_leaves_no_temporary_files_behind(): void
    {
        $this->makePost('hello', 'Hello');

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertSame(['sitemap.xml'], array_values(array_diff(scandir($this->publicPath) ?: [], ['.', '..'])));
    }
}
