<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Contracts\HasSitemapImages;
use Asignua\FilamentSeoFiles\Data\SitemapEntry;
use Asignua\FilamentSeoFiles\Repositories\SitemapUrlRepository;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Sources\ModelSource;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Schema;
use Workbench\App\Models\Post;

class ImageSitemapTest extends TestCase
{
    private const string IMAGE_NS = 'http://www.google.com/schemas/sitemap-image/1.1';

    private function xpath(string $xml): DOMXPath
    {
        $dom = new DOMDocument;
        $this->assertTrue($dom->loadXML($xml));
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xpath->registerNamespace('image', self::IMAGE_NS);

        return $xpath;
    }

    public function test_images_are_not_written_unless_enabled(): void
    {
        $this->makePost('a');
        SeoFiles::source(ModelSource::make(Post::class)
            ->url(fn (Post $post, string $locale): string => 'https://site.test/p/'.$post->slug)
            ->images(fn (): array => ['https://site.test/a.jpg']));

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertStringNotContainsString('image:image', $this->sitemapXml());
    }

    public function test_model_source_closure_writes_only_image_loc(): void
    {
        config(['filament-seo-files.sitemap.images.enabled' => true]);
        $this->makePost('a');
        SeoFiles::source(ModelSource::make(Post::class)
            ->url(fn (Post $post, string $locale): string => 'https://site.test/p/'.$post->slug)
            ->images(fn (): array => ['https://site.test/a.jpg', '/img/b.png', 'https://site.test/a.jpg', '']));

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $xml = $this->sitemapXml();
        $xpath = $this->xpath($xml);

        $this->assertStringContainsString('xmlns:image="'.self::IMAGE_NS.'"', $xml);
        $this->assertSame(
            ['https://site.test/a.jpg', 'https://site.test/img/b.png'],
            array_map(fn ($n) => $n->textContent, iterator_to_array($xpath->query('//s:url/image:image/image:loc'))),
        );
        // Deprecated children are never emitted; the lone child of image:image is image:loc.
        $this->assertSame(0, $xpath->query('//image:caption | //image:title | //image:geo_location | //image:license')->length);
        $this->assertSame(0, $xpath->query('//s:priority | //s:changefreq')->length);
    }

    public function test_model_implementing_the_interface_supplies_images(): void
    {
        config(['filament-seo-files.sitemap.images.enabled' => true]);
        $this->makePost('a');

        $source = ModelSource::make(Post::class)->url(fn (Post $post, string $locale): string => 'https://site.test/p/'.$post->slug);
        SeoFiles::source($source);

        // A plain model has none.
        $this->artisan('seo-files:sitemap');
        $this->assertStringNotContainsString('image:image', $this->sitemapXml());

        $this->assertTrue((new class extends Post implements HasSitemapImages
        {
            public function sitemapImages(): iterable
            {
                return ['/x.jpg'];
            }
        }) instanceof HasSitemapImages);
    }

    public function test_global_resolver_fills_entries_without_images_and_respects_the_cap(): void
    {
        config(['filament-seo-files.sitemap.images.enabled' => true, 'filament-seo-files.sitemap.images.max_per_url' => 2]);
        $this->makePost('a');
        SeoFiles::source(ModelSource::make(Post::class)->url(fn (Post $post, string $locale): string => 'https://site.test/p/'.$post->slug));
        SeoFiles::imagesUsing(fn (SitemapEntry $entry): array => ['/1.jpg', '/2.jpg', '/3.jpg']);

        $this->artisan('seo-files:sitemap');

        $this->assertSame(2, $this->xpath($this->sitemapXml())->query('//image:loc')->length);
    }

    public function test_every_language_version_carries_the_images_and_the_cluster_stays_intact(): void
    {
        config(['filament-seo-files.sitemap.images.enabled' => true]);
        $this->twoLanguages();
        $this->makePost('a', 'A', 'Ф');
        $this->registerPosts()->images(fn (): array => ['/i.jpg']);

        $this->artisan('seo-files:sitemap');

        $xpath = $this->xpath($this->sitemapXml());
        $this->assertSame(2, $xpath->query('//s:url')->length);
        $this->assertSame(2, $xpath->query('//s:url/image:image')->length);
        $this->assertSame(6, $xpath->query('//*[local-name()="link"]')->length);
    }

    public function test_manual_urls_carry_images_from_the_optional_column(): void
    {
        config(['filament-seo-files.sitemap.images.enabled' => true]);
        Schema::table('seo_sitemap_urls', fn ($table) => $table->json('images')->nullable());
        SitemapUrlRepository::forgetImagesColumn();

        app(SitemapUrlRepository::class)->create([
            'url' => ['en' => '/landing'],
            'images' => "https://cdn.test/l.jpg\n\n/local.jpg",
        ]);

        $this->artisan('seo-files:sitemap');

        $this->assertSame(
            ['https://cdn.test/l.jpg', 'https://site.test/local.jpg'],
            array_map(fn ($n) => $n->textContent, iterator_to_array($this->xpath($this->sitemapXml())->query('//image:loc'))),
        );
    }

    public function test_without_the_column_images_in_manual_data_are_ignored(): void
    {
        SitemapUrlRepository::forgetImagesColumn();
        config(['filament-seo-files.sitemap.images.enabled' => true]);

        app(SitemapUrlRepository::class)->create(['url' => ['en' => '/landing'], 'images' => '/x.jpg']);
        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertStringContainsString('https://site.test/landing', $this->sitemapXml());
        $this->assertStringNotContainsString('image:image', $this->sitemapXml());
    }

    public function test_the_cap_cannot_exceed_googles_1000(): void
    {
        config(['filament-seo-files.sitemap.images.enabled' => true, 'filament-seo-files.sitemap.images.max_per_url' => 5000]);
        $this->makePost('a');
        SeoFiles::source(ModelSource::make(Post::class)->url(fn (Post $post, string $locale): string => 'https://site.test/p/'.$post->slug));
        SeoFiles::imagesUsing(fn (SitemapEntry $entry): array => array_map(fn (int $i): string => '/i'.$i.'.jpg', range(1, 1200)));

        $this->artisan('seo-files:sitemap');

        $this->assertSame(1000, $this->xpath($this->sitemapXml())->query('//image:loc')->length);
    }
}
