<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\Support\SitemapFile;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Workbench\App\Models\Post;

/**
 * Large sites: above `sitemap.max_urls` sitemap.xml becomes a <sitemapindex> of parts.
 */
class SitemapIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->twoLanguages();
        $this->registerPosts();
    }

    private function posts(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->makePost('post-'.$i, 'Post '.$i, 'Допис '.$i);
        }
    }

    private function part(int $number): string
    {
        return (string) file_get_contents($this->publicPath.'/sitemap-'.$number.'.xml');
    }

    /**
     * @return list<string>
     */
    private function files(): array
    {
        return array_values(array_diff(scandir($this->publicPath) ?: [], ['.', '..']));
    }

    public function test_seven_urls_with_a_limit_of_three_give_an_index_and_three_parts(): void
    {
        config(['filament-seo-files.sitemap.max_urls' => 3]);
        $this->posts(7);

        $this->artisan('seo-files:sitemap')
            ->expectsOutputToContain('index of 3 part(s)')
            ->assertExitCode(0);

        $index = $this->sitemapXml();

        $this->assertStringContainsString('<sitemapindex', $index);
        $this->assertSame(3, substr_count($index, '<sitemap>'));
        $this->assertStringContainsString('<loc>https://site.test/sitemap-1.xml</loc>', $index);
        $this->assertStringContainsString('<loc>https://site.test/sitemap-3.xml</loc>', $index);
        $this->assertStringContainsString('<lastmod>', $index);

        $this->assertSame(3, substr_count($this->part(1), '<url>'));
        $this->assertSame(3, substr_count($this->part(2), '<url>'));
        $this->assertSame(1, substr_count($this->part(3), '<url>'));
        $this->assertSame(['sitemap-1.xml', 'sitemap-2.xml', 'sitemap-3.xml', 'sitemap.xml'], $this->files());
    }

    public function test_no_address_is_repeated_across_parts_and_a_cluster_stays_in_one_part(): void
    {
        config(['filament-seo-files.sitemap.max_urls' => 3]);
        $this->posts(5);
        // A manual record that duplicates the 4th page lives in the sources pass already.
        $this->manualUrl(['en' => 'posts/post-4']);
        $this->manualUrl(['en' => 'search', 'uk' => 'poshuk']);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $locs = [];

        foreach ([1, 2] as $number) {
            preg_match_all('~<loc>([^<]+)</loc>~', $this->part($number), $matches);
            $locs = [...$locs, ...$matches[1]];
        }

        $this->assertSame(count($locs), count(array_unique($locs)), 'a <loc> must not repeat across parts');
        $this->assertSame(1, count(array_keys($locs, 'https://site.test/posts/post-4', true)));

        // Each page's Ukrainian alternate sits in the same part as its <loc>.
        foreach ([1, 2] as $number) {
            preg_match_all('~<loc>https://site\.test/posts/(post-\d)</loc>~', $this->part($number), $slugs);

            foreach ($slugs[1] as $slug) {
                $this->assertStringContainsString('href="https://site.test/uk/posts/'.$slug.'"', $this->part($number));
            }
        }
    }

    public function test_going_back_to_a_single_file_removes_the_parts(): void
    {
        config(['filament-seo-files.sitemap.max_urls' => 3]);
        $this->posts(7);
        $this->artisan('seo-files:sitemap')->assertExitCode(0);
        $this->assertFileExists($this->publicPath.'/sitemap-3.xml');

        Post::query()->where('id', '>', 2)->delete();
        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertStringNotContainsString('<sitemapindex', $this->sitemapXml());
        $this->assertSame(2, substr_count($this->sitemapXml(), '<url>'));
        $this->assertSame(['sitemap.xml'], $this->files());
    }

    public function test_fewer_parts_than_before_removes_only_the_stale_ones(): void
    {
        config(['filament-seo-files.sitemap.max_urls' => 3]);
        $this->posts(7);
        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        // Files that do not match the part-name template are never touched.
        File::put($this->publicPath.'/sitemap-notes.txt', 'keep');
        File::put($this->publicPath.'/sitemap-archive.xml', 'keep');

        Post::query()->where('id', '>', 4)->delete();
        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertFileExists($this->publicPath.'/sitemap-2.xml');
        $this->assertFileDoesNotExist($this->publicPath.'/sitemap-3.xml');
        $this->assertFileExists($this->publicPath.'/sitemap-notes.txt');
        $this->assertFileExists($this->publicPath.'/sitemap-archive.xml');
    }

    public function test_never_writes_one_file_even_above_the_limit(): void
    {
        config(['filament-seo-files.sitemap.max_urls' => 3, 'filament-seo-files.sitemap.split' => 'never']);
        $this->posts(7);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertSame(7, substr_count($this->sitemapXml(), '<url>'));
        $this->assertStringNotContainsString('<sitemapindex', $this->sitemapXml());
        $this->assertSame(['sitemap.xml'], $this->files());
    }

    public function test_always_writes_an_index_even_for_a_few_urls(): void
    {
        config(['filament-seo-files.sitemap.split' => 'always']);
        $this->posts(2);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertStringContainsString('<sitemapindex', $this->sitemapXml());
        $this->assertSame(2, substr_count($this->part(1), '<url>'));
        $this->assertSame(['sitemap-1.xml', 'sitemap.xml'], $this->files());
    }

    public function test_always_with_nothing_to_list_still_writes_a_valid_index(): void
    {
        config(['filament-seo-files.sitemap.split' => 'always']);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertSame(1, substr_count($this->sitemapXml(), '<sitemap>'));
        $this->assertSame(0, substr_count($this->part(1), '<url>'));
    }

    public function test_a_part_is_closed_early_when_its_estimated_size_is_reached(): void
    {
        config(['filament-seo-files.sitemap.max_bytes' => 700]);
        $this->posts(6);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertStringContainsString('<sitemapindex', $this->sitemapXml());
        $this->assertGreaterThan(1, substr_count($this->sitemapXml(), '<sitemap>'));
        $this->assertSame(6, (new SitemapFile)->urlCount());
    }

    public function test_the_part_name_template_is_configurable(): void
    {
        config(['filament-seo-files.sitemap.max_urls' => 2, 'filament-seo-files.sitemap.chunk_name' => 'map_{n}.xml']);
        $this->posts(3);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertFileExists($this->publicPath.'/map_1.xml');
        $this->assertFileExists($this->publicPath.'/map_2.xml');
        $this->assertStringContainsString('<loc>https://site.test/map_1.xml</loc>', $this->sitemapXml());
    }

    public function test_a_part_name_without_the_number_falls_back_to_the_default(): void
    {
        config(['filament-seo-files.sitemap.max_urls' => 2, 'filament-seo-files.sitemap.chunk_name' => 'parts.xml']);
        $this->posts(3);

        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $this->assertFileExists($this->publicPath.'/sitemap-1.xml');
    }

    public function test_the_file_helper_counts_urls_across_parts(): void
    {
        config(['filament-seo-files.sitemap.max_urls' => 3]);
        $this->posts(7);
        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        $file = new SitemapFile;

        $this->assertTrue($file->isIndex());
        $this->assertSame(7, $file->urlCount());
        $this->assertCount(3, $file->chunkFiles());
    }

    public function test_the_index_is_written_after_its_parts_so_it_never_points_at_missing_files(): void
    {
        config(['filament-seo-files.sitemap.max_urls' => 3]);
        $this->posts(7);
        $this->artisan('seo-files:sitemap')->assertExitCode(0);

        preg_match_all('~<loc>https://site\.test/([^<]+)</loc>~', $this->sitemapXml(), $matches);

        foreach ($matches[1] as $file) {
            $this->assertFileExists($this->publicPath.'/'.$file);
        }
    }
}
