<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Contracts\SitemapSource;
use Asignua\FilamentSeoFiles\Data\SitemapEntry;
use Asignua\FilamentSeoFiles\Data\SitemapNews;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\File;
use Workbench\App\Models\Post;

class NewsSitemapTest extends TestCase
{
    private const string NEWS_NS = 'http://www.google.com/schemas/sitemap-news/0.9';

    private function xpath(string $xml): DOMXPath
    {
        $dom = new DOMDocument;
        $this->assertTrue($dom->loadXML($xml));
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xpath->registerNamespace('news', self::NEWS_NS);

        return $xpath;
    }

    /**
     * @param list<array{0: string, 1: string, 2: \DateTimeInterface}> $items slug, title, date
     */
    private function source(array $items): void
    {
        SeoFiles::source(new class($items) implements SitemapSource
        {
            public function __construct(private array $items) {}

            public function sitemapEntries(): iterable
            {
                foreach ($this->items as [$slug, $title, $date]) {
                    $url = 'https://site.test/news/'.$slug;
                    yield new SitemapEntry($url, ['en' => $url], null, [], new SitemapNews($title, $date));
                }
            }
        });
    }

    public function test_off_by_default(): void
    {
        $this->source([['a', 'A', now()]]);
        $this->artisan('seo-files:sitemap');

        $this->assertFileDoesNotExist($this->publicPath.'/sitemap-news.xml');
        $this->assertStringContainsString('<urlset', $this->sitemapXml());
    }

    public function test_news_file_lists_recent_articles_and_is_in_the_index(): void
    {
        config(['filament-seo-files.sitemap.news.enabled' => true]);
        $this->source([
            ['fresh', 'Fresh & hot', now()->subHours(3)],
            ['old', 'Old one', now()->subDays(3)],
        ]);

        $this->artisan('seo-files:sitemap')->expectsOutputToContain('News sitemap: 1 article(s)')->assertExitCode(0);

        $index = $this->xpath($this->sitemapXml());
        $this->assertSame(1, $index->query('/s:sitemapindex')->length);
        $locs = array_map(fn ($n) => $n->textContent, iterator_to_array($index->query('//s:sitemap/s:loc')));
        $this->assertContains('https://site.test/sitemap-news.xml', $locs);
        $this->assertContains('https://site.test/sitemap-1.xml', $locs);

        $news = $this->xpath((string) file_get_contents($this->publicPath.'/sitemap-news.xml'));
        $this->assertSame(1, $news->query('//s:url')->length);
        $this->assertSame('https://site.test/news/fresh', $news->query('//s:url/s:loc')->item(0)->textContent);
        $this->assertSame('Site', $news->query('//news:news/news:publication/news:name')->item(0)->textContent);
        $this->assertSame('en', $news->query('//news:news/news:publication/news:language')->item(0)->textContent);
        $this->assertSame('Fresh & hot', $news->query('//news:news/news:title')->item(0)->textContent);
        $this->assertMatchesRegularExpression('/^\d{4}-\d\d-\d\dT[\d:]+[+-]\d\d:\d\d$/', $news->query('//news:news/news:publication_date')->item(0)->textContent);
        $this->assertSame(0, $news->query('//s:priority | //s:changefreq')->length);
    }

    public function test_the_file_is_capped_to_the_newest_and_never_treated_as_a_stale_part(): void
    {
        config(['filament-seo-files.sitemap.news.enabled' => true, 'filament-seo-files.sitemap.news.max_urls' => 2]);
        $this->source([
            ['a', 'A', now()->subHours(5)],
            ['b', 'B', now()->subHours(1)],
            ['c', 'C', now()->subHours(3)],
        ]);

        $this->artisan('seo-files:sitemap');
        $this->artisan('seo-files:sitemap');

        $news = $this->xpath((string) file_get_contents($this->publicPath.'/sitemap-news.xml'));
        $this->assertSame(
            ['https://site.test/news/b', 'https://site.test/news/c'],
            array_map(fn ($n) => $n->textContent, iterator_to_array($news->query('//s:url/s:loc'))),
        );
    }

    public function test_an_empty_news_file_is_removed_but_the_index_stays(): void
    {
        config(['filament-seo-files.sitemap.news.enabled' => true]);
        File::put($this->publicPath.'/sitemap-news.xml', 'stale');
        $this->source([['old', 'Old', now()->subDays(5)]]);

        $this->artisan('seo-files:sitemap');

        $this->assertFileDoesNotExist($this->publicPath.'/sitemap-news.xml');
        $this->assertStringNotContainsString('sitemap-news.xml', $this->sitemapXml());
    }

    public function test_model_source_news_closure_with_a_language_per_locale(): void
    {
        config(['filament-seo-files.sitemap.news.enabled' => true]);
        $this->twoLanguages();
        $this->makePost('a', 'Hello', 'Привіт');
        $this->registerPosts()->news(fn (Post $post, string $locale): SitemapNews => new SitemapNews(
            $locale === 'uk' ? 'Привіт' : 'Hello',
            now()->subHour(),
            'The Site',
        ));

        $this->artisan('seo-files:sitemap');

        $news = $this->xpath((string) file_get_contents($this->publicPath.'/sitemap-news.xml'));
        $this->assertSame(2, $news->query('//news:news')->length);
        $langs = array_map(fn ($n) => $n->textContent, iterator_to_array($news->query('//news:language')));
        sort($langs);
        $this->assertSame(['en', 'uk'], $langs);
        $this->assertSame('The Site', $news->query('//news:name')->item(0)->textContent);
    }
}
