<?php

declare(strict_types=1);

namespace Asignua\FilamentSeoFiles\Tests\Feature;

use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Support\RobotsFile;
use Asignua\FilamentSeoFiles\Tests\TestCase;
use Illuminate\Support\Facades\File;

class RobotsFileTest extends TestCase
{
    public function test_template_contains_sitemap_and_allows_all(): void
    {
        $template = (new RobotsFile)->template();

        $this->assertStringContainsString('User-agent: *', $template);
        $this->assertStringContainsString('Sitemap: https://site.test/sitemap.xml', $template);
    }

    public function test_the_base_url_is_the_registry_one_not_app_url(): void
    {
        // eEgnith's robots.txt used app.url while its sitemap used the `sitemap_url` setting:
        // one resolver now feeds both.
        SeoFiles::baseUrlUsing(fn (): string => 'https://canonical.example.org');

        $this->assertStringContainsString('Sitemap: https://canonical.example.org/sitemap.xml', (new RobotsFile)->template());
    }

    public function test_the_sitemap_line_follows_a_renamed_sitemap_file(): void
    {
        config(['filament-seo-files.sitemap.path' => $this->publicPath.'/map.xml']);

        $this->assertStringContainsString('Sitemap: https://site.test/map.xml', (new RobotsFile)->template());
    }

    public function test_write_and_read_round_trip(): void
    {
        $robots = new RobotsFile;

        $robots->write("User-agent: *\nDisallow: /private\n\n\n");

        $this->assertSame("User-agent: *\nDisallow: /private\n", $robots->read());
        $this->assertSame("User-agent: *\nDisallow: /private\n", File::get(public_path('robots.txt')));
    }

    public function test_read_returns_the_template_when_the_file_is_absent(): void
    {
        $this->assertFalse(File::exists(public_path('robots.txt')));

        $this->assertSame((new RobotsFile)->template(), (new RobotsFile)->read());
    }

    public function test_the_path_is_configurable(): void
    {
        config(['filament-seo-files.robots.path' => $this->publicPath.'/custom/robots.txt']);

        (new RobotsFile)->write('User-agent: *');

        $this->assertFileExists($this->publicPath.'/custom/robots.txt');
        $this->assertFileDoesNotExist($this->publicPath.'/robots.txt');
    }
}
